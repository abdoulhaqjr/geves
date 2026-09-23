<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

// Fabriques partagées : verifiedAccount() (tests/Pest.php).

it('suggère des affiliations via l’API ROR', function () {
    Http::fake(['api.ror.org/*' => Http::response([
        'items' => [
            ['name' => 'University of Kara', 'country' => ['country_name' => 'Togo']],
            ['name' => 'Université de Lomé', 'country' => ['country_name' => 'Togo']],
        ],
    ], 200)]);

    $this->actingAs(verifiedAccount(), 'account')
        ->getJson(route('account.affiliations', ['q' => 'kara']))
        ->assertOk()
        ->assertJsonPath('items.0', 'University of Kara (Togo)');
});

it('replie sur les affiliations locales quand ROR ne renvoie rien', function () {
    Http::fake(['api.ror.org/*' => Http::response(['items' => []], 200)]);

    $account = verifiedAccount(); // affiliation « Université de Kara »
    User::create(['name' => 'X', 'first_name' => 'X', 'last_name' => 'Y', 'email' => 'x@y.tg', 'password' => 'pass12345', 'affiliation' => 'Université de Lomé']);

    $this->actingAs($account, 'account')
        ->getJson(route('account.affiliations', ['q' => 'Lomé']))
        ->assertOk()
        ->assertJsonPath('items.0', 'Université de Lomé');
});
