<?php

use App\Models\User;

/**
 * La page « Mes séances à modérer » et le retour de visio.
 *
 * ⚠️ Elle n'était fermée qu'au visiteur anonyme : tout compte connecté l'ouvrait,
 * vide mais ouverte. Ces cas figent la règle - on y entre parce qu'on a une séance
 * à modérer, pas parce qu'on a un compte.
 */
function sessionConfiee(User $moderateur, $edition = null)
{
    $edition ??= openEdition();

    return $edition->sessions()->create([
        'type' => 'plenary',
        'label' => 'Séance 3 - Agronomie',
        'moderator_email' => $moderateur->email,
        'meeting_status' => 'scheduled',
    ]);
}

it('refuse la page de modération à un compte qui ne modère rien', function () {
    $compte = verifiedAccount();
    openEdition();

    $this->actingAs($compte, 'account')->get('/moderation')->assertForbidden();
});

it('ouvre la page à qui a une séance confiée', function () {
    $compte = verifiedAccount();
    sessionConfiee($compte);

    $this->actingAs($compte, 'account')->get('/moderation')
        ->assertOk()
        ->assertSee('Séance 3 - Agronomie');
});

it('rapproche le modérateur par e-mail sans tenir compte de la casse', function () {
    // ⚠️ L'e-mail de la séance est saisi à la main par le secrétariat : « Awa@… »
    // et « awa@… » désignent la même personne.
    $compte = verifiedAccount();
    sessionConfiee($compte)->update(['moderator_email' => mb_strtoupper($compte->email)]);

    $this->actingAs($compte, 'account')->get('/moderation')->assertOk();
});

it('ne laisse pas entrer un visiteur anonyme', function () {
    $this->get('/moderation')->assertRedirect();
});

it('garde l’ancienne adresse en redirection', function () {
    // Des signets et des courriels portent encore /animation.
    // ⚠️ La redirection vit DANS le groupe authentifié : hors connexion, c'est la
    // page de connexion qui répond d'abord, et l'adresse voulue est retenue pour
    // l'après-connexion. L'ancien lien reste donc utilisable dans les deux cas.
    $compte = verifiedAccount();
    sessionConfiee($compte);

    $this->actingAs($compte, 'account')->get('/animation')->assertRedirect('/moderation');
});

it('ne montre que les séances de l’autre modérateur à personne d’autre', function () {
    $edition = openEdition();
    $moi = verifiedAccount();
    $autre = User::create([
        'name' => 'Moussa Mensah', 'first_name' => 'Moussa', 'last_name' => 'Mensah',
        'email' => 'moussa@example.tg', 'password' => 'pass12345', 'phone' => '+22890000001',
    ]);
    sessionConfiee($moi, $edition);
    $edition->sessions()->create([
        'type' => 'plenary', 'label' => 'Séance de Moussa',
        'moderator_email' => $autre->email, 'meeting_status' => 'scheduled',
    ]);

    $this->actingAs($moi, 'account')->get('/moderation')
        ->assertOk()
        ->assertSee('Séance 3 - Agronomie')
        ->assertDontSee('Séance de Moussa');
});

// --- Retour de visio --------------------------------------------------------

it('ramène le modérateur à ses séances quand il quitte la visio', function () {
    $compte = verifiedAccount();
    $seance = sessionConfiee($compte);
    $seance->update(['meeting_status' => 'live']);

    $this->actingAs($compte, 'account')->get("/session/{$seance->id}/visio")
        ->assertOk()
        ->assertSee(route('account.moderation'));
});

it('ne détourne pas les autres participants vers l’espace du modérateur', function () {
    // ⚠️ Et surtout pas le staff : il est modérateur DANS la salle, mais n'a
    // aucune séance à lui - il atterrirait sur un 403.
    $edition = openEdition();
    $compte = verifiedAccount();
    $seance = $edition->sessions()->create([
        'type' => 'plenary', 'label' => 'Cérémonie', 'meeting_status' => 'live',
        'meeting_visibility' => 'public',
    ]);

    $this->actingAs($compte, 'account')->get("/session/{$seance->id}/visio")
        ->assertOk()
        ->assertDontSee(route('account.moderation'));
});
