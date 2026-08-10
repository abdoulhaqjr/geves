<?php

use App\Livewire\Account\Profile;
use App\Livewire\Admin\Settings\Index as SettingsPage;
use App\Support\HalClient;
use App\Support\Settings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

// adminStaff() -> AdminSubmissionWorkflowTest ; verifiedAccount() -> AccountSubmissionTest

beforeEach(function () {
    Cache::flush();
});

function halDocs(): array
{
    return ['response' => ['docs' => [
        ['docid' => '12', 'label_s' => 'Université de Kara', 'acronym_s' => 'UK', 'country_s' => 'tg'],
        ['docid' => '13', 'name_s' => 'Laboratoire X'],
    ]]];
}

it('renvoie une liste vide quand HAL est désactivé', function () {
    config()->set('services.hal.enabled', false);
    Http::fake();

    expect(HalClient::searchStructures('Université de Kara'))->toBe([]);
    Http::assertNothingSent();
});

it('ignore les requêtes trop courtes', function () {
    config()->set('services.hal.enabled', true);
    Http::fake();

    expect(HalClient::searchStructures('ka'))->toBe([]);
    Http::assertNothingSent();
});

it('normalise les structures renvoyées par AuréHAL', function () {
    config()->set('services.hal.enabled', true);
    Http::fake(['*' => Http::response(halDocs(), 200)]);

    $res = HalClient::searchStructures('Kara');

    expect($res)->toHaveCount(2);
    expect($res[0]['label'])->toBe('Université de Kara');
    expect($res[0]['acronym'])->toBe('UK');
    expect($res[0]['country'])->toBe('tg');
    expect($res[1]['label'])->toBe('Laboratoire X'); // repli name_s
});

it('est fail-closed en cas d’erreur de l’API HAL', function () {
    config()->set('services.hal.enabled', true);
    Http::fake(['*' => Http::response('boom', 500)]);

    expect(HalClient::searchStructures('Kara'))->toBe([]);
});

it('met en cache les résultats (une seule requête HTTP)', function () {
    config()->set('services.hal.enabled', true);
    Http::fake(['*' => Http::response(halDocs(), 200)]);

    HalClient::searchStructures('Kara');
    HalClient::searchStructures('Kara');

    Http::assertSentCount(1);
});

it('propose des suggestions HAL sur le profil et remplit à la sélection', function () {
    config()->set('services.hal.enabled', true);
    Http::fake(['*' => Http::response(halDocs(), 200)]);
    $account = verifiedAccount();

    Livewire::actingAs($account, 'account')
        ->test(Profile::class)
        ->set('affiliation', 'Université de K')
        ->assertSet('halSuggestions.0.label', 'Université de Kara')
        ->call('chooseAffiliation', 'Université de Kara')
        ->assertSet('affiliation', 'Université de Kara')
        ->assertSet('halSuggestions', []);
});

it('n’appelle pas HAL sur le profil quand désactivé', function () {
    config()->set('services.hal.enabled', false);
    Http::fake();
    $account = verifiedAccount();

    Livewire::actingAs($account, 'account')
        ->test(Profile::class)
        ->set('affiliation', 'Université de K')
        ->assertSet('halSuggestions', []);

    Http::assertNothingSent();
});

it('enregistre le réglage HAL depuis le back-office', function () {
    $staff = adminStaff();

    Livewire::actingAs($staff, 'web')
        ->test(SettingsPage::class)
        ->set('hal_enabled', true)
        ->call('save')
        ->assertHasNoErrors();

    expect((bool) Settings::get('hal.enabled'))->toBeTrue();
});
