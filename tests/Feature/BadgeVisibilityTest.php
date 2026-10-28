<?php

use App\Support\BadgeCatalog;
use App\Support\EventSettings;
use App\Support\ParticipantDashboard;

it('enregistre la visibilité de chaque catégorie dans les options des badges', function () {
    $event = openEdition();
    $staff = adminStaff();
    $optionsUrl = route('admin.documents.attestations', [
        'event_id' => $event->id,
        'tab' => 'badges',
        'stab' => 'options',
    ]);

    $this->actingAs($staff, 'web')->get($optionsUrl)
        ->assertOk()
        ->assertSee('Catégorie de badge');

    $visibility = array_fill_keys(array_keys(BadgeCatalog::TYPES), '1');
    $visibility['default'] = '0';
    $this->post(route('admin.documents.badges.options'), [
        'event_id' => $event->id,
        'badgeVisibility' => $visibility,
    ])->assertRedirect($optionsUrl)->assertSessionHasNoErrors();

    $hiddenEvent = $event->fresh();
    expect(EventSettings::get($hiddenEvent, 'badges.visible_to_participants.default'))->toBeFalse();
    expect(EventSettings::get($hiddenEvent, 'badges.visible_to_participants.moderation'))->toBeTrue();
});

it('ne montre au participant que ses catégories de badges autorisées', function () {
    $event = openEdition();
    $account = verifiedAccount();
    $participant = $event->participants()->create([
        'user_id' => $account->id,
        'first_name' => 'Awa',
        'last_name' => 'Kodjo',
        'email' => $account->email,
    ]);
    $event->sessions()->create([
        'type' => 'plenary',
        'label' => 'Plénière',
        'moderator_email' => $account->email,
    ]);

    EventSettings::setMany($event, [
        'badges.visible_to_participants.default' => false,
        'badges.visible_to_participants.moderation' => true,
    ]);

    expect(BadgeCatalog::forAccount($event, $account, $participant)->pluck('type')->all())
        ->toBe(['moderation']);
    expect(collect((new ParticipantDashboard($account, $event))->documents())
        ->pluck('url')->contains(route('account.documents').'#mon-badge'))->toBeTrue();

    $this->actingAs($account, 'account')->get(route('account.documents'))
        ->assertOk()
        ->assertSee('id="badge-moderation"', false)
        ->assertDontSee('id="badge-default"', false);
    $this->put(route('account.documents.badge.photo', [$event, 'default']), [
        'photo_mode' => 'with',
    ])->assertForbidden();

    EventSettings::set($event, 'badges.visible_to_participants.moderation', false);
    expect(BadgeCatalog::forAccount($event, $account, $participant))->toBeEmpty();
    expect(collect((new ParticipantDashboard($account, $event))->documents())
        ->pluck('url')->contains(route('account.documents').'#mon-badge'))->toBeFalse();
    $this->get(route('account.documents'))
        ->assertOk()
        ->assertDontSee('id="mon-badge"', false);
});
