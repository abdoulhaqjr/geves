<?php

use App\AI\AiManager;
use App\AI\Exceptions\AiUnavailableException;
use App\Models\Participant;
use App\Models\Testimonial;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Dépôt participant
|--------------------------------------------------------------------------
*/

it('permet à un participant connecté de déposer un témoignage (en attente)', function () {
    $account = verifiedAccount();

    $this->actingAs($account, 'account')
        ->post(route('account.testimonials.store'), [
            'quote' => 'La plateforme a rendu le dépôt de mon résumé limpide.',
            'consent' => '1',
        ])->assertRedirect();

    $t = Testimonial::first();
    expect($t)->not->toBeNull()
        ->and($t->status)->toBe('pending')
        ->and($t->user_id)->toBe($account->id)
        ->and($t->author_name)->toBe('Awa Kodjo')
        ->and($t->consent)->toBeTrue();
});

it('exige le consentement de publication', function () {
    $account = verifiedAccount();

    $this->actingAs($account, 'account')
        ->post(route('account.testimonials.store'), [
            'quote' => 'Un témoignage sans consentement ne doit pas passer.',
        ])->assertSessionHasErrors('consent');

    expect(Testimonial::count())->toBe(0);
});

it('n’autorise qu’un seul témoignage en attente à la fois', function () {
    $account = verifiedAccount();
    Testimonial::create(['user_id' => $account->id, 'author_name' => 'Awa Kodjo', 'quote' => 'Premier témoignage en attente.', 'status' => 'pending', 'consent' => true]);

    $this->actingAs($account, 'account')
        ->post(route('account.testimonials.store'), [
            'quote' => 'Deuxième tentative pendant modération.',
            'consent' => '1',
        ])->assertRedirect();

    expect(Testimonial::where('user_id', $account->id)->count())->toBe(1);
});

it('ignore un contexte d’événement auquel la personne n’a pas participé', function () {
    $account = verifiedAccount();
    $event = openEdition();
    // Le compte n'a AUCUN participant sur cette événement.

    $this->actingAs($account, 'account')
        ->post(route('account.testimonials.store'), [
            'quote' => 'Témoignage avec une événement qui n’est pas la mienne.',
            'event_id' => $event->id,
            'consent' => '1',
        ])->assertRedirect();

    expect(Testimonial::first()->event_id)->toBeNull();
});

it('conserve le contexte d’événement d’un participant légitime', function () {
    $account = verifiedAccount();
    $event = openEdition();
    Participant::create(['event_id' => $event->id, 'user_id' => $account->id, 'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $account->email, 'role' => 'author']);

    $this->actingAs($account, 'account')
        ->post(route('account.testimonials.store'), [
            'quote' => 'Un vrai retour sur l’événement à laquelle j’ai participé.',
            'event_id' => $event->id,
            'consent' => '1',
        ])->assertRedirect();

    expect(Testimonial::first()->event_id)->toBe($event->id);
});

/*
|--------------------------------------------------------------------------
| Modération back-office
|--------------------------------------------------------------------------
*/

it('refuse l’accès admin sans la capacité testimonials', function () {
    $this->actingAs(User::factory()->create(), 'web')
        ->get(route('admin.testimonials.index'))->assertForbidden();
});

it('laisse l’admin approuver puis mettre à la une', function () {
    $t = Testimonial::create(['author_name' => 'M. Ouattara', 'quote' => 'Gain de temps précieux en tant que modérateur.', 'status' => 'pending', 'consent' => true]);
    $staff = adminStaff();

    $this->actingAs($staff, 'web')->post(route('admin.testimonials.approve', $t))->assertRedirect();
    expect($t->refresh()->status)->toBe('approved')->and($t->moderated_at)->not->toBeNull();

    $this->actingAs($staff, 'web')->post(route('admin.testimonials.feature', $t))->assertRedirect();
    expect($t->refresh()->featured)->toBeTrue();
});

it('empêche de mettre à la une un témoignage non approuvé', function () {
    $t = Testimonial::create(['author_name' => 'X', 'quote' => 'Témoignage encore en attente de modération.', 'status' => 'pending', 'consent' => true]);

    $this->actingAs(adminStaff(), 'web')->post(route('admin.testimonials.feature', $t));
    expect($t->refresh()->featured)->toBeFalse();
});

it('réordonne les témoignages à la une', function () {
    $a = Testimonial::create(['author_name' => 'A', 'quote' => 'Premier témoignage approuvé et mis en avant.', 'status' => 'approved', 'featured' => true, 'consent' => true]);
    $b = Testimonial::create(['author_name' => 'B', 'quote' => 'Deuxième témoignage approuvé et mis en avant.', 'status' => 'approved', 'featured' => true, 'consent' => true]);

    $this->actingAs(adminStaff(), 'web')
        ->post(route('admin.testimonials.reorder'), ['order' => [$b->id, $a->id]])->assertRedirect();

    expect($b->refresh()->position)->toBe(0)->and($a->refresh()->position)->toBe(1);
});

it('permet la saisie manuelle par l’admin (approuvée d’office)', function () {
    $this->actingAs(adminStaff(), 'web')->post(route('admin.testimonials.store'), [
        'author_name' => 'Comité d’organisation',
        'quote' => 'La vérification des attestations renforce la crédibilité.',
        'featured' => '1',
    ])->assertRedirect();

    $t = Testimonial::first();
    expect($t->status)->toBe('approved')->and($t->featured)->toBeTrue()->and($t->consent)->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Sélection assistée par IA (à la demande)
|--------------------------------------------------------------------------
*/

it('applique la proposition de l’IA (scores + mise en avant)', function () {
    $t = Testimonial::create(['author_name' => 'A. Kombate', 'quote' => 'Interface claire, suivi jusqu’à l’acceptation.', 'status' => 'approved', 'consent' => true]);

    $this->mock(AiManager::class, function ($m) use ($t) {
        $m->shouldReceive('extract')->once()->andReturn([
            'results' => [['id' => $t->id, 'score' => 88, 'reason' => 'Concret et crédible.']],
            'featured' => [$t->id],
        ]);
    });

    $this->actingAs(adminStaff(), 'web')->post(route('admin.testimonials.propose'))->assertRedirect();

    $t->refresh();
    expect($t->impact_score)->toBe(88)->and($t->impact_reason)->toBe('Concret et crédible.')
        ->and($t->featured)->toBeTrue()->and($t->position)->toBe(0);
});

it('signale proprement l’absence d’assistant IA', function () {
    Testimonial::create(['author_name' => 'A', 'quote' => 'Un témoignage approuvé à analyser par l’IA.', 'status' => 'approved', 'consent' => true]);

    $this->mock(AiManager::class, function ($m) {
        $m->shouldReceive('extract')->andThrow(new AiUnavailableException('Aucun assistant IA n’est configuré (voir Réglages).'));
    });

    $this->actingAs(adminStaff(), 'web')->post(route('admin.testimonials.propose'))
        ->assertRedirect()->assertSessionHas('ukc_error');
});

/*
|--------------------------------------------------------------------------
| Page d'accueil publique
|--------------------------------------------------------------------------
*/

it('affiche sur l’accueil uniquement les témoignages approuvés et mis en avant', function () {
    Testimonial::create(['author_name' => 'Visible Featured', 'quote' => 'Ce témoignage doit apparaître sur l’accueil public.', 'status' => 'approved', 'featured' => true, 'consent' => true]);
    Testimonial::create(['author_name' => 'Approved NotFeatured', 'quote' => 'Approuvé mais pas mis en avant, donc masqué.', 'status' => 'approved', 'featured' => false, 'consent' => true]);
    Testimonial::create(['author_name' => 'Still Pending', 'quote' => 'En attente, ne doit jamais apparaître publiquement.', 'status' => 'pending', 'featured' => true, 'consent' => true]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Visible Featured')
        ->assertDontSee('Approved NotFeatured')
        ->assertDontSee('Still Pending');
});
