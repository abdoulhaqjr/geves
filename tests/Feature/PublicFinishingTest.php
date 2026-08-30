<?php

use App\Mail\ContactMessageMail;
use App\Models\Email;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

// adminStaff() -> AdminSubmissionWorkflowTest ; openEdition() -> AccountSubmissionTest

beforeEach(function () {
    RateLimiter::clear('contact:127.0.0.1');
});

it('envoie un message de contact et le journalise', function () {
    Mail::fake();
    $edition = openEdition(['contact' => ['email' => 'colloque@univkara.tg']]);

    $this->post(route('public.contact', $edition->id), [
        'loaded_at' => time() - 10, // contourne le délai minimal
        'name' => 'Awa Kodjo',
        'email' => 'awa@example.tg',
        'message' => 'Bonjour, j’ai une question sur les frais.',
    ])->assertRedirect()->assertSessionHas('contact_sent', true);

    Mail::assertQueued(ContactMessageMail::class, fn ($m) => $m->hasTo('colloque@univkara.tg'));
    expect(Email::where('template', 'contact')->count())->toBe(1);
});

it('ignore silencieusement un envoi piégé par le honeypot', function () {
    Mail::fake();
    $edition = openEdition();

    $this->post(route('public.contact', $edition->id), [
        'loaded_at' => time() - 10,
        'name' => 'Bot',
        'email' => 'bot@spam.tld',
        'message' => 'achetez maintenant !!!',
        'website' => 'http://spam.example',
    ])->assertRedirect()->assertSessionHas('contact_sent', true);

    Mail::assertNothingQueued();
    expect(Email::count())->toBe(0);
});

it('ignore un envoi trop rapide (anti-bot temporel)', function () {
    Mail::fake();
    $edition = openEdition();

    $this->post(route('public.contact', $edition->id), [
        'loaded_at' => time(), // délai < 3 s
        'name' => 'Trop rapide',
        'email' => 'x@example.tg',
        'message' => 'message instantané suspect',
    ])->assertRedirect()->assertSessionHas('contact_sent', true);

    Mail::assertNothingQueued();
});

it('valide les champs requis du formulaire de contact', function () {
    $edition = openEdition();

    $this->post(route('public.contact', $edition->id), [
        'loaded_at' => time() - 10,
        'name' => '',
        'email' => 'pas-un-email',
        'message' => 'court',
    ])->assertSessionHasErrors(['name', 'email', 'message']);
});

it('affiche la section contact et le formulaire sur la page publique', function () {
    $edition = openEdition(['contact' => ['email' => 'colloque@univkara.tg', 'phone' => '+228 90 00 00 00']]);

    $this->get(route('public.colloque', $edition->slug))
        ->assertOk()
        ->assertSee('Contact')
        ->assertSee('+228 90 00 00 00')
        ->assertSee('Envoyer le message');
});

it('affiche la bannière promo quand elle est renseignée', function () {
    $edition = openEdition(['promo' => ['text' => 'Early-bird jusqu’au 30 septembre', 'url' => 'https://uk.tg/promo', 'cta' => 'En profiter']]);

    $this->get(route('public.colloque', $edition->slug))
        ->assertOk()
        ->assertSee('Early-bird jusqu’au 30 septembre')
        ->assertSee('En profiter');
});

it('masque la bannière promo sans texte', function () {
    $edition = openEdition();

    $this->get(route('public.colloque', $edition->slug))
        ->assertOk()
        ->assertDontSee('En profiter');
});

it('affiche la section infos pratiques', function () {
    $edition = openEdition(['practical' => [
        'venue' => 'Campus de Kara, amphi A',
        'transport' => 'Navettes depuis l’aéroport',
    ]]);

    $this->get(route('public.colloque', $edition->slug))
        ->assertOk()
        ->assertSee('Infos pratiques')
        ->assertSee('Campus de Kara, amphi A')
        ->assertSee('Navettes depuis l’aéroport');
});

it('enregistre promo et infos pratiques depuis le formulaire d’édition', function () {
    $staff = adminStaff();
    $edition = openEdition();

    $this->actingAs($staff, 'web')->put(route('admin.editions.update', $edition), [
        'title' => $edition->title, 'status' => $edition->status, 'colloque_id' => $edition->colloque_id, 'max_communications' => 3,
        'promo_text' => 'Inscriptions ouvertes',
        'infoCards' => [['title' => 'Lieu', 'text' => 'Université de Kara']],
    ])->assertSessionHasNoErrors();

    $settings = $edition->fresh()->settings;
    expect($settings['promo']['text'])->toBe('Inscriptions ouvertes');
    // Le formulaire édite désormais les infos pratiques en « cartes » (fidèle au mockup).
    expect($settings['info_cards'][0]['title'])->toBe('Lieu');
    expect($settings['info_cards'][0]['text'])->toBe('Université de Kara');
});

it('affiche « Sa page » d’un participant en lecture seule', function () {
    $staff = adminStaff();
    $edition = openEdition();
    $p = $edition->participants()->create(['first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author', 'title' => 'Docteur']);
    $edition->submissions()->create(['participant_id' => $p->id, 'title' => 'Ma communication', 'status' => 'accepted', 'revision_of' => 0, 'ref_code' => 'AA-001', 'access_token' => Str::random(64)]);
    $edition->payments()->create(['participant_id' => $p->id, 'amount' => 15000, 'status' => 'paid', 'gateway' => 'manual', 'method' => 'cash', 'receipt_no' => 'REC-2026-001', 'paid_at' => now()]);

    $this->actingAs($staff, 'web')
        ->get(route('admin.participants.preview', $p))
        ->assertOk()
        ->assertSee('lecture seule')
        ->assertSee('Awa Kodjo')
        ->assertSee('AA-001')
        ->assertSee('Ma communication')
        ->assertSee('15 000');
});

it('refuse « Sa page » aux invités', function () {
    $edition = openEdition();
    $p = $edition->participants()->create(['first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author']);

    $this->get(route('admin.participants.preview', $p))->assertRedirect(route('account.login'));
});
