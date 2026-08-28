<?php

use App\Actions\NotifyCoauthorsForPayment;
use App\Mail\CoauthorPaymentInvitationMail;
use App\Models\Attestation;
use App\Models\Edition;
use App\Models\Email;
use App\Models\Participant;
use App\Models\Payment;
use App\Models\Submission;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

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

    $this->get(route('public.coauthor', $edition->slug).'?email=co@example.tg')
        ->assertOk()
        ->assertSee('AA-001')
        ->assertSee('Docteur')
        ->assertSee('20 000'); // tarif du grade dans la grille (montant calculé côté client)
});

it('n’associe rien à un e-mail inconnu', function () {
    $edition = coauthorEdition();
    acceptedWithCoauthor($edition);

    $this->get(route('public.coauthor', $edition->slug).'?email=personne@example.tg')
        ->assertSee('Aucune communication');
});

it('expose la grille tarifaire pour recalculer le montant selon le grade (client)', function () {
    $edition = coauthorEdition();
    acceptedWithCoauthor($edition);

    // Le montant est recalculé côté client (Alpine) depuis la grille exposée.
    $this->get(route('public.coauthor', $edition->slug).'?email=co@example.tg')
        ->assertOk()
        ->assertSee('Doctorant')
        ->assertSee('10 000')
        ->assertSee('20 000');
});

it('crée un participant co-auteur et un paiement en attente marqué flow=coauthor', function () {
    $edition = coauthorEdition();
    acceptedWithCoauthor($edition);

    $this->post(route('public.coauthor.pay', $edition->slug), [
        'email' => 'co@example.tg',
        'grade' => 'Docteur',
        'gateway' => 'manual',
    ]);

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

    $this->get(route('public.coauthor', $edition->slug).'?email=co@example.tg')
        ->assertSee('Frais déjà réglés');
});

it('notifie les co-auteurs d’une communication acceptée et journalise', function () {
    Mail::fake();
    $edition = coauthorEdition();
    $submission = acceptedWithCoauthor($edition);

    $count = app(NotifyCoauthorsForPayment::class)->handle($submission);

    expect($count)->toBe(1);
    Mail::assertQueued(CoauthorPaymentInvitationMail::class, fn ($m) => $m->hasTo('co@example.tg'));
    expect(Email::where('template', 'coauthor_payment')->where('recipient', 'co@example.tg')->count())->toBe(1);
});

it('ne notifie pas les co-auteurs si la communication n’est pas acceptée', function () {
    Mail::fake();
    $edition = coauthorEdition();
    $submission = acceptedWithCoauthor($edition, status: 'submitted');

    expect(app(NotifyCoauthorsForPayment::class)->handle($submission))->toBe(0);
    Mail::assertNothingQueued();
});

it('déclenche la notification des co-auteurs depuis l’acceptation admin', function () {
    Mail::fake();
    $edition = coauthorEdition();
    $submission = acceptedWithCoauthor($edition, status: 'submitted');

    $this->actingAs(adminStaff(), 'web')
        ->post(route('admin.submissions.status', $submission), ['status' => 'accepted']);

    Mail::assertQueued(CoauthorPaymentInvitationMail::class, 1);
});

it('rend un co-auteur payé éligible à l’attestation de communication', function () {
    $edition = coauthorEdition();
    acceptedWithCoauthor($edition);

    $co = $edition->participants()->create(['first_name' => 'Ben', 'last_name' => 'Co', 'email' => 'co@example.tg', 'role' => 'coauthor', 'title' => 'Docteur']);
    $edition->payments()->create(['participant_id' => $co->id, 'amount' => 20000, 'status' => 'paid', 'gateway' => 'manual', 'method' => 'cash', 'paid_at' => now()]);

    $this->actingAs(adminStaff(), 'web')->post(route('admin.documents.attestations.issue'), [
        'edition_id' => (string) $edition->id,
        'type' => 'communication',
    ]);

    expect(Attestation::where('participant_id', $co->id)->where('type', 'communication')->exists())->toBeTrue();
});

it('n’associe pas un co-auteur non payé à l’attestation de communication', function () {
    $edition = coauthorEdition();
    acceptedWithCoauthor($edition);

    $co = $edition->participants()->create(['first_name' => 'Ben', 'last_name' => 'Co', 'email' => 'co@example.tg', 'role' => 'coauthor', 'title' => 'Docteur']);

    $this->actingAs(adminStaff(), 'web')->post(route('admin.documents.attestations.issue'), [
        'edition_id' => (string) $edition->id,
        'type' => 'communication',
    ]);

    expect(Attestation::where('participant_id', $co->id)->exists())->toBeFalse();
});
