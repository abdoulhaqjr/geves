<?php

use App\AI\AiManager;
use App\AI\Contracts\AiProvider;
use App\Models\Claim;
use App\Models\User;

// bindFakeAi(), fakeAiProvider() -> GenerateReportTest ; adminStaff() -> helpers ; openEdition(), verifiedAccount() -> tests/Pest.php

/** Fournisseur IA factice qui renvoie une réponse DIFFÉRENTE à chaque appel (tour de chat). */
function bindSequentialAi(array $results): void
{
    $provider = new class($results) implements AiProvider
    {
        private int $i = 0;

        public function __construct(private array $results) {}

        public function key(): string
        {
            return 'fake';
        }

        public function label(): string
        {
            return 'Fake';
        }

        public function isAvailable(): bool
        {
            return true;
        }

        public function supportsPdf(): bool
        {
            return true;
        }

        public function extract(string $instruction, array $files = [], ?string $tier = null): array
        {
            return $this->results[min($this->i++, count($this->results) - 1)];
        }
    };

    $manager = Mockery::mock(AiManager::class);
    $manager->shouldReceive('resolve')->andReturn($provider);
    $manager->shouldReceive('extract')->andReturnUsing(
        fn (string $instruction, array $files = [], ?string $tier = null, ?string $preferKey = null) => $provider->extract($instruction, $files, $tier)
    );
    app()->instance(AiManager::class, $manager);
}

function seedClaim(array $overrides = []): Claim
{
    return Claim::create(array_merge([
        'name' => 'Awa Kodjo', 'email' => 'awa@example.tg',
        'type' => 'reclamation', 'category' => 'paiement',
        'subject' => 'Paiement non pris en compte', 'message' => 'J’ai réglé mais mon statut reste impayé.',
        'status' => 'open', 'source' => 'chatbot',
    ], $overrides));
}

it('l’assistante enregistre une réclamation quand elle ne peut pas résoudre', function () {
    bindFakeAi(fakeAiProvider([
        'answer' => 'Je transmets votre souci au service concerné.',
        'claim' => [
            'record' => true, 'type' => 'reclamation', 'category' => 'paiement',
            'subject' => 'Paiement non pris en compte',
            'summary' => 'Le participant indique avoir payé, mais son inscription reste « à régler ».',
        ],
    ]));

    $account = verifiedAccount();

    $resp = $this->actingAs($account, 'account')
        ->postJson(route('gesco.ask'), ['question' => 'Je veux réclamer : mon paiement n’est pas pris en compte.']);
    $resp->assertOk();

    $claim = Claim::first();
    expect($claim)->not->toBeNull();
    expect($claim->email)->toBe($account->email);
    expect($claim->source)->toBe('chatbot');
    expect($claim->type)->toBe('reclamation');
    $resp->assertJsonPath('claim.reference', $claim->reference);
});

it('au fil d’une même discussion, met à jour la réclamation au lieu de la dupliquer', function () {
    // Deux tours : le second apporte une précision (que Gesco avait demandée).
    bindSequentialAi([
        ['answer' => 'Je note votre souci.', 'claim' => [
            'record' => true, 'type' => 'reclamation', 'category' => 'paiement',
            'subject' => 'Paiement', 'summary' => 'Paiement non pris en compte.',
        ]],
        ['answer' => 'Merci pour la précision.', 'claim' => [
            'record' => true, 'type' => 'reclamation', 'category' => 'paiement',
            'subject' => 'Paiement Mobile Money non pris en compte',
            'summary' => 'Paiement Mobile Money réglé le 3 octobre, statut toujours « à régler ».',
        ]],
    ]);

    $account = verifiedAccount();
    $cid = 'conv-abc-123';

    $r1 = $this->actingAs($account, 'account')
        ->postJson(route('gesco.ask'), ['question' => 'Mon paiement n’est pas pris en compte.', 'conversation_id' => $cid])
        ->assertOk();
    $r2 = $this->actingAs($account, 'account')
        ->postJson(route('gesco.ask'), ['question' => 'C’était un Mobile Money réglé le 3 octobre.', 'conversation_id' => $cid])
        ->assertOk();

    // Une seule réclamation, enrichie des dernières précisions, même référence.
    expect(Claim::count())->toBe(1);
    $claim = Claim::first();
    expect($claim->subject)->toBe('Paiement Mobile Money non pris en compte');
    expect($claim->message)->toContain('3 octobre');
    expect($claim->conversation)->toBe($cid);
    $r1->assertJsonPath('claim.reference', $claim->reference);
    $r2->assertJsonPath('claim.reference', $claim->reference);
    expect($r2->json('answer'))->toContain('mise à jour');
});

it('deux discussions distinctes créent deux réclamations', function () {
    bindFakeAi(fakeAiProvider([
        'answer' => 'Je note votre souci.',
        'claim' => ['record' => true, 'type' => 'reclamation', 'subject' => 'Souci', 'summary' => 'Détails du souci.'],
    ]));

    $account = verifiedAccount();

    $this->actingAs($account, 'account')
        ->postJson(route('gesco.ask'), ['question' => 'Je réclame.', 'conversation_id' => 'conv-1'])->assertOk();
    $this->actingAs($account, 'account')
        ->postJson(route('gesco.ask'), ['question' => 'Autre souci.', 'conversation_id' => 'conv-2'])->assertOk();

    expect(Claim::count())->toBe(2);
});

it('ne met pas à jour une réclamation déjà résolue (nouvelle réclamation créée)', function () {
    bindFakeAi(fakeAiProvider([
        'answer' => 'Je note votre souci.',
        'claim' => ['record' => true, 'type' => 'reclamation', 'subject' => 'Rechute', 'summary' => 'Le problème revient.'],
    ]));

    $account = verifiedAccount();
    seedClaim(['user_id' => $account->id, 'email' => $account->email, 'conversation' => 'conv-done', 'status' => 'resolved']);

    $this->actingAs($account, 'account')
        ->postJson(route('gesco.ask'), ['question' => 'Le souci revient.', 'conversation_id' => 'conv-done'])->assertOk();

    expect(Claim::count())->toBe(2); // l'ancienne (résolue) + la nouvelle
});

it('n’enregistre rien quand l’assistante résout (claim null)', function () {
    bindFakeAi(fakeAiProvider(['answer' => 'Voici la procédure de paiement…', 'claim' => null]));

    $this->postJson(route('gesco.ask'), ['question' => 'Comment régler mes frais ?'])->assertOk();

    expect(Claim::count())->toBe(0);
});

it('n’enregistre pas de réclamation pour un visiteur anonyme sans e-mail', function () {
    bindFakeAi(fakeAiProvider([
        'answer' => 'Pouvez-vous m’indiquer votre e-mail ?',
        'claim' => ['record' => true, 'type' => 'reclamation', 'subject' => 'Souci', 'summary' => 'Détails', 'contactEmail' => ''],
    ]));

    $resp = $this->postJson(route('gesco.ask'), ['question' => 'Je veux réclamer.']);
    $resp->assertOk()->assertJsonPath('claim', null);
    expect(Claim::count())->toBe(0);
});

it('liste les réclamations et protège la page par la capacité claims', function () {
    $edition = openEdition();
    seedClaim(['edition_id' => $edition->id, 'subject' => 'Badge introuvable']);

    $noAccess = User::create(['first_name' => 'No', 'last_name' => 'Access', 'email' => 'noacc@example.tg', 'password' => 'pass12345']);
    $this->actingAs($noAccess, 'web')->get(route('admin.claims.index'))->assertForbidden();

    $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.claims.index'))
        ->assertOk()
        ->assertSee('Badge introuvable');
});

it('génère une analyse IA de traitement et la stocke sur la réclamation', function () {
    bindFakeAi(fakeAiProvider([
        'verdict' => 'resolvable', 'summary' => 'Cas simple de régularisation.',
        'steps' => [], 'resolution' => 'Vérifier le reçu FedaPay et marquer le paiement réglé.', 'reason' => '',
    ]));

    $claim = seedClaim();

    $this->actingAs(adminStaff(), 'web')
        ->post(route('admin.claims.analyze', $claim))
        ->assertRedirect();

    $claim->refresh();
    expect($claim->analyses())->toHaveCount(1);
    expect($claim->analyses()[0]['verdict'])->toBe('resolvable');
    expect($claim->analyses()[0]['resolution'])->toContain('reçu FedaPay');
});

it('met à jour le statut (résolue) et assigne un traitant', function () {
    $claim = seedClaim();
    $staff = adminStaff();

    $this->actingAs($staff, 'web')
        ->post(route('admin.claims.status', $claim), ['status' => 'resolved', 'resolution' => 'Régularisé.'])
        ->assertRedirect();
    expect($claim->fresh()->status)->toBe('resolved');
    expect($claim->fresh()->resolved_at)->not->toBeNull();

    $this->actingAs($staff, 'web')
        ->post(route('admin.claims.assign', $claim), ['assigned_to' => $staff->id])
        ->assertRedirect();
    expect($claim->fresh()->assigned_to)->toBe($staff->id);
});
