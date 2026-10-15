<?php

use App\Support\BadgeCatalog;
use Illuminate\Support\Facades\Route;

/** La photo est un calque du modèle participant, pas une seconde catégorie. */
it('ne publie qu une catégorie de badge participant', function () {
    expect(BadgeCatalog::valid('default'))->toBeTrue()
        ->and(BadgeCatalog::valid('participant_photo'))->toBeFalse();
});

it('utilise le même modèle participant avec ou sans photo de profil', function () {
    $event = openEdition();
    $account = verifiedAccount();
    $participant = $event->participants()->create([
        'user_id' => $account->id, 'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $account->email,
    ]);

    expect(BadgeCatalog::forAccount($event, $account, $participant)->first()['type'])->toBe('default');

    $account->forceFill(['photo_path' => 'avatars/awa.jpg'])->save();
    expect(BadgeCatalog::forAccount($event, $account->fresh(), $participant)->first()['type'])->toBe('default');
});

it('ignore l ancienne préférence photo du compte pour choisir le modèle', function () {
    $event = openEdition();
    $account = verifiedAccount();
    $account->forceFill(['photo_path' => 'avatars/awa.jpg', 'meta' => ['badge_without_photo' => true]])->save();
    $participant = $event->participants()->create([
        'user_id' => $account->id, 'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $account->email,
    ]);

    expect(BadgeCatalog::forAccount($event, $account, $participant)->first()['type'])->toBe('default');
});

it('ne propose plus de préférence photo dans l espace participant', function () {
    expect(Route::has('account.badge.photo'))->toBeFalse();
});
