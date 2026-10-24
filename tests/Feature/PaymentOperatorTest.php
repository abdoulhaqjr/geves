<?php

use App\Models\Payment;
use App\Payments\GatewayRegistry;
use App\Payments\Gateways\FedaPayGateway;
use App\Support\Settings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

// openEdition()/verifiedAccount() -> tests/Pest.php

it('n’expose jamais le nom de l’agrégateur dans le libellé d’un moyen de paiement', function () {
    foreach (app(GatewayRegistry::class)->all() as $gateway) {
        expect($gateway->label())
            ->not->toContain('FedaPay')
            ->not->toContain('CashPay')
            ->not->toContain('SEMOA');
    }
});

/**
 * ⚠️ Le lexique des opérateurs sert à **LIRE** ce que le prestataire rapporte
 * sur un encaissement abouti, plus à faire choisir le payeur. Il finit sous les
 * yeux d'un gestionnaire et sur un reçu : il reste donc nommé en clair, et sans
 * jamais citer l'agrégateur.
 */
it('nomme en clair les opérateurs que le prestataire rapporte', function () {
    $operators = (new FedaPayGateway)->mobileOperators();

    expect($operators)->toHaveKey('togocel');
    expect($operators['togocel'])->not->toContain('FedaPay');
});

/**
 * ⚠️ **CashPay - c'est-à-dire SEMOA - a été RETIRÉE du projet.** Elle n'avait
 * jamais encaissé (aucun paiement sous cette clé, et la validation au bac à
 * sable n'avait pas été faite), mais elle restait proposée dans les deux listes
 * de l'admin et ses identifiants dormaient en base.
 *
 * ⚠️ Ce qui RESTE, et ne doit pas être « nettoyé » : le libellé `cashpay` dans
 * {@see Payment::ONLINE_METHODS}. Un paiement déjà encaissé par elle garderait
 * sa clé en base ; sans ce libellé, l'historique l'afficherait sans nom.
 */
it('ne propose plus CashPay comme passerelle, ni dans le registre ni en réglage', function () {
    $registre = app(GatewayRegistry::class);

    expect(array_map(fn ($g) => $g->key(), $registre->all()))->not->toContain('cashpay');
    expect($registre->get('cashpay'))->toBeNull();
    expect(file_exists(app_path('Payments/Gateways/CashpayGateway.php')))->toBeFalse();

    // La passerelle par défaut de la plateforme ne peut plus être posée sur elle.
    $this->actingAs(adminStaff(), 'web')
        ->post(route('admin.settings.update'), ['payments_default' => 'cashpay'])
        ->assertSessionHasErrors('payments_default');

    // Ni celle d'un événement.
    $this->actingAs(adminStaff(), 'web')
        ->post(route('admin.evenements.payment', openEdition()), ['gateway' => 'cashpay'])
        ->assertSessionHasErrors('gateway');
});

/** Le libellé d'historique, lui, reste lisible. */
it('sait encore nommer un paiement déjà encaissé par CashPay', function () {
    expect(Payment::ONLINE_METHODS)->toHaveKey('cashpay');
    expect(Payment::ONLINE_METHODS['cashpay'])->not->toContain('CashPay')->not->toContain('SEMOA');
});

it('distingue la nature des paiements et nomme la formation concernée', function () {
    $event = openEdition(['formation_enabled' => true]);
    $formation = publishedFormation($event, 10000);

    $participation = Payment::create(['event_id' => $event->id, 'amount' => 1, 'method' => 'cash', 'gateway' => 'manual']);
    $forma = Payment::create([
        'event_id' => $event->id, 'amount' => 2, 'method' => 'fedapay', 'gateway' => 'fedapay',
        'meta' => ['flow' => 'formation', 'formation_id' => $formation->id],
    ]);
    $co = Payment::create([
        'event_id' => $event->id, 'amount' => 3, 'method' => 'fedapay', 'gateway' => 'fedapay',
        'meta' => ['flow' => 'coauthor'],
    ]);

    expect($participation->flowLabel())->toBe('Participation');
    expect($participation->flowDetail())->toBeNull();
    expect($forma->flowLabel())->toBe('Formation');
    expect($forma->flowDetail())->toBe($formation->title);

    // Un co-auteur règle pour PARTICIPER : même nature affichée, la nuance
    // n'apparaît qu'en détail. La valeur technique reste distincte en base
    // (elle pilote la garde anti-double de ConfirmPayment).
    expect($co->flowLabel())->toBe('Participation');
    expect($co->displayFlow())->toBe('participation');
    expect($co->flowDetail())->toBe('Co-auteur');
    expect($co->flow())->toBe('coauthor');
});

it('range les paiements de co-auteur avec la participation dans le filtre admin', function () {
    $event = openEdition(['formation_enabled' => true]);
    $formation = publishedFormation($event, 10000);
    Payment::create([
        'event_id' => $event->id, 'amount' => 333, 'method' => 'fedapay', 'gateway' => 'fedapay',
        'meta' => ['flow' => 'coauthor'],
    ]);
    Payment::create([
        'event_id' => $event->id, 'amount' => 444, 'method' => 'fedapay', 'gateway' => 'fedapay',
        'meta' => ['flow' => 'formation', 'formation_id' => $formation->id],
    ]);

    $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.payments.index', ['event_id' => $event->id, 'flow' => 'participation']))
        ->assertOk()->assertSee('333')->assertDontSee('444');
});

it('filtre les paiements par nature sur la page admin', function () {
    $event = openEdition(['formation_enabled' => true]);
    $formation = publishedFormation($event, 10000);
    Payment::create(['event_id' => $event->id, 'amount' => 111, 'method' => 'cash', 'gateway' => 'manual']);
    Payment::create([
        'event_id' => $event->id, 'amount' => 222, 'method' => 'fedapay', 'gateway' => 'fedapay',
        'meta' => ['flow' => 'formation', 'formation_id' => $formation->id],
    ]);

    $admin = adminStaff();

    $this->actingAs($admin, 'web')->get(route('admin.payments.index', ['event_id' => $event->id, 'flow' => 'formation']))
        ->assertOk()->assertSee('222')->assertDontSee('111');

    $this->actingAs($admin, 'web')->get(route('admin.payments.index', ['event_id' => $event->id, 'flow' => 'participation']))
        ->assertOk()->assertSee('111')->assertDontSee('222');
});

/**
 * ⚠️⚠️ **LA PASSERELLE CHOISIE EST LE MOYEN DE PAIEMENT.** Le payeur désignait
 * d'abord son opérateur chez nous, pour que la page du prestataire arrive
 * pré-sélectionnée. Mesuré en conditions réelles : **elle ne l'était pas** - la
 * page affichait son propre sélecteur quand même. C'était donc un écran de plus
 * pour un choix refait aussitôt après, dans un cadre qui n'est pas le nôtre.
 *
 * ⚠️ **Le champ a été retiré du formulaire ET de ce qu'on accepte.** Retirer la
 * saisie sans fermer la porte laisserait un paramètre invisible piloter la page
 * du prestataire : qui le poste à la main dirigerait le paiement, et personne ne
 * le verrait - ni à l'écran, ni dans les gardes.
 */
it('ne fait plus choisir d’opérateur au payeur', function () {
    $event = openEdition(['formation_enabled' => true]);
    $user = verifiedAccount();
    $event->participants()->create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $user->email, 'role' => 'trainee', 'user_id' => $user->id,
    ]);
    $formation = publishedFormation($event, 10000);

    // ⚠️ La passerelle se RÈGLE en plus de ses identifiants : depuis qu'elle est
    // la seule proposée, des clés sans sélection laissent l'événement au guichet.
    config([
        'payments.default' => 'fedapay',
        'services.fedapay' => ['secret' => 'sk_test', 'mode' => 'test', 'webhook_secret' => 'whsec'],
    ]);
    Http::fake([
        '*/transactions/*/token' => Http::response(['url' => 'https://paiement.exemple/abc']),
        '*/transactions' => Http::response(['v1/transaction' => ['id' => 77]], 201),
    ]);

    // La page ne propose aucun opérateur…
    $this->actingAs($user, 'account')->get(route('account.formations'))
        ->assertOk()
        ->assertDontSee('name="operator"', false);

    // … et un code posté à la main n'est ni retenu, ni transmis.
    $this->actingAs($user, 'account')->post(route('account.formations.enroll', $formation), [
        'gateway' => 'fedapay', 'operator' => 'moov_tg',
    ]);

    expect(Payment::first()->meta ?? [])->not->toHaveKey('operator');

    $creation = collect(Http::recorded())
        ->first(fn (array $paire) => str_ends_with((string) $paire[0]->url(), '/transactions'));

    expect($creation)->not->toBeNull('aucune transaction créée');
    expect($creation[0]->data())->not->toHaveKey('mode');
});

/**
 * ⚠️ **Et plus rien ne sait enregistrer un opérateur AVANT le paiement.** Le
 * champ `meta['operator']` n'est plus alimenté que par ce que le prestataire
 * RAPPORTE une fois l'encaissement abouti ({@see OnlineGateway::details()}, qui
 * écrit sous `meta['gateway_detail']`). Laisser un `setOperator()` dormant
 * rouvrirait la porte qu'on vient de fermer, sans un écran pour le signaler.
 */
it('n’offre plus aucun moyen d’imposer l’opérateur avant le paiement', function () {
    expect(method_exists(Payment::class, 'setOperator'))->toBeFalse();

    $fautifs = [];

    foreach ([app_path(), resource_path('views')] as $dossier) {
        /** @var iterable<SplFileInfo> $fichiers */
        $fichiers = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dossier, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($fichiers as $f) {
            if (! $f instanceof SplFileInfo || ! in_array($f->getExtension(), ['php'], true)) {
                continue;
            }

            // ⚠️ On lit le CODE, commentaires retirés : cette garde s'explique
            // elle-même en citant `setOperator`, et un commentaire ne doit pas
            // la faire tomber (même esprit que `DegradeHerosTest`).
            $code = '';
            foreach (token_get_all((string) file_get_contents($f->getPathname())) as $t) {
                if (is_array($t) && in_array($t[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                    continue;
                }
                $code .= is_array($t) ? $t[1] : $t;
            }

            if (str_contains($code, 'setOperator') || str_contains($code, "input('operator')")) {
                $fautifs[] = $f->getFilename();
            }
        }
    }

    expect($fautifs)->toBe([]);
});

it('garde l’événement sélectionné quand on change un filtre sur la page Paiements', function () {
    $event = openEdition();
    $other = openEdition();
    Payment::create(['event_id' => $event->id, 'amount' => 111, 'method' => 'cash', 'gateway' => 'manual', 'status' => 'pending']);
    Payment::create(['event_id' => $other->id, 'amount' => 222, 'method' => 'cash', 'gateway' => 'manual', 'status' => 'pending']);

    // Le sélecteur d'événement est en tête, et le formulaire de filtres le reporte
    // (champ caché) : filtrer par statut ne doit pas élargir le périmètre.
    $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.payments.index', ['event_id' => $event->id, 'status' => 'pending']))
        ->assertOk()
        ->assertSee('111')
        ->assertDontSee('222');
});

/**
 * La migration de retrait. ⚠️ Chargée UNE fois : un `require` déjà exécuté rend
 * `true`, pas la migration.
 */
function migrationDeRetraitCashpay(): object
{
    static $migration = null;

    if ($migration === null) {
        $migration = require database_path('migrations/2026_10_18_000100_retirer_les_identifiants_cashpay.php');
    }

    return $migration;
}

/**
 * ⚠️ **Les identifiants d'une passerelle retirée ne dorment pas en base.** Mot
 * de passe, client secret, clé de vérification du callback : ce sont des
 * secrets marchands, et plus rien ne les lit. Les laisser, c'est les exposer
 * sans aucune contrepartie.
 *
 * ⚠️ Et il y a **deux dépôts** : les réglages de la plateforme et ceux propres
 * à chaque événement. N'en nettoyer qu'un laissait la moitié des secrets.
 */
it('efface les identifiants CashPay des réglages de la plateforme', function () {
    Settings::setMany([
        'cashpay.username' => 'user.demo',
        'cashpay.api_key' => 'cle-de-callback-tres-secrete',
        'payments.default' => 'cashpay',
        'fedapay.secret' => 'sk_test_garde',
    ]);

    migrationDeRetraitCashpay()->up();

    expect(DB::table('settings')->where('key', 'like', 'cashpay.%')->count())->toBe(0);
    // ⚠️ Le réglage ne peut pas rester sur une passerelle absente : sans ce
    // retour au manuel, le registre y retomberait en silence.
    expect(Settings::get('payments.default'))->toBe('manual');
    // Ce qui n'est pas CashPay n'est pas touché.
    expect(Settings::get('fedapay.secret'))->toBe('sk_test_garde');
});

it('efface les identifiants CashPay propres à un événement, sans toucher aux autres', function () {
    $garde = openEdition();
    $garde->forceFill(['settings' => ['payment' => [
        'gateway' => 'fedapay',
        'fedapay' => ['secret' => 'sk_edition'],
        'cashpay' => ['username' => 'u', 'api_key' => 'secret-callback'],
    ]] + $garde->settings])->save();

    migrationDeRetraitCashpay()->up();

    $paiement = $garde->fresh()->settings['payment'];
    expect($paiement)->not->toHaveKey('cashpay');
    expect($paiement['gateway'])->toBe('fedapay');
    expect($paiement['fedapay']['secret'])->toBe('sk_edition');
});

it('retire la passerelle de l’événement quand elle désignait CashPay', function () {
    $event = openEdition();
    $event->forceFill(['settings' => ['payment' => [
        'gateway' => 'cashpay',
        'cashpay' => ['username' => 'u'],
    ]] + $event->settings])->save();

    migrationDeRetraitCashpay()->up();

    // ⚠️ Le bloc devenu vide S'EN VA, il ne reste pas en tableau vide :
    // `EventPaymentConfig` distingue « aucun réglage » de « réglage vide ».
    expect($event->fresh()->settings)->not->toHaveKey('payment');
});
