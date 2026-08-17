<?php

use App\Livewire\Account\Submissions\Create;
use App\Models\Submission;
use App\Support\Html;
use Livewire\Livewire;

// openEdition(), verifiedAccount() -> AccountSubmissionTest

it('nettoie le HTML en gardant les balises autorisées sans attribut', function () {
    $dirty = '<p onclick="x()">Bonjour <strong>monde</strong></p><script>alert(1)</script><a href="http://evil">lien</a>';
    $clean = Html::clean($dirty);

    expect($clean)->toContain('<p>')
        ->toContain('<strong>monde</strong>')
        ->not->toContain('onclick')
        ->not->toContain('<script>')
        ->not->toContain('<a')
        ->not->toContain('href');
});

it('convertit le HTML en texte brut avec sauts de ligne', function () {
    $text = Html::toText('<p>Première ligne</p><ul><li>a</li><li>b</li></ul>');

    expect($text)->toContain('Première ligne')->toContain('- a')->toContain('- b');
    expect($text)->not->toContain('<');
});

it('compte les caractères visibles hors balises', function () {
    expect(Html::textLength('<p><strong>abc</strong> def</p>'))->toBe(7); // "abc def"
});

it('rend le texte simple en échappant et en gardant les sauts de ligne', function () {
    expect((string) Html::render("a\nb"))->toContain('<br');
    expect((string) Html::render('<script>bad</script>'))->not->toContain('<script>');
    expect((string) Html::render('<p>ok <strong>gras</strong></p>'))->toContain('<strong>gras</strong>');
});

it('stocke un résumé enrichi assaini', function () {
    $edition = openEdition();
    $account = verifiedAccount();

    Livewire::actingAs($account, 'account')
        ->test(Create::class, ['slug' => $edition->slug])
        ->set('title', 'Ma communication enrichie')
        ->set('track', 'Santé')
        ->set('abstract', '<p onclick="x">Contexte <strong>et</strong> objectifs détaillés du travail scientifique présenté ici.</p><script>alert(1)</script>')
        ->call('submit')
        ->assertHasNoErrors();

    $sub = Submission::latest('id')->first();
    expect($sub->abstract)->toContain('<strong>et</strong>')
        ->not->toContain('onclick')
        ->not->toContain('<script>');
});

it('refuse un résumé trop court même en comptant hors balises', function () {
    $edition = openEdition();
    $account = verifiedAccount();

    Livewire::actingAs($account, 'account')
        ->test(Create::class, ['slug' => $edition->slug])
        ->set('title', 'Court')
        ->set('track', 'Santé')
        ->set('abstract', '<p><strong>Trop court</strong></p>')
        ->call('submit')
        ->assertHasErrors('abstract');
});
