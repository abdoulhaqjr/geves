<?php

namespace App\Actions;

use App\AI\AiManager;
use App\Models\Edition;
use App\Support\AiAgents;
use App\Support\EditionReport;
use Illuminate\Support\Str;

/**
 * Copilote d'administration : répond aux questions du staff (synthèses,
 * brouillons, vérifications) à partir de l'agent « admin » (cf. {@see AiAgents})
 * et des chiffres réels de l'édition active. Il **propose** — il n'exécute
 * jamais d'action (suppression, validation, envoi) : la décision reste humaine.
 *
 * @phpstan-type ChatTurn array{role: string, text: string}
 */
class AskAdmin
{
    public function __construct(protected AiManager $manager) {}

    /**
     * @param  list<ChatTurn>  $history
     * @return array{answer: string, proposal: array{title: string, detail: string, impact: string}|null}
     */
    public function handle(array $history, string $question, ?Edition $edition = null): array
    {
        $provider = $this->manager->resolve(false);
        if (! $provider) {
            throw new \RuntimeException('Le copilote IA est momentanément indisponible (voir Réglages).');
        }

        $raw = $provider->extract($this->instruction($history, $question, $edition), []);
        $answer = trim((string) ($raw['answer'] ?? ''));

        return [
            'answer' => $answer !== '' ? Str::limit($answer, 4000, '') : 'Je n’ai pas d’élément suffisant pour répondre.',
            'proposal' => $this->sanitizeProposal($raw['proposal'] ?? null),
        ];
    }

    /**
     * Normalise la proposition d'action éventuelle (titre + détail + impact).
     *
     * @return array{title: string, detail: string, impact: string}|null
     */
    protected function sanitizeProposal(mixed $proposal): ?array
    {
        if (! is_array($proposal)) {
            return null;
        }

        $title = Str::limit(trim((string) ($proposal['title'] ?? '')), 120, '');
        if ($title === '') {
            return null;
        }

        return [
            'title' => $title,
            'detail' => Str::limit(trim((string) ($proposal['detail'] ?? '')), 400, ''),
            'impact' => Str::limit(trim((string) ($proposal['impact'] ?? '')), 200, ''),
        ];
    }

    /**
     * @param  list<ChatTurn>  $history
     */
    protected function instruction(array $history, string $question, ?Edition $edition): string
    {
        $agent = AiAgents::find('admin') ?? AiAgents::defaults()[1];

        $context = 'Aucune édition active.';
        if ($edition) {
            $s = (new EditionReport($edition))->summary();
            $context = implode(' · ', [
                ($edition->title ?: 'Édition').' ('.$edition->year.')',
                'Participants : '.$s['participants'],
                'Communications : '.$s['submissions'].' (acceptées : '.$s['accepted'].')',
                'Encaissé : '.$s['collected'].' '.$s['currency'],
                'En attente : '.$s['pending'].' '.$s['currency'],
            ]);
        }

        $convo = collect($history)->slice(-8)
            ->map(fn (array $t) => ($t['role'] === 'user' ? 'Admin' : 'Copilote').' : '.Str::limit((string) $t['text'], 500, ''))
            ->implode("\n");

        return <<<TXT
        {$agent['persona']}

        À TOUJOURS FAIRE :
        {$agent['positive']}

        À NE JAMAIS FAIRE :
        {$agent['negative']}

        Contexte (chiffres réels de l'édition active) : {$context}

        Historique récent :
        {$convo}

        Nouvelle demande de l'administrateur : {$question}

        Tu PROPOSES uniquement (synthèses, brouillons, points de vigilance) ; tu ne réalises aucune
        action. Réponds en texte simple sans markdown.

        Quand ta réponse suggère une action concrète que l'administrateur pourrait approuver (relance
        d'e-mails, export, génération de document, plan d'actions…), ajoute une "proposal" décrivant
        cette action : "title" (intitulé court), "detail" (ce qui serait fait), "impact" (portée /
        garde-fou). Sinon mets "proposal" à null. La proposition n'est JAMAIS exécutée automatiquement.

        Réponds UNIQUEMENT par un objet JSON STRICT (aucun texte autour) :
        { "answer": string, "proposal": { "title": string, "detail": string, "impact": string } | null }.
        TXT;
    }
}
