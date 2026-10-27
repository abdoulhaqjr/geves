<?php

use App\Models\Event;
use App\Support\Fees;

/*
|--------------------------------------------------------------------------
| Rapprochement grade -> catégorie tarifaire (clé normalisée)
|--------------------------------------------------------------------------
|
| Le grade vit en DEUX endroits qui doivent s'accorder : la liste éditable
| (`ParticipantTypes`, Réglages) que porte le participant, et le `grades` d'une
| catégorie - une copie du libellé. Renommer un grade dans les Réglages
| (« Enseignant / Chercheur » -> « Enseignant-Chercheur ») laissait la grille sur
| l'ancien texte : plus aucune correspondance, et le participant retombait EN
| SILENCE sur le repli (défaut, ou le plus cher). On compare donc par clé
| normalisée (casse, accents, séparateurs), jamais par chaîne brute.
|
*/

/** Grille à grades liés + un défaut, pour éprouver le rapprochement. */
function evenementAvecGrades(): Event
{
    $event = openEdition();
    $settings = $event->settings ?? [];
    $settings['fees'] = [
        ['key' => 'ens', 'label' => 'Enseignants et chercheurs', 'amount' => 35000, 'grades' => ['Enseignant / Chercheur', 'Enseignant'], 'default' => false],
        ['key' => 'doc', 'label' => 'Doctorants', 'amount' => 10000, 'grades' => ['Doctorant'], 'default' => false],
        ['key' => 'autres', 'label' => 'Autres participants', 'amount' => 90000, 'grades' => [], 'default' => true],
    ];
    $event->settings = $settings;
    $event->save();

    return $event->fresh();
}

it('rapproche un grade renommé par séparateur (tiret vs barre oblique)', function () {
    $event = evenementAvecGrades();

    // La grille porte « Enseignant / Chercheur » ; le participant porte le grade
    // renommé « Enseignant-Chercheur » - sans normalisation, il retombait sur le
    // défaut (90 000) alors qu'il relève des enseignants (35 000).
    expect(Fees::resolveAmount($event, 'Enseignant-Chercheur'))->toBe(35000.0);
    expect(Fees::resolveAmount($event, 'enseignant chercheur'))->toBe(35000.0);
});

it('rapproche un grade saisi sans ses accents', function () {
    $event = evenementAvecGrades();

    // « Maître de conférences » couvert, grade stocké « Maitre de Conférences ».
    $event->settings = array_replace($event->settings, [
        'fees' => [
            ['key' => 'ens', 'label' => 'Enseignants', 'amount' => 35000, 'grades' => ['Maître de conférences'], 'default' => false],
            ['key' => 'autres', 'label' => 'Autres participants', 'amount' => 90000, 'grades' => [], 'default' => true],
        ],
    ]);
    $event->save();

    expect(Fees::resolveAmount($event->fresh(), 'Maitre de Conférences'))->toBe(35000.0);
});

it('ne confond PAS deux grades distincts', function () {
    // « Enseignant » (grade à lui seul) ne doit pas se rabattre sur
    // « Enseignant-Chercheur » : seule la ligne qui le couvre explicitement le prend.
    $event = openEdition();
    $event->settings = array_replace($event->settings ?? [], [
        'fees' => [
            ['key' => 'ec', 'label' => 'Enseignants-chercheurs', 'amount' => 35000, 'grades' => ['Enseignant-Chercheur'], 'default' => false],
            ['key' => 'autres', 'label' => 'Autres participants', 'amount' => 90000, 'grades' => [], 'default' => true],
        ],
    ]);
    $event->save();
    $event = $event->fresh();

    expect(Fees::resolveAmount($event, 'Enseignant-Chercheur'))->toBe(35000.0);
    // « Enseignant » n'est couvert nulle part -> repli sur le défaut, pas 35 000.
    expect(Fees::resolveAmount($event, 'Enseignant'))->toBe(90000.0);
});

it('normalise la clé d’un grade', function () {
    expect(Fees::gradeKey('Enseignant / Chercheur'))->toBe(Fees::gradeKey('Enseignant-Chercheur'));
    expect(Fees::gradeKey('Maître de conférences'))->toBe(Fees::gradeKey('Maitre de Conférences'));
    expect(Fees::gradeKey('Enseignant'))->not->toBe(Fees::gradeKey('Enseignant-Chercheur'));
    expect(Fees::gradeKey(''))->toBe('');
});
