<?php

namespace App\Actions;

use App\AI\AiManager;
use App\Models\Claim;
use App\Support\ColloqueKnowledge;
use Illuminate\Support\Str;

/**
 * Analyse de traitement d'une réclamation / incident par l'IA. À la demande d'un
 * gestionnaire, l'IA examine le souci et propose l'une de trois issues :
 *  - « resolvable » : l'IA peut proposer une résolution directe (à valider) ;
 *  - « procedure »  : la procédure à suivre pour résoudre (étapes) ;
 *  - « unresolvable »: le problème est irrésolvable en l'état + explication.
 * L'IA PROPOSE ; la décision et l'exécution restent humaines.
 */
class AnalyzeClaim
{
    public function __construct(protected AiManager $manager) {}

    /**
     * @return array{verdict: string, summary: string, steps: list<string>, resolution: string, reason: string}
     */
    public function handle(Claim $claim): array
    {
        $provider = $this->manager->resolve(false);
        if (! $provider) {
            throw new \RuntimeException('L’IA est momentanément indisponible (voir Réglages).');
        }

        // Analyse de traitement : palier qualité (raisonnement plus fin).
        $raw = $provider->extract($this->instruction($claim), [], 'deep');

        return $this->sanitize($raw);
    }

    protected function instruction(Claim $claim): string
    {
        $knowledge = ColloqueKnowledge::brief($claim->edition);
        $context = $knowledge !== '' ? $knowledge : 'Aucune information d’édition disponible.';

        $type = $claim->typeLabel();
        $edition = $claim->edition?->title ?: ($claim->edition?->year ? (string) $claim->edition->year : 'non précisée');
        $category = $claim->category ?: 'non précisée';
        $subject = $claim->subject;
        $message = Str::limit((string) $claim->message, 4000, '');
        $who = trim(($claim->name ?: '').' <'.($claim->email ?: 'e-mail inconnu').'>');

        return <<<TXT
        Tu assistes le service d'organisation d'un colloque scientifique (Université de Kara) pour
        traiter une {$type}. Analyse le souci ci-dessous et propose une issue réaliste, en français,
        en t'appuyant sur la BASE DE CONNAISSANCE. N'invente aucune donnée ni procédure inexistante.

        ============================================================
        BASE DE CONNAISSANCE DU COLLOQUE :
        {$context}
        ============================================================

        {$type} à traiter :
        - Édition : {$edition}
        - Catégorie : {$category}
        - Déclarant : {$who}
        - Objet : {$subject}
        - Description : {$message}

        Détermine UNE des trois issues :
        - "resolvable" : le problème peut être résolu directement (ex. réponse/procédure simple,
          information à fournir). Donne alors "resolution" = ce qu'il convient de faire/répondre
          pour résoudre (texte prêt à appliquer par le gestionnaire).
        - "procedure" : la résolution demande plusieurs étapes / une intervention. Donne alors
          "steps" = la liste ordonnée des étapes concrètes à suivre.
        - "unresolvable" : le problème est irrésolvable en l'état (hors périmètre, données
          manquantes, contrainte réglementaire…). Donne alors "reason" = ce qui bloque et pourquoi,
          et éventuellement ce qu'il faudrait pour débloquer.

        "summary" = un résumé bref (1–2 phrases) de ton analyse.

        Réponds UNIQUEMENT par un objet JSON STRICT (aucun texte autour) :
        { "verdict": "resolvable"|"procedure"|"unresolvable", "summary": string,
          "steps": [string], "resolution": string, "reason": string }.
        Les champs non pertinents peuvent être vides ("" ou []).
        TXT;
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array{verdict: string, summary: string, steps: list<string>, resolution: string, reason: string}
     */
    protected function sanitize(array $raw): array
    {
        $verdict = (string) ($raw['verdict'] ?? '');
        if (! in_array($verdict, ['resolvable', 'procedure', 'unresolvable'], true)) {
            $verdict = 'procedure';
        }

        $steps = [];
        foreach (is_array($raw['steps'] ?? null) ? $raw['steps'] : [] as $step) {
            $s = Str::limit(trim((string) $step), 500, '');
            if ($s !== '') {
                $steps[] = $s;
            }
        }

        return [
            'verdict' => $verdict,
            'summary' => Str::limit(trim((string) ($raw['summary'] ?? '')), 1000, ''),
            'steps' => array_slice($steps, 0, 20),
            'resolution' => Str::limit(trim((string) ($raw['resolution'] ?? '')), 5000, ''),
            'reason' => Str::limit(trim((string) ($raw['reason'] ?? '')), 3000, ''),
        ];
    }
}
