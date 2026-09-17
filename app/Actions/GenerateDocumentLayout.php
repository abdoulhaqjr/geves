<?php

namespace App\Actions;

use App\AI\AiManager;
use App\Documents\LayoutSanitizer;

/**
 * Génère la mise en page (JSON) d'un badge ou d'une attestation par IA à partir
 * d'une consigne de style (et d'une image d'inspiration optionnelle). L'IA ne
 * produit QUE le JSON ; la validation (LayoutSanitizer) et le rendu restent
 * déterministes.
 */
class GenerateDocumentLayout
{
    public function __construct(protected AiManager $manager) {}

    /**
     * @param  array<int, array{mime: string, data: string}>  $files
     * @return array<string, mixed>
     */
    public function handle(string $kind, string $type, string $prompt, array $files = []): array
    {
        $needsPdf = collect($files)->contains(fn ($f) => str_contains($f['mime'], 'pdf'));

        if (! $this->manager->resolve($needsPdf)) {
            throw new \RuntimeException('Aucun fournisseur d’IA n’est configuré.');
        }

        // Conception de maquette de document : palier qualité + bascule de fournisseur.
        $raw = $this->manager->extract($this->instruction($kind, $prompt), $files, 'deep');

        return LayoutSanitizer::sanitize($raw, $kind, $type);
    }

    protected function instruction(string $kind, string $prompt): string
    {
        $default = LayoutSanitizer::default($kind);
        $w = $default['width'];
        $h = $default['height'];

        $vars = $kind === 'badge'
            ? '{org} {event} {year} {name} {role} {institution} {ref}'
            : '{org} {event} {year} {name} {type} {date} {location} {ref} {verify_url}';

        return <<<TXT
        Tu es un designer. Génère la mise en page d'un(e) {$kind} scientifique sous forme d'un
        objet JSON STRICT (aucun texte autour).

        Canvas : width={$w}, height={$h} (pixels). Reste dans ces dimensions.
        Champs : { "width": int, "height": int, "bg": "#rrggbb", "accent": "#rrggbb", "elements": [ … ] }

        Éléments autorisés (uniquement) :
        - {"type":"rect","x":int,"y":int,"w":int,"h":int,"color":"#rrggbb"}
        - {"type":"text","x":int,"y":int,"w":int,"h":int,"text":string,"size":int,"weight":int,"color":"#rrggbb","align":"left|center|right"}
        - {"type":"image","x":int,"y":int,"w":int,"h":int,"src":"https://…"}
        - {"type":"qr","x":int,"y":int,"size":int,"value":string}

        Utilise EXCLUSIVEMENT ces variables dans les textes (elles seront remplacées) : {$vars}.
        N'écris pas de vraies valeurs à leur place. Respecte une charte sobre et lisible.

        Consigne de style : {$prompt}
        TXT;
    }
}
