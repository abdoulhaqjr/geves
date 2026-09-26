<?php

// openEdition() -> tests/Pest.php

use App\Support\Fees;

it('rend la page publique sans planter quand une entrée fees est malformée', function () {
    // Grille mêlant des tarifs valides et une entrée scalaire (non-tableau) :
    // reproduit le bug 500 « Trying to access array offset on int ».
    $edition = openEdition([
        'fees' => [
            ['label' => 'Doctorant', 'amount' => 15000],
            25000, // entrée malformée -> doit être ignorée, pas casser la page
            ['label' => 'Enseignant-chercheur', 'amount' => 35000],
        ],
    ]);

    $this->get(route('public.colloque', $edition->slug))
        ->assertOk()
        ->assertSee('Frais de participation')
        ->assertSee('Doctorant')
        ->assertSee('15 000')
        ->assertSee('Enseignant-chercheur')
        ->assertSee('35 000');
});

it('rend la page publique quand toute la grille fees est au format legacy associatif', function () {
    // Format legacy « grade => montant » (cf. données de dev cip-uk-2023) :
    // chaque valeur est un entier, donc toutes les entrées sont malformées.
    $edition = openEdition([
        'fees' => ['Enseignant-chercheur' => 30000, 'Doctorant' => 15000],
    ]);

    // Pas de 500 ; la section tarifs est simplement masquée faute d'entrée valide.
    $this->get(route('public.colloque', $edition->slug))
        ->assertOk()
        ->assertDontSee('ukc-pricing', false);
});

it('normalise la grille tarifaire en ignorant les entrées invalides', function () {
    expect(Fees::normalize([
        ['label' => 'Doctorant', 'amount' => 15000],
        25000,                                   // scalaire -> ignoré
        ['label' => '', 'amount' => 9000],       // libellé vide -> ignoré
        ['amount' => 9000],                      // pas de libellé -> ignoré
        ['label' => 'Enseignant', 'amount' => '35000'], // montant en chaîne -> float
    ]))->toBe([
        ['label' => 'Doctorant', 'amount' => 15000.0, 'grades' => [], 'default' => false],
        ['label' => 'Enseignant', 'amount' => 35000.0, 'grades' => [], 'default' => false],
    ]);

    expect(Fees::normalize(null))->toBe([]);
    expect(Fees::normalize('cassé'))->toBe([]);
    // Format legacy associatif : entièrement ignoré (valeurs scalaires).
    expect(Fees::normalize(['Doctorant' => 15000]))->toBe([]);
});

it('retrouve le tarif par le grade rattaché, pas par l’intitulé', function () {
    // Cas réel : l'intitulé est rédigé librement et ne correspond à aucun grade.
    $edition = openEdition(['fees' => [
        ['label' => 'Enseignants et chercheurs', 'amount' => 35000, 'grades' => ['Enseignant / Chercheur']],
        ['label' => 'Doctorants et assimilés', 'amount' => 15000, 'grades' => ['Doctorant']],
        ['label' => 'Autres participants', 'amount' => 50000, 'grades' => [], 'default' => true],
    ]]);

    expect(Fees::resolveAmount($edition, 'Doctorant'))->toBe(15000.0);
    expect(Fees::resolveAmount($edition, 'Enseignant / Chercheur'))->toBe(35000.0);
    // Grade non couvert → catégorie par défaut.
    expect(Fees::resolveAmount($edition, 'Autre participant'))->toBe(50000.0);
    expect(Fees::resolveAmount($edition, null))->toBe(50000.0);
});

it('reste compatible avec une grille sans rattachement (intitulé = grade)', function () {
    $edition = openEdition(['fees' => [
        ['label' => 'Doctorant', 'amount' => 15000],
        ['label' => 'Enseignant / Chercheur', 'amount' => 35000],
    ]]);

    expect(Fees::resolveAmount($edition, 'doctorant'))->toBe(15000.0);
    // Sans catégorie par défaut, un grade inconnu ne donne aucun montant.
    expect(Fees::resolveAmount($edition, 'Autre participant'))->toBeNull();
});
