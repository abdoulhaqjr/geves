<?php

use App\Models\EventSeries;
use App\Support\CurrentEvent;

/** Crée un compte inscrit à deux colloques distincts (événement + participation). */
function accountInTwoColloques(): array
{
    $account = verifiedAccount();

    $edA = EventSeries::create(['name' => 'EventSeries Alpha', 'slug' => 'alpha'])
        ->events()->create(['title' => 'Alpha 2026', 'year' => 2026, 'status' => 'open', 'slug' => 'alpha-2026', 'submission_open' => true]);
    $edB = EventSeries::create(['name' => 'EventSeries Beta', 'slug' => 'beta'])
        ->events()->create(['title' => 'Beta 2025', 'year' => 2025, 'status' => 'closed', 'slug' => 'beta-2025']);

    foreach ([$edA, $edB] as $e) {
        $e->participants()->create([
            'user_id' => $account->id, 'first_name' => 'Awa', 'last_name' => 'Kodjo',
            'email' => $account->email, 'role' => 'author',
        ]);
    }

    return [$account, $edA, $edB];
}

it('liste les deux colloques du compte', function () {
    [$account] = accountInTwoColloques();

    expect(CurrentEvent::events($account))->toHaveCount(2)
        ->and(CurrentEvent::hasMultiple($account))->toBeTrue();
});

it('choisit par défaut le colloque ouvert', function () {
    [$account, $edA] = accountInTwoColloques();

    expect(CurrentEvent::current($account)?->id)->toBe($edA->id);
});

it('bascule vers un autre colloque et le mémorise en session', function () {
    [$account, , $edB] = accountInTwoColloques();

    $this->actingAs($account, 'account')
        ->post(route('account.colloque.switch'), ['event_id' => $edB->id])
        ->assertRedirect();

    expect(CurrentEvent::current($account)?->id)->toBe($edB->id);
});

it('refuse de basculer vers un colloque auquel le compte ne participe pas', function () {
    [$account] = accountInTwoColloques();
    $foreign = EventSeries::create(['name' => 'Autre', 'slug' => 'autre'])
        ->events()->create(['title' => 'Autre 2026', 'year' => 2026, 'status' => 'open', 'slug' => 'autre-2026']);

    $this->actingAs($account, 'account')
        ->post(route('account.colloque.switch'), ['event_id' => $foreign->id]);

    // Le contexte reste sur un colloque du compte (pas l'étranger).
    expect(CurrentEvent::current($account)?->id)->not->toBe($foreign->id);
});

it('permet de sélectionner un appel ouvert non encore rejoint (pour soumettre)', function () {
    [$account] = accountInTwoColloques();

    // Un colloque dont l'appel est ouvert, auquel le compte ne participe PAS encore.
    $edC = EventSeries::create(['name' => 'EventSeries Gamma', 'slug' => 'gamma'])
        ->events()->create(['title' => 'Gamma 2026', 'year' => 2026, 'status' => 'open', 'slug' => 'gamma-2026', 'submission_open' => true]);

    expect(CurrentEvent::openCalls($account)->pluck('id'))->toContain($edC->id)
        ->and(CurrentEvent::selectable($account)->pluck('id'))->toContain($edC->id);

    // On peut basculer dessus bien qu'on n'y participe pas encore.
    $this->actingAs($account, 'account')
        ->post(route('account.colloque.switch'), ['event_id' => $edC->id])
        ->assertRedirect();

    expect(CurrentEvent::current($account)?->id)->toBe($edC->id);
});

it('propose par défaut un appel ouvert à un compte sans aucune participation', function () {
    $account = verifiedAccount();
    $edC = EventSeries::create(['name' => 'EventSeries Gamma', 'slug' => 'gamma'])
        ->events()->create(['title' => 'Gamma 2026', 'year' => 2026, 'status' => 'open', 'slug' => 'gamma-2026', 'submission_open' => true]);

    expect(CurrentEvent::current($account)?->id)->toBe($edC->id);
});

it('scope la liste des soumissions sur le colloque courant', function () {
    [$account, $edA, $edB] = accountInTwoColloques();

    $pA = $edA->participants()->where('user_id', $account->id)->first();
    $pB = $edB->participants()->where('user_id', $account->id)->first();
    $edA->submissions()->create(['participant_id' => $pA->id, 'title' => 'Sujet Alpha', 'status' => 'submitted', 'revision_of' => 0]);
    $edB->submissions()->create(['participant_id' => $pB->id, 'title' => 'Sujet Beta', 'status' => 'submitted', 'revision_of' => 0]);

    // Par défaut (Alpha), on voit Alpha et pas Beta.
    $this->actingAs($account, 'account')->get(route('account.submissions.index'))
        ->assertOk()->assertSee('Sujet Alpha')->assertDontSee('Sujet Beta');

    // Après bascule vers Beta, l'inverse.
    $this->post(route('account.colloque.switch'), ['event_id' => $edB->id]);
    $this->get(route('account.submissions.index'))
        ->assertOk()->assertSee('Sujet Beta')->assertDontSee('Sujet Alpha');
});
