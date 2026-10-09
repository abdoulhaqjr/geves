<?php

namespace App\Support;

use App\Models\TutorialGuide;
use Illuminate\Support\Facades\Storage;

/**
 * Contrat transmis à l'agent qui produit les vidéos de prise en main.
 *
 * Il garde les décisions de marque hors des prompts libres saisis par les
 * administrateurs : chaque rendu GEVES part donc avec la même charte.
 */
final class TutorialVideoProduction
{
    /** @return array<string, mixed> */
    public static function briefFor(TutorialGuide $guide, ?string $instruction = null): array
    {
        $stepCount = max(1, count($guide->steps ?: []));
        $customMark = Settings::get(Branding::MARK);
        $logo = filled($customMark) && Storage::disk('public')->exists((string) $customMark)
            ? Storage::disk('public')->path((string) $customMark)
            : public_path('img/apple-touch-icon.png');

        return [
            'engine' => 'geves-hyperframes',
            'renderer' => 'hyperframes',
            'workflow' => 'brag-inspired',
            'format' => ['ratio' => '16:9', 'duration_seconds' => max(42, 20 + ($stepCount * 10)), 'tone' => 'polished'],
            'guide' => [
                'key' => $guide->key,
                'title' => $guide->title,
                'intro' => $guide->intro,
                'steps' => $guide->steps ?: [],
                'instruction' => blank($instruction) ? null : $instruction,
            ],
            'brand' => [
                'logo' => $logo,
                'colors' => [
                    'blue' => '#223ea4',
                    'blue_dark' => '#1a3080',
                    'blue_bright' => '#2f52c8',
                    'green' => '#009933',
                    'ink' => '#0f172a',
                    'background' => '#f8fafc',
                ],
                'font' => 'Jost',
            ],
            'creative_direction' => [
                'show_real_interface' => false,
                'keep_text_readable' => true,
                'motion' => 'Transitions douces, interactions illustrées et simulations d’écran contextualisées à chaque étape.',
                'music' => 'Lit musical original, chaleureux et entraînant, synchronisé avec les révélations ; effets UI discrets.',
                'voiceover' => false,
                'avoid' => ['visuels génériques', 'revendications non présentes dans GEVES', 'pulsations ou effets sonores agressifs'],
            ],
            'deliverables' => ['brag-plan.md', 'composition-brief.md', 'brag.mp4', 'brag.jpg', 'share-copy.txt'],
        ];
    }
}
