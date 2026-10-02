<?php

namespace App\Documents;

/**
 * Polices des documents, hébergées par le site (`public/fonts`).
 *
 * Source unique des fontes déclarées : le partial `documents/_fonts` et le
 * rendu (`LayoutRenderer`) y puisent tous les deux, sinon le document délivré
 * demanderait une graisse que la feuille de styles n'a pas déclarée.
 */
class DocumentFonts
{
    /**
     * Fontes livrées : [fichier, famille, graisse, style].
     *
     * ⚠️ Les graisses reprennent exactement celles du concepteur : une graisse
     * absente serait synthétisée par le moteur, ce qui change le rendu.
     *
     * @var list<array{0: string, 1: string, 2: int, 3: string}>
     */
    public const FACES = [
        ['jost-400', 'Jost', 400, 'normal'],
        ['jost-500', 'Jost', 500, 'normal'],
        ['jost-600', 'Jost', 600, 'normal'],
        ['jost-700', 'Jost', 700, 'normal'],
        ['jost-800', 'Jost', 800, 'normal'],
        ['jost-900', 'Jost', 900, 'normal'],
        ['jost-400-italic', 'Jost', 400, 'italic'],
        ['jost-700-italic', 'Jost', 700, 'italic'],
        ['playfair-700', 'Playfair Display', 700, 'normal'],
        ['playfair-900', 'Playfair Display', 900, 'normal'],
        ['playfair-700-italic', 'Playfair Display', 700, 'italic'],
    ];

    /** Famille réelle, par clé de police du concepteur. */
    private const FAMILIES = [
        'jost' => 'Jost',
        'playfair' => 'Playfair Display',
    ];

    /**
     * Nom de famille **par graisse**, pour le PDF.
     *
     * ⚠️ **dompdf ne garde que quatre variantes par famille** (normale, grasse,
     * italique, grasse italique) : nos six graisses de Jost s'y écrasaient deux
     * à deux, et un texte dessiné en 500 ressortait en 400, un titre en 700 en
     * 900. On déclare donc une famille par graisse (« Jost 500 »), que dompdf
     * n'a plus à départager.
     */
    public static function pdfFamily(string $key, int $weight, bool $italic): ?string
    {
        $family = self::FAMILIES[$key] ?? null;
        if ($family === null) {
            return null;
        }

        $style = $italic ? 'italic' : 'normal';
        $candidats = array_values(array_filter(
            self::FACES,
            fn (array $f) => $f[1] === $family && $f[3] === $style
        ));

        if ($candidats === []) {
            return null;
        }

        // La graisse livrée la plus proche de celle demandée.
        usort($candidats, fn ($a, $b) => abs($a[2] - $weight) <=> abs($b[2] - $weight));

        return self::pdfFamilyName($candidats[0][1], $candidats[0][2], $candidats[0][3]);
    }

    /** Nom de la famille dédiée à une fonte, côté PDF. */
    public static function pdfFamilyName(string $family, int $weight, string $style): string
    {
        return $family.' '.$weight.($style === 'italic' ? ' Italic' : '');
    }

    /** Fichier dont on lit les métriques pour une clé du concepteur. */
    public static function metricsFile(string $key): ?string
    {
        $family = self::FAMILIES[$key] ?? null;
        if ($family === null) {
            return null;
        }

        foreach (self::FACES as [$file, $f, , $style]) {
            if ($f === $family && $style === 'normal') {
                return public_path('fonts/'.$file.'.ttf');
            }
        }

        return null;
    }
}
