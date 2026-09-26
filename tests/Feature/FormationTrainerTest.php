<?php

use App\Models\Formation;
use App\Models\FormationTrainer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

// openEdition()/verifiedAccount()/adminStaff() -> tests/Pest.php + AdminSubmissionWorkflowTest

it('ajoute un formateur avec grade, spécialité et photo', function () {
    Storage::fake('public');
    $edition = openEdition();
    $formation = Formation::create(['edition_id' => $edition->id, 'title' => 'F', 'fee' => 0, 'published' => true]);

    $this->actingAs(adminStaff(), 'web')->post(route('admin.formations.trainers.store', $formation), [
        'title' => 'Pr.', 'name' => 'Awa Kodjo', 'specialty' => 'Méthodologie', 'email' => 'awa@example.tg',
        'photo' => UploadedFile::fake()->image('t.jpg'),
    ]);

    $t = FormationTrainer::where('formation_id', $formation->id)->first();
    expect($t->title)->toBe('Pr.');
    expect($t->specialty)->toBe('Méthodologie');
    expect($t->photo_path)->not->toBeNull();
    Storage::disk('public')->assertExists($t->photo_path);
    expect($t->photoUrl())->toContain('/storage/');
});

it('change la photo d’un formateur (clic avatar)', function () {
    Storage::fake('public');
    $edition = openEdition();
    $formation = Formation::create(['edition_id' => $edition->id, 'title' => 'F', 'fee' => 0, 'published' => true]);
    $t = $formation->trainers()->create(['name' => 'Ben Co']);

    $this->actingAs(adminStaff(), 'web')->post(route('admin.formations.trainers.photo', [$formation, $t]), [
        'photo' => UploadedFile::fake()->image('new.png'),
    ]);

    expect($t->fresh()->photo_path)->not->toBeNull();
});

it('change la photo par AJAX et renvoie l’URL en JSON', function () {
    Storage::fake('public');
    $edition = openEdition();
    $formation = Formation::create(['edition_id' => $edition->id, 'title' => 'F', 'fee' => 0, 'published' => true]);
    $t = $formation->trainers()->create(['name' => 'Ben Co']);

    $res = $this->actingAs(adminStaff(), 'web')
        ->postJson(route('admin.formations.trainers.photo', [$formation, $t]), [
            'photo' => UploadedFile::fake()->image('new.png'),
        ]);

    $res->assertOk()->assertJson(['ok' => true]);
    expect($res->json('url'))->toContain('/storage/');
    expect($t->fresh()->photo_path)->not->toBeNull();
});

it('modifie les informations d’un formateur', function () {
    $edition = openEdition();
    $formation = Formation::create(['edition_id' => $edition->id, 'title' => 'F', 'fee' => 0, 'published' => true]);
    $t = $formation->trainers()->create(['name' => 'Ancien Nom']);

    $this->actingAs(adminStaff(), 'web')->put(route('admin.formations.trainers.update', [$formation, $t]), [
        'title' => 'Dr.', 'name' => 'Kofi Mensah', 'specialty' => 'Statistiques', 'email' => 'kofi@example.tg', 'is_moderator' => '1',
    ]);

    $t->refresh();
    expect($t->title)->toBe('Dr.');
    expect($t->name)->toBe('Kofi Mensah');
    expect($t->specialty)->toBe('Statistiques');
    expect($t->email)->toBe('kofi@example.tg');
    expect($t->is_moderator)->toBeTrue();
});

it('relie le formateur au compte quand l’e-mail modifié correspond', function () {
    $edition = openEdition();
    $formation = Formation::create(['edition_id' => $edition->id, 'title' => 'F', 'fee' => 0, 'published' => true]);
    $user = verifiedAccount();
    $t = $formation->trainers()->create(['name' => 'Sans compte']);

    $this->actingAs(adminStaff(), 'web')->put(route('admin.formations.trainers.update', [$formation, $t]), [
        'name' => 'Sans compte', 'email' => $user->email,
    ]);

    expect($t->fresh()->user_id)->toBe($user->id);
});

it('utilise la photo du compte lié à défaut de photo propre', function () {
    $edition = openEdition();
    $formation = Formation::create(['edition_id' => $edition->id, 'title' => 'F', 'fee' => 0, 'published' => true]);
    $user = verifiedAccount();
    $user->update(['photo_path' => 'photos/awa.jpg']);

    $t = $formation->trainers()->create(['name' => 'Awa', 'email' => $user->email, 'user_id' => $user->id]);

    expect($t->photoUrl())->toContain('awa.jpg');
});
