<?php

use App\Actions\NotifyCoauthorsForPayment;
use App\Livewire\Admin\Documents\Attestations;
use App\Livewire\Admin\Submissions\Show;
use App\Livewire\Public\CoauthorPayment;
use App\Mail\CoauthorPaymentInvitationMail;
use App\Models\Attestation;
use App\Models\Edition;
use App\Models\Email;
use App\Models\Participant;
use App\Models\Payment;
use App\Models\Submission;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Livewire;

// adminStaff() -> AdminSubmissionWorkflowTest ; openEdition() -> AccountSubmissionTest

function coauthorEdition(): Edition
{
    return openEdition(['fees' => [
        ['label' => 'Doctorant', 'amount' => 10000],
        ['label' => 'Docteur', 'amount' => 20000],
    ]]);
}

function acceptedWithCoauthor(Edition $edition, string $coEmail = 'co@example.tg', string $coGrade = 'Docteur', string $status = 'accepted'): Submission
{
    $author = $edition->participants()->create(['first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author']);

    return $edition->submissions()->create([
        'participant_id' => $author->id,
        'title' => 'Sur les colloques',
        'status' => $status,
        'revision_of' => 0,
        'ref_code' => 'AA-001',
        'access_token' => Str::random(64),
        'track' => 'Sciences',
        'type' => 'oral',
        'authors' => [
            ['name' => 'Awa Kodjo', 'email' => 'awa@example.tg', 'corresponding' => true],
            ['name' => 'Ben Co', 'email' => $coEmail, 'affiliation' => 'UK', 'grade' => $coGrade, 'corresponding' => false],
        ],
    ]);
}

it('retrouve les communications acceptées du co-auteur et calcule le montant du grade', function () {
    $edition = coauthorEdition();
    acceptedWithCoauthor($edition);

    Livewire::test(CoauthorPayment::class, ['slug' => $edition->slug])
        ->set('email', 'co@example.tg')
        ->call('search')
        ->assertSet('grade', 'Docteur')
        ->assertSet('amount', 20000.0)
        ->assertSee('AA-001');
});

it('n’associe rien à un e-mail inconnu', function () {
    $edition = coauthorEdition();
    acceptedWithCoauthor($edition);

    Livewire::test(CoauthorPayment::class, ['slug' => $edition->slug])
        ->set('email', 'personne@example.tg')
        ->call('search')
        ->assertSee('Aucune communication');
});

it('recalcule le montant quand le co-auteur corrige son grade', function () {
    $edition = coauthorEdition();
    acceptedWithCoauthor($edition);

    Livewire::test(CoauthorPayment::class, ['slug' => $edition->slug])
        ->set('email', 'co@example.tg')
        ->call('search')
        ->set('grade', 'Doctorant')
        ->assertSet('amount', 10000.0);
});

it('crée un participant co-auteur et un paiement en attente marqué flow=coauthor', function () {
    $edition = coauthorEdition();
    acceptedWithCoauthor($edition);

    Livewire::test(CoauthorPayment::class, ['slug' => $edition->slug])
        ->set('email', 'co@example.tg')
        ->call('search')
        ->call('pay', 'manual');

    $participant = Participant::where('edition_id', $edition->id)->where('email', 'co@example.tg')->first();
    expect($participant)->not->toBeNull();
    expect($participant->role)->toBe('coauthor');
    expect($participant->title)->toBe('Docteur');

    $payment = Payment::where('participant_id', $participant->id)->first();
    expect($payment->status)->toBe('pending');
    expect((float) $payment->amount)->toBe(20000.0);
    expect($payment->meta['flow'] ?? null)->toBe('coauthor');
});

it('affiche l’état « déjà réglé » pour un co-auteur ayant payé', function () {
    $edition = coauthorEdition();
    acceptedWithCoauthor($edition);

    $co = $edition->participants()->create(['first_name' => 'Ben', 'last_name' => 'Co', 'email' => 'co@example.tg', 'role' => 'coauthor', 'title' => 'Docteur']);
    $edition->payments()->create(['participant_id' => $co->id, 'amount' => 20000, 'status' => 'paid', 'gateway' => 'manual', 'method' => 'cash', 'receipt_no' => 'REC-2026-001', 'paid_at' => now()]);

    Livewire::test(CoauthorPayment::class, ['slug' => $edition->slug])
        ->set('email', 'co@example.tg')
        ->call('search')
        ->assertSee('Frais déjà réglés');
});

it('notifie les co-auteurs d’une communication acceptée et journalise', function () {
    Mail::fake();
    $edition = coauthorEdition();
    $submission = acceptedWithCoauthor($edition);

    $count = app(NotifyCoauthorsForPayment::class)->handle($submission);

    expect($count)->toBe(1);
    Mail::assertSent(CoauthorPaymentInvitationMail::class, fn ($m) => $m->hasTo('co@example.tg'));
    expect(Email::where('template', 'coauthor_payment')->where('recipient', 'co@example.tg')->count())->toBe(1);
});

it('ne notifie pas les co-auteurs si la communication n’est pas acceptée', function () {
    Mail::fake();
    $edition = coauthorEdition();
    $submission = acceptedWithCoauthor($edition, status: 'submitted');

    expect(app(NotifyCoauthorsForPayment::class)->handle($submission))->toBe(0);
    Mail::assertNothingSent();
});

it('déclenche la notification des co-auteurs depuis l’acceptation admin', function () {
    Mail::fake();
    $edition = coauthorEdition();
    $submission = acceptedWithCoauthor($edition, status: 'submitted');

    Livewire::actingAs(adminStaff(), 'web')
        ->test(Show::class, ['submission' => $submission])
        ->call('applyStatus', 'accepted');

    Mail::assertSent(CoauthorPaymentInvitationMail::class, 1);
});

it('rend un co-auteur payé éligible à l’attestation de communication', function () {
    $edition = coauthorEdition();
    acceptedWithCoauthor($edition);

    $co = $edition->participants()->create(['first_name' => 'Ben', 'last_name' => 'Co', 'email' => 'co@example.tg', 'role' => 'coauthor', 'title' => 'Docteur']);
    $edition->payments()->create(['participant_id' => $co->id, 'amount' => 20000, 'status' => 'paid', 'gateway' => 'manual', 'method' => 'cash', 'paid_at' => now()]);

    Livewire::actingAs(adminStaff(), 'web')
        ->test(Attestations::class)
        ->set('edition_id', (string) $edition->id)
        ->set('type', 'communication')
        ->set('audience', 'communicants')
        ->call('issue');

    expect(Attestation::where('participant_id', $co->id)->where('type', 'communication')->exists())->toBeTrue();
});

it('n’associe pas un co-auteur non payé à l’attestation de communication', function () {
    $edition = coauthorEdition();
    acceptedWithCoauthor($edition);

    $co = $edition->participants()->create(['first_name' => 'Ben', 'last_name' => 'Co', 'email' => 'co@example.tg', 'role' => 'coauthor', 'title' => 'Docteur']);

    Livewire::actingAs(adminStaff(), 'web')
        ->test(Attestations::class)
        ->set('edition_id', (string) $edition->id)
        ->set('type', 'communication')
        ->set('audience', 'communicants')
        ->call('issue');

    expect(Attestation::where('participant_id', $co->id)->exists())->toBeFalse();
});
