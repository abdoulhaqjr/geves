<?php

// openEdition() -> tests/Pest.php

use App\Support\Fees;
use App\Support\ParticipantTypes;

it('rend la page publique sans planter quand une entrée fees est malformée', function () {
    // Grille mêlant des tarifs valides et une entrée scalaire (non-tableau) :
    // reproduit le bug 500 « Trying to access array offset on int ».
    $event = openEdition([
        'fees' => [
            ['label' => 'Doctorant', 'amount' => 15000],
            25000, // entrée malformée -> doit être ignorée, pas casser la page
            ['label' => 'Enseignant-chercheur', 'amount' => 35000],
        ],
    ]);

    $this->get(route('public.colloque', $event->slug))
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
    $event = openEdition([
        'fees' => ['Enseignant-chercheur' => 30000, 'Doctorant' => 15000],
    ]);

    // Pas de 500 ; la section tarifs est simplement masquée faute d'entrée valide.
    $this->get(route('public.colloque', $event->slug))
        ->assertOk()
        ->assertDontSee('ukc-fees', false);
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
    $event = openEdition(['fees' => [
        ['label' => 'Enseignants et chercheurs', 'amount' => 35000, 'grades' => ['Enseignant / Chercheur']],
        ['label' => 'Doctorants et assimilés', 'amount' => 15000, 'grades' => ['Doctorant']],
        ['label' => 'Autres participants', 'amount' => 50000, 'grades' => [], 'default' => true],
    ]]);

    expect(Fees::resolveAmount($event, 'Doctorant'))->toBe(15000.0);
    expect(Fees::resolveAmount($event, 'Enseignant / Chercheur'))->toBe(35000.0);
    // Grade non couvert → catégorie par défaut.
    expect(Fees::resolveAmount($event, 'Autre participant'))->toBe(50000.0);
    expect(Fees::resolveAmount($event, null))->toBe(50000.0);
});

it('reste compatible avec une grille sans rattachement (intitulé = grade)', function () {
    $event = openEdition(['fees' => [
        ['label' => 'Doctorant', 'amount' => 15000],
        ['label' => 'Enseignant / Chercheur', 'amount' => 35000],
    ]]);

    expect(Fees::resolveAmount($event, 'doctorant'))->toBe(15000.0);
    // Sans catégorie par défaut, un grade inconnu ne donne aucun montant.
    expect(Fees::resolveAmount($event, 'Autre participant'))->toBeNull();
});

it('le grade choisi à l’inscription permet de résoudre le tarif', function () {
    $event = openEdition(['fees' => [
        ['label' => 'Enseignants et chercheurs', 'amount' => 35000, 'grades' => ['Enseignant / Chercheur']],
        ['label' => 'Doctorants et assimilés', 'amount' => 15000, 'grades' => ['Doctorant']],
        ['label' => 'Autres participants', 'amount' => 50000, 'grades' => ['Autre participant'], 'default' => true],
    ]]);

    // Le formulaire d'inscription ne propose que les grades de référence.
    $this->get(route('account.register'))->assertOk()
        ->assertSee('Doctorant')
        ->assertDontSee('Doctorant / étudiant');

    // Chaque grade proposé a bien un tarif : c'est la garantie recherchée.
    foreach (ParticipantTypes::all() as $grade) {
        expect(Fees::resolveAmount($event, $grade))
            ->not->toBeNull("aucun tarif pour le grade « {$grade} »");
    }
});

/**
 * ⚠️ **Le participant ne choisit pas son tarif.** Les cartes étaient des cartes
 * d'achat - un bouton « Choisir » par tarif, et une pastille « Recommandé »
 * posée sur la carte du milieu : elles disaient l'inverse de ce que fait le
 * logiciel et invitaient à prendre la moins chère.
 */
it('la grille publique renseigne au lieu de faire choisir', function () {
    $event = openEdition(['fees' => [
        ['label' => 'Enseignants et chercheurs', 'amount' => 35000, 'grades' => ['Enseignant / Chercheur', 'Professeur']],
        ['label' => 'Doctorants et assimilés', 'amount' => 15000, 'grades' => ['Doctorant']],
        ['label' => 'Autres participants', 'amount' => 50000, 'grades' => [], 'default' => true],
    ]]);

    $page = $this->get(route('public.colloque', $event->slug))->assertOk();

    // La règle se lit dans les cartes elles-mêmes : le bandeau qui la répétait
    // au-dessus de la grille doublait ce qu'elles montrent déjà.
    $page->assertDontSee('ukc-fees-rule', false);
    // Les grades couverts, c'est-à-dire la seule chose qui distingue une carte.
    $page->assertSee('Enseignant / Chercheur')->assertSee('Doctorant')->assertSee('Tout autre grade');
    // Plus de geste d'achat par carte.
    $page->assertDontSee('>Choisir<', false);
    $page->assertDontSee('ukc-price-card--featured', false);
});

/**
 * Sans grade rattaché, le visiteur ne peut PAS savoir laquelle des cartes est
 * la sienne : on le lui dit plutôt que de le laisser deviner devant trois prix.
 */
it('annonce que le tarif sera confirmé quand aucun grade n’est rattaché', function () {
    $event = openEdition(['fees' => [
        ['label' => 'Enseignants et chercheurs', 'amount' => 35000],
        ['label' => 'Doctorants et assimilés', 'amount' => 15000],
    ]]);

    $this->get(route('public.colloque', $event->slug))->assertOk()
        ->assertSee('confirmé au moment de l’inscription', false);
});
