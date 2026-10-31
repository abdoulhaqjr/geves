<?php

namespace App\Actions;

use App\AI\AiManager;
use App\Models\Claim;
use App\Models\Participant;
use App\Payments\GatewayRegistry;
use App\Support\EventKnowledge;
use App\Support\EventPaymentConfig;
use App\Support\Fees;
use App\Support\Money;
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
        if (! $this->manager->resolve(false)) {
            throw new \RuntimeException('L’IA est momentanément indisponible (voir Réglages).');
        }

        // Analyse de traitement : palier qualité (raisonnement plus fin) + bascule de fournisseur.
        $raw = $this->manager->extract($this->instruction($claim), [], 'deep');

        return $this->sanitize($raw);
    }

    /**
     * Ce que la BASE dit de ce dossier, pour que l'analyse cesse d'inventer.
     *
     * ⚠️⚠️ **CONSTATÉ LE 31/10/2026 SUR UNE VRAIE RÉCLAMATION.** Faute de faits,
     * le modèle comblait : il proposait « virement bancaire direct » et
     * « paiement sur place » à un participant dont l'événement **n'offre que le
     * paiement en ligne** (depuis que `GatewayRegistry::offered()` ne propose
     * que la passerelle réglée), et inventait une heure de passage. Le
     * gestionnaire aurait transmis au déclarant des démarches que la plateforme
     * refuse - c'est pire que pas d'analyse du tout.
     *
     * ⚠️ Même famille que la garde du copilote : **le chiffre qui fait foi est
     * celui de l'action, pas un sous-total recalculé par le modèle.** Ici, les
     * moyens de paiement, le montant et l'état du dossier viennent des mêmes
     * sources que les écrans - jamais d'une déduction.
     *
     * ⚠️ On écrit « inconnu » plutôt que de taire un manque : une absence
     * silencieuse est exactement ce que le modèle remplit tout seul.
     */
    protected function faits(Claim $claim): string
    {
        $event = $claim->event;
        if (! $event) {
            return "- Aucun événement rattaché : impossible de parler de tarifs, de paiement ou de programme.\n"
                .'- Première chose à faire : rattacher la réclamation à son événement (bloc « Rattachement »).';
        }

        $lignes = [];

        // Moyens de paiement RÉELLEMENT proposés par cet événement.
        $moyens = EventPaymentConfig::using($event, fn () => array_map(
            fn ($g) => $g->label(),
            app(GatewayRegistry::class)->offered(),
        ));
        $lignes[] = '- Moyens de paiement proposés par cet événement : '
            .($moyens === [] ? 'aucun' : implode(', ', $moyens))
            .'. AUCUN AUTRE n’existe sur la plateforme pour cet événement.';

        $participant = Participant::where('event_id', $event->id)
            ->where(fn ($q) => $q->where('email', $claim->email)
                ->orWhereHas('user', fn ($u) => $u->where('email', $claim->email)))
            ->first();

        if (! $participant) {
            $lignes[] = '- Déclarant : AUCUNE fiche participant à cet événement pour cette adresse. '
                .'Son dossier, son tarif et son paiement sont donc inconnus.';

            return implode("\n", $lignes);
        }

        $montant = Fees::forParticipant($participant);
        $categorie = Fees::labelForParticipant($participant);

        $lignes[] = '- Déclarant : inscrit à cet événement, rôle « '.$participant->roleLabel().' ».';
        $lignes[] = '- Frais : '.($montant === null
            ? 'montant inconnu (aucune catégorie tarifaire ne correspond à son grade)'
            : Money::format($montant).($categorie ? ' (catégorie « '.$categorie.' »)' : ''));
        $lignes[] = '- Paiement : '.($participant->hasPaid()
            ? 'DÉJÀ RÉGLÉ - ne lui demande pas de payer.'
            : ($participant->owesParticipationFees()
                ? 'dû et non réglé.'
                : 'pas encore dû (sa participation n’ouvre pas de frais à ce stade).'));

        return implode("\n", $lignes);
    }

    protected function instruction(Claim $claim): string
    {
        $knowledge = EventKnowledge::brief($claim->event);
        $context = $knowledge !== '' ? $knowledge : 'Aucune information d’événement disponible.';

        $type = $claim->typeLabel();
        $event = $claim->event?->title ?: ($claim->event?->year ? (string) $claim->event->year : 'non précisée');
        $category = $claim->category ?: 'non précisée';
        $subject = $claim->subject;
        $message = Str::limit((string) $claim->message, 4000, '');
        $who = trim(($claim->name ?: '').' <'.($claim->email ?: 'e-mail inconnu').'>');

        $faits = $this->faits($claim);

        return <<<TXT
        Tu assistes le service d'organisation d'un colloque scientifique (Université de Kara) pour
        traiter une {$type}. Analyse le souci ci-dessous et propose une issue réaliste, en français,
        en t'appuyant sur la BASE DE CONNAISSANCE. N'invente aucune donnée ni procédure inexistante.

        ============================================================
        BASE DE CONNAISSANCE DU COLLOQUE :
        {$context}
        ============================================================
        FAITS DU DOSSIER (lus dans la base, ils FONT AUTORITÉ) :
        {$faits}
        ============================================================

        RÈGLES ABSOLUES, elles priment sur tout le reste :
        - Ne propose AUCUN moyen de paiement, canal, interlocuteur, lieu ou démarche qui ne
          figure pas dans les FAITS DU DOSSIER. En particulier : si un seul moyen de paiement y
          est listé, c'est le seul qui existe - ne suggère ni virement bancaire, ni espèces, ni
          paiement sur place, ni « contactez votre banque », à moins que les FAITS ne les nomment.
        - N'invente ni horaire, ni salle, ni numéro, ni montant, ni référence. Si un élément
          manque, écris qu'il manque et dis où le chercher.
        - Ce qui est marqué « inconnu » dans les FAITS est inconnu : ne le devine pas.
        - Reste dans le périmètre de la plateforme : tu proposes des gestes que le service peut
          réellement accomplir ici.

        {$type} à traiter :
        - Événement : {$event}
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

        "summary" = un résumé bref (1-2 phrases) de ton analyse.

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
