<?php

use App\Documents\LivretView;
use App\Models\Media;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Support\Str;

// adminStaff() -> AdminSubmissionWorkflowTest ; openEdition()/verifiedAccount() -> AccountSubmissionTest

/** Rattache le compte à l'événement en tant que participant. */
function participantFor(User $account, $event, string $role = 'author'): Participant
{
    return $event->participants()->create([
        'user_id' => $account->id,
        'first_name' => $account->first_name,
        'last_name' => $account->last_name,
        'email' => $account->email,
        'role' => $role,
    ]);
}

it('permet à l’admin d’ajouter et retirer un document d’événement', function () {
    $staff = adminStaff();
    $event = openEdition();

    $this->actingAs($staff, 'web')
        ->post(route('admin.evenements.documents.store', $event), [
            'type' => 'document',
            'title' => 'Programme définitif',
            'url' => 'https://example.tg/programme.pdf',
        ])
        ->assertSessionHasNoErrors();

    $media = Media::where('event_id', $event->id)->first();
    expect($media)->not->toBeNull();
    expect($media->title)->toBe('Programme définitif');

    $this->actingAs($staff, 'web')
        ->delete(route('admin.evenements.documents.destroy', [$event, $media]));
    expect(Media::find($media->id))->toBeNull();
});

it('valide l’URL du document', function () {
    $staff = adminStaff();
    $event = openEdition();

    $this->actingAs($staff, 'web')
        ->post(route('admin.evenements.documents.store', $event), [
            'type' => 'document',
            'title' => 'X',
            'url' => 'pas-une-url',
        ])
        ->assertSessionHasErrors('url');
});

it('expose les documents de l’événement sur le tableau de bord participant', function () {
    $event = openEdition();
    $account = verifiedAccount();
    $participant = participantFor($account, $event);

    // Communication acceptée → livret disponible.
    $event->submissions()->create(['participant_id' => $participant->id, 'title' => 'T', 'status' => 'accepted', 'revision_of' => 0, 'ref_code' => 'AA-001', 'access_token' => Str::random(64)]);
    Media::create(['event_id' => $event->id, 'type' => 'document', 'title' => 'Programme', 'url' => 'https://example.tg/prog.pdf', 'ordre' => 1]);

    $this->actingAs($account, 'account')
        ->get(route('account.dashboard'))
        ->assertOk()
        // ⚠️ « Documents de mes événements » est devenu « Documents du colloque » :
        // ce bloc listait les médias de TOUS les événements du compte alors que
        // tout le reste du tableau de bord se scope sur le colloque sélectionné -
        // il était le seul à ignorer le sélecteur. Le titre dit désormais ce que
        // le bloc montre.
        ->assertSee('Documents du colloque')
        ->assertSee('Programme')
        ->assertSee('Livret des résumés');
});

it('ne montre pas le livret sans communication acceptée', function () {
    $event = openEdition();
    $account = verifiedAccount();
    participantFor($account, $event);
    Media::create(['event_id' => $event->id, 'type' => 'document', 'title' => 'Programme', 'url' => 'https://example.tg/prog.pdf', 'ordre' => 1]);

    $this->actingAs($account, 'account')
        ->get(route('account.dashboard'))
        ->assertOk()
        ->assertSee('Programme')
        ->assertDontSee('Livret des résumés');
});

/**
 * ⚠️⚠️ **LE LIVRE QU'ON LAISSE AU PARTICIPANT EST LE PDF.** Il recevait une vue
 * HTML propre à son espace - plus pauvre que le livre du back-office, sans les
 * comités ni le programme - et un .docx, qui est un **gabarit à retoucher**, pas
 * le document qu'on remet.
 *
 * ⚠️ Le contenu s'éprouve sur la **donnée partagée**, pas sur les octets du PDF :
 * dompdf comprime ses flux, donc y chercher un titre absent rendrait un cas qui
 * passe sans rien prouver.
 */
it('donne au participant le livre en PDF, le même que celui du back-office', function () {
    $event = openEdition();
    $account = verifiedAccount();
    $participant = participantFor($account, $event);
    $event->submissions()->create(['participant_id' => $participant->id, 'title' => 'Ma communication', 'status' => 'accepted', 'revision_of' => 0, 'ref_code' => 'AA-001', 'access_token' => Str::random(64)]);

    $reponse = $this->actingAs($account, 'account')
        ->get(route('account.documents.livret', $event));

    $reponse->assertOk();
    expect($reponse->headers->get('content-type'))->toContain('application/pdf');

    $groupes = $event->submissions()->acceptedCanonical()->orderBy('track')->get()
        ->groupBy(fn ($x) => $x->track ?: 'Autres');
    $html = view('documents.livret-pdf', LivretView::data($event, $groupes))->render();

    expect($html)->toContain('Ma communication');
});

it('refuse le livret à un compte non participant', function () {
    $event = openEdition();
    $account = verifiedAccount();
    // Aucun participant rattaché à ce compte pour l'événement.

    $this->actingAs($account, 'account')
        ->get(route('account.documents.livret', $event))
        ->assertForbidden();
});

it('refuse la gestion des documents aux invités', function () {
    $event = openEdition();
    $this->get(route('admin.evenements.documents', $event))->assertRedirect(route('account.login'));
});
