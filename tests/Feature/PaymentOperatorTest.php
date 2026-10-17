<?php

use App\Models\Payment;
use App\Payments\GatewayRegistry;
use App\Payments\Gateways\FedaPayGateway;
use App\Support\Settings;
use Illuminate\Support\Facades\DB;

// openEdition()/verifiedAccount() -> tests/Pest.php

it('n’expose jamais le nom de l’agrégateur dans le libellé d’un moyen de paiement', function () {
    foreach (app(GatewayRegistry::class)->all() as $gateway) {
        expect($gateway->label())
            ->not->toContain('FedaPay')
            ->not->toContain('CashPay')
            ->not->toContain('SEMOA');
    }
});

it('propose des opérateurs mobile money ciblables', function () {
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

it('mémorise l’opérateur choisi sur le paiement et l’oublie si vidé', function () {
    $event = openEdition();
    $payment = Payment::create([
        'event_id' => $event->id, 'amount' => 5000, 'method' => 'fedapay', 'gateway' => 'fedapay',
        'meta' => ['flow' => 'formation'],
    ]);

    $payment->setOperator('togocel');
    expect($payment->fresh()->meta['operator'])->toBe('togocel');
    // Le flux d'origine est préservé.
    expect($payment->fresh()->meta['flow'])->toBe('formation');

    $payment->setOperator('');
    expect($payment->fresh()->meta)->not->toHaveKey('operator');
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

it('enregistre l’opérateur choisi lors de l’inscription payante à une formation', function () {
    $event = openEdition(['formation_enabled' => true]);
    $user = verifiedAccount();
    $event->participants()->create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $user->email, 'role' => 'trainee', 'user_id' => $user->id,
    ]);
    $formation = publishedFormation($event, 10000);

    $this->actingAs($user, 'account')->post(route('account.formations.enroll', $formation), [
        'gateway' => 'manual', 'operator' => 'moov_tg',
    ]);

    expect(Payment::first()->meta['operator'])->toBe('moov_tg');
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
