<?php

use App\Models\User;
use App\Support\AuthorMatcher;
use App\Support\Phone;

/**
 * Le téléphone est un identifiant FORT : `AuthorMatcher` en déduit un compte et
 * le serveur impose ensuite l'identité de ce compte (co-auteurs, formateurs).
 * Il doit donc désigner une personne et une seule - d'où la forme canonique à
 * l'écriture et l'unicité en base.
 */

// verifiedAccount() -> tests/Pest.php

it('met le téléphone en forme canonique', function () {
    expect(Phone::normalize('+228 90 01 52 84'))->toBe('+22890015284');
    expect(Phone::normalize('(+228) 90-01.52.84'))->toBe('+22890015284');
    expect(Phone::normalize('  90015284  '))->toBe('90015284');
    expect(Phone::normalize(''))->toBe('');
    expect(Phone::normalize(null))->toBe('');

    // ⚠️ Aucun indicatif deviné : un numéro local reste local.
    expect(Phone::normalize('90015284'))->not->toBe('+22890015284');
});

it('stocke le téléphone déjà normalisé', function () {
    $user = User::create([
        'name' => 'Kossi Adjo', 'first_name' => 'Kossi', 'last_name' => 'Adjo',
        'email' => 'kossi.phone@example.tg', 'password' => 'pass12345',
        'phone' => '+228 90 11 22 33',
    ]);

    expect($user->fresh()->phone)->toBe('+22890112233');
});

it('refuse à l’inscription un numéro déjà pris', function () {
    $existing = verifiedAccount(); // +22890000000

    $this->post(route('account.register.store'), [
        'first_name' => 'Kossi', 'last_name' => 'Adjo',
        'email' => 'kossi.new@example.tg',
        'phone' => $existing->phone,
        'password' => 'MotDePasse!2026', 'password_confirmation' => 'MotDePasse!2026',
        'terms' => '1',
    ])->assertSessionHasErrors('phone');

    expect(User::where('email', 'kossi.new@example.tg')->exists())->toBeFalse();
});

it('ne laisse pas contourner l’unicité par la mise en forme', function () {
    verifiedAccount(); // +22890000000

    // Même numéro, écrit autrement : normalisé AVANT le contrôle d'unicité.
    $this->post(route('account.register.store'), [
        'first_name' => 'Kossi', 'last_name' => 'Adjo',
        'email' => 'kossi.space@example.tg',
        'phone' => '+228 90 00 00 00',
        'password' => 'MotDePasse!2026', 'password_confirmation' => 'MotDePasse!2026',
        'terms' => '1',
    ])->assertSessionHasErrors('phone');
});

it('accepte une inscription avec un numéro libre', function () {
    verifiedAccount();

    $this->post(route('account.register.store'), [
        'first_name' => 'Kossi', 'last_name' => 'Adjo',
        'email' => 'kossi.free@example.tg',
        'phone' => '+228 91 11 11 11',
        'password' => 'MotDePasse!2026', 'password_confirmation' => 'MotDePasse!2026',
        'terms' => '1',
    ])->assertSessionHasNoErrors();

    expect(User::where('email', 'kossi.free@example.tg')->value('phone'))->toBe('+22891111111');
});

it('interdit de reprendre au profil le numéro d’un autre compte', function () {
    $other = verifiedAccount();
    $me = User::create([
        'name' => 'Kossi Adjo', 'first_name' => 'Kossi', 'last_name' => 'Adjo',
        'email' => 'kossi.profil@example.tg', 'password' => 'pass12345',
        'phone' => '+22891234567', 'affiliation' => 'Université de Kara',
    ]);
    $me->markEmailAsVerified();

    $this->actingAs($me, 'account')
        ->put(route('account.profile.update'), [
            'first_name' => 'Kossi', 'last_name' => 'Adjo',
            'phone' => $other->phone,
            'affiliation' => 'Université de Kara',
        ])->assertSessionHasErrors('phone');

    expect($me->fresh()->phone)->toBe('+22891234567');
});

it('laisse garder son propre numéro au profil', function () {
    $me = User::create([
        'name' => 'Kossi Adjo', 'first_name' => 'Kossi', 'last_name' => 'Adjo',
        'email' => 'kossi.self@example.tg', 'password' => 'pass12345',
        'phone' => '+22891234567', 'affiliation' => 'Université de Kara',
    ]);
    $me->markEmailAsVerified();

    $this->actingAs($me, 'account')
        ->put(route('account.profile.update'), [
            'first_name' => 'Kossi', 'last_name' => 'Adjoh',
            'phone' => '+228 91 23 45 67', // le sien, écrit autrement
            'affiliation' => 'Université de Kara',
        ])->assertSessionHasNoErrors();

    expect($me->fresh()->last_name)->toBe('Adjoh');
    expect($me->fresh()->phone)->toBe('+22891234567');
});

it('rapproche un auteur malgré une mise en forme différente', function () {
    $user = verifiedAccount(); // +22890000000

    $match = app(AuthorMatcher::class)->match(['phone' => '+228 90 00 00 00']);

    expect($match['type'])->toBe('phone');
    expect($match['user']?->id)->toBe($user->id);
});

it('empêche de s’approprier le numéro d’un tiers par la 2FA SMS', function () {
    $other = verifiedAccount(); // +22890000000
    $me = User::create([
        'name' => 'Kossi Adjo', 'first_name' => 'Kossi', 'last_name' => 'Adjo',
        'email' => 'kossi.sms@example.tg', 'password' => 'pass12345',
        'phone' => '+22891234567', 'affiliation' => 'Université de Kara',
    ]);
    $me->markEmailAsVerified();

    // ⚠️ Ce parcours écrit `users.phone` : il doit appliquer le même contrôle
    // d'unicité que le profil, sinon on prend le numéro d'autrui.
    $this->actingAs($me, 'account')->post(route('account.security.sms.start'));
    $this->actingAs($me, 'account')
        ->post(route('account.security.sms.send'), ['phone' => $other->phone])
        ->assertSessionHasErrors('phone');

    expect($me->fresh()->phone)->toBe('+22891234567');
});
