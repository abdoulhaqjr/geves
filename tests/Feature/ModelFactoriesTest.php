<?php

use App\Models\EventSeries;
use App\Models\Event;
use App\Models\Participant;
use App\Models\Payment;
use App\Models\Submission;
use App\Models\User;

it('crée un colloque et une événement (avec colloque parent)', function () {
    $event = Event::factory()->create();

    expect($event->exists)->toBeTrue();
    expect($event->series)->toBeInstanceOf(EventSeries::class);
    expect($event->status)->toBe('open');
});

it('gère les états d’événement', function () {
    expect(Event::factory()->draft()->create()->status)->toBe('draft');
    expect(Event::factory()->closed()->create()->submission_open)->toBeFalse();
});

it('crée un compte vérifié, hache le mot de passe, et gère les états', function () {
    $account = User::factory()->create();

    expect($account->hasVerifiedEmail())->toBeTrue();
    expect($account->password)->not->toBe('password'); // haché par le cast
    expect(User::factory()->unverified()->create()->hasVerifiedEmail())->toBeFalse();
    expect(User::factory()->incomplete()->create()->affiliation)->toBeNull();
});

it('crée participant, soumission et paiement liés', function () {
    $event = Event::factory()->create();
    $participant = Participant::factory()->for($event)->create();

    $submission = Submission::factory()
        ->for($event)->for($participant)->accepted()->create();
    $payment = Payment::factory()
        ->for($event)->for($participant)->paid()->create();

    expect($participant->event->is($event))->toBeTrue();
    expect($submission->status)->toBe('accepted');
    expect($submission->revision_of)->toBe(0);
    expect($payment->status)->toBe('paid');
    expect($payment->receipt_no)->not->toBeNull();
});

it('applique les états de rôle du participant', function () {
    expect(Participant::factory()->reviewer()->create()->role)->toBe('reviewer');
    expect(Participant::factory()->coauthor()->create()->role)->toBe('coauthor');
});
