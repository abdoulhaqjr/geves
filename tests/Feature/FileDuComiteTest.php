<?php

use App\Models\Event;
use App\Models\Review;
use App\Models\Submission;
use App\Models\User;

/**
 * La file de travail du comité scientifique : retrouver, parmi des centaines de
 * communications, celles dont les évaluations sont rendues et qui attendent une
 * décision. Filtre « Évaluations » de la liste + indicateurs cliquables.
 */

/** Une communication de l'événement, avec les évaluations décrites. */
function communication(Event $event, string $ref, string $status, array $reviews = []): Submission
{
    $participant = $event->participants()->create([
        'first_name' => 'Auteur', 'last_name' => $ref, 'email' => strtolower($ref).'@example.tg',
    ]);

    $submission = $event->submissions()->create([
        'participant_id' => $participant->id,
        'ref_code' => $ref,
        'title' => 'Communication '.$ref,
        'abstract' => str_repeat('Contexte. ', 10),
        'track' => 'Sciences',
        'type' => 'oral',
        'status' => $status,
        'authors' => [['name' => 'Auteur '.$ref, 'email' => strtolower($ref).'@example.tg', 'corresponding' => true]],
    ]);

    foreach ($reviews as $review) {
        $submission->reviews()->create([
            'reviewer_id' => 0,
            'status' => $review['status'],
            'due_at' => $review['due_at'] ?? null,
        ]);
    }

    return $submission;
}

/** Les références rendues par la liste, pour le filtre demandé. */
function refsFiltrees(User $staff, Event $event, array $params): array
{
    return refsDeLAdresse($staff, route('admin.submissions.index', ['event_id' => $event->id] + $params));
}

/**
 * Les références rendues par une adresse.
 *
 * ⚠️ C'est par ici que passent les cas des indicateurs : ils doivent SUIVRE le
 * lien de la carte, jamais reconstruire ses paramètres. Reconstruits, ils
 * éprouvent le filtre et laissent le lien mentir - un premier sabotage
 * (« En revue » pointant sur un seul statut) est passé au vert à cause de ça.
 */
function refsDeLAdresse(User $staff, string $url): array
{
    $reponse = test()->actingAs($staff, 'web')->get($url)->assertOk();

    return $reponse->viewData('submissions')->pluck('ref_code')->sort()->values()->all();
}

/** La carte d'indicateur demandée, telle que la page la rend. */
function carteKpi(User $staff, Event $event, string $cle): array
{
    $reponse = test()->actingAs($staff, 'web')
        ->get(route('admin.submissions.index', ['event_id' => $event->id]))->assertOk();

    return collect($reponse->viewData('kpis'))->firstWhere('cle', $cle);
}

beforeEach(function () {
    $this->staff = adminStaff();
    $this->event = openEdition();

    // RENDUE : la seule qui attend une décision.
    communication($this->event, 'A-RENDUE', 'under_review', [
        ['status' => 'completed'],
    ]);

    // ⚠️ RENDUE AUSSI : le refus et l'annulation n'occupent plus de place, donc
    // plus personne ne doit rien - c'est le piège du « status != completed ».
    communication($this->event, 'B-RENDUE', 'under_review', [
        ['status' => 'completed'],
        ['status' => 'declined'],
        ['status' => 'cancelled'],
    ]);

    // PARTIELLE : une rendue, une encore attendue.
    communication($this->event, 'C-PARTIELLE', 'under_review', [
        ['status' => 'completed'],
        ['status' => 'accepted'],
    ]);

    // AUCUNE, avec un évaluateur invité EN RETARD.
    communication($this->event, 'D-RETARD', 'under_review', [
        ['status' => 'invited', 'due_at' => now()->subDays(3)],
    ]);

    // AUCUNE, et personne n'a encore été sollicité.
    communication($this->event, 'E-VIERGE', 'submitted');

    // Déjà décidée : ses évaluations sont rendues, mais elle ne doit plus
    // figurer dans la file de travail du comité.
    communication($this->event, 'F-ACCEPTEE', 'accepted', [
        ['status' => 'completed'],
    ]);
});

it('isole les communications dont TOUTES les évaluations sont rendues', function () {
    expect(refsFiltrees($this->staff, $this->event, ['reviews' => 'done']))
        ->toBe(['A-RENDUE', 'B-RENDUE', 'F-ACCEPTEE']);
});

it('⚠️ ne retient pas une communication hors des « rendues » pour une évaluation refusée ou annulée', function () {
    // B-RENDUE porte un refus ET une annulation : un `status != completed`
    // l'aurait classée « il manque quelque chose », alors que plus aucun
    // évaluateur ne lui doit quoi que ce soit.
    $refs = refsFiltrees($this->staff, $this->event, ['reviews' => 'done']);

    expect($refs)->toContain('B-RENDUE');
    expect(refsFiltrees($this->staff, $this->event, ['reviews' => 'partial']))->not->toContain('B-RENDUE');
});

it('sépare les évaluations partielles et celles dont rien n’est rendu', function () {
    expect(refsFiltrees($this->staff, $this->event, ['reviews' => 'partial']))->toBe(['C-PARTIELLE']);
    expect(refsFiltrees($this->staff, $this->event, ['reviews' => 'none']))->toBe(['D-RETARD', 'E-VIERGE']);
});

it('les trois états PARTITIONNENT la liste', function () {
    $toutes = refsFiltrees($this->staff, $this->event, []);
    $partition = array_merge(
        refsFiltrees($this->staff, $this->event, ['reviews' => 'done']),
        refsFiltrees($this->staff, $this->event, ['reviews' => 'partial']),
        refsFiltrees($this->staff, $this->event, ['reviews' => 'none']),
    );
    sort($partition);

    expect($partition)->toBe($toutes);
});

it('ne déclare en retard qu’une évaluation ENCORE DUE et dont l’échéance est passée', function () {
    expect(refsFiltrees($this->staff, $this->event, ['reviews' => 'late']))->toBe(['D-RETARD']);

    // Une évaluation rendue APRÈS son échéance n'est plus en retard : elle est
    // rendue. Et sans échéance, on n'est jamais en retard.
    communication($this->event, 'G-RENDUE-TARD', 'under_review', [
        ['status' => 'completed', 'due_at' => now()->subMonth()],
    ]);
    communication($this->event, 'H-SANS-ECHEANCE', 'under_review', [
        ['status' => 'invited', 'due_at' => null],
    ]);

    expect(refsFiltrees($this->staff, $this->event, ['reviews' => 'late']))->toBe(['D-RETARD']);
});

it('⚠️ l’indicateur « À décider » OUVRE exactement ce qu’il compte', function () {
    $carte = carteKpi($this->staff, $this->event, 'toDecide');

    // A-RENDUE et B-RENDUE : F-ACCEPTEE est décidée, elle n'attend plus rien.
    expect($carte['valeur'])->toBe(2);
    expect(refsDeLAdresse($this->staff, $carte['url']))->toBe(['A-RENDUE', 'B-RENDUE']);
});

it('⚠️ l’indicateur « En revue » OUVRE les DEUX statuts qu’il additionne', function () {
    $carte = carteKpi($this->staff, $this->event, 'underReview');

    // Quatre `under_review` + une `submitted`. Un lien vers le seul
    // `under_review` rendrait moins de lignes que la carte n'en annonce, et un
    // total qui baisse sans explication se lit comme une panne.
    expect($carte['valeur'])->toBe(5);
    expect(refsDeLAdresse($this->staff, $carte['url']))
        ->toHaveCount(5)
        ->toContain('E-VIERGE');
});

it('⚠️ chaque indicateur OUVRE exactement ce qu’il compte', function () {
    foreach (['total', 'toDecide', 'underReview', 'accepted', 'revision', 'rejected'] as $cle) {
        $carte = carteKpi($this->staff, $this->event, $cle);

        expect(refsDeLAdresse($this->staff, $carte['url']))
            ->toHaveCount($carte['valeur'], $carte['label'].' : le lien ne rend pas le chiffre annoncé');
    }
});

it('allume la carte du filtre courant, et elle seule', function () {
    $reponse = $this->actingAs($this->staff, 'web')->get(route('admin.submissions.index', [
        'event_id' => $this->event->id, 'status' => Submission::STATUS_IN_REVIEW, 'reviews' => 'done',
    ]))->assertOk();

    $allumees = collect($reponse->viewData('kpis'))->where('active', true)->pluck('cle')->all();

    // « En revue » ne doit PAS s'allumer sous « À décider », qui la restreint.
    expect($allumees)->toBe(['toDecide']);
});

it('propose le filtre dans la barre et n’applique pas une valeur inconnue', function () {
    $this->actingAs($this->staff, 'web')
        ->get(route('admin.submissions.index', ['event_id' => $this->event->id]))
        ->assertOk()
        ->assertSee('Toutes les évaluations')
        ->assertSee('Toutes rendues');

    // ⚠️ Une valeur fantaisiste ne filtre rien ET ne doit pas faire CROIRE à un
    // filtre actif : sans la liste blanche, « Réinitialiser » s'affiche pour un
    // filtre que la barre ne montre nulle part, et l'écran dit deux choses
    // contraires en même temps.
    $reponse = $this->actingAs($this->staff, 'web')->get(route('admin.submissions.index', [
        'event_id' => $this->event->id, 'reviews' => 'n-importe-quoi',
    ]))->assertOk();

    expect($reponse->viewData('submissions')->total())->toBe(6);
    expect($reponse->viewData('reviews'))->toBe('');
    $reponse->assertDontSee('Réinitialiser');
});

it('⚠️ la barre de filtres TRANSPORTE le statut choisi sur une carte', function () {
    // Le statut ne se choisit plus dans une liste déroulante : ce sont les
    // cartes qui le posent. Mais la barre poste TOUT le formulaire - sans un
    // champ pour le porter, taper une recherche ou changer d'axe reperdrait le
    // filtre qu'on vient de poser, et la liste se réélargirait sous les doigts.
    $html = $this->actingAs($this->staff, 'web')->get(route('admin.submissions.index', [
        'event_id' => $this->event->id, 'status' => Submission::STATUS_IN_REVIEW,
    ]))->assertOk()->getContent();

    $barre = str($html)->after('data-filter="submissions"')->before('</form>')->toString();

    expect($barre)->toMatch('/name="status"[^>]*value="'.Submission::STATUS_IN_REVIEW.'"/');

    // Et le serveur le respecte quand il arrive accompagné d'un autre filtre.
    expect(refsFiltrees($this->staff, $this->event, [
        'status' => Submission::STATUS_IN_REVIEW, 'track' => 'Sciences',
    ]))->toHaveCount(5);
});

it('⚠️ l’export CSV porte le filtre d’évaluation de l’écran', function () {
    $csv = $this->actingAs($this->staff, 'web')
        ->get(route('admin.submissions.export', [
            'event_id' => $this->event->id,
            'status' => Submission::STATUS_IN_REVIEW,
            'reviews' => 'done',
        ]))->assertOk()->streamedContent();

    expect($csv)->toContain('A-RENDUE')->toContain('B-RENDUE')
        ->not->toContain('C-PARTIELLE')
        ->not->toContain('F-ACCEPTEE');
});

it('le filtre d’évaluation se combine aux autres sans les écraser', function () {
    communication($this->event, 'I-AUTRE-AXE', 'under_review', [['status' => 'completed']]);
    Submission::where('ref_code', 'I-AUTRE-AXE')->update(['track' => 'Santé']);

    expect(refsFiltrees($this->staff, $this->event, ['reviews' => 'done', 'track' => 'Santé']))
        ->toBe(['I-AUTRE-AXE']);
});

it('garde la règle d’avancement identique à celle de la colonne affichée', function () {
    // La colonne « Évaluations » lit Review::progressOf ; le filtre lit le scope
    // du modèle. Les deux doivent dire la même chose du même lot, sinon on
    // filtrerait sur autre chose que le « 1/1 » qu'on lit à l'écran.
    foreach (['A-RENDUE', 'B-RENDUE', 'C-PARTIELLE', 'D-RETARD', 'E-VIERGE'] as $ref) {
        $submission = Submission::where('ref_code', $ref)->with('reviews')->firstOrFail();
        $progres = Review::progressOf($submission->reviews);
        $toutesRendues = $progres['total'] > 0 && $progres['done'] === $progres['total'];

        expect(Submission::query()->where('id', $submission->id)->reviewsState('done')->exists())
            ->toBe($toutesRendues, $ref.' : le filtre et la colonne divergent');
    }
});

/** La page rendue sous les filtres demandés. */
function pageFiltree(User $staff, Event $event, array $params)
{
    return test()->actingAs($staff, 'web')
        ->get(route('admin.submissions.index', ['event_id' => $event->id] + $params))->assertOk();
}

it('⚠️ le lien d’une carte EMPORTE les autres filtres', function () {
    communication($this->event, 'J-SANTE', 'accepted', [['status' => 'completed']]);
    Submission::where('ref_code', 'J-SANTE')->update(['track' => 'Santé', 'type' => 'poster']);

    // On regarde l'axe « Santé » et on clique « Acceptées » : le lien ne portait
    // que l'événement et son statut, donc l'axe disparaissait sans un mot - on
    // se retrouvait devant TOUTES les acceptées, en croyant filtrer dans Santé.
    $cartes = collect(pageFiltree($this->staff, $this->event, [
        'track' => 'Santé', 'type' => 'poster', 'search' => 'SANTE',
    ])->viewData('kpis'));

    $url = $cartes->firstWhere('cle', 'accepted')['url'];

    expect($url)->toContain('track=Sant')->toContain('type=poster')->toContain('search=SANTE');
    expect(refsDeLAdresse($this->staff, $url))->toBe(['J-SANTE']);

    // Et « Total » emporte le contexte lui aussi : il remet le statut à zéro,
    // il ne défait pas le travail de qui l'a cliqué.
    expect(refsDeLAdresse($this->staff, $cartes->firstWhere('cle', 'total')['url']))->toBe(['J-SANTE']);
});

it('⚠️ cliquer la carte ALLUMÉE en ressort, en gardant le contexte', function () {
    $cartes = collect(pageFiltree($this->staff, $this->event, [
        'status' => 'accepted', 'track' => 'Sciences',
    ])->viewData('kpis'));

    $carte = $cartes->firstWhere('cle', 'accepted');

    // C'est la SORTIE du filtre : sans elle, on ne quitte « Acceptées » que par
    // « Total », qui emporterait aussi l'axe qu'on regardait.
    expect($carte['active'])->toBeTrue();
    expect($carte['url'])->not->toContain('status=')->toContain('track=Sciences');
    expect(refsDeLAdresse($this->staff, $carte['url']))->toHaveCount(6);
});

it('⚠️ « Déposées » et « En évaluation » restent atteignables sous « En revue »', function () {
    // Prix du retrait de la liste déroulante : les deux statuts se fondent dans
    // « En revue ». Les pastilles les rendent de nouveau joignables, et elles
    // n'existent QUE là - une septième carte permanente ferait une rangée de
    // plus pour un filtre qu'on ne regarde que depuis celle-là.
    expect(pageFiltree($this->staff, $this->event, [])->viewData('sousStatuts'))->toBe([]);

    $pastilles = collect(pageFiltree($this->staff, $this->event, [
        'status' => Submission::STATUS_IN_REVIEW,
    ])->viewData('sousStatuts'));

    expect($pastilles->pluck('label')->all())->toBe(['Toutes', 'Déposées', 'En évaluation']);
    expect($pastilles->firstWhere('valeur', 'submitted')['nombre'])->toBe(1);
    expect($pastilles->firstWhere('valeur', 'under_review')['nombre'])->toBe(4);

    foreach ($pastilles as $pastille) {
        expect(refsDeLAdresse($this->staff, $pastille['url']))
            ->toHaveCount($pastille['nombre'], $pastille['label'].' : le lien ne rend pas le chiffre annoncé');
    }

    // ⚠️ Raffiner « En revue » ne doit pas ÉTEINDRE la rangée : sans cela, on
    // se retrouve dans un filtre que plus aucune carte n'annonce.
    $reponse = pageFiltree($this->staff, $this->event, ['status' => 'submitted']);
    expect(collect($reponse->viewData('kpis'))->where('active', true)->pluck('cle')->all())->toBe(['underReview']);
    expect($reponse->viewData('sousStatuts'))->not->toBe([]);
});

it('⚠️ une pastille de subdivision OUVRE exactement ce qu’elle compte', function () {
    // À l'inverse des cartes, elles se comptent SOUS les filtres courants :
    // leurs liens emportent l'avancement des évaluations et le reste de la
    // barre, donc comptées sur le périmètre entier elles annonceraient 5 là où
    // elles en ouvrent 2 - le travers que la carte « En revue » a déjà payé.
    foreach ([['reviews' => 'done'], ['reviews' => 'none'], ['track' => 'Sciences']] as $filtres) {
        $pastilles = pageFiltree($this->staff, $this->event, [
            'status' => Submission::STATUS_IN_REVIEW,
        ] + $filtres)->viewData('sousStatuts');

        foreach ($pastilles as $pastille) {
            expect(refsDeLAdresse($this->staff, $pastille['url']))->toHaveCount(
                $pastille['nombre'],
                $pastille['label'].' sous '.json_encode($filtres).' : le lien ne rend pas le chiffre annoncé',
            );
        }
    }

    // Et le chiffre a bien BOUGÉ : sous « À décider », il ne reste que les deux
    // communications dont tout est rendu.
    $sous = collect(pageFiltree($this->staff, $this->event, [
        'status' => Submission::STATUS_IN_REVIEW, 'reviews' => 'done',
    ])->viewData('sousStatuts'));

    expect($sous->firstWhere('valeur', Submission::STATUS_IN_REVIEW)['nombre'])->toBe(2);
    expect($sous->firstWhere('valeur', 'submitted')['nombre'])->toBe(0);

    // ⚠️ Le statut courant, lui, ne compte PAS : sinon « Déposées » tomberait à
    // zéro dès qu'on regarde « En évaluation », et la pastille d'à côté
    // deviendrait injoignable.
    $sous = collect(pageFiltree($this->staff, $this->event, ['status' => 'under_review'])->viewData('sousStatuts'));
    expect($sous->firstWhere('valeur', 'submitted')['nombre'])->toBe(1);
});

it('⚠️ « Réinitialiser » vit dans une RÉGION ÉCHANGÉE', function () {
    // Il n'apparaît que selon les filtres, et il vivait hors de toute région :
    // filtrer en AJAX ne le faisait donc PAS apparaître, et on ne pouvait plus
    // sortir du filtre sans vider les champs un par un.
    $html = pageFiltree($this->staff, $this->event, ['search' => 'RENDUE'])->getContent();

    // ⚠️ On exige la RÉGION, pas seulement son contenu : `Str::after` rend la
    // chaîne ENTIÈRE quand l'aiguille manque, donc un cas qui se contente
    // d'extraire passerait au vert sur une région retirée.
    expect($html)->toContain('data-filter-part="submissions-reset"');

    $region = str($html)->after('data-filter-part="submissions-reset"')->before('</span>')->toString();

    expect($region)->toContain('Réinitialiser');

    // Et il disparaît quand il n'y a plus rien à réinitialiser.
    expect(pageFiltree($this->staff, $this->event, [])->viewData('filtreActif'))->toBeFalse();
});

it('⚠️ les cartes vivent dans une RÉGION ÉCHANGÉE, parce que leurs liens suivent les filtres', function () {
    // Les CHIFFRES ne dépendent pas des filtres, mais les LIENS si : hors
    // région, les six cartes resteraient pointées sur l'axe précédent après un
    // filtre en AJAX, et cliquer l'une d'elles y ramènerait.
    $html = pageFiltree($this->staff, $this->event, ['track' => 'Sciences'])->getContent();

    expect($html)->toContain('data-filter-part="submissions-kpis"');

    $region = str($html)->after('data-filter-part="submissions-kpis"')->before('data-filter="submissions"')->toString();

    expect($region)->toContain('ukc-stat-card')->toContain('track=Sciences');
});

it('⚠️ l’export CSV porte TOUS les filtres de l’écran, pas une liste recopiée', function () {
    // Le lien était écrit à la main et il avait oublié « reviews » et
    // « unlinked » : exporter depuis « Toutes rendues » sortait toute la
    // sélection de statut, sans que rien ne le signale.
    $url = pageFiltree($this->staff, $this->event, [
        'status' => Submission::STATUS_IN_REVIEW, 'reviews' => 'done',
        'track' => 'Sciences', 'unlinked' => 1,
    ])->viewData('exportUrl');

    expect($url)->toContain('reviews=done')->toContain('unlinked=1')
        ->toContain('track=Sciences')->toContain('status='.Submission::STATUS_IN_REVIEW);

    // Et le CSV qu'il rend est bien celui de l'écran.
    $csv = $this->actingAs($this->staff, 'web')->get(
        pageFiltree($this->staff, $this->event, [
            'status' => Submission::STATUS_IN_REVIEW, 'reviews' => 'done',
        ])->viewData('exportUrl')
    )->assertOk()->streamedContent();

    expect($csv)->toContain('A-RENDUE')->toContain('B-RENDUE')->not->toContain('C-PARTIELLE');
});
