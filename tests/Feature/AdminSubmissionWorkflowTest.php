<?php

use App\Mail\SubmissionStatusMail;
use App\Models\Email;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

// adminStaff() et seededSubmission() sont définis dans tests/Pest.php (fabriques partagées).

it('affiche la liste des soumissions au staff habilité', function () {
    $staff = adminStaff();
    seededSubmission();

    $this->actingAs($staff, 'web')
        ->get(route('admin.submissions.index'))
        ->assertOk()
        ->assertSee('Innovation durable')
        ->assertSee('26-001');
});

it('accepte directement une soumission déposée, sans relecture préalable', function () {
    Mail::fake();
    $staff = adminStaff();
    $submission = seededSubmission('submitted'); // aucune relecture lancée

    expect($submission->reviews()->count())->toBe(0);

    $this->actingAs($staff, 'web')->post(route('admin.submissions.status', $submission), [
        'status' => 'accepted',
    ])->assertSessionHasNoErrors();

    expect($submission->fresh()->status)->toBe('accepted');
    Mail::assertQueued(SubmissionStatusMail::class, fn ($mail) => $mail->statusKey === 'accepted');
});

it('accepte une soumission, notifie l’auteur et journalise l’e-mail', function () {
    Mail::fake();
    $staff = adminStaff();
    $submission = seededSubmission('under_review');

    $this->actingAs($staff, 'web')->post(route('admin.submissions.status', $submission), [
        'status' => 'accepted',
        'note' => 'Félicitations, présentation orale prévue.',
    ])->assertSessionHasNoErrors();

    expect($submission->fresh()->status)->toBe('accepted');

    Mail::assertQueued(SubmissionStatusMail::class, function ($mail) {
        return $mail->statusKey === 'accepted' && $mail->hasTo('awa@example.tg');
    });

    $log = Email::where('template', 'submission_accepted')->first();
    expect($log)->not->toBeNull();
    expect($log->recipient)->toBe('awa@example.tg');
    expect($log->status)->toBe('queued');
});

it('refuse un statut non autorisé', function () {
    $staff = adminStaff();
    $submission = seededSubmission();

    $this->actingAs($staff, 'web')
        ->post(route('admin.submissions.status', $submission), ['status' => 'submitted'])
        ->assertStatus(400);
});

it('protège les soumissions admin sans la permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'web')->get(route('admin.submissions.index'))->assertForbidden();
});

it('rend le Mailable de statut (le template markdown compile)', function () {
    $submission = seededSubmission();

    $html = (new SubmissionStatusMail($submission, 'accepted', 'Bravo !'))->render();

    expect($html)->toContain('Communication acceptée');
    expect($html)->toContain('26-001');
    expect($html)->toContain('Bravo');
});

it('envoie un accusé de réception à la création d’une soumission', function () {
    Mail::fake();
    $event = openEdition();
    $account = verifiedAccount();

    $this->actingAs($account, 'account')
        ->post(route('account.submissions.store', $event->slug), [
            'title' => 'Ma communication',
            'track' => 'Sciences',
            'abstract' => str_repeat('a', 60),
        ])
        ->assertSessionHasNoErrors();

    Mail::assertQueued(SubmissionStatusMail::class, fn ($mail) => $mail->statusKey === 'submitted');
});

it('trie les soumissions par nom d’auteur dans les deux sens', function () {
    $event = openEdition();
    foreach ([['Zoe', 'Zannou'], ['Ama', 'Adjo'], ['Kofi', 'Mensah']] as [$first, $last]) {
        $p = $event->participants()->create([
            'first_name' => $first, 'last_name' => $last,
            'email' => strtolower($first).'@test.tg', 'role' => 'author',
        ]);
        $event->submissions()->create([
            'participant_id' => $p->id, 'ref_code' => '26-'.substr($last, 0, 3),
            'title' => 'Comm '.$last, 'abstract' => 'x', 'track' => 'Sciences',
            'type' => 'oral', 'status' => 'submitted', 'authors' => [],
        ]);
    }

    $order = function (array $query) use ($event) {
        $html = $this->actingAs(adminStaff(), 'web')
            ->get(route('admin.submissions.index', $query + ['event_id' => $event->id]))
            ->assertOk()->getContent();

        return collect(['Adjo', 'Mensah', 'Zannou'])
            ->sortBy(fn ($n) => strpos($html, 'Comm '.$n))->values()->all();
    };

    expect($order(['sort' => 'author', 'dir' => 'asc']))->toBe(['Adjo', 'Mensah', 'Zannou']);
    expect($order(['sort' => 'author', 'dir' => 'desc']))->toBe(['Zannou', 'Mensah', 'Adjo']);
});

it('garde une pagination correcte quand on trie par auteur (jointure)', function () {
    $event = openEdition();
    foreach (range(1, 20) as $i) {
        $p = $event->participants()->create([
            'first_name' => 'A'.$i, 'last_name' => 'Nom'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
            'email' => "a{$i}@test.tg", 'role' => 'author',
        ]);
        $event->submissions()->create([
            'participant_id' => $p->id, 'ref_code' => '26-'.$i, 'title' => 'Comm '.$i,
            'abstract' => 'x', 'track' => 'Sciences', 'type' => 'oral', 'status' => 'submitted', 'authors' => [],
        ]);
    }

    // La jointure ne doit ni dupliquer les lignes ni fausser le total.
    $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.submissions.index', ['event_id' => $event->id, 'sort' => 'author', 'dir' => 'asc']))
        ->assertOk()
        ->assertSee('NOM01')
        ->assertDontSee('NOM20'); // 20 lignes, 15 par page
});
