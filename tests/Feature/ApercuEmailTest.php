<?php

use App\Notifications\AccountResetPassword;
use Illuminate\Testing\TestResponse;

// adminStaff() -> Pest.php ; verifiedAccount() -> Pest.php

/**
 * L'aperçu d'un modèle d'e-mail, et ce que le gabarit de marque garantit au
 * message qui part vraiment.
 *
 * ⚠️ L'aperçu montrait l'objet et le corps **en texte brut** : on y relisait sa
 * prose, on n'y voyait ni la mise en page, ni ce que les balises de fusion
 * allaient donner. Il rend désormais le VRAI message.
 */
function apercuDuModele(array $champs = []): TestResponse
{
    return test()->actingAs(adminStaff(), 'web')
        ->post(route('admin.emails.templates.preview'), array_merge([
            'subject' => 'Votre inscription',
            'body' => 'Bonjour, voici un message.',
        ], $champs));
}

/** Le HTML d'un e-mail réel portant un bouton (celui des captures). */
function mailAvecBouton(): string
{
    return (string) (new AccountResetPassword('jeton-exemple'))->toMail(verifiedAccount())->render();
}

it('rend le vrai gabarit de marque, pas un texte brut', function () {
    $reponse = apercuDuModele(['body' => 'Bonjour, voici un message.']);

    $reponse->assertOk();
    $html = $reponse->getContent();

    expect($html)->toContain('Bonjour, voici un message.');
    // Le bandeau de la charte, le filet or et le pied : le message tel qu'il part.
    expect($html)->toContain('#223ea4');
    expect($html)->toContain('#ffd700');
    expect($html)->toContain('Université de Kara');
});

it('remplace les balises comme l’envoi le ferait', function () {
    // ⚠️ La substitution est celle de l'envoi (QueueEmailCampaign::fill) sur un
    // participant d'exemple : une seconde table de valeurs finirait par promettre
    // autre chose que ce qui part.
    $html = apercuDuModele([
        'subject' => 'Bonjour {{participant_name}}',
        'body' => "Bonjour {{participant_name}}, l’événement {{edition_name}} vous attend.\nVotre espace : {{login_url}}",
    ])->getContent();

    expect($html)->toContain('Awa KODJO');
    expect($html)->toContain('Colloque international de l’Université de Kara');
    expect($html)->not->toContain('{{participant_name}}');
    // ⚠️ `{{login_url}}` pointait sur `/mon-espace`, qui n'existe pas : toute
    // campagne l'employant envoyait ses destinataires sur une page d'erreur.
    expect($html)->toContain(route('account.dashboard'));
    expect($html)->not->toContain('/mon-espace');
});

it('montre VIDE une balise que l’envoi ne sait pas remplir', function () {
    // C'est l'information la plus utile de cet écran : sept balises de la palette
    // sont encore remplacées par du vide à l'envoi. L'aperçu ne doit surtout pas
    // les embellir, sinon il promet une phrase que le destinataire n'aura pas.
    $html = apercuDuModele(['body' => 'Montant dû : {{amount}} FCFA.'])->getContent();

    expect($html)->toContain('Montant dû :  FCFA.');
});

it('n’exécute pas ce que l’admin tape', function () {
    $html = apercuDuModele(['body' => '<script>alert(1)</script>'])->getContent();

    expect($html)->not->toContain('<script>alert(1)</script>');
    expect($html)->toContain('&lt;script&gt;');
});

it('réserve l’aperçu au staff', function () {
    $this->post(route('admin.emails.templates.preview'), ['body' => 'x'])
        ->assertRedirect(route('account.login'));
});

it('défend le bouton du mode sombre de Gmail', function () {
    // ⚠️ Gmail REPEINT les couleurs : le bouton bleu à texte blanc ressortait en
    // lavande pâle à texte bleu foncé. Le fond est donc doublé d'une image
    // (dégradé d'une seule couleur), que Gmail ne retouche pas.
    $html = mailAvecBouton();

    expect($html)->toContain('background-image: linear-gradient(#223ea4, #223ea4)');
    expect($html)->toContain('color: #ffffff');
    // ⚠️ Et le rembourrage n'est plus un jeu de bordures : une bordure se recolore
    // indépendamment du fond, et c'est elle qui dessinait le halo pâle.
    expect($html)->not->toContain('border-left: 20px solid');
});

it('ne coupe plus l’intitulé du bouton en plein mot', function () {
    // `word-break: break-all` était posé sur TOUS les liens de la carte. Il ne
    // vaut que pour l'adresse copiée-collée sous le bouton.
    $html = mailAvecBouton();
    $bouton = (string) (preg_match('/<a[^>]*class="button[^"]*"[^>]*>/', $html, $m) ? $m[0] : '');

    expect($bouton)->not->toBe('');
    expect($bouton)->not->toContain('word-break');
    // Le lien de secours, lui, doit toujours pouvoir se couper.
    expect($html)->toContain('word-break: break-all');
});

it('resserre l’espace intérieur du message', function () {
    // 36 px de rembourrage de chaque côté mangeaient un cinquième d'un écran de
    // téléphone, et le pied en ajoutait autant sous la carte.
    $html = mailAvecBouton();

    expect($html)->toContain('padding: 26px 24px');
    expect($html)->toContain('padding: 16px 24px 20px');
    expect($html)->not->toContain('padding: 36px');
});
