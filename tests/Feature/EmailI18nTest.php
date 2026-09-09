<?php

use App\Mail\SubmissionStatusMail;
use App\Models\Submission;

// openEdition() / verifiedAccount() : helpers partagés (tests/Pest.php).

function acceptedSubmissionFor(string $locale): Submission
{
    $edition = openEdition();
    $account = verifiedAccount();
    $account->update(['locale' => $locale]);
    $participant = $edition->participants()->create([
        'account_id' => $account->id, 'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $account->email,
    ]);

    return $edition->submissions()->create([
        'participant_id' => $participant->id, 'ref_code' => '26-1', 'title' => 'Ma communication',
        'abstract' => 'a', 'track' => 'Sciences', 'type' => 'oral', 'status' => 'accepted',
        'authors' => [['name' => 'Awa Kodjo', 'email' => $account->email, 'corresponding' => true]],
    ]);
}

it('rend l’e-mail de statut en anglais selon la préférence du compte', function () {
    $mail = new SubmissionStatusMail(acceptedSubmissionFor('en'), 'accepted');

    $mail->assertHasSubject('Your paper is accepted');
    $mail->assertSeeInHtml('Paper accepted');        // titre par défaut traduit
    $mail->assertSeeInHtml('View my papers');        // bouton traduit
});

it('rend l’e-mail de statut en français par défaut', function () {
    $mail = new SubmissionStatusMail(acceptedSubmissionFor('fr'), 'accepted');

    $mail->assertHasSubject('Votre communication est acceptée');
    $mail->assertSeeInHtml('Voir mes communications');
});
