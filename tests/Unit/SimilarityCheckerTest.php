<?php

use App\Support\SimilarityChecker;

function sim(): SimilarityChecker
{
    return new SimilarityChecker;
}

it('donne un score maximal pour deux textes identiques', function () {
    $t = 'Approche innovante de la gestion durable des ressources hydriques au Sahel.';
    expect(sim()->score($t, $t))->toBeGreaterThan(0.99);
});

it('donne un score faible pour deux textes sans rapport', function () {
    $a = 'Optimisation des réseaux de neurones pour la vision par ordinateur.';
    $b = 'Histoire des institutions politiques médiévales en Afrique de l’Ouest.';
    expect(sim()->score($a, $b))->toBeLessThan(0.2);
});

it('détecte une quasi-duplication (quelques mots changés)', function () {
    $a = 'Gestion durable des ressources hydriques et résilience climatique au Sahel.';
    $b = 'Gestion durable des ressources hydriques et résilience climatique dans la région du Sahel.';
    expect(sim()->score($a, $b))->toBeGreaterThan(0.6);
});

it('classe le corpus par proximité, seuil et limite respectés', function () {
    $ref = 'Impact des politiques publiques sur l’entrepreneuriat étudiant en Afrique.';
    $corpus = [
        ['id' => 1, 'text' => 'Impact des politiques publiques sur l’entrepreneuriat étudiant en Afrique subsaharienne.'], // très proche
        ['id' => 2, 'text' => 'Modélisation numérique des écoulements turbulents en mécanique des fluides.'], // sans rapport
        ['id' => 3, 'text' => 'Politiques publiques et entrepreneuriat des étudiants africains : quel impact ?'], // proche
    ];

    $matches = sim()->against($ref, $corpus, 0.3, 5);

    expect($matches)->not->toBeEmpty()
        ->and($matches[0]['id'])->toBe(1)                        // le plus proche en tête
        ->and(collect($matches)->pluck('id'))->not->toContain(2); // le hors-sujet exclu par le seuil
});

it('renvoie vide pour un texte sans jeton significatif', function () {
    expect(sim()->against('le la les de du', [['id' => 1, 'text' => 'un une des à en']]))->toBeEmpty();
});
