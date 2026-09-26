<?php

/**
 * Test de garde : « a payé » doit toujours signifier « a réglé sa PARTICIPATION ».
 *
 * Un paiement de formation est rattaché au même `Participant` que les frais
 * d'événement. Un contrôle écrit naïvement sur `payments` le compte donc et
 * ouvre à tort le badge, les attestations et l'accès - le bug a déjà été
 * réintroduit plusieurs fois, d'où ce garde-fou automatique.
 *
 * La règle : tout contrôle « payé » passe par `participationPayments()` ou par
 * le scope `Payment::forParticipation()`. Les seules exceptions sont les
 * agrégats FINANCIERS (recette encaissée), listés explicitement ci-dessous.
 */

/** Fichiers scannés : code applicatif + vues. */
function paymentGuardFiles(): array
{
    $roots = [base_path('app'), base_path('resources/views')];
    $files = [];

    foreach ($roots as $root) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if ($file->isFile() && in_array($file->getExtension(), ['php'], true)) {
                $files[] = $file->getPathname();
            }
        }
    }

    return $files;
}

it('n’interroge jamais les paiements « payés » sans filtrer la participation', function () {
    // Agrégats financiers : ils DOIVENT compter les formations (c'est du chiffre).
    $allowed = [
        'app/Support/EditionReport.php' => [
            '$collected = (float) Payment::where',      // recette encaissée
            "'paidCount' => Payment::where",            // nombre de paiements encaissés
        ],
    ];

    // Motifs qui dénotent un contrôle « payé » sur la relation/le modèle brut.
    $risky = '/(->payments\b|payments\'\s*,\s*fn|Payment::)/';

    $offenders = [];

    foreach (paymentGuardFiles() as $path) {
        $relative = str_replace(DIRECTORY_SEPARATOR, '/', str_replace(base_path().DIRECTORY_SEPARATOR, '', $path));

        foreach (file($path) as $no => $line) {
            if (! str_contains($line, "'paid'") || ! preg_match($risky, $line)) {
                continue;
            }
            // Déjà filtré : conforme.
            if (str_contains($line, 'participationPayments') || str_contains($line, 'forParticipation')) {
                continue;
            }
            // Exception financière explicitement autorisée.
            $isAllowed = false;
            foreach ($allowed[$relative] ?? [] as $needle) {
                if (str_contains($line, $needle)) {
                    $isAllowed = true;
                    break;
                }
            }
            if ($isAllowed) {
                continue;
            }

            $offenders[] = $relative.':'.($no + 1).'  '.trim($line);
        }
    }

    expect($offenders)->toBe([], implode("\n", array_merge(
        ['Contrôle de paiement non filtré (une formation réglée ouvrirait les droits) :'],
        $offenders,
        ['', 'Utilisez Participant::participationPayments() ou Payment::forParticipation().',
            'S’il s’agit d’un total financier, ajoutez-le à la liste $allowed de ce test.'],
    )));
});
