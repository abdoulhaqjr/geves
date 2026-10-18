<?php

use App\Models\Event;
use App\Models\EventSeries;
use App\Models\User;
use App\Payments\Gateways\FedaPayGateway;
use App\Support\MobileNetwork;

/**
 * **Le numéro du payeur DIT déjà son opérateur.**
 *
 * La liste en portait onze, du Togo au Niger, et le payeur devait y retrouver le
 * sien alors que le préfixe de son numéro le désigne. Ces cas tiennent les deux
 * moitiés : les plans de numérotation d'un côté, la traduction vers le
 * vocabulaire du prestataire de l'autre.
 *
 * ⚠️ Et ils tiennent surtout ce qu'on ne fait PAS : deviner. Un préfixe inconnu,
 * un indicatif hors zone, un opérateur que le prestataire ne sait pas cibler -
 * dans tous ces cas le payeur choisit, comme avant.
 */

/* ------------------------------ Plans de numérotation --------------------- */

it('déduit l’opérateur du préfixe, pays par pays', function (string $numero, ?string $reseau) {
    expect(MobileNetwork::fromPhone($numero))->toBe($reseau);
})->with([
    // Togo - 8 chiffres. Les deux opérateurs se partagent les tranches 7x et 9x.
    ['+22890000000', 'togocom'],
    ['+22873000000', 'togocom'],
    ['+22897000000', 'moov-tg'],
    ['+22878000000', 'moov-tg'],
    // Côte d'Ivoire - 10 chiffres depuis 2021.
    ['+2250502030405', 'mtn-ci'],
    ['+2250102030405', 'moov-ci'],
    ['+2250702030405', 'orange-ci'],
    // Sénégal - 9 chiffres.
    ['+221761234567', 'free-sn'],
    ['+221771234567', 'orange-sn'],
    // Niger - 8 chiffres.
    ['+22796000000', 'airtel-ne'],
    // ⚠️ Un numéro écrit comme on le lit : le composant reçoit ce que la base
    // contient, et la base contient ce que la personne a tapé.
    ['+228 90 01 52 84', 'togocom'],
]);

/**
 * ⚠️ **Le Bénin a posé « 01 » devant TOUS ses numéros** le 30 novembre 2024, en
 * passant à dix chiffres. Les deux formes désignent le même abonné, et la base
 * porte les deux - un numéro saisi avant la réforme n'a pas été réécrit.
 */
it('reconnaît un numéro béninois dans ses DEUX formes', function () {
    expect(MobileNetwork::fromPhone('+2290196123456'))->toBe('mtn-bj');
    expect(MobileNetwork::fromPhone('+22996123456'))->toBe('mtn-bj');

    expect(MobileNetwork::fromPhone('+2290194123456'))->toBe('moov-bj');
    expect(MobileNetwork::fromPhone('+2290120123456'))->toBe('celtiis-bj');
});

/**
 * ⚠️ **Préfixe inconnu = rien du tout.** Les plans publiés sont incomplets - le
 * Niger n'attribue publiquement pas la moitié de ses tranches - et ils bougent.
 * Ne rien déduire laisse le payeur choisir, ce qui fonctionnait déjà ; deviner
 * le dirigerait vers le mauvais guichet.
 */
it('ne devine RIEN hors des plans connus', function (?string $numero) {
    expect(MobileNetwork::fromPhone($numero))->toBeNull();
})->with([
    ['+22875000000'],   // Togo : 74 à 77 ne sont pas attribués
    ['+22712000000'],   // Niger : tranche absente du plan publié
    ['+33612345678'],   // hors zone
    ['+228'],           // indicatif seul
    ['90001234'],       // numéro national seul : on ne suppose pas le pays
    [''],
    [null],
]);

/* --------------------------- Vocabulaire du prestataire ------------------- */

it('traduit le réseau dans les codes du prestataire', function () {
    $gw = new FedaPayGateway;

    expect($gw->operatorFromPhone('+22890000000'))->toBe('togocel');
    expect($gw->operatorFromPhone('+22897000000'))->toBe('moov_tg');
    expect($gw->operatorFromPhone('+2290196123456'))->toBe('mtn_open');
    expect($gw->operatorFromPhone('+2290120123456'))->toBe('sbin');
    expect($gw->operatorFromPhone('+2250502030405'))->toBe('mtn_open_ci');
    expect($gw->operatorFromPhone('+221761234567'))->toBe('free_sn');
});

/**
 * ⚠️ **Ce que le prestataire ne sait pas cibler ne se déduit pas.** Il n'a pas
 * de code pour Orange Côte d'Ivoire, Orange Sénégal ni Moov Niger : le réseau
 * est parfaitement reconnu, et pourtant on ne pré-sélectionne rien. Rendre un
 * code approchant enverrait le payeur chez un opérateur qui n'est pas le sien.
 */
it('ne rend aucun code pour un réseau que le prestataire n’offre pas', function () {
    $gw = new FedaPayGateway;

    expect(MobileNetwork::fromPhone('+2250702030405'))->toBe('orange-ci');
    expect($gw->operatorFromPhone('+2250702030405'))->toBeNull();
    expect($gw->operatorFromPhone('+221771234567'))->toBeNull();
});

/**
 * ⚠️ **Et jamais un code qu'on n'offre PLUS.** Le dernier filtre tient la
 * promesse de `mobileOperators()`, seule liste qui part dans la requête : sans
 * lui, retirer un opérateur de la liste laisserait son code se glisser dans
 * `mode` par cette porte - là où personne ne penserait à regarder.
 */
it('ne rend jamais un code absent de la liste transmissible', function () {
    $gw = new class extends FedaPayGateway
    {
        public function mobileOperators(): array
        {
            return [];
        }
    };

    expect(MobileNetwork::fromPhone('+22890000000'))->toBe('togocom');
    expect($gw->operatorFromPhone('+22890000000'))->toBeNull();
});

/* ------------------------------- Sur la page ------------------------------ */

/**
 * Un événement ouvert, un compte vérifié et sa fiche de participation.
 *
 * ⚠️ Aide LOCALE : les aides de Pest sont globales, mais une exécution filtrée
 * ne charge pas le fichier qui les déclare.
 *
 * @return array{0: Event, 1: User}
 */
function payeurAvecNumero(string $telephone): array
{
    $series = EventSeries::create(['name' => 'UK', 'slug' => 'uk-op']);
    $event = $series->events()->create(['title' => 'UK 2026', 'year' => 2026, 'status' => 'open', 'slug' => 'uk-2026-op',
        'settings' => ['fees' => [['label' => 'Doctorant', 'amount' => 15000, 'default' => true]]]]);

    $account = User::create(['first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa-op@example.tg',
        'password' => 'pass12345', 'affiliation' => 'UK', 'grade' => 'Doctorant', 'phone' => $telephone]);
    $account->markEmailAsVerified();

    $participant = $event->participants()->create(['user_id' => $account->id, 'first_name' => 'Awa',
        'last_name' => 'Kodjo', 'email' => $account->email, 'phone' => $telephone, 'title' => 'Doctorant']);
    $event->submissions()->create(['participant_id' => $participant->id, 'ref_code' => '26-OP1', 'title' => 'T', 'status' => 'accepted']);

    config(['services.fedapay' => ['secret' => 'sk_test', 'mode' => 'test']]);

    return [$event, $account];
}

it('pré-sélectionne l’opérateur déduit du numéro du payeur', function () {
    [$event, $account] = payeurAvecNumero('+22897000000'); // Moov Togo

    $this->actingAs($account, 'account')->get(route('account.payment', $event->id))->assertOk()
        // ⚠️ La directive `@selected` rend l'attribut NU : `selected`, pas
        // `selected="selected"`. Un cas qui attend la forme longue échoue sur un
        // rendu parfaitement correct.
        ->assertSee('value="moov_tg" selected>', false)
        // ⚠️ **On DIT que c'est déduit.** Un opérateur pré-sélectionné sans un mot
        // se lit comme une obligation, et la portabilité peut l'avoir démenti : le
        // payeur doit savoir d'où vient ce choix pour oser le changer.
        ->assertSee('Déduit de votre numéro')
        ->assertDontSee('<option value="" selected>', false);
});

it('ne pré-sélectionne rien quand le numéro ne dit rien', function () {
    [$event, $account] = payeurAvecNumero('+33612345678');

    $this->actingAs($account, 'account')->get(route('account.payment', $event->id))->assertOk()
        ->assertSee('<option value="" selected>', false)
        ->assertDontSee('Déduit de votre numéro');
});

/**
 * ⚠️ **`old()` rend null quand rien n'a été saisi, et `''` quand le payeur a
 * VOULU « je choisis sur la page de paiement ».** Confondre les deux lui
 * remettrait son opérateur au retour d'une erreur de formulaire, contre son
 * choix explicite - et c'est précisément le moment où il a besoin qu'on ne
 * touche plus à ses réponses.
 */
it('respecte le choix explicite de ne rien choisir', function () {
    [$event, $account] = payeurAvecNumero('+22897000000');

    $this->actingAs($account, 'account')
        ->withSession(['_old_input' => ['operator' => '']])
        ->get(route('account.payment', $event->id))->assertOk()
        ->assertSee('<option value="" selected>', false)
        ->assertDontSee('value="moov_tg" selected>', false);
});
