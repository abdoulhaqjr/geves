<?php

use App\Actions\IssueAttestation;
use App\Documents\AttestationVars;
use App\Documents\LayoutRenderer;
use App\Documents\LayoutSanitizer;
use App\Models\Attestation;
use App\Models\DocumentTemplate;
use App\Support\AttestationPolicy;
use App\Support\EventSettings;

/**
 * Page publique d'une attestation : c'est l'adresse que porte le QR code et que
 * le bénéficiaire partage. Elle doit tenir debout devant un tiers - le document,
 * qui l'a délivrée, ce qu'elle certifie et comment la vérifier.
 */
it('présente l’attestation, son émetteur et ce qu’elle certifie', function () {
    $event = openEdition();
    $event->series->update(['description' => 'Rendez-vous annuel des universités partenaires.']);
    $event->update(['theme' => 'Innover pour un avenir durable']);

    $p = $event->participants()->create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author',
    ]);
    $att = (new IssueAttestation)->handle($p, 'participation');

    $this->get(route('attestation.document', $att->uuid))
        ->assertOk()
        ->assertSee('Awa Kodjo')
        ->assertSee($event->series->name)
        ->assertSee('Rendez-vous annuel des universités partenaires.')
        ->assertSee('Innover pour un avenir durable')
        ->assertSee($att->uuid)
        // Les gestes attendus d'une page d'attestation.
        ->assertSee('Partager l’attestation', false)
        ->assertSee(route('attestation.pdf', $att->uuid))
        ->assertSee('linkedin.com/profile/add', false)
        // ⚠️ La vérification se fait SUR CETTE PAGE : le bouton interroge
        // l'endpoint JSON, il n'emmène plus le visiteur ailleurs.
        ->assertSee(route('attestation.verify.json', $att->uuid))
        ->assertDontSee('href="'.url('/verifier/'.$att->uuid).'"', false);
});

it('vérifie l’attestation sur place, sans quitter la page', function () {
    $event = openEdition();
    $p = $event->participants()->create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author',
    ]);
    $att = (new IssueAttestation)->handle($p, 'participation');

    $this->getJson(route('attestation.verify.json', $att->uuid))
        ->assertOk()
        ->assertJsonPath('found', true)
        ->assertJsonPath('status', 'ok')
        ->assertJsonPath('facts.Bénéficiaire', 'Awa Kodjo');

    $att->update(['revoked' => true]);
    $this->getJson(route('attestation.verify.json', $att->uuid))
        ->assertOk()
        ->assertJsonPath('status', 'revoked');

    $this->getJson(route('attestation.verify.json', 'code-bidon'))
        ->assertNotFound()
        ->assertJsonPath('found', false);
});

it('dit qu’une attestation révoquée ne vaut plus preuve', function () {
    $event = openEdition();
    $p = $event->participants()->create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author',
    ]);
    $att = (new IssueAttestation)->handle($p, 'participation');
    $att->update(['revoked' => true]);

    $this->get(route('attestation.document', $att->uuid))
        ->assertOk()
        ->assertSee('révoquée')
        ->assertSee('ne vaut plus comme preuve');
});

it('n’affiche pas d’intitulé sans valeur', function () {
    $event = openEdition();
    // Ni thème ni lieu : les lignes correspondantes ne doivent pas apparaître
    // vides - un intitulé sans valeur donne l'impression d'un gabarit cassé.
    $event->update(['theme' => null, 'location' => null]);

    $p = $event->participants()->create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author',
    ]);
    $att = (new IssueAttestation)->handle($p, 'participation');

    $this->get(route('attestation.document', $att->uuid))
        ->assertOk()
        ->assertDontSee('<dt>Thème</dt>', false)
        ->assertDontSee('<dt>Lieu</dt>', false);
});

it('remplace [signatory.name] par le signataire réglé pour l’événement', function () {
    $event = openEdition();
    EventSettings::setMany($event, [
        'attestations.signatory' => 'Pr. Padabo KADOUZA',
        'attestations.signatory_role' => 'Président du comité d’organisation',
    ]);

    $p = $event->participants()->create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author',
    ]);
    $att = (new IssueAttestation)->handle($p, 'participation');

    // Le modèle livré porte le jeton ; sans valeur réglée, la ligne sortait vide.
    $tpl = DocumentTemplate::ensureFor((int) $event->id, 'attestation', 'participation');
    $layout = (array) $tpl->layout;
    $layout['elements'][] = ['type' => 'text', 'x' => 10, 'y' => 10, 'w' => 400, 'h' => 30, 'text' => '[signatory.name]'];
    $tpl->forceFill(['layout' => $layout])->save();

    $this->get(route('attestation.document', $att->uuid))
        ->assertOk()
        ->assertSee('Pr. Padabo KADOUZA');

    expect(Attestation::whereKey($att->id)->value('revoked'))->toBeFalsy();
});

it('fait pointer le QR du document vers la page de l’attestation', function () {
    // ⚠️ Le QR menait à la page de vérification seule : on scannait une
    // attestation pour atterrir sur un verdict, sans le document.
    $event = openEdition();
    $p = $event->participants()->create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author',
    ]);
    $att = (new IssueAttestation)->handle($p, 'participation');

    $vars = AttestationVars::for($att->fresh(), route('attestation.document', $att->uuid));

    expect($vars['verify_url'])->toBe(route('attestation.document', $att->uuid));
});

// adminStaff() -> AdminSubmissionWorkflowTest

/** Une attestation de participation, sur un événement neuf. */
function attestationPublique(): Attestation
{
    $event = openEdition();
    $p = $event->participants()->create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa-pub@example.tg', 'role' => 'author',
    ]);

    return (new IssueAttestation)->handle($p, 'participation');
}

/**
 * ⚠️ En délivrance physique, la page publique montre un **spécimen** : mêmes
 * informations, même vérification, mais ni téléchargement ni partage. Sans la
 * marque, deux documents identiques circuleraient, dont un que l'émetteur n'a
 * jamais remis.
 */
it('marque le document SPÉCIMEN et retire les gestes en délivrance papier', function () {
    $attestation = attestationPublique();
    EventSettings::set(
        $attestation->event,
        'attestations.delivery',
        AttestationPolicy::DELIVERY_PAPER,
    );

    $this->get(route('attestation.document', $attestation->uuid))
        ->assertOk()
        ->assertSee('SPÉCIMEN')
        ->assertSee('Vérifier l’attestation', false)
        ->assertSee('secrétariat')
        ->assertDontSee('Partager l’attestation', false)
        ->assertDontSee('Télécharger (PDF)');

    // ⚠️ Et le PDF n'est pas une porte dérobée : il est fermé au public.
    $this->get(route('attestation.pdf', $attestation->uuid))->assertForbidden();

    // ⚠️ Mais le back-office garde l'accès : c'est lui qui imprime les originaux.
    $this->actingAs(adminStaff(), 'web')
        ->get(route('attestation.pdf', $attestation->uuid))
        ->assertOk();
});

/** En délivrance en ligne, rien ne change : partage et téléchargement restent. */
it('garde partage et téléchargement en délivrance en ligne', function () {
    $attestation = attestationPublique();

    $this->get(route('attestation.document', $attestation->uuid))
        ->assertOk()
        ->assertSee('Partager l’attestation', false)
        ->assertSee('Télécharger (PDF)')
        ->assertDontSee('SPÉCIMEN');
});

/**
 * ⚠️ **Le spécimen doit être le document imprimé, au pixel près.** C'est sa mise
 * en page que le bénéficiaire vient regarder : un aperçu qui diffère de
 * l'original ne lui apprend rien, et lui ferait découvrir le vrai document le
 * jour de la remise.
 *
 * Ce cas gèle la raison pour laquelle ils ne peuvent PAS diverger : les deux
 * sortent du **même modèle enregistré**, par le **même moteur**. Le drapeau `pdf`
 * ne change que la façon d'embarquer images et polices (dompdf ne va rien
 * chercher à distance) - jamais une position, une taille ni un contenu.
 */
it('rend le spécimen avec la géométrie exacte du document imprimé', function () {
    $attestation = attestationPublique();

    $template = DocumentTemplate::ensureFor(
        (int) $attestation->event_id, 'attestation', (string) $attestation->type,
    );
    $layout = LayoutSanitizer::sanitize((array) $template->layout, 'attestation', $attestation->type);
    $vars = AttestationVars::for($attestation, route('attestation.document', $attestation->uuid));

    // ⚠️ Positions ENTIÈRES seulement : ce sont celles des éléments de la mise en
    // page (et du cadre du QR). Les coordonnées décimales sont les modules du QR,
    // que dompdf fait dessiner en blocs faute de savoir rendre un SVG - même
    // emplacement, même taille, autre technique de tracé. Les comparer
    // reviendrait à exiger que deux moteurs dessinent un QR de la même façon.
    $geometrie = function (string $html): array {
        preg_match_all('/left:(-?\d+)px;top:(-?\d+)px/', $html, $m, PREG_SET_ORDER);

        return array_map(fn (array $p) => $p[1].'/'.$p[2], $m);
    };

    $ecran = LayoutRenderer::render($layout, $vars, pdf: false);
    $imprime = LayoutRenderer::render($layout, $vars, pdf: true);

    expect($geometrie($ecran))->not->toBeEmpty();
    expect($geometrie($ecran))->toBe($geometrie($imprime));

    // Mêmes dimensions de page, et le même texte : rien n'est retiré ni ajouté au
    // document - la mention SPÉCIMEN se pose PAR-DESSUS, dans la vue.
    $texte = fn (string $html): string => trim(preg_replace('/\s+/', ' ', strip_tags($html)));
    expect($texte($ecran))->toBe($texte($imprime));
    expect($layout['width'])->toBeGreaterThan(0);
});

/**
 * ⚠️ `/verifier` appartient à la **même famille visuelle** que la page d'une
 * attestation (`.ukc-cred`) : c'est la même démarche - « ce document est-il
 * authentique ? » - et l'une mène à l'autre. Elle portait l'en-tête et le pied
 * de page du site, comme une page d'information quelconque, alors qu'on y
 * arrive un diplôme à la main.
 */
it('présente la saisie de code comme la page d’attestation', function () {
    $page = $this->get(route('attestation.verify.landing'))->assertOk();

    $page->assertSee('ukc-cred-top', false)      // barre de marque + pastille d'état
        ->assertSee('ukc-cred-landing', false)
        ->assertSee('ukc-cred-kicker', false);   // surtitre des cartes

    // Et plus le décor des pages d'information.
    $page->assertDontSee('site-header', false)
        ->assertDontSee('page-hero', false);
});

/**
 * ⚠️ Un code inconnu ne mérite pas un 404 : il a été SAISI ici, et la faute de
 * frappe se corrige ici - le code reste donc sous les yeux.
 */
it('garde le code saisi sous les yeux quand il ne correspond à rien', function () {
    $this->get(route('attestation.verify.landing', ['code' => '00000000-0000-0000-0000-000000000000']))
        ->assertOk()
        ->assertSee('Aucune attestation ne correspond')
        ->assertSee('00000000-0000-0000-0000-000000000000', false)
        ->assertSee('ukc-cred-alert', false);
});
