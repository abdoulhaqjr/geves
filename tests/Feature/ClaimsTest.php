<?php

use App\Actions\AnalyzeClaim;
use App\Actions\NotifyClaimRecipients;
use App\AI\AiManager;
use App\AI\Contracts\AiProvider;
use App\Models\Claim;
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Payments\GatewayRegistry;
use App\Support\EventPaymentConfig;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

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
    $event = openEdition();
    seedClaim(['event_id' => $event->id, 'subject' => 'Badge introuvable']);

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

// ─── Notification des gestionnaires / admin + transfert ──────────────────────

it('une réclamation d’événement notifie ses gestionnaires, pas les admins non affectés', function () {
    Notification::fake();
    $event = openEdition();
    $gerant = User::factory()->create();
    $coGerant = User::factory()->create();
    $gerant->assignedEditions()->attach($event->id, ['role' => 'gerant']);
    $coGerant->assignedEditions()->attach($event->id, ['role' => 'co_gerant']);
    $adminNonAffecte = adminStaff();

    app(NotifyClaimRecipients::class)->handle(seedClaim(['event_id' => $event->id]));

    Notification::assertSentTo($gerant, SystemNotification::class, fn ($n) => $n->kind === 'claim_new');
    Notification::assertSentTo($coGerant, SystemNotification::class, fn ($n) => $n->kind === 'claim_new');
    Notification::assertNotSentTo($adminNonAffecte, SystemNotification::class);
});

it('une réclamation générale (sans événement) notifie les administrateurs', function () {
    Notification::fake();
    $admin = adminStaff();
    $event = openEdition();
    $gerant = User::factory()->create();
    $gerant->assignedEditions()->attach($event->id, ['role' => 'gerant']);

    app(NotifyClaimRecipients::class)->handle(seedClaim(['event_id' => null]));

    Notification::assertSentTo($admin, SystemNotification::class, fn ($n) => $n->kind === 'claim_new');
    Notification::assertNotSentTo($gerant, SystemNotification::class);
});

it('un événement sans gestionnaire retombe sur les administrateurs', function () {
    Notification::fake();
    $admin = adminStaff();
    $event = openEdition(); // aucun gérant attaché

    app(NotifyClaimRecipients::class)->handle(seedClaim(['event_id' => $event->id]));

    Notification::assertSentTo($admin, SystemNotification::class, fn ($n) => $n->kind === 'claim_new');
});

it('le gestionnaire peut couper l’e-mail des réclamations, pas l’in-app', function () {
    $gerant = User::factory()->create();
    // Par défaut : in-app ET e-mail générique.
    expect($gerant->notificationChannels('claim_new'))->toContain('database')->toContain('mail');

    $gerant->saveNotificationPreferences(['claim_new' => ['inapp' => true, 'email' => false]], 'staff');

    expect($gerant->fresh()->notificationChannels('claim_new'))
        ->toContain('database')->not->toContain('mail');
});

it('l’admin transfère une réclamation générale vers un événement, les gestionnaires sont notifiés', function () {
    Notification::fake();
    $admin = adminStaff();
    $event = openEdition();
    $gerant = User::factory()->create();
    $gerant->assignedEditions()->attach($event->id, ['role' => 'gerant']);
    $claim = seedClaim(['event_id' => null]);

    $this->actingAs($admin, 'web')
        ->post(route('admin.claims.transfer', $claim), ['event_id' => $event->id])
        ->assertRedirect();

    expect($claim->fresh()->event_id)->toBe($event->id);
    Notification::assertSentTo($gerant, SystemNotification::class, fn ($n) => $n->kind === 'claim_new');
});

it('un gestionnaire ne peut pas transférer une réclamation (réservé à l’admin)', function () {
    $event = openEdition();
    $gerant = User::factory()->create();
    $gerant->assignedEditions()->attach($event->id, ['role' => 'gerant']);
    $claim = seedClaim(['event_id' => $event->id]);

    $this->actingAs($gerant, 'web')
        ->post(route('admin.claims.transfer', $claim), ['event_id' => $event->id])
        ->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| Le dégagement des boutons se règle par le PRIMITIF, pas au cas par cas
|--------------------------------------------------------------------------
|
| ⚠️⚠️ `.ukc-form-actions` existe depuis longtemps et douze vues l'emploient -
| cette page-ci ne l'employait pas, et ses deux boutons se collaient au champ
| du dessus. C'est la raison pour laquelle on redemande « de l'espace » écran
| après écran : la règle existe, c'est son application qui se perd.
|
*/
it('clôt ses deux formulaires par le primitif d’actions', function () {
    $claim = seedClaim();

    $html = $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.claims.show', $claim))
        ->assertOk()
        ->getContent();

    $sansRangee = [];
    foreach (['Enregistrer', 'Transférer'] as $bouton) {
        // Le bouton doit être précédé, de peu, de l'ouverture de sa rangée
        // d'actions : on regarde les 300 caractères qui le précèdent.
        $position = mb_strpos($html, $bouton.'</button>');
        $amont = $position === false ? '' : mb_substr($html, max(0, $position - 600), 600);
        if (! str_contains($amont, 'ukc-form-actions')) {
            $sansRangee[] = $bouton;
        }
    }

    expect($sansRangee)->toBe([]);
});

it('garde sur UNE ligne l’avertissement d’envoi au déclarant', function () {
    // ⚠️ Mesuré au navigateur (la colonne fait 449 px, Public Sans 12 px) : les
    // deux incises de la version précédente la faisaient passer à deux lignes.
    // Public Sans n'est pas hébergée en TTF, on ne peut donc pas la remesurer
    // ici comme la phrase du bandeau de cookies - on fige le texte exact.
    $claim = seedClaim();

    $html = $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.claims.show', $claim))
        ->assertOk()
        ->getContent();

    expect($html)->toContain('Une nouvelle réponse ou le statut « Résolue » déclenche un e-mail au déclarant.')
        ->and($html)->not->toContain('ou le passage au statut');
});

it('offre une variante SANS filet pour un formulaire en colonne', function () {
    // ⚠️ Un filet qui s'arrête au milieu de la carte, en face d'une colonne
    // voisine qu'il ne traverse pas, se lit comme une carte coupée en deux.
    $css = file_get_contents(resource_path('css/ukc.css'));

    expect($css)->toContain('.ukc-form-actions--plain')
        ->and(Str::after($css, '.ukc-form-actions--plain'))->toStartWith(' { padding-top: 0; border-top: 0; }');
});

/*
|--------------------------------------------------------------------------
| L'analyse IA s'appuie sur la BASE, et le panneau ne s'allonge plus
|--------------------------------------------------------------------------
|
| ⚠️⚠️ Constaté le 31/10/2026 sur une vraie réclamation : faute de faits, le
| modèle comblait. Il proposait « virement bancaire direct » et « paiement sur
| place » à un participant dont l'événement n'offre QUE le paiement réglé, et
| inventait une heure de passage. Le gestionnaire aurait transmis au déclarant
| des démarches que la plateforme refuse - pire que pas d'analyse du tout.
|
*/

/** Capture l'instruction envoyée au modèle. */
function capterInstructionIa(array $reponse): object
{
    $sonde = new class
    {
        public string $instruction = '';
    };

    $manager = Mockery::mock(AiManager::class);
    $manager->shouldReceive('resolve')->andReturn(fakeAiProvider([]));
    $manager->shouldReceive('extract')->andReturnUsing(
        function (string $instruction) use ($sonde, $reponse) {
            $sonde->instruction = $instruction;

            return $reponse;
        }
    );
    app()->instance(AiManager::class, $manager);

    return $sonde;
}

it('donne au modèle les MOYENS DE PAIEMENT réels, et lui interdit d’en inventer', function () {
    $claim = seedClaim(['event_id' => openEdition()->id]);
    $sonde = capterInstructionIa(['verdict' => 'procedure', 'summary' => 'ok', 'steps' => ['a']]);

    app(AnalyzeClaim::class)->handle($claim->fresh());

    $offerts = array_map(
        fn ($g) => $g->label(),
        EventPaymentConfig::using($claim->event, fn () => app(GatewayRegistry::class)->offered()),
    );

    expect($sonde->instruction)->toContain('FAITS DU DOSSIER')
        ->and($sonde->instruction)->toContain('Moyens de paiement proposés par cet événement')
        ->and($sonde->instruction)->toContain('ni virement bancaire, ni espèces, ni')
        ->and($sonde->instruction)->toContain($offerts[0]);
});

it('dit « inconnu » plutôt que de taire un manque', function () {
    // ⚠️ Une absence silencieuse est exactement ce que le modèle remplit tout seul.
    $claim = seedClaim(['event_id' => openEdition()->id, 'email' => 'inconnu@nulle-part.test']);
    $sonde = capterInstructionIa(['verdict' => 'procedure', 'summary' => 'ok', 'steps' => ['a']]);

    app(AnalyzeClaim::class)->handle($claim->fresh());

    expect($sonde->instruction)->toContain('AUCUNE fiche participant');
});

it('ne déplie que la DERNIÈRE analyse, et replie les précédentes', function () {
    // ⚠️ Empilées, dix analyses faisaient une page entière à faire défiler, et la
    // plus récente - la seule qu'on vient lire - s'y perdait. On ne les supprime
    // pas : quelqu'un a pu agir sur une analyse ancienne.
    $claim = seedClaim();
    $meta = (array) $claim->meta;
    $meta['analyses'] = [
        ['verdict' => 'procedure', 'summary' => 'LA PLUS RECENTE', 'steps' => ['x'], 'at' => '31/10/2026 - 06:10'],
        ['verdict' => 'resolvable', 'summary' => 'UNE PRECEDENTE', 'resolution' => 'y', 'at' => '30/10/2026 - 22:40'],
    ];
    $claim->update(['meta' => $meta]);

    $html = $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.claims.show', $claim))
        ->assertOk()
        ->getContent();

    // La précédente est là - mais DANS un bloc repliable, pas dépliée.
    expect($html)->toContain('UNE PRECEDENTE')
        ->and($html)->toContain('1 analyse(s) précédente(s)')
        ->and($html)->toContain('ukc-claim-ancienne');

    $recente = mb_strpos($html, 'LA PLUS RECENTE');
    $repli = mb_strpos($html, 'ukc-claim-anciennes');

    expect($recente)->toBeLessThan($repli, 'la plus récente doit précéder le bloc replié');
    expect(mb_strpos($html, 'UNE PRECEDENTE'))->toBeGreaterThan($repli);
});

it('n’affiche PAS deux fois la réponse déjà envoyée', function () {
    // ⚠️⚠️ Le champ repeuplé montrait le même texte que le bloc « Résolution »
    // juste au-dessus, et se lisait comme un brouillon qu'on aurait oublié
    // d'effacer après l'envoi. Vide, il dit ce qu'il est : ce qu'on s'apprête à
    // envoyer EN PLUS. ⚠️ Et le laisser vide n'efface rien - `updateStatus()`
    // n'écrase la résolution que si le champ est rempli (cas suivant).
    $claim = seedClaim();
    $claim->update(['resolution' => 'REPONSE DEJA ENVOYEE', 'status' => 'resolved']);

    $html = $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.claims.show', $claim))
        ->assertOk()
        ->getContent();

    expect($html)->toContain('Nouvelle réponse au déclarant')
        ->and($html)->toContain('<textarea name="resolution" rows="4" placeholder="Une nouvelle réponse remplacera celle ci-dessus et sera envoyée…"></textarea>');
});

it('enregistrer SANS rien écrire ne détruit pas la réponse déjà envoyée', function () {
    $claim = seedClaim();
    $claim->update(['resolution' => 'REPONSE DEJA ENVOYEE', 'status' => 'resolved']);

    $this->actingAs(adminStaff(), 'web')
        ->post(route('admin.claims.status', $claim), ['status' => 'closed', 'resolution' => ''])
        ->assertRedirect();

    expect($claim->fresh()->resolution)->toBe('REPONSE DEJA ENVOYEE');
});

it('espace les champs EMPILÉS d’un formulaire', function () {
    // ⚠️ `.ukc-field` n'a que sa gouttière interne : deux champs posés l'un sous
    // l'autre se touchent, et le libellé du second colle au champ du premier.
    $claim = seedClaim();

    $html = $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.claims.show', $claim))
        ->assertOk()
        ->getContent();
    $css = file_get_contents(resource_path('css/ukc.css'));

    expect($html)->toContain('ukc-field-stack')
        ->and($css)->toContain('.ukc-field-stack { display: grid; gap: 16px; }');
});
