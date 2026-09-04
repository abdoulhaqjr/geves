<?php

use App\Models\Colloque;
use App\Support\CurrentColloque;

/** Crée un compte inscrit à deux colloques distincts (édition + participation). */
function accountInTwoColloques(): array
{
    $account = verifiedAccount();

    $edA = Colloque::create(['name' => 'Colloque Alpha', 'slug' => 'alpha'])
        ->editions()->create(['title' => 'Alpha 2026', 'year' => 2026, 'status' => 'open', 'slug' => 'alpha-2026', 'submission_open' => true]);
    $edB = Colloque::create(['name' => 'Colloque Beta', 'slug' => 'beta'])
        ->editions()->create(['title' => 'Beta 2025', 'year' => 2025, 'status' => 'closed', 'slug' => 'beta-2025']);

    foreach ([$edA, $edB] as $e) {
        $e->participants()->create([
            'account_id' => $account->id, 'first_name' => 'Awa', 'last_name' => 'Kodjo',
            'email' => $account->email, 'role' => 'author',
        ]);
    }

    return [$account, $edA, $edB];
}

it('liste les deux colloques du compte', function () {
    [$account] = accountInTwoColloques();

    expect(CurrentColloque::editions($account))->toHaveCount(2)
        ->and(CurrentColloque::hasMultiple($account))->toBeTrue();
});

it('choisit par défaut le colloque ouvert', function () {
    [$account, $edA] = accountInTwoColloques();

    expect(CurrentColloque::current($account)?->id)->toBe($edA->id);
});

it('bascule vers un autre colloque et le mémorise en session', function () {
    [$account, , $edB] = accountInTwoColloques();

    $this->actingAs($account, 'account')
        ->post(route('account.colloque.switch'), ['edition_id' => $edB->id])
        ->assertRedirect();

    expect(CurrentColloque::current($account)?->id)->toBe($edB->id);
});

it('refuse de basculer vers un colloque auquel le compte ne participe pas', function () {
    [$account] = accountInTwoColloques();
    $foreign = Colloque::create(['name' => 'Autre', 'slug' => 'autre'])
        ->editions()->create(['title' => 'Autre 2026', 'year' => 2026, 'status' => 'open', 'slug' => 'autre-2026']);

    $this->actingAs($account, 'account')
        ->post(route('account.colloque.switch'), ['edition_id' => $foreign->id]);

    // Le contexte reste sur un colloque du compte (pas l'étranger).
    expect(CurrentColloque::current($account)?->id)->not->toBe($foreign->id);
});

it('scope la liste des soumissions sur le colloque courant', function () {
    [$account, $edA, $edB] = accountInTwoColloques();

    $pA = $edA->participants()->where('account_id', $account->id)->first();
    $pB = $edB->participants()->where('account_id', $account->id)->first();
    $edA->submissions()->create(['participant_id' => $pA->id, 'title' => 'Sujet Alpha', 'status' => 'submitted', 'revision_of' => 0]);
    $edB->submissions()->create(['participant_id' => $pB->id, 'title' => 'Sujet Beta', 'status' => 'submitted', 'revision_of' => 0]);

    // Par défaut (Alpha), on voit Alpha et pas Beta.
    $this->actingAs($account, 'account')->get(route('account.submissions.index'))
        ->assertOk()->assertSee('Sujet Alpha')->assertDontSee('Sujet Beta');

    // Après bascule vers Beta, l'inverse.
    $this->post(route('account.colloque.switch'), ['edition_id' => $edB->id]);
    $this->get(route('account.submissions.index'))
        ->assertOk()->assertSee('Sujet Beta')->assertDontSee('Sujet Alpha');
});
