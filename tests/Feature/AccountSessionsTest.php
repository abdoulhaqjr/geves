<?php

use App\Livewire\Account\Security;
use App\Models\Account;
use App\Support\UserAgent;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

function sessionAccount(): Account
{
    return Account::create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg',
        'password' => 'MotDePasse123!', 'affiliation' => 'UK', 'email_verified_at' => now(),
    ]);
}

function insertSession(string $id, ?int $accountId, string $ua = 'Mozilla/5.0 (Windows NT 10.0) Chrome/120'): void
{
    DB::table('sessions')->insert([
        'id' => $id,
        'user_id' => null,
        'account_id' => $accountId,
        'ip_address' => '127.0.0.1',
        'user_agent' => $ua,
        'payload' => 'x',
        'last_activity' => now()->timestamp,
    ]);
}

it('décrit un User-Agent (navigateur + système)', function () {
    expect(UserAgent::describe('Mozilla/5.0 (Windows NT 10.0) Chrome/120'))->toBe('Chrome · Windows');
    expect(UserAgent::describe('Mozilla/5.0 (iPhone) Safari/605'))->toBe('Safari · iOS');
    expect(UserAgent::describe(''))->toBe('Appareil inconnu');
});

it('liste les sessions actives du compte, la courante marquée', function () {
    $account = sessionAccount();

    insertSession('appareil-bureau', $account->id, 'Mozilla/5.0 (Windows NT 10.0) Chrome/120');
    insertSession('appareil-mobile', $account->id, 'Mozilla/5.0 (Android) Firefox/119');
    insertSession('autre-compte-999', 999); // autre compte → exclu

    config(['session.driver' => 'database']); // en test le driver est « array »
    $this->actingAs($account, 'account');

    Livewire::test(Security::class)
        ->assertSee('Sessions actives')
        ->assertSee('Chrome · Windows')      // appareil de bureau du compte
        ->assertSee('Firefox · Android')     // appareil mobile du compte
        ->assertDontSee('autre-compte-999'); // session d'un autre compte exclue
});

it('révoque une autre session', function () {
    $account = sessionAccount();
    insertSession(session()->getId(), $account->id);
    insertSession('autre-appareil-123', $account->id);

    Livewire::actingAs($account, 'account')->test(Security::class)
        ->call('revokeSession', 'autre-appareil-123');

    expect(DB::table('sessions')->where('id', 'autre-appareil-123')->exists())->toBeFalse();
});

it('ne révoque pas la session courante via revokeSession', function () {
    $account = sessionAccount();
    $current = session()->getId();
    insertSession($current, $account->id);

    Livewire::actingAs($account, 'account')->test(Security::class)
        ->call('revokeSession', $current);

    expect(DB::table('sessions')->where('id', $current)->exists())->toBeTrue();
});

it('déconnecte les autres sessions avec le bon mot de passe', function () {
    $account = sessionAccount();
    $current = session()->getId();
    insertSession($current, $account->id);
    insertSession('autre-1', $account->id);
    insertSession('autre-2', $account->id);

    Livewire::actingAs($account, 'account')->test(Security::class)
        ->set('disable_password', 'MotDePasse123!')
        ->call('revokeOtherSessions')
        ->assertHasNoErrors();

    expect(DB::table('sessions')->where('account_id', $account->id)->count())->toBe(1);
    expect(DB::table('sessions')->where('id', $current)->exists())->toBeTrue();
});

it('refuse la déconnexion des autres sessions avec un mauvais mot de passe', function () {
    $account = sessionAccount();
    insertSession(session()->getId(), $account->id);
    insertSession('autre-1', $account->id);

    Livewire::actingAs($account, 'account')->test(Security::class)
        ->set('disable_password', 'mauvais')
        ->call('revokeOtherSessions')
        ->assertHasErrors('disable_password');

    expect(DB::table('sessions')->where('id', 'autre-1')->exists())->toBeTrue();
});
