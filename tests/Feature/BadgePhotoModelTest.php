<?php

use App\Support\BadgeCatalog;

/**
 * Règle de choix du modèle de badge participant : AVEC photo si une photo de
 * profil existe et n'a pas été refusée, SANS photo sinon. Le refus se pose
 * depuis « Mes badges » (clé `meta.badge_without_photo`).
 */
it('prend le modèle AVEC photo quand une photo de profil existe', function () {
    $account = verifiedAccount();
    $account->forceFill(['photo_path' => 'avatars/awa.jpg'])->save();

    expect(BadgeCatalog::participationType($account))->toBe('participant_photo');
});

it('retombe sur le modèle SANS photo quand aucune photo de profil', function () {
    $account = verifiedAccount();

    expect($account->photo_path)->toBeNull();
    expect(BadgeCatalog::participationType($account))->toBe('default');
});

it('respecte le refus « Badge sans photo de profil » même avec une photo', function () {
    $account = verifiedAccount();
    $account->forceFill([
        'photo_path' => 'avatars/awa.jpg',
        'meta' => ['badge_without_photo' => true],
    ])->save();

    expect(BadgeCatalog::prefersNoPhoto($account))->toBeTrue();
    expect(BadgeCatalog::participationType($account))->toBe('default');
});

it('le badge participant de forAccount suit ce choix', function () {
    $event = openEdition();
    $account = verifiedAccount();
    $account->forceFill(['photo_path' => 'avatars/awa.jpg'])->save();
    $participant = $event->participants()->create([
        'user_id' => $account->id, 'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $account->email,
    ]);

    $badges = BadgeCatalog::forAccount($event, $account, $participant);
    expect($badges->first()['type'])->toBe('participant_photo');

    // Refus → modèle sans photo.
    $account->forceFill(['meta' => ['badge_without_photo' => true]])->save();
    expect(BadgeCatalog::forAccount($event, $account->fresh(), $participant)->first()['type'])->toBe('default');
});

it('enregistre la préférence depuis « Mes badges »', function () {
    $account = verifiedAccount();
    $account->forceFill(['photo_path' => 'avatars/awa.jpg'])->save();

    $this->actingAs($account, 'account')
        ->post(route('account.badge.photo'), ['without_photo' => '1'])
        ->assertRedirect();

    expect(BadgeCatalog::prefersNoPhoto($account->fresh()))->toBeTrue();

    $this->actingAs($account, 'account')
        ->post(route('account.badge.photo'), ['without_photo' => '0'])
        ->assertRedirect();

    expect(BadgeCatalog::prefersNoPhoto($account->fresh()))->toBeFalse();
});
