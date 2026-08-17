<?php

namespace App\Actions;

use App\AI\AiManager;
use App\Models\Edition;
use App\Support\AiAgents;
use Illuminate\Support\Str;

/**
 * Répond à une question de participant via l'assistante Gesco. Utilise la
 * configuration de l'agent « gesco » (persona + instructions POSITIVES /
 * NÉGATIVES, cf. {@see AiAgents}) et le contexte de l'édition active. Gesco
 * informe et oriente : elle ne réalise aucune action à la place de l'utilisateur.
 *
 * @phpstan-type ChatTurn array{role: string, text: string}
 */
class AskGesco
{
    public function __construct(protected AiManager $manager) {}

    /**
     * @param  list<ChatTurn>  $history  tours précédents (rôle bot|user)
     */
    public function handle(array $history, string $question, ?Edition $edition = null): string
    {
        $provider = $this->manager->resolve(false);
        if (! $provider) {
            throw new \RuntimeException('L’assistance Gesco est momentanément indisponible.');
        }

        $raw = $provider->extract($this->instruction($history, $question, $edition), []);

        $answer = trim((string) ($raw['answer'] ?? ''));

        return $answer !== ''
            ? Str::limit($answer, 4000, '')
            : 'Je ne suis pas sûre de pouvoir répondre à cela. Contactez la DRI à dri@univkara.tg pour toute question précise.';
    }

    /**
     * @param  list<ChatTurn>  $history
     */
    protected function instruction(array $history, string $question, ?Edition $edition): string
    {
        $agent = AiAgents::find('gesco') ?? AiAgents::defaults()[0];

        $context = 'Aucune édition active pour le moment.';
        if ($edition) {
            $context = trim(implode(' · ', array_filter([
                $edition->title ?: $edition->colloque?->name,
                $edition->theme ? 'Thème : '.$edition->theme : null,
                $edition->date_start ? 'Dates : '.$edition->date_start->translatedFormat('d/m/Y').($edition->date_end ? ' – '.$edition->date_end->translatedFormat('d/m/Y') : '') : null,
                $edition->location,
                $edition->submission_open ? 'Soumissions ouvertes'.($edition->submission_deadline ? ' jusqu’au '.$edition->submission_deadline->translatedFormat('d/m/Y') : '') : 'Soumissions fermées',
            ])));
        }

        // Derniers tours de conversation (bornés).
        $convo = collect($history)
            ->slice(-8)
            ->map(fn (array $t) => ($t['role'] === 'user' ? 'Utilisateur' : 'Gesco').' : '.Str::limit((string) $t['text'], 500, ''))
            ->implode("\n");

        return <<<TXT
        {$agent['persona']}

        À TOUJOURS FAIRE :
        {$agent['positive']}

        À NE JAMAIS FAIRE :
        {$agent['negative']}

        Contexte du colloque actif : {$context}

        Historique récent :
        {$convo}

        Nouvelle question de l'utilisateur : {$question}

        Réponds de façon utile et concise, en texte simple SANS markdown (pas de ** ni de #).
        Réponds UNIQUEMENT par un objet JSON STRICT (aucun texte autour) : { "answer": string }.
        Le texte peut contenir des sauts de ligne.
        TXT;
    }
}
