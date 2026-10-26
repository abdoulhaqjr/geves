<?php

use App\Models\Submission;
use App\Models\SubmissionAuthor;
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Support\EventNameFormat;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

/*
|--------------------------------------------------------------------------
| Corriger la liste des auteurs d'une communication
|--------------------------------------------------------------------------
|
| Les auteurs ne sont pas rééditables par le déposant : sa page de correction
| ne reprend que titre, résumé, mots-clés et fichier. L'omission d'un co-auteur
| se répare donc au back-office, et c'est un geste de GESTIONNAIRE.
|
*/

/** Une communication déposée, avec son auteur correspondant et un co-auteur. */
function soumissionAvecAuteurs(): Submission
{
    $submission = seededSubmission();
    $submission->authors = [
        ['name' => 'Awa KODJO', 'first_name' => 'Awa', 'last_name' => 'KODJO', 'email' => 'awa@example.tg',
            'phone' => null, 'affiliation' => 'Université de Kara', 'grade' => 'Doctorante',
            'corresponding' => true, 'is_submitter' => true],
        ['name' => 'Kofi MENSAH', 'first_name' => 'Kofi', 'last_name' => 'MENSAH', 'email' => 'kofi@example.tg',
            'phone' => null, 'affiliation' => 'Université de Lomé', 'grade' => null],
    ];
    $submission->save();

    foreach ($submission->authors as $i => $a) {
        SubmissionAuthor::create([
            'submission_id' => $submission->id,
            'event_id' => $submission->event_id,
            'role' => $i === 0 ? 'corresponding' : 'coauthor',
            'author_index' => $i,
            'name' => $a['name'],
            'email' => $a['email'],
            'match_type' => $i === 0 ? 'self' : null,
            'status' => $i === 0 ? 'linked' : 'unmatched',
            'user_id' => $i === 0 ? $submission->participant?->user_id : null,
            'token' => 'jeton-'.$i,
        ]);
    }

    return $submission->fresh();
}

/** Le gestionnaire de l'événement de cette communication. */
function gestionnaireDe(Submission $submission): User
{
    $user = User::factory()->create(['phone' => null]);
    $submission->event->team()->attach($user->id, ['role' => 'gerant']);

    return $user;
}

/** @return array<int, array<string, mixed>> */
function lignesDepuis(Submission $submission): array
{
    return collect($submission->authors ?? [])->map(fn ($a, $i) => [
        'origine' => $i,
        'first_name' => $a['first_name'] ?? '',
        'last_name' => $a['last_name'] ?? '',
        'email' => $a['email'] ?? '',
        'phone' => $a['phone'] ?? '',
        'affiliation' => $a['affiliation'] ?? '',
        'grade' => $a['grade'] ?? '',
    ])->values()->all();
}

beforeEach(function () {
    Mail::fake();
    Notification::fake();
});

it('ajoute un co-auteur oublié, et le prévient', function () {
    $submission = soumissionAvecAuteurs();
    $gestionnaire = gestionnaireDe($submission);

    $lignes = lignesDepuis($submission);
    $lignes[] = ['origine' => null, 'first_name' => 'Ama', 'last_name' => 'Doumbia', 'email' => 'ama@example.tg',
        'phone' => '', 'affiliation' => 'Université de Parakou', 'grade' => ''];

    $this->actingAs($gestionnaire, 'web')
        ->post(route('admin.submissions.authors.update', $submission), ['auteurs' => $lignes])
        ->assertRedirect();

    $submission->refresh();

    expect($submission->authors)->toHaveCount(3)
        // ⚠️ Nom de famille en CAPITALES, comme au dépôt : une seconde forme se
        // verrait dans le livret, à côté des auteurs saisis le premier jour.
        ->and($submission->authors[2]['last_name'])->toBe('DOUMBIA')
        ->and($submission->authors[2]['name'])->toBe('Ama DOUMBIA')
        ->and($submission->authorLinks()->count())->toBe(3);

    $lien = $submission->authorLinks()->where('author_index', 2)->firstOrFail();
    expect($lien->role)->toBe('coauthor')
        ->and($lien->email)->toBe('ama@example.tg')
        // Personne non inscrite : invitée à créer son compte, comme au dépôt.
        ->and($lien->status)->toBe('invited');
});

it('rattache et prévient un co-auteur qui a DÉJÀ un compte', function () {
    $submission = soumissionAvecAuteurs();
    $gestionnaire = gestionnaireDe($submission);
    $compte = User::factory()->create(['email' => 'deja@example.tg']);

    $lignes = lignesDepuis($submission);
    $lignes[] = ['origine' => null, 'first_name' => 'Deja', 'last_name' => 'Inscrit', 'email' => 'deja@example.tg',
        'phone' => '', 'affiliation' => '', 'grade' => ''];

    $this->actingAs($gestionnaire, 'web')
        ->post(route('admin.submissions.authors.update', $submission), ['auteurs' => $lignes]);

    $lien = $submission->fresh()->authorLinks()->where('author_index', 2)->firstOrFail();

    expect($lien->user_id)->toBe($compte->id)
        ->and($lien->status)->toBe('linked')
        // La communication doit apparaître dans « Mes communications » : il faut
        // donc aussi une fiche participant sur cet événement.
        ->and($submission->event->participants()->where('user_id', $compte->id)->exists())->toBeTrue();

    Notification::assertSentTo($compte, SystemNotification::class);
});

it('corrige une faute de frappe sans rien renvoyer à personne', function () {
    $submission = soumissionAvecAuteurs();
    $gestionnaire = gestionnaireDe($submission);

    $lignes = lignesDepuis($submission);
    $lignes[1]['last_name'] = 'MENSA'; // la coquille signalée par les auteurs

    $this->actingAs($gestionnaire, 'web')
        ->post(route('admin.submissions.authors.update', $submission), ['auteurs' => $lignes]);

    expect($submission->fresh()->authors[1]['last_name'])->toBe('MENSA');

    // ⚠️ Corriger une orthographe n'est pas un évènement pour l'intéressé : on
    // ne lui réexpédie pas l'annonce qu'il est co-auteur.
    Mail::assertNothingSent();
    Notification::assertNothingSent();
});

it('garde `author_index` ALIGNÉ sur le JSON après un retrait', function () {
    // ⚠️⚠️ L'invariant qui tient tout : `EventNameFormat::submissionAuthor()`
    // retombe sur le nom du COMPTE par cet index. Laissé en arrière, il
    // afficherait le nom de quelqu'un d'autre en face d'un auteur - dans le
    // livret comme dans le programme, et sans la moindre erreur.
    $submission = soumissionAvecAuteurs();
    $gestionnaire = gestionnaireDe($submission);

    $lignes = lignesDepuis($submission);
    $lignes[] = ['origine' => null, 'first_name' => 'Ama', 'last_name' => 'Doumbia', 'email' => 'ama@example.tg',
        'phone' => '', 'affiliation' => '', 'grade' => ''];

    $this->actingAs($gestionnaire, 'web')
        ->post(route('admin.submissions.authors.update', $submission), ['auteurs' => $lignes]);

    // Puis on retire le co-auteur du MILIEU : le dernier doit remonter d'un cran.
    $submission->refresh();
    $apres = lignesDepuis($submission);
    unset($apres[1]);

    $this->actingAs($gestionnaire, 'web')
        ->post(route('admin.submissions.authors.update', $submission), ['auteurs' => array_values($apres)]);

    $submission->refresh();

    expect($submission->authors)->toHaveCount(2)
        ->and($submission->authors[1]['last_name'])->toBe('DOUMBIA');

    $liens = $submission->authorLinks()->orderBy('author_index')->get();
    expect($liens)->toHaveCount(2)
        ->and($liens->pluck('author_index')->all())->toBe([0, 1])
        ->and($liens[1]->name)->toBe('Ama DOUMBIA');

    // Et la résolution par index désigne bien le bon auteur.
    expect(EventNameFormat::submissionAuthor($submission->event, $submission, $submission->authors[1], 1))
        ->toContain('DOUMBIA');
});

it('réordonne les co-auteurs sans perdre leur compte', function () {
    $submission = soumissionAvecAuteurs();
    $gestionnaire = gestionnaireDe($submission);
    $compte = User::factory()->create(['email' => 'deja@example.tg']);

    $lignes = lignesDepuis($submission);
    $lignes[] = ['origine' => null, 'first_name' => 'Deja', 'last_name' => 'Inscrit', 'email' => 'deja@example.tg',
        'phone' => '', 'affiliation' => '', 'grade' => ''];
    $this->actingAs($gestionnaire, 'web')
        ->post(route('admin.submissions.authors.update', $submission), ['auteurs' => $lignes]);

    // On permute les deux co-auteurs (places 1 et 2).
    $submission->refresh();
    $apres = lignesDepuis($submission);
    [$apres[1], $apres[2]] = [$apres[2], $apres[1]];

    $this->actingAs($gestionnaire, 'web')
        ->post(route('admin.submissions.authors.update', $submission), ['auteurs' => $apres]);

    $submission->refresh();
    $lien = $submission->authorLinks()->where('user_id', $compte->id)->firstOrFail();

    expect($submission->authors[1]['last_name'])->toBe('INSCRIT')
        // Le lien a SUIVI son auteur, il n'est pas resté sur l'ancienne place.
        ->and($lien->author_index)->toBe(1)
        ->and($submission->authorLinks()->orderBy('author_index')->pluck('author_index')->all())->toBe([0, 1, 2]);
});

it('refuse de retirer ou de reléguer l’auteur correspondant', function () {
    $submission = soumissionAvecAuteurs();
    $gestionnaire = gestionnaireDe($submission);

    // Le co-auteur passé en tête.
    $lignes = lignesDepuis($submission);
    [$lignes[0], $lignes[1]] = [$lignes[1], $lignes[0]];

    $this->actingAs($gestionnaire, 'web')
        ->post(route('admin.submissions.authors.update', $submission), ['auteurs' => $lignes])
        ->assertSessionHasErrors('auteurs.0');

    // Et retiré purement et simplement.
    $sans = lignesDepuis($submission);
    unset($sans[0]);

    $this->actingAs($gestionnaire, 'web')
        ->post(route('admin.submissions.authors.update', $submission), ['auteurs' => array_values($sans)])
        ->assertSessionHasErrors('auteurs.0');

    expect($submission->fresh()->authors[0]['last_name'])->toBe('KODJO');
});

it('refuse qu’un même auteur occupe deux places', function () {
    $submission = soumissionAvecAuteurs();
    $gestionnaire = gestionnaireDe($submission);

    $lignes = lignesDepuis($submission);
    $lignes[] = $lignes[1]; // la même `origine` deux fois

    $this->actingAs($gestionnaire, 'web')
        ->post(route('admin.submissions.authors.update', $submission), ['auteurs' => $lignes])
        ->assertSessionHasErrors('auteurs');
});

it('exige un nom de famille pour chaque auteur', function () {
    $submission = soumissionAvecAuteurs();
    $gestionnaire = gestionnaireDe($submission);

    $lignes = lignesDepuis($submission);
    $lignes[] = ['origine' => null, 'first_name' => 'Sans', 'last_name' => '', 'email' => '',
        'phone' => '', 'affiliation' => '', 'grade' => ''];

    $this->actingAs($gestionnaire, 'web')
        ->post(route('admin.submissions.authors.update', $submission), ['auteurs' => $lignes])
        ->assertSessionHasErrors('auteurs.2.last_name');
});

it('propose un découpage pour les auteurs qui n’ont QUE `name`', function () {
    // ⚠️ Imports et anciens dépôts n'ont jamais écrit prénom/nom : sans
    // proposition, le formulaire s'ouvrirait avec des champs obligatoires vides
    // et il faudrait retaper chaque nom pour ajouter un seul co-auteur.
    expect(EventNameFormat::decouper(['name' => 'Awa KODJO']))->toBe(['Awa', 'KODJO'])
        ->and(EventNameFormat::decouper(['name' => 'KODJO Awa']))->toBe(['Awa', 'KODJO'])
        ->and(EventNameFormat::decouper(['name' => 'Pauline Invitée']))->toBe(['Pauline', 'Invitée'])
        ->and(EventNameFormat::decouper(['name' => 'Jean Marc VAN DEN BERG']))->toBe(['Jean Marc', 'VAN DEN BERG'])
        ->and(EventNameFormat::decouper(['name' => 'Cher']))->toBe(['', 'Cher'])
        // Des champs déjà renseignés font foi : on ne devine pas par-dessus.
        ->and(EventNameFormat::decouper(['name' => 'X Y', 'first_name' => 'Awa', 'last_name' => 'KODJO']))->toBe(['Awa', 'KODJO']);
});

it('ne réécrit PAS le nom d’un auteur que personne n’a touché', function () {
    // ⚠️⚠️ Le découpage n'est qu'une proposition. Sans cette garde, ouvrir le
    // panneau et enregistrer suffirait à passer en capitales - et parfois à
    // réordonner - le nom de TOUS les auteurs, alors qu'on venait en ajouter un.
    $submission = seededSubmission();
    $submission->authors = [
        ['name' => 'Awa KODJO', 'email' => 'awa@example.tg', 'corresponding' => true, 'is_submitter' => true],
        ['name' => 'Pauline Invitée', 'email' => 'pauline@example.tg'],
    ];
    $submission->save();
    SubmissionAuthor::create([
        'submission_id' => $submission->id, 'event_id' => $submission->event_id,
        'role' => 'corresponding', 'author_index' => 0, 'name' => 'Awa KODJO',
        'match_type' => 'self', 'status' => 'linked', 'token' => 'j0',
    ]);

    $gestionnaire = gestionnaireDe($submission);
    $lignes = [
        ['origine' => 0, 'first_name' => 'Awa', 'last_name' => 'KODJO', 'email' => 'awa@example.tg',
            'phone' => '', 'affiliation' => '', 'grade' => ''],
        ['origine' => 1, 'first_name' => 'Pauline', 'last_name' => 'Invitée', 'email' => 'pauline@example.tg',
            'phone' => '', 'affiliation' => '', 'grade' => ''],
    ];

    $this->actingAs($gestionnaire, 'web')
        ->post(route('admin.submissions.authors.update', $submission), ['auteurs' => $lignes]);

    // Intact : la casse d'origine est conservée, telle que l'auteur l'a écrite.
    expect($submission->fresh()->authors[1]['name'])->toBe('Pauline Invitée');

    // Mais une VRAIE correction s'applique, et suit la convention du dépôt.
    $lignes[1]['last_name'] = 'Doumbia';
    $this->actingAs($gestionnaire, 'web')
        ->post(route('admin.submissions.authors.update', $submission), ['auteurs' => $lignes]);

    expect($submission->fresh()->authors[1]['name'])->toBe('Pauline DOUMBIA');
});

it('garde la trace de qui a corrigé la liste', function () {
    // Une liste d'auteurs sort dans le livret et dans les attestations : une
    // correction sans trace est une correction que personne ne peut expliquer.
    $submission = soumissionAvecAuteurs();
    $gestionnaire = gestionnaireDe($submission);

    $lignes = lignesDepuis($submission);
    $lignes[] = ['origine' => null, 'first_name' => 'Ama', 'last_name' => 'Doumbia', 'email' => '',
        'phone' => '', 'affiliation' => '', 'grade' => ''];

    $this->actingAs($gestionnaire, 'web')
        ->post(route('admin.submissions.authors.update', $submission), ['auteurs' => $lignes]);

    $journal = $submission->fresh()->meta['authors_log'] ?? [];

    expect($journal)->toHaveCount(1)
        ->and($journal[0]['by'])->toBe($gestionnaire->id)
        ->and($journal[0]['ajoutes'])->toBe(1)
        ->and($journal[0]['retires'])->toBe(0);
});

describe('qui a le droit', function () {
    // ⚠️ UNE seule communication par cas : `seededSubmission()` fabrique son
    // déposant avec un téléphone FIXE, et `users.phone` est unique - deux
    // appels dans la même boucle tombent sur la contrainte.
    it('ouvre la correction au gestionnaire et au co-gestionnaire', function () {
        $submission = soumissionAvecAuteurs();

        foreach (['gerant', 'co_gerant'] as $role) {
            $user = User::factory()->create(['phone' => null]);
            $submission->event->team()->attach($user->id, ['role' => $role]);

            $this->actingAs($user, 'web')
                ->post(route('admin.submissions.authors.update', $submission), ['auteurs' => lignesDepuis($submission->fresh())])
                ->assertRedirect();
        }
    });

    it('la REFUSE à l’évaluateur et au secrétariat', function () {
        // ⚠️⚠️ Ils portent `submissions_list`, qui ouvre la LISTE des
        // soumissions - pas le droit de réécrire la paternité d'un travail
        // scientifique. C'est `editions_write` qui garde cette porte.
        $submission = soumissionAvecAuteurs();

        foreach (['reviewer', 'secretariat'] as $role) {
            $user = User::factory()->create(['phone' => null]);
            $submission->event->team()->attach($user->id, ['role' => $role]);

            $this->actingAs($user, 'web')
                ->post(route('admin.submissions.authors.update', $submission), ['auteurs' => lignesDepuis($submission)])
                ->assertForbidden();
        }
    });

    it('ferme aussi le rattachement manuel d’un auteur à un compte', function () {
        // Il vivait sous `submissions_list` : un évaluateur pouvait rattacher
        // un auteur au compte de son choix.
        $submission = soumissionAvecAuteurs();
        $user = User::factory()->create(['phone' => null]);
        $submission->event->team()->attach($user->id, ['role' => 'reviewer']);
        $lien = $submission->authorLinks()->where('author_index', 1)->firstOrFail();

        $this->actingAs($user, 'web')
            ->post(route('admin.submissions.author.link', [$submission, $lien]), ['email' => 'x@example.tg'])
            ->assertForbidden();
    });

    it('ne montre le bouton qu’à qui peut s’en servir', function () {
        $submission = soumissionAvecAuteurs();

        $lecteur = User::factory()->create(['phone' => null]);
        $submission->event->team()->attach($lecteur->id, ['role' => 'reviewer']);
        $this->actingAs($lecteur, 'web')->get(route('admin.submissions.show', $submission))
            ->assertOk()->assertDontSee('Corriger les auteurs');

        $gestionnaire = gestionnaireDe($submission);
        $this->actingAs($gestionnaire, 'web')->get(route('admin.submissions.show', $submission))
            ->assertOk()->assertSee('Corriger les auteurs');
    });
});
