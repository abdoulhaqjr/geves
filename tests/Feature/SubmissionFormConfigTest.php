<?php

use App\Livewire\Account\Submissions\Create;
use App\Livewire\Admin\Editions\SubmissionFormEditor;
use App\Models\Submission;
use App\Support\SubmissionFormConfig;
use Livewire\Livewire;

// adminStaff() -> AdminSubmissionWorkflowTest ; openEdition()/verifiedAccount() -> AccountSubmissionTest

it('applique les valeurs par défaut sans configuration', function () {
    $edition = openEdition();
    $config = new SubmissionFormConfig($edition);

    expect($config->label('title'))->toBe('Titre');
    expect($config->enabled('keywords'))->toBeTrue();
    expect($config->required('keywords'))->toBeFalse();
    expect($config->required('track'))->toBeTrue();
    expect($config->abstractMin())->toBe(50);
    expect($config->customFields())->toBe([]);
});

it('lit une configuration personnalisée depuis settings', function () {
    $edition = openEdition(['submission_form' => [
        'fields' => [
            'keywords' => ['label' => 'Mots-clés libres', 'enabled' => false],
            'abstract' => ['min' => 100, 'max' => 500],
        ],
        'custom' => [
            ['key' => 'orcid', 'label' => 'ORCID', 'type' => 'text', 'required' => true],
        ],
    ]]);
    $config = new SubmissionFormConfig($edition);

    expect($config->label('keywords'))->toBe('Mots-clés libres');
    expect($config->enabled('keywords'))->toBeFalse();
    expect($config->abstractMin())->toBe(100);
    expect($config->abstractMax())->toBe(500);
    expect($config->customFields())->toHaveCount(1);
    expect($config->customFields()[0]['key'])->toBe('orcid');
});

it('garantit un max de résumé supérieur au min', function () {
    $edition = openEdition(['submission_form' => ['fields' => ['abstract' => ['min' => 200, 'max' => 50]]]]);
    $config = new SubmissionFormConfig($edition);

    expect($config->abstractMin())->toBe(200);
    expect($config->abstractMax())->toBeGreaterThan(200);
});

it('enregistre la configuration depuis l’éditeur admin', function () {
    $staff = adminStaff();
    $edition = openEdition();

    Livewire::actingAs($staff, 'web')
        ->test(SubmissionFormEditor::class, ['edition' => $edition])
        ->set('fields.title.label', 'Intitulé de la communication')
        ->set('fields.keywords.enabled', false)
        ->set('fields.abstract.min', 120)
        ->call('addCustom')
        ->set('custom.0.label', 'Numéro ORCID')
        ->set('custom.0.type', 'text')
        ->set('custom.0.required', true)
        ->call('save')
        ->assertHasNoErrors();

    $edition->refresh();
    $config = new SubmissionFormConfig($edition);

    expect($config->label('title'))->toBe('Intitulé de la communication');
    expect($config->enabled('keywords'))->toBeFalse();
    expect($config->abstractMin())->toBe(120);
    expect($config->customFields()[0]['label'])->toBe('Numéro ORCID');
});

it('convertit les options de liste (une par ligne) en tableau', function () {
    $staff = adminStaff();
    $edition = openEdition();

    Livewire::actingAs($staff, 'web')
        ->test(SubmissionFormEditor::class, ['edition' => $edition])
        ->call('addCustom')
        ->set('custom.0.label', 'Session préférée')
        ->set('custom.0.type', 'select')
        ->set('custom.0.options_text', "Matin\nAprès-midi\n")
        ->call('save');

    $edition->refresh();
    $field = (new SubmissionFormConfig($edition))->customFields()[0];

    expect($field['type'])->toBe('select');
    expect($field['options'])->toBe(['Matin', 'Après-midi']);
});

it('respecte le libellé personnalisé et masque un champ désactivé sur le formulaire public', function () {
    $edition = openEdition(['submission_form' => ['fields' => [
        'title' => ['label' => 'Intitulé unique'],
        'keywords' => ['enabled' => false],
    ]]]);
    $account = verifiedAccount();

    Livewire::actingAs($account, 'account')
        ->test(Create::class, ['slug' => $edition->slug])
        ->assertSee('Intitulé unique')
        ->assertDontSee('Mots-clés');
});

it('valide et enregistre les champs personnalisés à la soumission', function () {
    $edition = openEdition(['submission_form' => ['custom' => [
        ['key' => 'orcid', 'label' => 'ORCID', 'type' => 'text', 'required' => true],
        ['key' => 'session', 'label' => 'Session', 'type' => 'select', 'required' => false, 'options' => ['Matin', 'Après-midi']],
    ]]]);
    $account = verifiedAccount();

    // Requis manquant → erreur.
    Livewire::actingAs($account, 'account')
        ->test(Create::class, ['slug' => $edition->slug])
        ->set('title', 'Ma communication')
        ->set('abstract', str_repeat('a', 60))
        ->call('submit')
        ->assertHasErrors('customAnswers.orcid');

    // Renseigné → enregistré dans meta.
    Livewire::actingAs($account, 'account')
        ->test(Create::class, ['slug' => $edition->slug])
        ->set('title', 'Ma communication')
        ->set('abstract', str_repeat('a', 60))
        ->set('customAnswers.orcid', '0000-0002-1825-0097')
        ->set('customAnswers.session', 'Matin')
        ->call('submit')
        ->assertHasNoErrors();

    $submission = Submission::where('edition_id', $edition->id)->latest('id')->first();
    $custom = collect($submission->meta['custom_fields'] ?? []);

    expect($custom->firstWhere('key', 'orcid')['value'])->toBe('0000-0002-1825-0097');
    expect($custom->firstWhere('key', 'session')['value'])->toBe('Matin');
});

it('impose la limite minimale de caractères du résumé configurée', function () {
    $edition = openEdition(['submission_form' => ['fields' => ['abstract' => ['min' => 200, 'max' => 8000]]]]);
    $account = verifiedAccount();

    Livewire::actingAs($account, 'account')
        ->test(Create::class, ['slug' => $edition->slug])
        ->set('title', 'Titre')
        ->set('abstract', str_repeat('a', 100))
        ->call('submit')
        ->assertHasErrors('abstract');
});
