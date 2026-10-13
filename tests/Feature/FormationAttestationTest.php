<?php

use App\Actions\IssueAttestation;
use App\Documents\AttestationVars;
use App\Documents\DocumentVars;
use App\Documents\LayoutRenderer;
use App\Documents\LayoutSanitizer;
use App\Models\Attestation;
use App\Models\Formation;
use App\Models\FormationEnrollment;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

// openEdition()/verifiedAccount()/adminStaff() -> tests/Pest.php + AdminSubmissionWorkflowTest

function enrolledFormation(Formation $formation, User $user, string $status): Participant
{
    $participant = $formation->event->participants()->create([
        'first_name' => $user->first_name ?: 'X', 'last_name' => $user->last_name ?: 'Y',
        'email' => $user->email, 'role' => 'trainee', 'user_id' => $user->id,
    ]);
    FormationEnrollment::create([
        'formation_id' => $formation->id, 'event_id' => $formation->event_id,
        'user_id' => $user->id, 'participant_id' => $participant->id, 'status' => $status, 'amount' => $formation->fee,
    ]);

    return $participant;
}

it('émet participation aux inscrits réglés et animation aux formateurs, en nommant la formation', function () {
    $event = openEdition();
    $formation = Formation::create(['event_id' => $event->id, 'title' => 'Rédaction scientifique', 'fee' => 10000, 'published' => true]);

    $participant = enrolledFormation($formation, verifiedAccount(), 'paid');
    $formation->trainers()->create(['name' => 'Pr. Ben Co', 'email' => 'ben@example.tg', 'is_moderator' => true]);

    $this->actingAs(adminStaff(), 'web')->post(route('admin.formations.attestations', $formation));

    // Participation (inscrit réglé) nommant la formation.
    $att = Attestation::where('type', 'formation')->where('participant_id', $participant->id)->first();
    expect($att)->not->toBeNull();
    expect((int) $att->formation_id)->toBe($formation->id);
    expect($att->meta['formation_title'])->toBe('Rédaction scientifique');

    // Animation (formateur -> participant support créé par e-mail).
    $trainerP = Participant::where('event_id', $event->id)->where('email', 'ben@example.tg')->first();
    expect($trainerP)->not->toBeNull();
    $anim = Attestation::where('type', 'formation_animation')->where('participant_id', $trainerP->id)->first();
    expect($anim)->not->toBeNull();
    expect((int) $anim->formation_id)->toBe($formation->id);
});

it('n’émet pas d’attestation à un inscrit non réglé d’une formation payante', function () {
    $event = openEdition();
    $formation = Formation::create(['event_id' => $event->id, 'title' => 'Payante', 'fee' => 10000, 'published' => true]);
    $participant = enrolledFormation($formation, verifiedAccount(), 'registered');

    $this->actingAs(adminStaff(), 'web')->post(route('admin.formations.attestations', $formation));

    expect(Attestation::where('type', 'formation')->where('participant_id', $participant->id)->exists())->toBeFalse();
});

it('émet l’attestation à un inscrit d’une formation gratuite', function () {
    $event = openEdition();
    $formation = Formation::create(['event_id' => $event->id, 'title' => 'Gratuite', 'fee' => 0, 'published' => true]);
    $participant = enrolledFormation($formation, verifiedAccount(), 'registered');

    $this->actingAs(adminStaff(), 'web')->post(route('admin.formations.attestations', $formation));

    expect(Attestation::where('type', 'formation')->where('participant_id', $participant->id)->exists())->toBeTrue();
});

it('expose le type d’attestation d’animation de formation', function () {
    expect(Attestation::TYPES)->toHaveKey('formation_animation');
});

it('téléverse la signature d’un formateur', function () {
    Storage::fake('public');
    $event = openEdition(['formation_enabled' => true]);
    $formation = Formation::create(['event_id' => $event->id, 'title' => 'Atelier', 'fee' => 0, 'published' => true]);
    $trainer = $formation->trainers()->create(['name' => 'Awa Kodjo', 'title' => 'Pr.']);

    $res = $this->actingAs(adminStaff(), 'web')
        ->postJson(route('admin.formations.trainers.signature', [$formation, $trainer]), [
            'signature' => UploadedFile::fake()->image('sign.png'),
        ]);

    $res->assertOk()->assertJson(['ok' => true]);
    expect($trainer->fresh()->signature_path)->not->toBeNull();
    expect($trainer->fresh()->signatureUrl())->toContain('/storage/');
    expect($trainer->fresh()->displayName())->toBe('Pr. Awa Kodjo');
});

it('fige le thème et les signatures des formateurs sur l’attestation de formation', function () {
    Storage::fake('public');
    $event = openEdition(['formation_enabled' => true]);
    $formation = Formation::create([
        'event_id' => $event->id, 'title' => 'Rédaction scientifique',
        'theme' => 'Publier dans une revue indexée', 'fee' => 0, 'published' => true,
    ]);
    $trainer = $formation->trainers()->create(['name' => 'Awa Kodjo', 'title' => 'Pr.', 'specialty' => 'Spécialiste en bibliométrie']);
    $trainer->update(['signature_path' => 'formation-signatures/sign.png']);

    $user = verifiedAccount();
    $participant = enrolledFormation($formation, $user, 'registered');

    $attestation = app(IssueAttestation::class)->handle($participant, 'formation', null, $formation);

    expect($attestation->meta['formation_title'])->toBe('Rédaction scientifique');
    expect($attestation->meta['formation_theme'])->toBe('Publier dans une revue indexée');
    expect($attestation->meta['formation_trainers'])->toBe(['Pr. Awa Kodjo']);
    expect($attestation->meta['formation_signatures'][0]['name'])->toBe('Pr. Awa Kodjo');
    // La fonction / spécialité est figée à côté du nom (affichée sous le nom).
    expect($attestation->meta['formation_signatures'][0]['role'])->toBe('Spécialiste en bibliométrie');
    expect($attestation->meta['formation_signatures'][0]['src'])->toContain('sign.png');

    // Elle apparaît bien dans le rendu, sous le nom du formateur.
    $html = LayoutRenderer::render(
        LayoutSanitizer::default('attestation', 'formation'),
        DocumentVars::expand(AttestationVars::for($attestation, 'https://example.test/attestation/x'))
    );
    expect($html)->toContain('Spécialiste en bibliométrie')->toContain('Pr. Awa Kodjo');
});

it('plafonne à DEUX les signatures de formateurs sur l’attestation', function () {
    Storage::fake('public');
    $event = openEdition(['formation_enabled' => true]);
    $formation = Formation::create(['event_id' => $event->id, 'title' => 'T', 'theme' => 'X', 'fee' => 0, 'published' => true]);
    foreach (['A', 'B', 'C'] as $i => $n) {
        $t = $formation->trainers()->create(['name' => "Formateur $n", 'title' => 'Dr']);
        $t->update(['signature_path' => "formation-signatures/s{$i}.png"]);
    }
    $participant = enrolledFormation($formation, verifiedAccount(), 'registered');

    $attestation = app(IssueAttestation::class)->handle($participant, 'formation', null, $formation);

    expect($attestation->meta['formation_signatures'])->toHaveCount(2);
});

it('rend l’attestation de formation sur le modèle officiel (cadre dédié, première personne, signatures)', function () {
    $layout = LayoutSanitizer::default('attestation', 'formation');

    expect($layout['bg_image'])->toBe('/img/attestations/attestation-formation-bg.png');
    $texts = collect($layout['elements'])->pluck('text')->filter()->implode(' | ');
    expect($texts)
        ->toContain('ATTESTATION DE FORMATION')
        ->toContain('Je soussigné, [signatory.name], [signatory.role], atteste que')
        ->toContain('[formation.title]')
        ->toContain('Référence de l’attestation');
    expect(collect($layout['elements'])->firstWhere('type', 'signatures'))->not->toBeNull();
    expect(collect($layout['elements'])->firstWhere('type', 'qr'))->not->toBeNull();
    // Titre de formation plus grand (Times 38), distinct du cadre kente des autres.
    $title = collect($layout['elements'])->firstWhere('text', 'ATTESTATION DE FORMATION');
    expect($title['font'])->toBe('times');
    expect($title['size'])->toBe(38);

    $html = LayoutRenderer::render($layout, DocumentVars::expand([
        'name' => 'Jean Test', 'formation' => 'la rédaction scientifique', 'event' => 'UK 2026',
        'dates' => 'du 06 au 10 octobre 2026', 'location' => 'Kara', 'theme' => 'Thème X',
        'signatory' => 'Prof. Padabô KADOUZA', 'signatory_role' => "Président du Comité d'Organisation",
        'date' => '13 octobre 2026', 'ref' => 'uuid-x', 'verify_url' => 'https://example.test/attestation/uuid-x',
        'signatures' => [
            ['name' => 'Dr A. Mensah', 'src' => 'https://example.test/s1.png'],
            ['name' => 'Dr B. Koffi', 'src' => 'https://example.test/s2.png'],
        ],
    ]));

    expect($html)
        ->toContain('ATTESTATION DE FORMATION')
        ->toContain('la rédaction scientifique')
        ->toContain('Dr A. Mensah')
        ->toContain('Dr B. Koffi')
        ->toContain('Prof. Padabô KADOUZA');
});
