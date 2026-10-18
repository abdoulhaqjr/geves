<?php

use App\Actions\ReconcilePayments;
use App\Models\Event;
use App\Models\EventSeries;
use App\Models\User;
use App\Payments\Gateways\FedaPayGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;

function onlineSetup(): array
{
    $series = EventSeries::create(['name' => 'UK', 'slug' => 'uk']);
    $event = $series->events()->create(['title' => 'UK 2026', 'year' => 2026, 'status' => 'open', 'slug' => 'uk-2026',
        'settings' => ['fees' => [['label' => 'Doctorant', 'amount' => 15000]]]]);
    $account = User::create(['first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'password' => 'pass12345', 'affiliation' => 'UK', 'grade' => 'Doctorant']);
    $account->markEmailAsVerified();
    $participant = $event->participants()->create(['user_id' => $account->id, 'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $account->email, 'phone' => '+22890000000', 'title' => 'Doctorant']);
    $event->submissions()->create(['participant_id' => $participant->id, 'ref_code' => '26-001', 'title' => 'T', 'status' => 'accepted']);

    return [$event, $account, $participant];
}

function configureFedapay(): void
{
    config(['services.fedapay' => ['secret' => 'sk_test', 'mode' => 'test', 'webhook_secret' => 'whsec']]);
}

/* ------------------------- Moyens de paiement ----------------------------- */

/**
 * Le corps de la requête de CRÉATION de transaction, ou null.
 *
 * ⚠️ **On lit la requête, on ne la fait pas valider par un rappel.**
 * `Http::assertSent` réussit dès qu'UNE requête satisfait le rappel : un rappel
 * qui laisse passer les autres appels (ici la demande de jeton) est donc
 * toujours satisfait, et le cas ne mord jamais. Mesuré : en envoyant « card »
 * dans `mode`, la garde restait verte.
 *
 * @return array<string, mixed>|null
 */
function corpsDeCreation(): ?array
{
    foreach (Http::recorded() as [$request, $response]) {
        if (str_ends_with($request->url(), '/transactions')) {
            return $request->data();
        }
    }

    return null;
}

/**
 * ⚠️ **La carte bancaire était acceptée sans être proposée.** La page de
 * paiement ne listait que des opérateurs mobile money, sous l'intitulé
 * « Choisir mon opérateur » : rien ne disait qu'on pouvait régler par carte, et
 * tout laissait croire qu'il fallait prendre un opérateur. Elle fonctionnait
 * pourtant - en ne choisissant rien, la page du prestataire propose tout ce que
 * le compte marchand accepte.
 */
it('propose la carte bancaire en plus du mobile money', function () {
    $moyens = (new FedaPayGateway)->paymentMethods();

    expect($moyens)->toHaveKey('card');
    expect($moyens['card'])->toContain('Carte');
    // ⚠️ Convention du projet : jamais le nom de l'agrégateur devant le payeur.
    expect($moyens['card'])->not->toContain('FedaPay');
    // Les opérateurs restent proposés à côté.
    expect($moyens)->toHaveKey('togocel');
});

/**
 * ⚠️ **Le code « carte » ne quitte JAMAIS la plateforme.** Le champ `mode` de la
 * requête ne désigne que des opérateurs mobile money ; aucun code carte n'est
 * documenté. En l'omettant, la page hébergée propose tout ce que le compte
 * marchand accepte - carte comprise, ce qu'on veut. Un code inventé ferait
 * échouer la création de la transaction, donc le paiement ENTIER : le payeur
 * n'aurait pas une page sans carte, il n'aurait pas de page du tout.
 */
it('n’envoie aucun mode au prestataire quand le payeur annonce la carte', function () {
    configureFedapay();
    [$event, $account, $participant] = onlineSetup();
    $payment = $event->payments()->create([
        'participant_id' => $participant->id, 'amount' => 15000,
        'method' => 'fedapay', 'gateway' => 'fedapay', 'status' => 'pending',
    ]);
    $payment->setOperator('card');

    Http::fake([
        '*/transactions/*/token' => Http::response(['url' => 'https://paiement.exemple/abc']),
        '*/transactions' => Http::response(['v1/transaction' => ['id' => 42]], 201),
    ]);

    (new FedaPayGateway)->initiate($payment);

    $corps = corpsDeCreation();

    expect($corps)->not->toBeNull('aucune transaction créée');
    expect($corps)->not->toHaveKey('mode');
});

/** Un opérateur mobile money, lui, EST transmis : c'est tout son intérêt. */
it('transmet l’opérateur mobile money choisi', function () {
    configureFedapay();
    [$event, $account, $participant] = onlineSetup();
    $payment = $event->payments()->create([
        'participant_id' => $participant->id, 'amount' => 15000,
        'method' => 'fedapay', 'gateway' => 'fedapay', 'status' => 'pending',
    ]);
    $payment->setOperator('moov_tg');

    Http::fake([
        '*/transactions/*/token' => Http::response(['url' => 'https://paiement.exemple/abc']),
        '*/transactions' => Http::response(['v1/transaction' => ['id' => 43]], 201),
    ]);

    (new FedaPayGateway)->initiate($payment);

    expect(corpsDeCreation()['mode'] ?? null)->toBe('moov_tg');
});

/**
 * ⚠️⚠️ **LA LISTE DES OPÉRATEURS EST CELLE DU PRESTATAIRE, PAS LA NÔTRE.** Elle
 * portait `mtn_open_ci` pour MTN Côte d'Ivoire - le code documenté est
 * **`mtn_ci`** - ainsi que `bmo`, `coris_money` et `wave_direct_ci`, qui ne
 * figurent dans aucune liste officielle. Les quatre avaient été écrits d'après
 * l'API publique sans jamais rencontrer un vrai compte marchand.
 *
 * ⚠️ Et un code inconnu est **ignoré en silence** : le payeur croyait choisir,
 * et rien ne partait. C'est le pire des échecs - celui qui ne se voit pas.
 */
it('ne propose que des codes opérateur documentés par le prestataire', function () {
    // Les codes publiés par le prestataire pour le mobile money.
    $documentes = ['mtn_open', 'moov', 'sbin', 'mtn_ci', 'moov_tg', 'togocel',
        'free_sn', 'airtel_ne', 'mtn_open_gn'];

    $inconnus = array_values(array_diff(array_keys((new FedaPayGateway)->mobileOperators()), $documentes));

    expect($inconnus)->toBe([]);
});

/**
 * ⚠️ **C'est le compte marchand qui accepte la carte, pas nous.** Elle demande
 * une couverture accordée sur demande ; sans elle, l'annoncer enverrait le
 * payeur sur une page qui ne la propose pas. D'où le réglage.
 */
it('retire la carte des moyens proposés quand le réglage la ferme', function () {
    configureFedapay();
    config(['services.fedapay.card' => false]);

    $moyens = (new FedaPayGateway)->paymentMethods();

    expect($moyens)->not->toHaveKey('card');
    expect($moyens)->toHaveKey('togocel'); // le mobile money n'est pas touché
});

/** La page de paiement du participant doit la MONTRER, pas seulement l'accepter. */
it('offre la carte sur la page de paiement du participant', function () {
    configureFedapay();
    [$event, $account, $participant] = onlineSetup();

    $this->actingAs($account, 'account')
        ->get(route('account.payment', $event->id))
        ->assertOk()
        ->assertSee('Carte bancaire (Visa / Mastercard)')
        // ⚠️ Le choix n'est pas obligatoire, et l'intitulé doit le dire : « Choisir
        // mon opérateur » laissait croire le contraire.
        ->assertSee('Je choisis sur la page de paiement')
        ->assertDontSee('Choisir mon opérateur');
});

/* ---------------------- Formulaire embarqué (sans redirection) ------------- */

/** La même configuration, plus la clé PUBLIQUE qui ouvre le formulaire chez nous. */
function configureFedapayEmbarque(): void
{
    configureFedapay();
    config(['services.fedapay.public_key' => 'pk_sandbox_abc123']);
}

/** Un paiement en attente, prêt à être engagé. */
function paiementEnAttente($event, $participant)
{
    return $event->payments()->create([
        'participant_id' => $participant->id, 'amount' => 15000,
        'method' => 'fedapay', 'gateway' => 'fedapay', 'status' => 'pending',
    ]);
}

/**
 * ⚠️ **La clé publique décide, et elle décide SEULE.** Pas de réglage « activer
 * le formulaire embarqué » à tenir d'accord avec la réalité : qui renseigne la
 * clé publique veut le formulaire sur sa page, qui ne la renseigne pas ne peut
 * pas l'avoir. Un interrupteur de plus aurait pu dire « activé » sans clé.
 */
it('ouvre le formulaire sur notre page dès qu’une clé publique est renseignée', function () {
    configureFedapayEmbarque();
    [$event, $account, $participant] = onlineSetup();
    $payment = paiementEnAttente($event, $participant);

    Http::fake([
        '*/transactions/*/token' => Http::response(['url' => 'https://paiement.exemple/abc']),
        '*/transactions' => Http::response(['v1/transaction' => ['id' => 51]], 201),
    ]);

    $resultat = (new FedaPayGateway)->initiate($payment);

    expect($resultat['type'])->toBe('embed');
    expect($resultat['public_key'])->toBe('pk_sandbox_abc123');
    // ⚠️ Le formulaire reprend la transaction CRÉÉE CÔTÉ SERVEUR : son montant est
    // le nôtre. Le laisser composer la sienne reviendrait à laisser le payeur
    // fixer ce qu'il règle.
    expect($resultat['transaction_id'])->toBe('51');
    // ⚠️ Porte de sortie : si le script du prestataire ne charge pas, le payeur
    // doit pouvoir finir. Un formulaire embarqué sans issue est un paiement perdu.
    expect($resultat['url'])->toBe('https://paiement.exemple/abc');
});

it('repart en redirection quand aucune clé publique n’est renseignée', function () {
    configureFedapay();
    [$event, $account, $participant] = onlineSetup();
    $payment = paiementEnAttente($event, $participant);

    Http::fake([
        '*/transactions/*/token' => Http::response(['url' => 'https://paiement.exemple/abc']),
        '*/transactions' => Http::response(['v1/transaction' => ['id' => 52]], 201),
    ]);

    expect((new FedaPayGateway)->initiate($payment)['type'])->toBe('redirect');
});

/**
 * ⚠️⚠️ **LE NAVIGATEUR NE DIT JAMAIS QU'UN PAIEMENT A RÉUSSI.** C'est la règle
 * du projet, et le formulaire embarqué est exactement l'endroit où on pourrait
 * l'oublier : son rappel s'exécute chez le payeur. La route ne lit aucun statut
 * de la requête - elle interroge le prestataire. Le navigateur ne fait que
 * donner l'heure, « regardez maintenant ».
 */
it('ne confirme rien sur la parole du navigateur', function () {
    configureFedapayEmbarque();
    [$event, $account, $participant] = onlineSetup();
    $payment = paiementEnAttente($event, $participant);
    $payment->update(['gateway_ref' => 'REF-51']);

    // Le prestataire, lui, dit que rien n'est réglé.
    Http::fake(['*/transactions/*' => Http::response(['v1/transaction' => ['status' => 'pending']])]);

    $this->postJson(route('payment.verify', ['gateway' => 'fedapay']), [
        'payment' => $payment->id,
        // Ce que le navigateur prétend - et qui ne doit peser RIEN.
        'status' => 'paid',
        'transaction' => ['status' => 'approved'],
    ])->assertOk()->assertJsonPath('status', 'pending');

    expect($payment->fresh()->status)->toBe('pending');
});

it('confirme le paiement quand le prestataire le dit', function () {
    Mail::fake();
    configureFedapayEmbarque();
    [$event, $account, $participant] = onlineSetup();
    $payment = paiementEnAttente($event, $participant);
    $payment->update(['gateway_ref' => 'REF-53']);

    Http::fake(['*/transactions/*' => Http::response(['v1/transaction' => ['status' => 'approved']])]);

    $this->postJson(route('payment.verify', ['gateway' => 'fedapay']), ['payment' => $payment->id])
        ->assertOk()->assertJsonPath('status', 'paid');

    expect($payment->fresh()->status)->toBe('paid');
});

/**
 * ⚠️ **La clé SECRÈTE ne doit jamais atteindre le navigateur.** La page porte la
 * clé publique, c'est son rôle ; la secrète signe les appels serveur. Les deux
 * voyagent côte à côte dans la configuration, et c'est précisément pour cela
 * qu'on le vérifie.
 */
it('pose la fenêtre de paiement sur la page, avec la clé publique et JAMAIS la secrète', function () {
    configureFedapayEmbarque();
    [$event, $account, $participant] = onlineSetup();

    Http::fake([
        '*/transactions/*/token' => Http::response(['url' => 'https://paiement.exemple/abc']),
        '*/transactions' => Http::response(['v1/transaction' => ['id' => 54]], 201),
    ]);

    // ⚠️ `back()` retombe sur l'accueil quand la requête n'a pas de référent :
    // dans un test il faut le poser, sinon on éprouve la page d'accueil.
    $page = $this->actingAs($account, 'account')
        ->from(route('account.payment', $event->id))
        ->followingRedirects()
        ->post(route('account.payment.pay', $event), ['gateway' => 'fedapay'])
        ->assertOk();

    $page->assertSee('data-pay-embed', false)
        ->assertSee('pk_sandbox_abc123', false)
        ->assertDontSee('sk_test', false);

    // ⚠️ **La fenêtre DIT ce qu'on règle et combien.** Elle ne portait que
    // « Régler en ligne » : le payeur arrivait devant un formulaire venu
    // d'ailleurs sans savoir ce qu'il engageait, au moment du parcours où l'on
    // doute le plus.
    // ⚠️⚠️ **LE BANDEAU NE RÉPÈTE NI L'OBJET NI LE MONTANT.** Il les a portés, et
    // c'était une erreur : le formulaire du prestataire annonce déjà la somme, et
    // il y AJOUTE ses frais - « 100 FCFA » chez nous, « PAYER 102 CFA » chez lui,
    // deux chiffres différents sur le même écran. Un seul vaut mieux que deux, et
    // c'est le sien qui sera débité.
    $page->assertSee('Paiement sécurisé')
        ->assertDontSee('ukc-pay-embed__what', false)
        ->assertDontSee('ukc-pay-embed__amount', false)
        ->assertDontSee('ukc-pay-embed-title', false);
});

/**
 * La feuille de style du projet, **commentaires retirés**.
 *
 * ⚠️ Sans ce retrait, le commentaire qui EXPLIQUE pourquoi la fenêtre attend
 * ferait passer le cas qui l'exige. Déjà vu deux fois ici, sur le dégradé des
 * héros puis sur la grille du barème : un commentaire n'applique rien.
 */
function cssUkc(): string
{
    return (string) preg_replace('#/\*.*?\*/#s', '', (string) file_get_contents(resource_path('css/ukc.css')));
}

/**
 * ⚠️ **La fenêtre ne s'ouvre QUE lorsque le formulaire du prestataire est là.**
 * Elle s'ouvrait vide, et le payeur regardait un cadre blanc se remplir pendant
 * une à deux secondes - au moment du parcours où l'on doute le plus, sur un
 * écran où une page blanche se lit comme une panne.
 */
it('montre une attente animée avant d’ouvrir la fenêtre', function () {
    configureFedapayEmbarque();
    [$event, $account, $participant] = onlineSetup();

    Http::fake([
        '*/transactions/*/token' => Http::response(['url' => 'https://paiement.exemple/abc']),
        '*/transactions' => Http::response(['v1/transaction' => ['id' => 55]], 201),
    ]);

    $this->actingAs($account, 'account')
        ->from(route('account.payment', $event->id))
        ->followingRedirects()
        ->post(route('account.payment.pay', $event), ['gateway' => 'fedapay'])
        ->assertOk()
        ->assertSee('ukc-pay-embed__wait', false)
        // ⚠️ Le texte est DANS la page, donc traduit : posé en `content:` dans la
        // feuille de style, il échapperait à `__()`.
        ->assertSee('Préparation du paiement sécurisé');

    $css = cssUkc();

    // Le panneau reste caché tant que rien n'est prêt...
    expect($css)->toContain('.ukc-pay-embed:not([data-ready]):not([data-failed]) .ukc-pay-embed__panel { display: none; }');
    // ...et l'attente s'efface dès que la fenêtre s'ouvre, dans les DEUX cas.
    expect($css)->toContain('.ukc-pay-embed[data-ready] .ukc-pay-embed__wait,');
    expect($css)->toContain('.ukc-pay-embed[data-failed] .ukc-pay-embed__wait { display: none; }');
    // Le ruban s'arrête pour qui demande moins de mouvement.
    expect($css)->toContain('.ukc-pay-embed__bar span { animation: none; }');
});

/**
 * ⚠️⚠️ **LA ZONE DU FORMULAIRE DOIT POUVOIR SE COMPRIMER**, sinon elle pousse le
 * pied hors du panneau - qui le rogne (`overflow: hidden`) - et c'est le pied
 * qui porte la **porte de sortie**. Mesuré à 800x460 : un plancher de 62dvh sur
 * la zone laissait 151px à un en-tête et un pied qui en réclament 164, et le
 * lien de secours disparaissait sous l'écran.
 *
 * ⚠️ Le plancher appartient donc au **cadre**, pas à la zone : c'est lui qui
 * donne sa hauteur à la fenêtre quand l'écran est confortable (mesuré à
 * 800x650 : 618px de fenêtre contre 501 avant), et c'est la zone qui rend la
 * main quand il ne l'est pas. ⚠️ `height: 100%` sur le cadre évite en plus les
 * deux défilements imbriqués - le formulaire du prestataire défilait DANS son
 * cadre, qui défilait dans notre zone.
 *
 * ⚠️⚠️ **ET IL NE S'EXPRIME JAMAIS EN `dvh`.** La page du prestataire centre sa
 * carte à la verticale (`display: flex; align-items: center`) dans un bloc haut
 * de 100vh DU CADRE : un cadre plus court que la carte partage le débordement en
 * haut ET en bas, et le haut devient INATTEIGNABLE - mesuré sur sa page, cadre de
 * 480px, carte à `top: -31px`, `scrollTop` bloqué à 0. Le plancher valait
 * `min(560px, 68dvh)` : sous 824px de fenêtre, 68dvh l'emportait - 491px sur un
 * écran de 722 - soit moins que sa carte, mesurée à 700x542 pour un cadre de
 * 778px. Le payeur perdait l'en-tête de la transaction, sans retour possible.
 *
 * ⚠️⚠️ **ET LA FENÊTRE RESTE SOUS 768px DE LARGE**, ce qui règle les deux à la
 * fois. Sa carte est figée à `max-width: 700px` : au-delà, il comble en GRIS,
 * chez nous. Et sa page porte `d-md-flex d-block` - sous 768px elle repasse en
 * `display: block`, la carte commence EN HAUT et son débordement redevient un
 * défilement ordinaire. Le rognage du haut n'est alors plus possible du tout.
 */
it('garde la porte de sortie à l’écran quelle que soit la hauteur', function () {
    $css = cssUkc();

    expect($css)->toContain('.ukc-pay-embed__box { flex: 1 1 auto; overflow-y: auto; min-height: 0; padding: 0; }');
    expect($css)->toContain('display: block; width: 100%; height: 542px; border: 0;');
    expect($css)->toContain('max-height: calc(100dvh - 24px);');

    // ⚠️ **La hauteur du cadre ne suit PAS celle de l'écran.** Exprimée en `dvh`,
    // elle repasse sous la carte du prestataire dès qu'on quitte le grand écran.
    // On lit la VALEUR plutôt que la ligne : c'est la propriété qui compte, pas
    // sa forme. (`[^-]height` écarte `max-height`.)
    preg_match('/\.ukc-pay-embed__box iframe \{[^}]*[^-]height: ([^;]+);/', $css, $m);
    $hauteur = trim($m[1] ?? '');
    expect($hauteur)->not->toContain('vh');
    expect((int) $hauteur)->toBeGreaterThanOrEqual(520);

    // ⚠️ **La fenêtre fait la largeur de sa carte**, figée à 700px : au-delà, il
    // comble en gris, et ce gris est chez nous.
    expect($css)->toContain('width: 100%; max-width: 700px; background: #fff;');

    // ⚠️⚠️ **ET ELLE NE REPASSE JAMAIS AU-DESSUS DE 768px.** C'est le seuil de sa
    // page (`d-md-flex d-block`) : au-dessus, elle centre sa carte à la verticale
    // et lui coupe le haut sans retour ; en dessous, la carte commence en haut.
    // On lit la valeur, pas la ligne - l'élargir d'un chiffre rouvrirait le défaut.
    preg_match('/\.ukc-pay-embed__panel \{[^}]*max-width: (\d+)px;/', $css, $l);
    expect((int) ($l[1] ?? 0))->toBeGreaterThan(0)->toBeLessThan(768);
});

/**
 * ⚠️⚠️ **DEUX MORCEAUX DE LA PAGE DU PRESTATAIRE N'ONT RIEN À FAIRE CHEZ NOUS.**
 *
 * ⚠️ **Sa croix** (`.modal-close`, 32px, posée à `top: -15px; right: -15px`)
 * appartient à SA fenêtre modale. Embarquée, elle dépasse de sa carte : il en
 * restait un quart de pastille blanche collé dans le coin, juste sous notre
 * propre croix - deux croix pour une fenêtre, dont une qui n'est pas à nous.
 *
 * ⚠️ **Sa bande du bas** (`.secured-by`, 22px) laisse voir le voile de sa page,
 * `body { background: rgba(0,0,0,.6) }` : une barre grise sous un formulaire
 * blanc partout ailleurs.
 *
 * ⚠️⚠️ **LA DÉCOUPE PORTE SUR LE CADRE, PAS SUR UN CACHE POSÉ DESSUS**, et c'est
 * ce que ce cas tient. Les pourcentages d'un `clip-path` se comptent sur la
 * largeur RÉELLE du cadre, qui perd 17px dès que notre zone sort sa barre de
 * défilement : un cache ancré à droite se décalerait d'autant et découvrirait la
 * croix. ⚠️ Et un élément posé DANS la zone ne survivrait pas - leur script y
 * écrit `innerHTML`.
 *
 * ⚠️ Les 17x17 du coin sont la marge droite de sa carte : son texte « MARCHAND »
 * s'arrête à 684px sur 700, mesuré au pixel près. Rien d'utile n'y passe.
 *
 * ⚠️ **Ses COINS laissent passer le même voile** : ses blocs blancs sont arrondis
 * de 3.8px, et en dehors de l'arrondi le fond sombre reparaît - quatre échardes,
 * dont une se voyait à l'œil nu en haut à gauche. D'où le cran de 4px à ce coin,
 * et les 25px du bas plutôt que 22 : ses coins bas s'arrêtent à 517px sur 542.
 */
it('ne laisse entrer ni la croix du prestataire ni sa bande grise', function () {
    $css = cssUkc();

    expect($css)->toContain('clip-path: polygon(0 4px, 4px 4px, 4px 0, calc(100% - 17px) 0, calc(100% - 17px) 17px, 100% 17px, 100% calc(100% - 25px), 0 calc(100% - 25px));');

    // ⚠️ En plein écran, sa bande du bas suit le bas de SON contenu et non celui
    // du cadre : découper le bas rognerait le formulaire. On ne retire que la croix.
    expect($css)->toContain('clip-path: polygon(0 4px, 4px 4px, 4px 0, calc(100% - 17px) 0, calc(100% - 17px) 17px, 100% 17px, 100% 100%, 0 100%);');

    // ⚠️ La découpe vit sur le CADRE. Posée sur la zone, elle rognerait aussi notre
    // barre de défilement, et le coin découpé ne suivrait plus le bord du cadre.
    preg_match('/\.ukc-pay-embed__box iframe \{([^}]*)\}/', $css, $m);
    expect($m[1] ?? '')->toContain('clip-path');
});

/**
 * ⚠️⚠️ **VÉRIFIER UN PAIEMENT SE COMPTE PAR PERSONNE, JAMAIS PAR IP.** Un
 * campus règle ses frais depuis une poignée d'adresses partagées : par IP, la
 * confirmation de quelqu'un peut être refusée parce que ses voisins viennent de
 * régler. Et ce n'est pas une requête de confort qu'on écarte là - c'est celle
 * qui dit au payeur que son règlement est passé. Même leçon que le fil de la
 * salle de visio.
 */
it('compte la vérification de paiement par personne, pas par adresse', function () {
    expect((string) file_get_contents(base_path('routes/web.php')))
        ->toContain("->middleware('throttle:paiement-verif')")
        ->not->toContain("->middleware('throttle:20,1')->name('payment.verify')");

    expect((string) file_get_contents(app_path('Providers/AppServiceProvider.php')))
        ->toContain("RateLimiter::for('paiement-verif'");
});

/**
 * ⚠️⚠️ **UN PAIEMENT EN ATTENTE A PU ÊTRE OUVERT AVEC UNE AUTRE PASSERELLE**, et
 * `firstOrCreate` ne pose `method`/`gateway` qu'à la CRÉATION. Qui avait d'abord
 * choisi « sur place », puis revenait régler en ligne, repartait sur une ligne
 * restée `manual`. Constaté sur un vrai paiement, et trois défauts d'un coup :
 *
 * - `setOperator()` interroge la passerelle **enregistrée** : la passerelle hors
 *   ligne ne connaît aucun opérateur, donc tout code était rejeté - le moyen
 *   choisi n'atteignait **jamais** le prestataire, et c'est ce qui faisait
 *   revenir le formulaire sur son opérateur par défaut ;
 * - `payments:reconcile` ne repasse que par la passerelle enregistrée : le
 *   filet de sécurité ne couvrait pas cette ligne ;
 * - la comptabilité annonçait un encaissement au guichet pour un règlement en
 *   ligne.
 */
it('réaligne la passerelle d’un paiement en attente ouvert ailleurs', function () {
    configureFedapayEmbarque();
    [$event, $account, $participant] = onlineSetup();

    // La ligne existe déjà, ouverte pour un encaissement au guichet.
    $payment = $event->payments()->create([
        'participant_id' => $participant->id, 'amount' => 15000,
        'method' => 'manual', 'gateway' => 'manual', 'status' => 'pending',
        'gateway_ref' => 'REF-ETRANGERE',
    ]);

    Http::fake([
        '*/transactions/*/token' => Http::response(['url' => 'https://paiement.exemple/abc']),
        '*/transactions' => Http::response(['v1/transaction' => ['id' => 59]], 201),
    ]);

    $this->actingAs($account, 'account')
        ->from(route('account.payment', $event->id))
        ->followingRedirects()
        ->post(route('account.payment.pay', $event), ['gateway' => 'fedapay', 'operator' => 'togocel'])
        ->assertOk();

    $payment->refresh();

    expect($payment->gateway)->toBe('fedapay');
    expect($payment->method)->toBe('fedapay');
    // Ce qui manquait : l'opérateur survit, et il part bien dans la requête.
    expect($payment->meta['operator'] ?? null)->toBe('togocel');
    expect(corpsDeCreation()['mode'] ?? null)->toBe('togocel');
    // ⚠️ L'ancienne référence appartenait à l'autre prestataire : la garder
    // reviendrait à lui demander des nouvelles d'une transaction étrangère.
    expect($payment->gateway_ref)->toBe('59');
});

/**
 * ⚠️⚠️ **LE FORMULAIRE EMBARQUÉ NE DEVINE PAS LE PAYS.** Il s'ouvre sur le
 * prestataire avec les options qu'on lui donne, et sans pays il retombe sur
 * celui du prestataire - **le Bénin**. Mesuré en production : un numéro togolais
 * (+228 90…) arrivait sur « Mtn Bénin », drapeau béninois compris, et le payeur
 * devait corriger à la main ce que nous savions déjà.
 *
 * ⚠️ C'est le MÊME `customer` que celui de la transaction, pas une seconde
 * construction : deux assemblages se décaleraient, et le formulaire
 * contredirait la transaction qu'il règle.
 */
it('donne au formulaire embarqué le pays du payeur et l’opérateur choisi', function () {
    configureFedapayEmbarque();
    [$event, $account, $participant] = onlineSetup();
    $payment = paiementEnAttente($event, $participant);
    $payment->setOperator('togocel');

    Http::fake([
        '*/transactions/*/token' => Http::response(['url' => 'https://paiement.exemple/abc']),
        '*/transactions' => Http::response(['v1/transaction' => ['id' => 56]], 201),
    ]);

    $resultat = (new FedaPayGateway)->initiate($payment->fresh());

    // Le participant de `onlineSetup()` porte un numéro togolais.
    expect($resultat['customer']['phone_number']['country'])->toBe('tg');
    expect($resultat['customer']['phone_number']['number'])->toBe('+22890000000');
    expect($resultat['mode'])->toBe('togocel');

    // ⚠️ Et c'est bien le même bloc que celui envoyé à la CRÉATION : le
    // formulaire ne doit pas contredire la transaction qu'il règle.
    expect(corpsDeCreation()['customer'])->toBe($resultat['customer']);
    expect(corpsDeCreation()['mode'])->toBe('togocel');
});

/**
 * ⚠️ **« Je choisis sur la page de paiement » reste un choix du PAYEUR.** Il a
 * dit qu'il trancherait là-bas : lui imposer quand même l'opérateur déduit de
 * son numéro lui retirerait la main, et la portabilité peut avoir démenti cette
 * déduction. Le **pays**, lui, part toujours - il ne décide de rien, il évite
 * seulement qu'on propose le Bénin à un Togolais.
 */
it('n’impose aucun opérateur quand le payeur a voulu choisir sur place', function () {
    configureFedapayEmbarque();
    [$event, $account, $participant] = onlineSetup();
    $payment = paiementEnAttente($event, $participant);

    Http::fake([
        '*/transactions/*/token' => Http::response(['url' => 'https://paiement.exemple/abc']),
        '*/transactions' => Http::response(['v1/transaction' => ['id' => 57]], 201),
    ]);

    $resultat = (new FedaPayGateway)->initiate($payment);

    expect($resultat['mode'])->toBe('');
    expect(corpsDeCreation())->not->toHaveKey('mode');
    expect($resultat['customer']['phone_number']['country'])->toBe('tg');
});

/**
 * Ce que le serveur sait doit ATTEINDRE le formulaire : la charge voyage dans
 * `data-pay-embed`, et c'est elle que le script relit.
 *
 * ⚠️ Un `mode` **vide** ne part pas : il vaudrait peut-être, chez le
 * prestataire, « aucun opérateur acceptable ».
 */
it('porte le pays et l’opérateur jusque dans la page', function () {
    configureFedapayEmbarque();
    [$event, $account, $participant] = onlineSetup();

    Http::fake([
        '*/transactions/*/token' => Http::response(['url' => 'https://paiement.exemple/abc']),
        '*/transactions' => Http::response(['v1/transaction' => ['id' => 58]], 201),
    ]);

    $this->actingAs($account, 'account')
        ->from(route('account.payment', $event->id))
        ->followingRedirects()
        ->post(route('account.payment.pay', $event), ['gateway' => 'fedapay', 'operator' => 'togocel'])
        ->assertOk()
        // ⚠️ Blade échappe les guillemets du JSON dans l'attribut.
        ->assertSee('&quot;country&quot;:&quot;tg&quot;', false)
        ->assertSee('&quot;mode&quot;:&quot;togocel&quot;', false);

    $js = (string) file_get_contents(resource_path('js/app.js'));

    expect($js)->toContain('{ customer: cfg.customer }');
    expect($js)->toContain('...(cfg.mode ? { mode: cfg.mode } : {})');
});

/**
 * ⚠️⚠️ **L'ATTENTE A UN PLANCHER, et la fenêtre s'ouvre même en cas d'échec.**
 * C'est la contrepartie indispensable de la fenêtre qui attend : le signal peut
 * ne jamais venir (cadre déjà chargé, script absent, CDN coupé), et sans issue
 * l'attente tournerait à l'infini devant un payeur dont la PORTE DE SORTIE - la
 * page hébergée, en pied de fenêtre - serait cachée. Un paiement perdu, pour
 * avoir voulu épargner un cadre blanc.
 */
it('ouvre la fenêtre même quand le formulaire ne vient jamais', function () {
    $js = (string) file_get_contents(resource_path('js/app.js'));

    // Un plancher général, et un second dès que le cadre est posé.
    expect($js)->toContain('window.setTimeout(montrer, 12000)');
    expect($js)->toContain('window.setTimeout(montrer, 4000)');

    // ⚠️ L'objet absent LÈVE une erreur au lieu de rendre la main en silence :
    // sinon la fenêtre resterait sur son attente, pour toujours, sans un mot.
    expect($js)->toContain("if (typeof window.FedaPay === 'undefined') throw new Error(");

    // Et l'échec MONTRE la fenêtre : c'est elle qui porte le message et le lien.
    expect(cssUkc())->toContain('.ukc-pay-embed[data-failed] .ukc-pay-embed__panel { animation:');
});

/* ------------------------------- FedaPay ---------------------------------- */

it('FedaPay : initie une transaction et redirige vers l’URL hébergée', function () {
    configureFedapay();
    [$event, $account, $participant] = onlineSetup();
    $payment = $event->payments()->create(['participant_id' => $participant->id, 'amount' => 15000, 'gateway' => 'fedapay', 'status' => 'pending']);

    Http::fake([
        '*/transactions/*/token' => Http::response(['url' => 'https://checkout.fedapay.com/xyz']),
        '*/transactions/*' => Http::response(['v1/transaction' => ['status' => 'approved']]),
        '*/transactions' => Http::response(['v1/transaction' => ['id' => 4242]], 201),
    ]);

    $result = (new FedaPayGateway)->initiate($payment);

    expect($result['url'])->toBe('https://checkout.fedapay.com/xyz');
    expect($payment->fresh()->gateway_ref)->toBe('4242');
});

it('FedaPay : le webhook signé HMAC confirme le paiement', function () {
    Mail::fake();
    configureFedapay();
    [$event, $account, $participant] = onlineSetup();
    $payment = $event->payments()->create(['participant_id' => $participant->id, 'amount' => 15000, 'gateway' => 'fedapay', 'gateway_ref' => '4242', 'status' => 'pending']);

    $body = json_encode(['name' => 'transaction.approved', 'entity' => ['id' => 4242, 'status' => 'approved']]);
    $ts = (string) time();
    $sig = hash_hmac('sha256', $ts.'.'.$body, 'whsec');
    $header = "t={$ts},s={$sig}";

    $this->call('POST', route('payment.webhook', ['gateway' => 'fedapay']), [], [], [],
        ['HTTP_X_FEDAPAY_SIGNATURE' => $header, 'CONTENT_TYPE' => 'application/json'], $body)->assertOk();

    expect($payment->fresh()->status)->toBe('paid');
});

it('FedaPay : webhook sans signature valide ne confirme rien', function () {
    configureFedapay();
    [$event, $account, $participant] = onlineSetup();
    $payment = $event->payments()->create(['participant_id' => $participant->id, 'amount' => 15000, 'gateway' => 'fedapay', 'gateway_ref' => '4242', 'status' => 'pending']);

    $body = json_encode(['entity' => ['id' => 4242, 'status' => 'approved']]);

    $this->call('POST', route('payment.webhook', ['gateway' => 'fedapay']), [], [], [],
        ['HTTP_X_FEDAPAY_SIGNATURE' => 't=1,s=deadbeef', 'CONTENT_TYPE' => 'application/json'], $body)->assertOk();

    expect($payment->fresh()->status)->toBe('pending');
});

/* ------------------- Webhook & passerelle propre à l'événement ---------------- */

/** Configure l'événement avec SON propre compte FedaPay (secrets différents du global). */
function fedapayForEdition(Event $event, string $secret = 'sk_edition', string $webhook = 'wh_edition'): void
{
    $event->update(['settings' => array_merge($event->settings ?? [], [
        'payment' => ['gateway' => 'fedapay', 'fedapay' => ['secret' => $secret, 'mode' => 'test', 'webhook_secret' => $webhook]],
    ])]);
}

/** Forge une requête de webhook FedaPay signée avec le secret donné. */
function fedapayWebhook(string $url, int $reference, string $secret, string $status = 'approved'): TestResponse
{
    $body = json_encode(['name' => 'transaction.approved', 'entity' => ['id' => $reference, 'status' => $status]]);
    $ts = (string) time();
    $sig = hash_hmac('sha256', $ts.'.'.$body, $secret);

    return test()->call('POST', $url, [], [], [],
        ['HTTP_X_FEDAPAY_SIGNATURE' => "t={$ts},s={$sig}", 'CONTENT_TYPE' => 'application/json'], $body);
}

it('FedaPay : un webhook signé avec le secret PROPRE à l’événement confirme le paiement', function () {
    Mail::fake();
    configureFedapay(); // global : « whsec »
    [$event, $account, $participant] = onlineSetup();
    fedapayForEdition($event); // l'événement signe avec « wh_edition »
    $payment = $event->payments()->create(['participant_id' => $participant->id, 'amount' => 15000, 'gateway' => 'fedapay', 'gateway_ref' => '777', 'status' => 'pending']);

    fedapayWebhook(route('payment.webhook', ['gateway' => 'fedapay']), 777, 'wh_edition')->assertOk();

    expect($payment->fresh()->status)->toBe('paid');
});

it('FedaPay : l’URL de webhook dédiée à l’événement confirme aussi le paiement', function () {
    Mail::fake();
    configureFedapay();
    [$event, $account, $participant] = onlineSetup();
    fedapayForEdition($event);
    $payment = $event->payments()->create(['participant_id' => $participant->id, 'amount' => 15000, 'gateway' => 'fedapay', 'gateway_ref' => '778', 'status' => 'pending']);

    fedapayWebhook(route('payment.webhook.edition', ['gateway' => 'fedapay', 'event' => $event]), 778, 'wh_edition')->assertOk();

    expect($payment->fresh()->status)->toBe('paid');
});

it('FedaPay : le marchand d’un événement ne peut pas confirmer le paiement d’une AUTRE événement', function () {
    configureFedapay();
    [$event, $account, $participant] = onlineSetup();
    fedapayForEdition($event); // événement A : « wh_edition »

    // Paiement rattaché à une AUTRE événement (compte global).
    $autre = $event->series->events()->create(['title' => 'UK 2027', 'year' => 2027, 'status' => 'open', 'slug' => 'uk-2027']);
    $payment = $autre->payments()->create(['participant_id' => $participant->id, 'amount' => 15000, 'gateway' => 'fedapay', 'gateway_ref' => '999', 'status' => 'pending']);

    fedapayWebhook(route('payment.webhook', ['gateway' => 'fedapay']), 999, 'wh_edition')->assertOk();

    expect($payment->fresh()->status)->toBe('pending'); // ignoré
});

it('Réconciliation : confirme un paiement réglé dont le webhook n’est jamais arrivé', function () {
    Mail::fake();
    configureFedapay();
    [$event, $account, $participant] = onlineSetup();
    $payment = $event->payments()->create(['participant_id' => $participant->id, 'amount' => 15000, 'gateway' => 'fedapay', 'gateway_ref' => '4242', 'status' => 'pending']);

    Http::fake(['*/transactions/4242' => Http::response(['v1/transaction' => ['status' => 'approved']])]);

    $report = app(ReconcilePayments::class)->handle();

    expect($report['paid'])->toBe(1);
    expect($payment->fresh()->status)->toBe('paid');
    expect($payment->fresh()->receipt_no)->not->toBeEmpty();
});

it('Réconciliation : interroge la passerelle avec les identifiants de l’ÉDITION', function () {
    Mail::fake();
    configureFedapay(); // global : « sk_test »
    [$event, $account, $participant] = onlineSetup();
    fedapayForEdition($event); // événement : « sk_edition »
    $payment = $event->payments()->create(['participant_id' => $participant->id, 'amount' => 15000, 'gateway' => 'fedapay', 'gateway_ref' => '4243', 'status' => 'pending']);

    Http::fake(['*/transactions/4243' => Http::response(['v1/transaction' => ['status' => 'approved']])]);

    app(ReconcilePayments::class)->handle();

    Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer sk_edition'));
    expect($payment->fresh()->status)->toBe('paid');
});

it('Réconciliation : laisse « en attente » un paiement non abouti', function () {
    configureFedapay();
    [$event, $account, $participant] = onlineSetup();
    $payment = $event->payments()->create(['participant_id' => $participant->id, 'amount' => 15000, 'gateway' => 'fedapay', 'gateway_ref' => '4244', 'status' => 'pending']);

    Http::fake(['*/transactions/4244' => Http::response(['v1/transaction' => ['status' => 'pending']])]);

    $report = app(ReconcilePayments::class)->handle();

    expect($report['paid'])->toBe(0);
    expect($payment->fresh()->status)->toBe('pending');
});

it('Back-office : « Revérifier » confirme un paiement réellement réglé', function () {
    Mail::fake();
    configureFedapay();
    [$event, $account, $participant] = onlineSetup();
    $payment = $event->payments()->create(['participant_id' => $participant->id, 'amount' => 15000, 'gateway' => 'fedapay', 'gateway_ref' => '5555', 'status' => 'pending']);

    Http::fake(['*/transactions/5555' => Http::response(['v1/transaction' => ['status' => 'approved']])]);

    $this->actingAs(adminStaff())
        ->post(route('admin.payments.recheck', $payment))
        ->assertRedirect();

    expect($payment->fresh()->status)->toBe('paid');
});

/* ------------------- Ce que le prestataire a enregistré -------------------- */

/**
 * ⚠️⚠️ **LE PAYEUR N'EST PAS TOUJOURS LE PARTICIPANT.** Mesuré en production le
 * 18/10/2026 : inscription au nom d'un numéro togolais, règlement effectué
 * **depuis un autre numéro, chez un autre opérateur** (Moov, alors qu'on avait
 * déduit Togocel). Sans ce relevé, un paiement contesté ne se raccroche à rien -
 * ni à l'opérateur employé, ni au téléphone débité, ni à la référence que le
 * payeur lit sur son relevé.
 *
 * ⚠️ `include=payment_method` n'est pas décoratif : sans lui, le numéro qui a
 * payé n'est pas dans la réponse, et l'adresse `/payment_methods/{id}` répond
 * 404.
 */
it('garde ce que le prestataire a enregistré de l’encaissement', function () {
    Mail::fake();
    configureFedapay();
    [$event, $account, $participant] = onlineSetup();
    $payment = $event->payments()->create(['participant_id' => $participant->id, 'amount' => 15000,
        'gateway' => 'fedapay', 'gateway_ref' => '7777', 'status' => 'pending']);

    Http::fake([
        '*/transactions/7777?include=payment_method' => Http::response(['v1/transaction' => [
            'status' => 'approved',
            'mode' => 'moov_tg',
            'transaction_key' => '040977964356',
            'amount_debited' => 15300,
            'fees' => 300,
            'approved_at' => '2026-10-18T18:30:07.371+00:00',
            'to_be_transferred_at' => '2026-10-21T18:30:06.920+00:00',
            'payment_method' => ['brand' => 'moov_tg', 'country' => 'TG', 'number' => '22896886551'],
            'payment_token' => 'JETON-QUI-NE-DOIT-JAMAIS-ETRE-EN-BASE',
            'receipt_url' => 'https://api.exemple/receipt?token=JETON-QUI-NE-DOIT-JAMAIS-ETRE-EN-BASE',
        ]]),
        '*/transactions/7777' => Http::response(['v1/transaction' => ['status' => 'approved']]),
    ]);

    app(ReconcilePayments::class)->handle();

    $detail = $payment->fresh()->gatewayDetail();

    expect($detail['operator'] ?? null)->toBe('moov_tg');
    expect($detail['operator_label'] ?? null)->toBe('Moov (Togo)');
    expect($detail['operator_ref'] ?? null)->toBe('040977964356');
    expect($detail['payer_phone'] ?? null)->toBe('22896886551');
    expect($detail['amount_debited'] ?? null)->toBe(15300);
    expect($detail['fees'] ?? null)->toBe(300);

    // ⚠️⚠️ **RIEN DE SECRET N'ENTRE EN BASE.** Le jeton autorise le règlement, et
    // l'URL du reçu en porte un dans son adresse : ce relevé finit sous les yeux
    // d'un gestionnaire, et dans une sauvegarde.
    expect(json_encode($payment->fresh()->meta, JSON_THROW_ON_ERROR))
        ->not->toContain('JETON-QUI-NE-DOIT-JAMAIS-ETRE-EN-BASE');
});

/**
 * ⚠️⚠️ **UN DÉTAIL ILLISIBLE NE DOIT JAMAIS COÛTER UNE INSCRIPTION.** Le
 * participant a payé ; si l'appel d'agrément échoue - réseau, adresse changée,
 * champ disparu - il perdrait sa place pour une ligne de confort.
 */
it('confirme le paiement même quand le détail est illisible', function () {
    Mail::fake();
    configureFedapay();
    [$event, $account, $participant] = onlineSetup();
    $payment = $event->payments()->create(['participant_id' => $participant->id, 'amount' => 15000,
        'gateway' => 'fedapay', 'gateway_ref' => '8888', 'status' => 'pending']);

    Http::fake([
        '*/transactions/8888?include=payment_method' => Http::response(['message' => 'en panne'], 500),
        '*/transactions/8888' => Http::response(['v1/transaction' => ['status' => 'approved']]),
    ]);

    app(ReconcilePayments::class)->handle();

    expect($payment->fresh()->status)->toBe('paid');
    expect($payment->fresh()->gatewayDetail())->toBe([]);
});

/**
 * ⚠️⚠️ **GEVES NE RENVOIE PAS L'ARGENT, ET DOIT LE DIRE.** Le prestataire ne
 * rembourse que depuis son tableau de bord, et seulement pour un opérateur : son
 * API n'expose aucune adresse de remboursement. Le geste est donc comptable -
 * il reprend les droits et marque la ligne - pendant qu'un humain fait le
 * virement ailleurs. Sans cette phrase, un gestionnaire croit l'argent reparti
 * et le participant attend.
 */
it('Back-office : rembourser dit que l’argent n’est pas renvoyé, et laisse une trace', function () {
    Mail::fake();
    configureFedapay();
    [$event, $account, $participant] = onlineSetup();
    $payment = $event->payments()->create(['participant_id' => $participant->id, 'amount' => 15000,
        'gateway' => 'fedapay', 'gateway_ref' => '9999', 'status' => 'paid']);

    $this->actingAs(adminStaff())
        ->post(route('admin.payments.refund', $payment))
        ->assertRedirect();

    $frais = $payment->fresh();

    expect($frais->status)->toBe('refunded');
    expect(session('ukc_status'))->toContain('n’a PAS été renvoyé');
    // La trace : qui, et quand. « Remboursé » chez nous contre « approuvé » chez
    // le prestataire, sans savoir qui demander, c'est une enquête pour rien.
    expect($frais->meta['refunded_at'] ?? null)->not->toBeNull();
    expect($frais->meta['refunded_by_name'] ?? null)->not->toBeNull();
});

/** ⚠️ Et il le dit AVANT le clic, pas seulement après. */
it('Back-office : la confirmation de remboursement prévient avant le clic', function () {
    configureFedapay();
    [$event, $account, $participant] = onlineSetup();
    $event->payments()->create(['participant_id' => $participant->id, 'amount' => 15000,
        'gateway' => 'fedapay', 'gateway_ref' => '9191', 'status' => 'paid']);

    $this->actingAs(adminStaff())
        ->get(route('admin.payments.index'))
        ->assertSee('le virement se fait depuis le tableau de bord du prestataire', false);
});
