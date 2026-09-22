<?php

use App\Mail\AuthorInviteMail;
use App\Mail\AuthorLinkedMail;
use App\Models\Participant;
use App\Models\Submission;
use App\Models\SubmissionAuthor;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

// Fabriques partagées : openEdition(), verifiedAccount() (tests/Pest.php).

function submitWithCoauthor(User $submitter, $edition, array $coauthor): void
{
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

it('lie un co-auteur par nom + affiliation (rapprochement faible)', function () {
    Mail::fake();
    $edition = openEdition();
    $submitter = verifiedAccount();
    $coauthor = User::create([
        'name' => 'Ama Koffi', 'first_name' => 'Ama', 'last_name' => 'Koffi',
        'email' => 'ama@uk.tg', 'password' => 'pass12345', 'affiliation' => 'Université de Lomé',
    ]);

    // Pas d'e-mail saisi : rapprochement par nom, départagé par l'affiliation.
    submitWithCoauthor($submitter, $edition, ['name' => 'Koffi Ama', 'affiliation' => 'Université de Lomé']);

    $link = SubmissionAuthor::where('role', 'coauthor')->first();
    expect($link->user_id)->toBe($coauthor->id);
    expect($link->match_type)->toBe('name');
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
