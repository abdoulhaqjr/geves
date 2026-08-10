<?php

use App\Livewire\Account\Dashboard;
use App\Livewire\Admin\Editions\Documents as AdminDocuments;
use App\Models\Account;
use App\Models\Media;
use App\Models\Participant;
use Illuminate\Support\Str;
use Livewire\Livewire;

// adminStaff() -> AdminSubmissionWorkflowTest ; openEdition()/verifiedAccount() -> AccountSubmissionTest

/** Rattache le compte à l'édition en tant que participant. */
function participantFor(Account $account, $edition, string $role = 'author'): Participant
{
    return $edition->participants()->create([
        'account_id' => $account->id,
        'first_name' => $account->first_name,
        'last_name' => $account->last_name,
        'email' => $account->email,
        'role' => $role,
    ]);
}

it('permet à l’admin d’ajouter et retirer un document d’édition', function () {
    $staff = adminStaff();
    $edition = openEdition();

    $component = Livewire::actingAs($staff, 'web')
        ->test(AdminDocuments::class, ['edition' => $edition])
        ->set('type', 'document')
        ->set('title', 'Programme définitif')
        ->set('url', 'https://example.tg/programme.pdf')
        ->call('add')
        ->assertHasNoErrors();

    $media = Media::where('edition_id', $edition->id)->first();
    expect($media)->not->toBeNull();
    expect($media->title)->toBe('Programme définitif');

    $component->call('remove', $media->id);
    expect(Media::find($media->id))->toBeNull();
});

it('valide l’URL du document', function () {
    $staff = adminStaff();
    $edition = openEdition();

    Livewire::actingAs($staff, 'web')
        ->test(AdminDocuments::class, ['edition' => $edition])
        ->set('title', 'X')
        ->set('url', 'pas-une-url')
        ->call('add')
        ->assertHasErrors('url');
});

it('expose les documents de l’édition sur le tableau de bord participant', function () {
    $edition = openEdition();
    $account = verifiedAccount();
    $participant = participantFor($account, $edition);

    // Communication acceptée → livret disponible.
    $edition->submissions()->create(['participant_id' => $participant->id, 'title' => 'T', 'status' => 'accepted', 'revision_of' => 0, 'ref_code' => 'AA-001', 'access_token' => Str::random(64)]);
    Media::create(['edition_id' => $edition->id, 'type' => 'document', 'title' => 'Programme', 'url' => 'https://example.tg/prog.pdf', 'ordre' => 1]);

    Livewire::actingAs($account, 'account')
        ->test(Dashboard::class)
        ->assertSee('Documents de mes éditions')
        ->assertSee('Programme')
        ->assertSee('Livret des résumés');
});

it('ne montre pas le livret sans communication acceptée', function () {
    $edition = openEdition();
    $account = verifiedAccount();
    participantFor($account, $edition);
    Media::create(['edition_id' => $edition->id, 'type' => 'document', 'title' => 'Programme', 'url' => 'https://example.tg/prog.pdf', 'ordre' => 1]);

    Livewire::actingAs($account, 'account')
        ->test(Dashboard::class)
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
    $this->get(route('admin.editions.documents', $edition))->assertRedirect(route('admin.login'));
});
