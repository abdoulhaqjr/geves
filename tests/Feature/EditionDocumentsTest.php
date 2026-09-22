<?php

use App\Models\Media;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Support\Str;

// adminStaff() -> AdminSubmissionWorkflowTest ; openEdition()/verifiedAccount() -> AccountSubmissionTest

/** Rattache le compte à l'édition en tant que participant. */
function participantFor(User $account, $edition, string $role = 'author'): Participant
{
    return $edition->participants()->create([
        'user_id' => $account->id,
        'first_name' => $account->first_name,
        'last_name' => $account->last_name,
        'email' => $account->email,
        'role' => $role,
    ]);
}

it('permet à l’admin d’ajouter et retirer un document d’édition', function () {
    $staff = adminStaff();
    $edition = openEdition();

    $this->actingAs($staff, 'web')
        ->post(route('admin.editions.documents.store', $edition), [
            'type' => 'document',
            'title' => 'Programme définitif',
            'url' => 'https://example.tg/programme.pdf',
        ])
        ->assertSessionHasNoErrors();

    $media = Media::where('edition_id', $edition->id)->first();
    expect($media)->not->toBeNull();
    expect($media->title)->toBe('Programme définitif');

    $this->actingAs($staff, 'web')
        ->delete(route('admin.editions.documents.destroy', [$edition, $media]));
    expect(Media::find($media->id))->toBeNull();
});

it('valide l’URL du document', function () {
    $staff = adminStaff();
    $edition = openEdition();

    $this->actingAs($staff, 'web')
        ->post(route('admin.editions.documents.store', $edition), [
            'type' => 'document',
            'title' => 'X',
            'url' => 'pas-une-url',
        ])
        ->assertSessionHasErrors('url');
});

it('expose les documents de l’édition sur le tableau de bord participant', function () {
    $edition = openEdition();
    $account = verifiedAccount();
    $participant = participantFor($account, $edition);

    // Communication acceptée → livret disponible.
    $edition->submissions()->create(['participant_id' => $participant->id, 'title' => 'T', 'status' => 'accepted', 'revision_of' => 0, 'ref_code' => 'AA-001', 'access_token' => Str::random(64)]);
    Media::create(['edition_id' => $edition->id, 'type' => 'document', 'title' => 'Programme', 'url' => 'https://example.tg/prog.pdf', 'ordre' => 1]);

    $this->actingAs($account, 'account')
        ->get(route('account.dashboard'))
        ->assertOk()
        ->assertSee('Documents de mes éditions')
        ->assertSee('Programme')
        ->assertSee('Livret des résumés');
});

it('ne montre pas le livret sans communication acceptée', function () {
    $edition = openEdition();
    $account = verifiedAccount();
    participantFor($account, $edition);
    Media::create(['edition_id' => $edition->id, 'type' => 'document', 'title' => 'Programme', 'url' => 'https://example.tg/prog.pdf', 'ordre' => 1]);

    $this->actingAs($account, 'account')
        ->get(route('account.dashboard'))
        ->assertOk()
        ->assertSee('Programme')
        ->assertDontSee('Livret des résumés');
});

it('donne accès au livret d’une édition à laquelle le participant a participé', function () {
    $edition = openEdition();
    $account = verifiedAccount();
    $participant = participantFor($account, $edition);
    $edition->submissions()->create(['participant_id' => $participant->id, 'title' => 'Ma communication', 'status' => 'accepted', 'revision_of' => 0, 'ref_code' => 'AA-001', 'access_token' => Str::random(64)]);

    $this->actingAs($account, 'account')
        ->get(route('account.documents.livret', $edition))
        ->assertOk()
        ->assertSee('Ma communication');
});

it('refuse le livret à un compte non participant', function () {
    $edition = openEdition();
    $account = verifiedAccount();
    // Aucun participant rattaché à ce compte pour l'édition.

    $this->actingAs($account, 'account')
        ->get(route('account.documents.livret', $edition))
        ->assertForbidden();
});

it('refuse la gestion des documents aux invités', function () {
    $edition = openEdition();
    $this->get(route('admin.editions.documents', $edition))->assertRedirect(route('account.login'));
});
