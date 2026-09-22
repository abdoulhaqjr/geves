<?php

use App\Models\Colloque;
use App\Models\Edition;
use App\Models\Participant;
use App\Models\Payment;
use App\Models\Submission;
use App\Models\User;

it('crée un colloque et une édition (avec colloque parent)', function () {
    $edition = Edition::factory()->create();

    expect($edition->exists)->toBeTrue();
    expect($edition->colloque)->toBeInstanceOf(Colloque::class);
    expect($edition->status)->toBe('open');
});

it('gère les états d’édition', function () {
    expect(Edition::factory()->draft()->create()->status)->toBe('draft');
    expect(Edition::factory()->closed()->create()->submission_open)->toBeFalse();
});

it('crée un compte vérifié, hache le mot de passe, et gère les états', function () {
    $account = User::factory()->create();

    expect($account->hasVerifiedEmail())->toBeTrue();
    expect($account->password)->not->toBe('password'); // haché par le cast
    expect(User::factory()->unverified()->create()->hasVerifiedEmail())->toBeFalse();
    expect(User::factory()->incomplete()->create()->affiliation)->toBeNull();
});

it('crée participant, soumission et paiement liés', function () {
    $edition = Edition::factory()->create();
    $participant = Participant::factory()->for($edition)->create();

    $submission = Submission::factory()
        ->for($edition)->for($participant)->accepted()->create();
    $payment = Payment::factory()
        ->for($edition)->for($participant)->paid()->create();

    expect($participant->edition->is($edition))->toBeTrue();
    expect($submission->status)->toBe('accepted');
    expect($submission->revision_of)->toBe(0);
    expect($payment->status)->toBe('paid');
    expect($payment->receipt_no)->not->toBeNull();
});

it('applique les états de rôle du participant', function () {
    expect(Participant::factory()->reviewer()->create()->role)->toBe('reviewer');
    expect(Participant::factory()->coauthor()->create()->role)->toBe('coauthor');
});
