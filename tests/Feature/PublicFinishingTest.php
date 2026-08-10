<?php

use App\Livewire\Admin\Participants\Preview;
use App\Livewire\Public\ColloquePage;
use App\Livewire\Public\ContactForm;
use App\Mail\ContactMessageMail;
use App\Models\Email;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Livewire;

// adminStaff() -> AdminSubmissionWorkflowTest ; openEdition() -> AccountSubmissionTest

beforeEach(function () {
    RateLimiter::clear('contact:127.0.0.1');
});

it('envoie un message de contact et le journalise', function () {
    Mail::fake();
    $edition = openEdition(['contact' => ['email' => 'colloque@univkara.tg']]);

    $component = Livewire::test(ContactForm::class, ['editionId' => $edition->id])
        ->set('loadedAt', time() - 10) // contourne le délai minimal
        ->set('name', 'Awa Kodjo')
        ->set('email', 'awa@example.tg')
        ->set('message', 'Bonjour, j’ai une question sur les frais.')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('sent', true);

    Mail::assertSent(ContactMessageMail::class, fn ($m) => $m->hasTo('colloque@univkara.tg'));
    expect(Email::where('template', 'contact')->count())->toBe(1);
});

it('ignore silencieusement un envoi piégé par le honeypot', function () {
    Mail::fake();
    $edition = openEdition();

    Livewire::test(ContactForm::class, ['editionId' => $edition->id])
        ->set('loadedAt', time() - 10)
        ->set('name', 'Bot')
        ->set('email', 'bot@spam.tld')
        ->set('message', 'achetez maintenant !!!')
        ->set('website', 'http://spam.example')
        ->call('submit')
        ->assertSet('sent', true);

    Mail::assertNothingSent();
    expect(Email::count())->toBe(0);
});

it('ignore un envoi trop rapide (anti-bot temporel)', function () {
    Mail::fake();
    $edition = openEdition();

    Livewire::test(ContactForm::class, ['editionId' => $edition->id])
        // loadedAt = maintenant (mount) → délai < 3 s
        ->set('name', 'Trop rapide')
        ->set('email', 'x@example.tg')
        ->set('message', 'message instantané suspect')
        ->call('submit')
        ->assertSet('sent', true);

    Mail::assertNothingSent();
});

it('valide les champs requis du formulaire de contact', function () {
    $edition = openEdition();

    Livewire::test(ContactForm::class, ['editionId' => $edition->id])
        ->set('loadedAt', time() - 10)
        ->set('name', '')
        ->set('email', 'pas-un-email')
        ->set('message', 'court')
        ->call('submit')
        ->assertHasErrors(['name', 'email', 'message']);
});

it('affiche la section contact et le formulaire sur la page publique', function () {
    $edition = openEdition(['contact' => ['email' => 'colloque@univkara.tg', 'phone' => '+228 90 00 00 00']]);

    Livewire::test(ColloquePage::class, ['slug' => $edition->slug])
        ->assertSee('Contact')
        ->assertSee('+228 90 00 00 00')
        ->assertSee('Envoyer le message');
});

it('affiche « Sa page » d’un participant en lecture seule', function () {
    $staff = adminStaff();
    $edition = openEdition();
    $p = $edition->participants()->create(['first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author', 'title' => 'Docteur']);
    $edition->submissions()->create(['participant_id' => $p->id, 'title' => 'Ma communication', 'status' => 'accepted', 'revision_of' => 0, 'ref_code' => 'AA-001', 'access_token' => Str::random(64)]);
    $edition->payments()->create(['participant_id' => $p->id, 'amount' => 15000, 'status' => 'paid', 'gateway' => 'manual', 'method' => 'cash', 'receipt_no' => 'REC-2026-001', 'paid_at' => now()]);

    Livewire::actingAs($staff, 'web')
        ->test(Preview::class, ['participant' => $p])
        ->assertSee('lecture seule')
        ->assertSee('Awa Kodjo')
        ->assertSee('AA-001')
        ->assertSee('Ma communication')
        ->assertSee('15 000');
});

it('refuse « Sa page » aux invités', function () {
    $edition = openEdition();
    $p = $edition->participants()->create(['first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author']);

    $this->get(route('admin.participants.preview', $p))->assertRedirect(route('admin.login'));
});
