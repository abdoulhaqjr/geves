<?php

use App\Actions\IssueAttestation;
use App\Models\Attestation;

// openEdition() -> AccountSubmissionTest

it('affiche la page d’accueil publique', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('GEVES')
        ->assertSee('séminaires, colloques et journées scientifiques', false);
});

it('affiche la galerie du dernier colloque sur l’accueil', function () {
    $event = openEdition();
    $event->update(['status' => 'archived', 'date_start' => '2024-03-18', 'date_end' => '2024-03-20']);
    $event->media()->create(['type' => 'photo', 'url' => 'https://example.test/g1.jpg', 'title' => 'Ouverture', 'ordre' => 1]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Galerie - dernier événement', false)
        ->assertSee('galleryData', false)
        ->assertSee('https://example.test/g1.jpg');
});

it('n’affiche pas la galerie sur l’accueil sans média passé', function () {
    openEdition(); // publiée mais non passée, sans média

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('Galerie - dernier événement', false);
});

it('affiche la liste des archives', function () {
    openEdition(); // événement publié mais non passé → liste vide, page OK

    $this->get(route('public.archives'))
        ->assertOk()
        ->assertSee('Événements passés');
});

it('liste un événement passé dans les archives', function () {
    $event = openEdition();
    $event->update(['status' => 'archived', 'date_start' => '2024-03-18', 'date_end' => '2024-03-20', 'location' => 'Kara']);

    $this->get(route('public.archives'))
        ->assertOk()
        ->assertSee('UK 2026')
        ->assertSee('Kara');
});

it('affiche le détail d’un événement avec sa galerie', function () {
    $event = openEdition();
    $event->media()->create(['type' => 'photo', 'url' => 'https://example.test/p1.jpg', 'title' => 'Plénière', 'ordre' => 1]);

    $this->get(route('public.archive', $event->slug))
        ->assertOk()
        ->assertSee('UK 2026')
        ->assertSee('Axes thématiques')      // settings.tracks
        ->assertSee('Galerie')
        ->assertSee('galleryData', false);   // JSON du lightbox
});

it('affiche l’état vide du déroulement sans colloque en cours', function () {
    $this->get(route('public.deroulement'))
        ->assertOk()
        ->assertSee('Aucun événement en cours', false);
});

it('affiche le déroulement d’un colloque en cours', function () {
    $event = openEdition();
    $event->update([
        'title' => 'EventSeries en direct',
        'date_start' => now()->subDay(),
        'date_end' => now()->addDay(),
        'location' => 'Kara',
    ]);
    $atelier = $event->ateliers()->create(['title' => 'Atelier 1', 'ordre' => 1]);
    $event->sessions()->create([
        'atelier_id' => $atelier->id, 'type' => 'atelier', 'label' => 'Session en cours',
        'room' => 'Salle A', 'session_day' => now()->toDateString(),
        'starts_at' => now()->subHour(), 'ends_at' => now()->addHour(),
        'meeting_status' => 'live', 'meeting_provider' => 'jitsi', 'ordre' => 1,
    ]);

    $this->get(route('public.deroulement'))
        ->assertOk()
        ->assertSee('EventSeries en direct')
        ->assertSee('En ce moment', false)
        ->assertSee('Session en cours');
});

it('affiche la landing de vérification d’attestation', function () {
    $this->get(route('attestation.verify.landing'))
        ->assertOk()
        ->assertSee('Vérifier une attestation');
});

/** Une attestation délivrée, pour les redirections de la page de saisie. */
function attestationDelivree(): Attestation
{
    $event = openEdition();
    $p = $event->participants()->create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author',
    ]);

    return (new IssueAttestation)->handle($p, 'participation');
}

it('redirige la saisie d’un code vers l’attestation', function () {
    $att = attestationDelivree();

    $this->get(route('attestation.verify.landing', ['code' => $att->uuid]))
        ->assertRedirect(route('attestation.document', $att->uuid));
});

it('extrait l’UUID d’une URL d’attestation collée', function () {
    $att = attestationDelivree();

    $this->get(route('attestation.verify.landing', ['code' => 'https://gesco.test/attestation/'.$att->uuid]))
        ->assertRedirect(route('attestation.document', $att->uuid));
});

// ⚠️ Il n'y a plus QU'UNE adresse pour une attestation. Deux pages disaient la
// même chose d'une même attestation, et l'une d'elles sans le document.
it('n’a plus d’adresse de vérification séparée', function () {
    $att = attestationDelivree();

    $this->get('/verifier/'.$att->uuid)->assertNotFound();
});

// ⚠️ Un code inconnu n'a plus de page de vérification où échouer : il ne doit pas
// pour autant tomber sur un 404, la faute de frappe se corrige sur le formulaire.
it('garde la saisie sur le formulaire quand le code est inconnu', function () {
    $this->get(route('attestation.verify.landing', ['code' => '11111111-2222-3333-4444-555555555555']))
        ->assertOk()
        ->assertSee('Aucune attestation ne correspond à ce code.')
        ->assertSee('11111111-2222-3333-4444-555555555555');
});

it('renvoie 404 pour un événement inconnu', function () {
    $this->get(route('public.archive', 'edition-inexistante'))->assertNotFound();
});

it('n’expose pas un événement en brouillon', function () {
    $event = openEdition();
    $event->update(['status' => 'draft']);

    $this->get(route('public.archive', $event->slug))->assertNotFound();
});

/**
 * ⚠️ Le compteur de communications d'une édition passée filtrait le STATUT mais
 * pas les resoumissions : une version corrigée acceptée comptait EN PLUS de sa
 * version d'origine, et l'édition s'annonçait plus fournie qu'elle ne l'a été
 * (cf. `Submission::scopeAcceptedCanonical`).
 */
it('ne compte qu’une fois une communication resoumise sur une édition passée', function () {
    $canonique = seededSubmission('accepted');
    $event = $canonique->event;

    // La version corrigée, acceptée elle aussi : c'est la MÊME communication.
    $event->submissions()->create([
        'participant_id' => $canonique->participant_id,
        'ref_code' => '26-001-v2',
        'title' => 'Innovation durable (version corrigée)',
        'abstract' => str_repeat('Contexte. ', 10),
        'track' => 'Sciences', 'type' => 'oral', 'status' => 'accepted',
        'revision_of' => $canonique->id,
        'authors' => [['name' => 'Awa Kodjo', 'email' => 'awa@example.tg', 'corresponding' => true]],
    ]);

    // Et une refusée, qui n'a jamais compté.
    $event->submissions()->create([
        'participant_id' => $canonique->participant_id,
        'ref_code' => '26-002',
        'title' => 'Communication refusée',
        'abstract' => str_repeat('Contexte. ', 10),
        'track' => 'Sciences', 'type' => 'oral', 'status' => 'rejected', 'revision_of' => 0,
        'authors' => [['name' => 'Kossi Mensah', 'email' => 'kossi@example.tg', 'corresponding' => true]],
    ]);

    $this->get(route('public.archive', $event->slug))
        ->assertOk()
        ->assertViewHas('stats', fn (array $stats) => $stats['communications'] === 1);
});
