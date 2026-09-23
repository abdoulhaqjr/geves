<?php

use App\Mail\AuthorInviteMail;
use App\Mail\AuthorLinkedMail;
use App\Models\Participant;
use App\Models\Submission;
use App\Models\SubmissionAuthor;
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Support\AuthorMatcher;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

// Fabriques partagées : openEdition(), verifiedAccount() (tests/Pest.php).

function submitWithCoauthor(User $submitter, $edition, array $coauthor): void
{
    // Nom + e-mail + affiliation sont obligatoires pour un co-auteur : on complète
    // les champs manquants par défaut pour les scénarios qui ne les précisent pas.
    $coauthor = array_merge(['affiliation' => 'Université de Kara'], $coauthor);

    test()->actingAs($submitter, 'account')
        ->post(route('account.submissions.store', $edition->slug), [
            'title' => 'Étude liée',
            'track' => 'Sciences',
            'abstract' => str_repeat('a', 60),
            'coauthors' => [$coauthor],
        ])
        ->assertSessionHasNoErrors();
}

it('lie un co-auteur à son compte existant (e-mail) et le notifie', function () {
    Mail::fake();
    Notification::fake();
    $edition = openEdition();
    $submitter = verifiedAccount();
    $coauthor = User::create([
        'name' => 'Yao Doe', 'first_name' => 'Yao', 'last_name' => 'Doe',
        'email' => 'yao@uk.tg', 'password' => 'pass12345', 'affiliation' => 'Université de Kara',
    ]);

    submitWithCoauthor($submitter, $edition, ['name' => 'Yao Doe', 'email' => 'yao@uk.tg', 'affiliation' => 'Université de Kara', 'grade' => 'Dr']);

    $sub = Submission::first();
    $link = SubmissionAuthor::where('submission_id', $sub->id)->where('role', 'coauthor')->first();
    expect($link->user_id)->toBe($coauthor->id);
    expect($link->status)->toBe('linked');
    expect($link->match_type)->toBe('email');

    // Participant créé pour le co-auteur + communication visible dans son espace.
    expect(Participant::where('edition_id', $edition->id)->where('user_id', $coauthor->id)->exists())->toBeTrue();
    $mine = Submission::whereHas('authorLinks', fn ($l) => $l->where('user_id', $coauthor->id)->where('status', 'linked'))->count();
    expect($mine)->toBe(1);

    Mail::assertQueued(AuthorLinkedMail::class);
    Notification::assertSentTo($coauthor, SystemNotification::class);
});

it('rapproche par nom + affiliation (AuthorMatcher, homonymes départagés)', function () {
    // L'e-mail d'un co-auteur est désormais obligatoire dans le formulaire ; le
    // rapprochement par nom reste utilisé (auto-liaison à l'inscription, admin).
    $coauthor = User::create([
        'name' => 'Ama Koffi', 'first_name' => 'Ama', 'last_name' => 'Koffi',
        'email' => 'ama@uk.tg', 'password' => 'pass12345', 'affiliation' => 'Université de Lomé',
    ]);

    $match = app(AuthorMatcher::class)->match(['name' => 'Koffi Ama', 'affiliation' => 'Université de Lomé']);

    expect($match['user']?->id)->toBe($coauthor->id);
    expect($match['type'])->toBe('name');
});

it('invite un co-auteur non inscrit', function () {
    Mail::fake();
    $edition = openEdition();
    $submitter = verifiedAccount();

    submitWithCoauthor($submitter, $edition, ['name' => 'Inconnu X', 'email' => 'inconnu@x.tg']);

    $link = SubmissionAuthor::where('email', 'inconnu@x.tg')->first();
    expect($link->status)->toBe('invited');
    Mail::assertQueued(AuthorInviteMail::class);
});

it('rattache automatiquement les communications en attente à l’inscription', function () {
    Mail::fake();
    $edition = openEdition();
    $submitter = verifiedAccount();
    submitWithCoauthor($submitter, $edition, ['name' => 'Futur Membre', 'email' => 'futur@x.tg', 'affiliation' => 'Université de Kara']);

    $new = User::create([
        'name' => 'Futur Membre', 'first_name' => 'Futur', 'last_name' => 'Membre',
        'email' => 'futur@x.tg', 'password' => 'pass12345', 'affiliation' => 'Université de Kara',
    ]);
    event(new Registered($new));

    $link = SubmissionAuthor::where('email', 'futur@x.tg')->first();
    expect($link->status)->toBe('linked');
    expect($link->user_id)->toBe($new->id);
    expect(Participant::where('edition_id', $edition->id)->where('user_id', $new->id)->exists())->toBeTrue();
});

it('permet de décliner une association', function () {
    Mail::fake();
    $edition = openEdition();
    $submitter = verifiedAccount();
    $coauthor = User::create([
        'name' => 'Yao Doe', 'first_name' => 'Yao', 'last_name' => 'Doe',
        'email' => 'yao@uk.tg', 'password' => 'pass12345', 'affiliation' => 'UK',
    ]);
    submitWithCoauthor($submitter, $edition, ['name' => 'Yao Doe', 'email' => 'yao@uk.tg']);

    $link = SubmissionAuthor::where('user_id', $coauthor->id)->first();
    $this->post(route('author.decline.confirm', ['token' => $link->token]))->assertRedirect();

    expect($link->fresh()->status)->toBe('declined');
    $mine = Submission::whereHas('authorLinks', fn ($l) => $l->where('user_id', $coauthor->id)->where('status', 'linked'))->count();
    expect($mine)->toBe(0);
});

it('affiche le statut de rattachement des auteurs côté back-office', function () {
    Mail::fake();
    $edition = openEdition();
    $submitter = verifiedAccount();
    $coauthor = User::create([
        'name' => 'Yao Doe', 'first_name' => 'Yao', 'last_name' => 'Doe',
        'email' => 'yao@uk.tg', 'password' => 'pass12345', 'affiliation' => 'UK',
    ]);
    submitWithCoauthor($submitter, $edition, ['name' => 'Yao Doe', 'email' => 'yao@uk.tg']);
    $sub = Submission::first();

    $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.submissions.show', $sub))
        ->assertOk()
        ->assertSee('Compte rattaché')
        ->assertSee('Yao Doe');
});

it('permet au staff de rattacher manuellement un auteur à un compte', function () {
    Mail::fake();
    $edition = openEdition();
    $submitter = verifiedAccount();
    submitWithCoauthor($submitter, $edition, ['name' => 'Kossi Alpha', 'email' => 'kossi@x.tg']);
    $sub = Submission::first();
    $link = SubmissionAuthor::where('submission_id', $sub->id)->where('role', 'coauthor')->first();
    expect($link->status)->toBe('invited');

    // Le vrai compte existe sous un autre e-mail.
    $user = User::create([
        'name' => 'Kossi Alpha', 'first_name' => 'Kossi', 'last_name' => 'Alpha',
        'email' => 'kossi.alpha@uk.tg', 'password' => 'pass12345',
    ]);

    $this->actingAs(adminStaff(), 'web')
        ->post(route('admin.submissions.author.link', [$sub, $link]), ['email' => 'kossi.alpha@uk.tg'])
        ->assertRedirect();

    $link->refresh();
    expect($link->user_id)->toBe($user->id);
    expect($link->status)->toBe('linked');
    expect($link->match_type)->toBe('manual');
    expect(Participant::where('edition_id', $edition->id)->where('user_id', $user->id)->exists())->toBeTrue();
});

it('permet au staff de détacher un compte mal rapproché', function () {
    Mail::fake();
    $edition = openEdition();
    $submitter = verifiedAccount();
    $coauthor = User::create([
        'name' => 'Yao Doe', 'first_name' => 'Yao', 'last_name' => 'Doe',
        'email' => 'yao@uk.tg', 'password' => 'pass12345',
    ]);
    submitWithCoauthor($submitter, $edition, ['name' => 'Yao Doe', 'email' => 'yao@uk.tg']);
    $sub = Submission::first();
    $link = SubmissionAuthor::where('user_id', $coauthor->id)->first();

    $this->actingAs(adminStaff(), 'web')
        ->post(route('admin.submissions.author.detach', [$sub, $link]))
        ->assertRedirect();

    $link->refresh();
    expect($link->user_id)->toBeNull();
    expect($link->status)->toBe('unmatched');
});

it('filtre les soumissions ayant des co-auteurs non rattachés', function () {
    Mail::fake();
    $edition = openEdition();
    $submitter = verifiedAccount();

    submitWithCoauthor($submitter, $edition, ['name' => 'Inconnu Z', 'email' => 'inconnu@z.tg']); // -> invited (non rattaché)
    $subA = Submission::orderBy('id')->first();

    User::create(['name' => 'Lie Ok', 'first_name' => 'Lie', 'last_name' => 'Ok', 'email' => 'lie@uk.tg', 'password' => 'pass12345']);
    submitWithCoauthor($submitter, $edition, ['name' => 'Lie Ok', 'email' => 'lie@uk.tg']); // -> linked
    $subB = Submission::orderBy('id', 'desc')->first();

    $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.submissions.index', ['unlinked' => 1]))
        ->assertOk()
        ->assertSee($subA->ref_code)
        ->assertDontSee($subB->ref_code);
});

it('un co-auteur voit la fiche mais ne peut pas la modifier', function () {
    Mail::fake();
    $edition = openEdition();
    $submitter = verifiedAccount();
    $coauthor = User::create([
        'name' => 'Yao Doe', 'first_name' => 'Yao', 'last_name' => 'Doe',
        'email' => 'yao@uk.tg', 'password' => 'pass12345', 'affiliation' => 'UK',
    ]);
    $coauthor->markEmailAsVerified();
    submitWithCoauthor($submitter, $edition, ['name' => 'Yao Doe', 'email' => 'yao@uk.tg']);
    $sub = Submission::first();

    $this->actingAs($coauthor, 'account')->get(route('account.submissions.show', $sub))->assertOk();
    $this->actingAs($coauthor, 'account')
        ->post(route('account.submissions.update', $sub), ['title' => 'Pirate', 'abstract' => str_repeat('x ', 20)])
        ->assertNotFound();
});
