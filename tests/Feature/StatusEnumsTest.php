<?php

use App\Enums\PaymentStatus;
use App\Enums\SubmissionStatus;
use App\Models\Payment;
use App\Models\Submission;

it('expose libellés et pastilles depuis SubmissionStatus', function () {
    expect(SubmissionStatus::Accepted->label())->toBe('Acceptée');
    expect(SubmissionStatus::Accepted->pill())->toBe('ok');
    expect(SubmissionStatus::Rejected->pill())->toBe('err');
    expect(SubmissionStatus::values())->toContain('submitted', 'accepted');
    expect(SubmissionStatus::options()['under_review'])->toBe('En relecture');
    expect(SubmissionStatus::pills()['revision'])->toBe('warn');
});

it('expose libellés et pastilles depuis PaymentStatus', function () {
    expect(PaymentStatus::Paid->label())->toBe('Réglé');
    expect(PaymentStatus::Paid->pill())->toBe('ok');
    expect(PaymentStatus::from('failed')->pill())->toBe('err');
    expect(PaymentStatus::options())->toHaveKeys(['pending', 'paid', 'refunded', 'failed']);
});

it('les modèles délèguent aux enums via leurs helpers', function () {
    $s = new Submission(['status' => 'accepted']);
    expect($s->statusLabel())->toBe('Acceptée');
    expect($s->statusPill())->toBe('ok');
    expect($s->statusEnum())->toBe(SubmissionStatus::Accepted);

    $p = new Payment(['status' => 'pending']);
    expect($p->statusLabel())->toBe('En attente');
    expect($p->statusPill())->toBe('warn');
    expect($p->statusEnum())->toBe(PaymentStatus::Pending);
});

it('retombe proprement sur un statut inconnu', function () {
    $s = new Submission(['status' => 'weird']);
    expect($s->statusEnum())->toBeNull();
    expect($s->statusLabel())->toBe('weird');
    expect($s->statusPill())->toBe('muted');
});

it('les listes statiques du modèle reflètent l’enum', function () {
    expect(Submission::statuses())->toBe(SubmissionStatus::options());
    expect(Payment::statusPills())->toBe(PaymentStatus::pills());
});
