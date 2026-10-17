<?php

use App\Actions\FreezeEventIdentities;
use App\Actions\IssueAttestation;
use App\Documents\AttestationVars;
use App\Models\Participant;
use App\Models\Payment;
use App\Support\BadgeCatalog;
use Illuminate\Support\Facades\Storage;

// openEdition(), verifiedAccount() -> tests/Pest.php

/** Participant payé (= destinataire du badge « default ») lié à un compte. */
function frozenFixture(): array
{
    $event = openEdition();
    $account = verifiedAccount();
    $participant = Participant::create([
        'event_id' => $event->id, 'user_id' => $account->id,
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $account->email,
        'role' => 'author', 'title' => 'Doctorante', 'institution' => 'Université de Kara',
    ]);
    Payment::factory()->paid()->create(['event_id' => $event->id, 'participant_id' => $participant->id]);

    return [$event, $account, $participant];
}

it('gèle l’identité des participants à l’archivage de l’événement', function () {
    [$event, $account, $participant] = frozenFixture();

    expect($participant->fresh()->isIdentityFrozen())->toBeFalse();

    $event->update(['status' => 'archived']);

    $snap = (array) ($participant->fresh()->meta['identity'] ?? []);
    expect($snap['name'] ?? null)->toBe('Awa Kodjo')
        ->and($snap['institution'] ?? null)->toBe('Université de Kara')
        ->and($snap['frozen_at'] ?? null)->not->toBeNull();
});

it('fige le badge : une modification de profil APRÈS archivage ne le change plus', function () {
    [$event, $account] = frozenFixture();

    $event->update(['status' => 'archived']);

    // La personne corrige/modifie son profil après la clôture.
    $account->update(['first_name' => 'Awa', 'last_name' => 'MODIFIÉ', 'affiliation' => 'Autre université']);

    $recipient = BadgeCatalog::recipients($event->fresh(), 'default')->first();
    expect($recipient['name'])->toBe('Awa Kodjo')
        ->and($recipient['institution'])->toBe('Université de Kara');
});

it('avant archivage, le badge suit l’identité EN DIRECT du compte', function () {
    [$event, $account] = frozenFixture();

    $account->update(['last_name' => 'Nouveau']);

    $recipient = BadgeCatalog::recipients($event->fresh(), 'default')->first();
    expect($recipient['name'])->toBe('Awa Nouveau');
});

it('recopie la photo dans un emplacement figé propre à l’événement', function () {
    Storage::fake('public');
    [$event, $account, $participant] = frozenFixture();
    Storage::disk('public')->put('account-photos/awa.jpg', 'xxx');
    $account->update(['photo_path' => 'account-photos/awa.jpg']);

    $event->update(['status' => 'archived']);

    $snap = (array) ($participant->fresh()->meta['identity'] ?? []);
    expect($snap['photo_path'] ?? '')->toBe("event-identity/{$event->id}/{$participant->id}.jpg");
    Storage::disk('public')->assertExists("event-identity/{$event->id}/{$participant->id}.jpg");

    // Suppression ultérieure de la photo de profil : le badge gelé la garde.
    Storage::disk('public')->delete('account-photos/awa.jpg');
    $account->update(['photo_path' => null]);
    expect($participant->fresh()->displayPhotoUrl())->not->toBe('');
});

it('l’attestation fige civilité et affiliation à l’émission', function () {
    [$event, $account, $participant] = frozenFixture();
    $account->update(['civility' => 'Mme']);

    $attestation = app(IssueAttestation::class)->handle($participant->fresh(), 'participation');

    // Le profil change APRÈS l'émission.
    $account->update(['civility' => 'M.']);
    $participant->update(['institution' => 'Affiliation modifiée']);

    $vars = AttestationVars::for($attestation->fresh(), 'https://example.test/verif');
    expect($vars['civility'])->toBe('Mme')
        ->and($vars['institution'])->toBe('Université de Kara');
});

it('la commande regèle l’identité (rafraîchit l’instantané)', function () {
    [$event, $account, $participant] = frozenFixture();
    $event->update(['status' => 'archived']);

    // Correction côté participant (back-office), puis regel manuel.
    $participant->update(['institution' => 'Affiliation corrigée']);
    app(FreezeEventIdentities::class)->handle($event->fresh());

    expect(($participant->fresh()->meta['identity']['institution'] ?? null))->toBe('Affiliation corrigée');
});
