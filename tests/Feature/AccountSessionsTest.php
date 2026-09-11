<?php

use App\Http\Controllers\Account\SecurityController;
use App\Models\Account;
use App\Support\UserAgent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;

function sessionAccount(): User
{
    return User::create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg',
        'password' => 'MotDePasse123!', 'affiliation' => 'UK', 'email_verified_at' => now(),
    ]);
}

function insertSession(string $id, ?int $accountId, string $ua = 'Mozilla/5.0 (Windows NT 10.0) Chrome/120'): void
{
    DB::table('sessions')->insert([
        'id' => $id,
        'user_id' => $accountId,
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

    $this->actingAs($account, 'account')->get(route('account.security'))
        ->assertOk()
        ->assertSee('Sessions actives')
        ->assertSee('Chrome · Windows')      // appareil de bureau du compte
        ->assertSee('Firefox · Android')     // appareil mobile du compte
        ->assertDontSee('autre-compte-999'); // session d'un autre compte exclue
});

it('révoque une autre session', function () {
    $account = sessionAccount();
    insertSession('autre-appareil-123', $account->id);

    $this->actingAs($account, 'account')
        ->post(route('account.security.sessions.revoke', 'autre-appareil-123'));

    expect(DB::table('sessions')->where('id', 'autre-appareil-123')->exists())->toBeFalse();
});

it('ne révoque pas la session courante via revokeSession', function () {
    $account = sessionAccount();
    $current = str_repeat('a', 40); // identifiant de session valide (40 alphanum.)
    $this->actingAs($account, 'account');
    app('session')->setId($current);
    insertSession($current, $account->id);
    insertSession('autre', $account->id);

    // La session courante est préservée…
    (new SecurityController)->revokeSession($current);
    expect(DB::table('sessions')->where('id', $current)->exists())->toBeTrue();

    // …mais une autre session est bien révoquée.
    (new SecurityController)->revokeSession('autre');
    expect(DB::table('sessions')->where('id', 'autre')->exists())->toBeFalse();
});

it('déconnecte les autres sessions avec le bon mot de passe', function () {
    $account = sessionAccount();
    $current = str_repeat('b', 40);
    $this->actingAs($account, 'account');
    app('session')->setId($current);
    insertSession($current, $account->id);
    insertSession('autre-1', $account->id);
    insertSession('autre-2', $account->id);

    $request = Request::create('/', 'POST', ['disable_password' => 'MotDePasse123!']);
    (new SecurityController)->revokeOtherSessions($request);

    // Seule la session courante subsiste.
    expect(DB::table('sessions')->where('user_id', $account->id)->count())->toBe(1);
    expect(DB::table('sessions')->where('id', $current)->exists())->toBeTrue();
});

it('refuse la déconnexion des autres sessions avec un mauvais mot de passe', function () {
    $account = sessionAccount();
    insertSession('autre-1', $account->id);

    $this->actingAs($account, 'account')
        ->post(route('account.security.sessions.revoke-others'), ['disable_password' => 'mauvais'])
        ->assertSessionHasErrors('disable_password');

    expect(DB::table('sessions')->where('id', 'autre-1')->exists())->toBeTrue();
});
