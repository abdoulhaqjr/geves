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
        ['label' => 'Doctorant', 'amount' => 15000.0],
        ['label' => 'Enseignant', 'amount' => 35000.0],
    ]);

    expect(Fees::normalize(null))->toBe([]);
    expect(Fees::normalize('cassé'))->toBe([]);
    // Format legacy associatif : entièrement ignoré (valeurs scalaires).
    expect(Fees::normalize(['Doctorant' => 15000]))->toBe([]);
});
