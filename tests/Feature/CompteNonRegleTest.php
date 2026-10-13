<?php

use App\Models\Event;
use App\Models\Participant;
use App\Models\Payment;
use App\Models\Submission;
use App\Models\SubmissionAuthor;
use App\Models\User;
use App\Support\Fees;
use Database\Seeders\ColloqueAvenirSeeder;

/**
 * **Le cas d'essai du paiement : accepté, mais pas réglé.**
 *
 * Le jeu de démonstration réglait tout le monde sauf un communicant - et celui-là
 * était au grade « Doctorant », avec sa communication déjà programmée. Il manquait
 * le parcours qu'on vient éprouver de bout en bout : une communication acceptée,
 * aucun paiement, et un montant qui se DÉDUIT du grade.
 */
function compteNonRegle(): User
{
    return User::where('email', 'tabdoulhaq@gmail.com')->firstOrFail();
}

/**
 * ⚠️ **Le profil doit être COMPLET**, sinon la plateforme refuse d'engager le
 * paiement (`User::profileComplete()`) et le cas d'essai est bloqué avant même
 * d'atteindre la passerelle. Le téléphone en fait partie : c'est lui qui
 * pré-remplit la page du prestataire pour le mobile money.
 */
it('crée un compte au profil complet, prêt à payer', function () {
    $this->seed(ColloqueAvenirSeeder::class);

    $compte = compteNonRegle();

    expect($compte->missingProfileFields())->toBe([]);
    expect($compte->profileComplete())->toBeTrue();
    expect($compte->salutationName())->toBe('M. Abdoul TOURE');
    expect($compte->phone)->toBe('+22890015284');
    expect($compte->hasVerifiedEmail())->toBeTrue();
});

/**
 * ⚠️ **Le grade est porté DEUX fois, et ce n'est pas une redite** : le profil
 * global (`users.grade`) et l'inscription à l'événement (`participants.title`).
 * C'est le SECOND que lit `Fees::resolveAmount` - une personne peut avoir des
 * grades différents d'un colloque à l'autre, et c'est celui de l'édition qui
 * décide de son tarif.
 */
it('porte le grade « Étudiant en master » sur le profil ET sur l’inscription', function () {
    $this->seed(ColloqueAvenirSeeder::class);

    $compte = compteNonRegle();
    $participant = Participant::where('user_id', $compte->id)->firstOrFail();

    expect($compte->grade)->toBe('Étudiant en master');
    expect($participant->title)->toBe('Étudiant en master');
    expect($participant->role)->toBe('author');
});

/**
 * ⚠️ **Le tarif ne se choisit pas, il se déduit du grade** (`Fees::resolveAmount`).
 * Et il vaut **100 FCFA** : c'est le montant sur lequel on éprouve un vrai
 * règlement mobile money. Les deux autres catégories gardent un tarif réaliste -
 * un barème entièrement à 100 F n'éprouverait plus la grille.
 */
it('résout 100 FCFA pour les doctorants et les étudiants en master', function () {
    $this->seed(ColloqueAvenirSeeder::class);

    $event = Event::where('slug', 'avenir-durable-2025')->firstOrFail();

    expect(Fees::resolveAmount($event, 'Étudiant en master'))->toBe(100.0);
    expect(Fees::resolveAmount($event, 'Doctorant'))->toBe(100.0);

    // Les autres catégories ne sont pas tombées à 100 au passage.
    expect(Fees::resolveAmount($event, 'Professeur'))->toBe(35000.0);
    expect(Fees::resolveAmount($event, 'Autre participant'))->toBe(50000.0);
    expect(Fees::highestAmount($event))->toBe(50000.0);

    // Et c'est bien le tarif de CE participant.
    $participant = Participant::where('user_id', compteNonRegle()->id)->firstOrFail();
    expect(Fees::resolveAmount($event, $participant->title))->toBe(100.0);
});

/**
 * ⚠️ **Acceptée, canonique, et rattachée à son compte.** La ligne
 * `submission_authors` (`status = linked` + `user_id`) est ce que lit « Mes
 * communications » : sans elle, la communication existe en base et n'apparaît
 * nulle part chez son auteur.
 */
it('lui donne une communication acceptée, rattachée à son compte', function () {
    $this->seed(ColloqueAvenirSeeder::class);

    $compte = compteNonRegle();
    $participant = Participant::where('user_id', $compte->id)->firstOrFail();

    $submission = Submission::where('participant_id', $participant->id)->firstOrFail();

    expect($submission->status)->toBe('accepted');
    expect($submission->revision_of)->toBe(0);
    expect($submission->isAcceptedCanonical())->toBeTrue();
    expect($submission->ref_code)->toBe('UK-2025-007');

    $lien = SubmissionAuthor::where('submission_id', $submission->id)->where('role', 'corresponding')->firstOrFail();
    expect($lien->user_id)->toBe($compte->id);
    expect($lien->status)->toBe('linked');
});

/**
 * ⚠️ **AUCUN paiement, et c'est tout l'intérêt du cas.** « A payé » se lit par
 * `participationPayments()` (le paiement de formation ne compte pas) : c'est
 * cette lecture-là qui doit rester vide, sinon badge, attestations et accès se
 * débloqueraient alors que rien n'est réglé.
 */
it('ne lui donne aucun paiement', function () {
    $this->seed(ColloqueAvenirSeeder::class);

    $participant = Participant::where('user_id', compteNonRegle()->id)->firstOrFail();

    expect(Payment::where('participant_id', $participant->id)->count())->toBe(0);
    expect($participant->participationPayments()->where('status', 'paid')->count())->toBe(0);
});

/**
 * La preuve par l'écran : il se connecte, et la page « Inscription & paiement »
 * lui annonce **100** à régler. Les quatre cas précédents décrivent l'état en
 * base ; celui-ci vérifie que la plateforme en tire bien la bonne somme.
 */
it('lui présente 100 FCFA à régler sur sa page de paiement', function () {
    $this->seed(ColloqueAvenirSeeder::class);

    $compte = compteNonRegle();
    $event = Event::where('slug', 'avenir-durable-2025')->firstOrFail();

    $html = (string) $this->actingAs($compte, 'account')
        ->get(route('account.payment', $event))
        ->assertOk()
        ->getContent();

    // Le montant dû, et pas l'ancien tarif.
    expect($html)->toContain('100');
    expect($html)->not->toContain('15 000');
    expect($html)->not->toContain('15000');
});

/**
 * ⚠️ **`users.phone` est un identifiant UNIQUE, et le numéro peut déjà être
 * posé ailleurs.** Il l'était sur le super-administrateur de la base de
 * développement, saisi à la main : le scénario tombait alors sur la contrainte
 * d'unicité, et c'étaient TOUTES les données de démonstration qui ne se
 * semaient pas - pour un numéro de téléphone.
 *
 * Arbitré avec l'équipe : le numéro va au cas d'essai du paiement, qui en a
 * besoin pour que `profileComplete()` soit vrai. L'autre compte le perd, et le
 * seeder le DIT en clair.
 */
it('libère le numéro s’il était posé sur un autre compte', function () {
    $squatteur = User::create([
        'name' => 'Compte qui portait le numéro',
        'email' => 'portait-le-numero@example.org',
        'password' => 'password',
        'phone' => '+22890015284',
    ]);

    $this->seed(ColloqueAvenirSeeder::class);

    // Le scénario s'est semé malgré la collision...
    $compte = compteNonRegle();
    expect($compte->phone)->toBe('+22890015284');

    // ...et l'autre compte existe toujours, simplement sans numéro.
    $squatteur->refresh();
    expect($squatteur->exists)->toBeTrue();
    expect($squatteur->phone)->toBeNull();

    // Un seul compte porte le numéro : c'est toute la raison de la manœuvre.
    expect(User::where('phone', '+22890015284')->count())->toBe(1);
});
