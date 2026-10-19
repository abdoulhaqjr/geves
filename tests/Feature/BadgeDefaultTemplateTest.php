<?php

use App\Documents\BadgeSheet;
use App\Documents\LayoutRenderer;
use App\Documents\LayoutSanitizer;

/**
 * Badge par défaut, calqué sur le badge officiel du comité d'organisation
 * (UK x Université de Parakou).
 *
 * ⚠️ Le défaut PHP (rendu) et le modèle du concepteur de badges
 * (badge-designer.js, modèle `co`) décrivent la MÊME maquette : ce test garde
 * le côté PHP.
 */
it('a un badge par défaut calqué sur le comité d’organisation', function () {
    $layout = LayoutSanitizer::default('badge');

    // A6 portrait + cadre décoratif vert/bleu en image de fond.
    // ⚠️⚠️ Le badge par défaut est au format MODÈLE 303 (88 × 125 mm), la taille
    // d'un porte-badge du commerce : c'est une dimension de SORTIE, et l'A4 en
    // reçoit 4. L'A6 papier (397 × 560) n'en laissait imprimer qu'UN par page,
    // 2 × 105 mm faisant la largeur entière de la feuille.
    expect($layout['width'])->toBe(333);
    expect($layout['height'])->toBe(472);

    $plan = BadgeSheet::plan($layout['width'], $layout['height']);
    expect($plan['label'])->toStartWith('Modèle 303')
        ->and($plan['trueSize'])->toBeTrue()
        ->and($plan['perPage'])->toBe(4);
    expect($layout['bg_image'])->toBe('/img/badges/badge-default-bg.png');

    // Jetons de la palette du concepteur ([espace.cle]), pas la forme plate.
    $texts = collect($layout['elements'])->where('type', 'text')->pluck('text')->implode(' | ');
    expect($texts)
        ->toContain('[edition.name]')
        ->toContain('[recipient.name]')
        ->toContain('[recipient.role]')
        ->toContain('[edition.theme]')
        ->toContain('[edition.location]')
        ->toContain('[edition.dates]');

    // Les deux logos OFFICIELS (UK large + Parakou large) sont présents, parmi les
    // images (les pictogrammes lieu/dates en sont aussi) ; plus un QR.
    $images = collect($layout['elements'])->where('type', 'image')->pluck('src');
    expect($images->contains('/img/attestations/logo-parakou.png'))->toBeTrue();
    expect($images->contains(fn ($s) => str_contains((string) $s, 'logo-uk')))->toBeTrue();
    expect(collect($layout['elements'])->firstWhere('type', 'qr'))->not->toBeNull();

    // ⚠️ **Les filets verts restent CUITS DANS LE CADRE** de l'image de fond (soudés
    // au contour, sans fente) : un filet posé en élément laissait un jour contre le
    // cadre dans le concepteur. Aucun `rect` ne doit reprendre leur vert ni leur
    // bande d'en-tête.
    $rects = collect($layout['elements'])->where('type', 'rect');
    expect($rects->where('color', '#00be62'))->toHaveCount(0);
    expect($rects->filter(fn (array $r) => (int) $r['y'] < 120))->toHaveCount(0);

    // ⚠️ Le SÉPARATEUR, lui, EST un élément - « Rendre le séparateur et les
    // pictogrammes du badge éditables » l'a voulu ainsi pour que son épaisseur se
    // règle dans le concepteur, ce qu'un trait cuit dans l'image interdit. Le cas
    // exigeait zéro `rect` et tombait donc sur lui : il n'en reste qu'un, et c'est
    // bien le séparateur.
    expect($rects)->toHaveCount(1);
    expect($rects->first()['shape'])->toBe('line-h');
});

it('a un modèle « Participant avec photo » : même maquette + une photo entre l’événement et le nom', function () {
    $base = LayoutSanitizer::default('badge');
    $photo = LayoutSanitizer::default('badge', 'participant_photo');

    // Même cadre, même taille que le badge participant.
    expect($photo['width'])->toBe(333);
    expect($photo['height'])->toBe(472);
    expect($photo['bg_image'])->toBe($base['bg_image']);

    // Un (et un seul) élément photo, rond, rempli par recipient.photo au rendu.
    $photos = collect($photo['elements'])->where('type', 'photo');
    expect($photos)->toHaveCount(1);
    expect(collect($base['elements'])->where('type', 'photo'))->toHaveCount(0);

    $yOf = fn (array $layout, string $token) => collect($layout['elements'])
        ->firstWhere('text', $token)['y'] ?? null;

    // L'événement REMONTE, le nom DESCEND, et la photo s'intercale entre les deux.
    expect($yOf($photo, '[edition.name]'))->toBeLessThan($yOf($base, '[edition.name]'));
    expect($yOf($photo, '[recipient.name]'))->toBeGreaterThan($yOf($base, '[recipient.name]'));
    $photoY = $photos->first()['y'];
    expect($photoY)->toBeGreaterThan($yOf($photo, '[edition.name]'));
    expect($photoY)->toBeLessThan($yOf($photo, '[recipient.name]'));
});

it('rend le badge avec photo en utilisant recipient.photo', function () {
    $layout = LayoutSanitizer::default('badge', 'participant_photo');
    $html = LayoutRenderer::render($layout, [
        'recipient.name' => 'Awa Kodjo',
        'recipient.role' => 'Participant',
        'recipient.photo' => '/img/badges/icon-location.png',
    ]);

    expect($html)
        ->toContain('Awa Kodjo')
        ->toContain('Participant')
        ->toContain('/img/badges/icon-location.png'); // la photo est bien insérée
});

it('rend le badge par défaut avec les valeurs du participant', function () {
    $layout = LayoutSanitizer::default('badge');
    $html = LayoutRenderer::render($layout, [
        'event' => '6e Colloque Scientifique International',
        'name' => 'M. Assogba Gervais',
        'role' => 'Participant',
        'theme' => 'Les métiers d’aujourd’hui et de demain',
        'location' => 'Palais des congrès de Kara',
        'dates' => 'Du 06 au 10 octobre 2025',
        'ref' => 'UKC-2026-001',
    ]);

    expect($html)
        ->toContain('M. Assogba Gervais')
        ->toContain('Participant')
        ->toContain('Palais des congrès de Kara')
        ->toContain('Du 06 au 10 octobre 2025')
        ->toContain('/img/badges/badge-default-bg.png');
});

it('compose la maquette officielle pour chaque porte-badge', function () {
    // ⚠️⚠️ Les trois pochettes n'ont PAS le même rapport (303 : 0,704 ; 304 : 0,741 ;
    // 106 : 0,684). Agrandir le 303 pour obtenir les autres les déformerait - c'est
    // pourquoi la maquette est composée par format, pas mise à l'échelle.
    $composer = new ReflectionMethod(LayoutSanitizer::class, 'defaultBadge');
    $composer->setAccessible(true);

    $attendu = [
        'modele-303' => [333, 472, 4],
        'modele-304' => [378, 510, 4],
        'modele-106' => [246, 359, 9],
        'a6-pochette' => [408, 586, 1],   // 2 x 108 mm > 210 : un seul par feuille
        'cr80' => [324, 204, 10],         // PAYSAGE : maquette de carte
    ];

    foreach ($attendu as $format => [$w, $h, $parPage]) {
        foreach ([false, true] as $avecPhoto) {
            $layout = $composer->invoke(null, $avecPhoto, $format);

            expect($layout['width'])->toBe($w, $format)
                ->and($layout['height'])->toBe($h, $format);

            // Rien ne doit sortir du badge : un élément hors cadre serait rogné.
            $dehors = [];
            foreach ($layout['elements'] as $el) {
                $ew = $el['w'] ?? $el['size'] ?? 0;
                $eh = $el['h'] ?? $el['size'] ?? 0;
                if ($el['x'] < 0 || $el['y'] < 0 || $w < $el['x'] + $ew || $h < $el['y'] + $eh) {
                    $dehors[] = $el['type'].'@'.$el['x'].','.$el['y'];
                }
            }
            expect($dehors)->toBe([], $format.' : éléments hors cadre - '.implode(' ', $dehors));
        }

        // La planche dispose ce format à sa taille réelle.
        $plan = BadgeSheet::plan($w, $h);
        expect($plan['trueSize'])->toBeTrue($format)
            ->and($plan['perPage'])->toBe($parPage, $format);
    }
});

it('garde le QR carré et le texte lisible sur le plus petit badge', function () {
    // ⚠️ Le QR doit rester CARRÉ (il se scanne) et le corps du texte ne doit pas
    // tomber sous le plancher : c'est ce qui arriverait si la composition suivait
    // la largeur et la hauteur séparément.
    $composer = new ReflectionMethod(LayoutSanitizer::class, 'defaultBadge');
    $composer->setAccessible(true);

    foreach (array_keys(LayoutSanitizer::BADGE_FORMATS) as $format) {
        $elements = collect($composer->invoke(null, false, $format)['elements']);

        $qr = $elements->firstWhere('type', 'qr');
        expect($qr)->not->toBeNull($format)
            // 11 mm : en deçà, un QR portant une URL ne se scanne plus de façon fiable.
            ->and($qr['size'] / 96 * 25.4)->toBeGreaterThan(11.0, $format.' : QR trop petit');

        foreach ($elements->where('type', 'text') as $texte) {
            expect($texte['size'])->toBeGreaterThanOrEqual(6, $format.' : texte sous le plancher');
        }

        // Les pictogrammes restent carrés.
        foreach ($elements->where('type', 'image')->where('color', '#2e3440') as $picto) {
            expect($picto['w'])->toBe($picto['h'], $format.' : pictogramme déformé');
        }
    }
});

it('compose le CR80 en CARTE, sans le thème', function () {
    // ⚠️⚠️ Le CR80 est en PAYSAGE (85,6 × 54 mm) : la pile verticale du portrait
    // n'y tient pas. Et le thème en est absent À DESSEIN - cent caractères
    // descendraient sous 2 mm sur une carte de crédit, on afficherait de
    // l'illisible pour faire nombre.
    $composer = new ReflectionMethod(LayoutSanitizer::class, 'defaultBadge');
    $composer->setAccessible(true);

    $carte = collect($composer->invoke(null, false, 'cr80')['elements']);
    $textes = $carte->where('type', 'text')->pluck('text');

    expect($textes)->not->toContain('[edition.theme]')
        ->and($textes)->toContain('[recipient.name]', '[recipient.role]', '[edition.name]')
        ->and($textes)->toContain('[edition.location]', '[edition.dates]');

    // ⚠️ La carte est CENTRÉE : le QR vit SOUS le bloc d'identité (et non plus à
    // sa droite), sinon il le recouvrirait.
    $qr = $carte->firstWhere('type', 'qr');
    $role = $carte->firstWhere('text', '[recipient.role]');
    expect($qr['y'])->toBeGreaterThanOrEqual($role['y'] + $role['h']);

    // Les formats portrait, eux, portent bien le thème.
    // ⚠️ On RELÈVE les fautifs : `toContain()` prend plusieurs AIGUILLES, pas un
    // message - passer le nom du format en second argument en ferait une aiguille
    // de plus, et le cas échouerait pour la mauvaise raison (piège de CLAUDE.md).
    $sansTheme = [];
    foreach (['modele-303', 'modele-304', 'modele-106', 'a6-pochette'] as $portrait) {
        $textes = collect($composer->invoke(null, false, $portrait)['elements'])->where('type', 'text')->pluck('text');
        if (! $textes->contains('[edition.theme]')) {
            $sansTheme[] = $portrait;
        }
    }

    expect($sansTheme)->toBe([], 'formats portrait sans thème : '.implode(', ', $sansTheme));
});

it('tient la maquette PHP et son jumeau du concepteur d’accord', function () {
    // ⚠️⚠️ « Chaque modèle PHP et son jumeau du concepteur décrivent la MÊME
    // maquette : toucher à l'un, c'est toucher à l'autre. » Les deux composent
    // désormais à partir des mêmes proportions - ce cas vérifie qu'elles le restent.
    $php = file_get_contents(app_path('Documents/LayoutSanitizer.php'));
    $js = file_get_contents(resource_path('js/badge-designer.js'));

    $fractions = function (string $sujet, string $motif): array {
        preg_match_all($motif, $sujet, $m);
        $liste = array_values(array_unique($m[1]));
        sort($liste);

        return $liste;
    };

    // Proportions de la maquette PHP (fx(...) / fy(...)).
    $cotePhp = $fractions($php, '/\$f[xy]\((0\.\d{4})\)/');
    // Les mêmes côté concepteur.
    $coteJs = $fractions($js, '/f[xy]\((0\.\d{4})\)/');

    expect($cotePhp)->not->toBeEmpty()
        ->and($coteJs)->toBe($cotePhp, 'le concepteur et le badge délivré doivent partager les mêmes proportions');
});
