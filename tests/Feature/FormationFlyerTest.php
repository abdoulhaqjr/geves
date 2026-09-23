<?php

use App\Models\Formation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

// openEdition()/adminStaff() -> tests/Pest.php + AdminSubmissionWorkflowTest

it('téléverse un flyer pour la formation (disque public)', function () {
    Storage::fake('public');
    $edition = openEdition();
    $formation = Formation::create(['edition_id' => $edition->id, 'title' => 'Avec flyer', 'fee' => 0, 'published' => true]);

    $this->actingAs(adminStaff(), 'web')->put(route('admin.formations.update', $formation), [
        'title' => 'Avec flyer', 'fee' => 0,
        'flyer' => UploadedFile::fake()->image('flyer.jpg'),
    ]);

    $formation->refresh();
    expect($formation->flyer_path)->not->toBeNull();
    Storage::disk('public')->assertExists($formation->flyer_path);
    expect($formation->flyerUrl())->toContain('/storage/');
});

it('retire le flyer', function () {
    Storage::fake('public');
    $edition = openEdition();
    $formation = Formation::create(['edition_id' => $edition->id, 'title' => 'X', 'fee' => 0, 'published' => true, 'flyer_path' => 'formations/old.jpg']);
    Storage::disk('public')->put('formations/old.jpg', 'x');

    $this->actingAs(adminStaff(), 'web')->put(route('admin.formations.update', $formation), [
        'title' => 'X', 'fee' => 0, 'flyer_remove' => '1',
    ]);

    expect($formation->fresh()->flyer_path)->toBeNull();
    Storage::disk('public')->assertMissing('formations/old.jpg');
});
