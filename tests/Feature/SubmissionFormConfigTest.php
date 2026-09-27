<?php

use App\Models\Submission;
use App\Support\SubmissionFormConfig;

// adminStaff() -> AdminSubmissionWorkflowTest ; openEdition()/verifiedAccount() -> AccountSubmissionTest

it('applique les valeurs par défaut sans configuration', function () {
    $edition = openEdition();
    $config = new SubmissionFormConfig($edition);

    expect($config->label('title'))->toBe('Titre');
    expect($config->enabled('keywords'))->toBeTrue();
    expect($config->required('keywords'))->toBeFalse();
    expect($config->required('track'))->toBeTrue();
    expect($config->abstractMin())->toBe(0);       // pas de minimum par défaut (en mots)
    expect($config->abstractMax())->toBe(300);     // 300 mots max par défaut
    expect($config->keywordsMax())->toBe(6);
    expect($config->customFields())->toBe([]);
});

it('lit une configuration personnalisée depuis settings', function () {
    $edition = openEdition(['submission_form' => [
        'fields' => [
            'keywords' => ['label' => 'Mots-clés libres', 'enabled' => false],
        ],
        'rules' => ['word_min' => 100, 'word_limit' => 500, 'keywords_min' => 4, 'keywords_max' => 8],
        'custom' => [
            ['key' => 'orcid', 'label' => 'ORCID', 'type' => 'text', 'required' => true],
        ],
    ]]);
    $config = new SubmissionFormConfig($edition);

    expect($config->label('keywords'))->toBe('Mots-clés libres');
    expect($config->enabled('keywords'))->toBeFalse();
    // Limites du résumé désormais en MOTS (via les règles).
    expect($config->abstractMin())->toBe(100);
    expect($config->abstractMax())->toBe(500);
    expect($config->keywordsMin())->toBe(4);
    expect($config->keywordsMax())->toBe(8);
    expect($config->customFields())->toHaveCount(1);
    expect($config->customFields()[0]['key'])->toBe('orcid');
});

it('garantit un max de résumé (mots) supérieur au min', function () {
    $edition = openEdition(['submission_form' => ['rules' => ['word_min' => 200, 'word_limit' => 50]]]);
    $config = new SubmissionFormConfig($edition);

    expect($config->abstractMin())->toBe(200);
    expect($config->abstractMax())->toBeGreaterThan(200);
});

it('enregistre la configuration depuis l’éditeur admin', function () {
    $staff = adminStaff();
    $edition = openEdition();

    $this->actingAs($staff, 'web')->put(route('admin.editions.form.update', $edition), [
        'fields' => [
            'title' => ['label' => 'Intitulé de la communication'],
            'abstract' => ['label' => 'Résumé'],
            // keywords : case « Afficher » non cochée → désactivé.
        ],
        'custom' => [
            ['key' => 'custom_x', 'label' => 'Numéro ORCID', 'type' => 'text', 'required' => '1'],
        ],
    ])->assertSessionHasNoErrors();

    $edition->refresh();
    $config = new SubmissionFormConfig($edition);

    expect($config->label('title'))->toBe('Intitulé de la communication');
    expect($config->enabled('keywords'))->toBeFalse();
    expect($config->customFields()[0]['label'])->toBe('Numéro ORCID');
});

it('enregistre les règles & limites depuis l’onglet Options', function () {
    $staff = adminStaff();
    $edition = openEdition();

    // ⚠️ Endpoint dédié : le PUT de l'éditeur reconstruit champs et champs
    // personnalisés, il ne touche plus aux règles.
    $this->actingAs($staff, 'web')->put(route('admin.editions.form.rules', $edition), [
        'rules' => [
            'word_limit' => '350', 'max_authors' => '10', 'file_max_mb' => '8', 'file_formats' => 'pdf, docx, odt',
            'no_duplicate' => '1', 'live_counter' => '1', // require_account & captcha non cochés
        ],
    ])->assertSessionHasNoErrors();

    $rules = (new SubmissionFormConfig($edition->refresh()))->rules();
    expect($rules['word_limit'])->toBe(350);
    expect($rules['max_authors'])->toBe(10);
    expect($rules['file_max_mb'])->toBe(8);
    expect($rules['file_formats'])->toBe('pdf, docx, odt');
    expect($rules['no_duplicate'])->toBeTrue();
    expect($rules['require_account'])->toBeFalse();
    expect($rules['captcha'])->toBeFalse();
});

it('convertit les options de liste (une par ligne) en tableau', function () {
    $staff = adminStaff();
    $edition = openEdition();

    $this->actingAs($staff, 'web')->put(route('admin.editions.form.update', $edition), [
        'fields' => [
            'title' => ['label' => 'Titre'],
            'abstract' => ['label' => 'Résumé', 'min' => 50, 'max' => 8000],
        ],
        'custom' => [
            ['key' => 'custom_x', 'label' => 'Session préférée', 'type' => 'select', 'options_text' => "Matin\nAprès-midi\n"],
        ],
    ])->assertSessionHasNoErrors();

    $edition->refresh();
    $field = (new SubmissionFormConfig($edition))->customFields()[0];

    expect($field['type'])->toBe('select');
    expect($field['options'])->toBe(['Matin', 'Après-midi']);
});

it('réordonne les champs personnalisés (monter/descendre) et persiste l’ordre', function () {
    $staff = adminStaff();
    $edition = openEdition(['submission_form' => ['custom' => [
        ['key' => 'a', 'label' => 'Champ A', 'type' => 'text'],
        ['key' => 'b', 'label' => 'Champ B', 'type' => 'text'],
        ['key' => 'c', 'label' => 'Champ C', 'type' => 'text'],
    ]]]);

    // Le réordonnancement est côté client (Alpine) ; le serveur persiste l'ordre soumis.
    $this->actingAs($staff, 'web')->put(route('admin.editions.form.update', $edition), [
        'fields' => [
            'title' => ['label' => 'Titre'],
            'abstract' => ['label' => 'Résumé', 'min' => 50, 'max' => 8000],
        ],
        'custom' => [
            ['key' => 'a', 'label' => 'Champ A', 'type' => 'text'],
            ['key' => 'c', 'label' => 'Champ C', 'type' => 'text'],
            ['key' => 'b', 'label' => 'Champ B', 'type' => 'text'],
        ],
    ])->assertSessionHasNoErrors();

    $edition->refresh();
    $labels = array_column((new SubmissionFormConfig($edition))->customFields(), 'label');

    expect($labels)->toBe(['Champ A', 'Champ C', 'Champ B']);
});

it('accepte un champ personnalisé de type date', function () {
    $edition = openEdition(['submission_form' => ['custom' => [
        ['key' => 'dispo', 'label' => 'Disponibilité', 'type' => 'date', 'required' => true],
    ]]]);
    $account = verifiedAccount();

    // Date invalide → erreur.
    $this->actingAs($account, 'account')
        ->post(route('account.submissions.store', $edition->slug), [
            'title' => 'Ma communication',
            'abstract' => str_repeat('a', 60),
            'customAnswers' => ['dispo' => 'pas-une-date'],
        ])
        ->assertSessionHasErrors('customAnswers.dispo');

    // Date valide → enregistrée.
    $this->actingAs($account, 'account')
        ->post(route('account.submissions.store', $edition->slug), [
            'title' => 'Ma communication',
            'abstract' => str_repeat('a', 60),
            'customAnswers' => ['dispo' => '2026-09-15'],
        ])
        ->assertSessionHasNoErrors();

    $submission = Submission::where('edition_id', $edition->id)->latest('id')->first();
    $custom = collect($submission->meta['custom_fields'] ?? []);

    expect($custom->firstWhere('key', 'dispo')['value'])->toBe('2026-09-15');
});

it('respecte le libellé personnalisé et masque un champ désactivé sur le formulaire public', function () {
    $edition = openEdition(['submission_form' => ['fields' => [
        'title' => ['label' => 'Intitulé unique'],
        'keywords' => ['enabled' => false],
    ]]]);
    $account = verifiedAccount();

    $this->actingAs($account, 'account')
        ->get(route('account.submissions.create', $edition->slug))
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
    $this->actingAs($account, 'account')
        ->post(route('account.submissions.store', $edition->slug), [
            'title' => 'Ma communication',
            'abstract' => str_repeat('a', 60),
        ])
        ->assertSessionHasErrors('customAnswers.orcid');

    // Renseigné → enregistré dans meta.
    $this->actingAs($account, 'account')
        ->post(route('account.submissions.store', $edition->slug), [
            'title' => 'Ma communication',
            'abstract' => str_repeat('a', 60),
            'customAnswers' => ['orcid' => '0000-0002-1825-0097', 'session' => 'Matin'],
        ])
        ->assertSessionHasNoErrors();

    $submission = Submission::where('edition_id', $edition->id)->latest('id')->first();
    $custom = collect($submission->meta['custom_fields'] ?? []);

    expect($custom->firstWhere('key', 'orcid')['value'])->toBe('0000-0002-1825-0097');
    expect($custom->firstWhere('key', 'session')['value'])->toBe('Matin');
});

it('impose la limite minimale de mots du résumé configurée', function () {
    $edition = openEdition(['submission_form' => ['rules' => ['word_min' => 40, 'word_limit' => 300]]]);
    $account = verifiedAccount();

    // 10 mots < 40 requis → refusé.
    $this->actingAs($account, 'account')
        ->post(route('account.submissions.store', $edition->slug), [
            'title' => 'Titre',
            'abstract' => str_repeat('mot ', 10),
        ])
        ->assertSessionHasErrors('abstract');
});

it('ne perd pas les règles quand on enregistre les champs', function () {
    $staff = adminStaff();
    $edition = openEdition();

    // Règles posées depuis l'onglet Options…
    $this->actingAs($staff, 'web')->put(route('admin.editions.form.rules', $edition), [
        'rules' => ['word_limit' => '333', 'max_authors' => '9'],
    ])->assertRedirect();

    // …puis enregistrement des CHAMPS depuis l'éditeur : les règles survivent.
    $this->actingAs($staff, 'web')->put(route('admin.editions.form.update', $edition), [
        'fields' => ['title' => ['label' => 'Titre'], 'abstract' => ['label' => 'Résumé']],
    ])->assertSessionHasNoErrors();

    $rules = (new SubmissionFormConfig($edition->refresh()))->rules();
    expect($rules['word_limit'])->toBe(333);
    expect($rules['max_authors'])->toBe(9);
});
