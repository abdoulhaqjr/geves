<?php

use App\Models\Claim;
use App\Models\User;

// bindFakeAi(), fakeAiProvider() -> GenerateReportTest ; adminStaff() -> helpers ; openEdition(), verifiedAccount() -> tests/Pest.php

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
