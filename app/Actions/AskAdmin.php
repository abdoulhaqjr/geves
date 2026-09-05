<?php

namespace App\Actions;

use App\AI\AiManager;
use App\Models\Edition;
use App\Models\Submission;
use App\Support\AiAgents;
use App\Support\ColloqueKnowledge;
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
        $knowledge = '';
        if ($edition) {
            $report = new EditionReport($edition);
            $s = $report->summary();
            $pay = $report->paymentStats();

            // Répartition des soumissions par statut (libellés réels).
            $statusLabels = Submission::statuses();
            $byStatus = collect($s['submissionsByStatus'])
                ->filter(fn ($n) => $n > 0)
                ->map(fn ($n, $k) => ($statusLabels[$k] ?? $k).' : '.$n)
                ->implode(', ');

            // Axes principaux (top 4 par volume).
            $axes = collect($report->submissionsByAxis())->take(4)
                ->map(fn ($a) => $a['axe'].' ('.$a['total'].', acceptées '.$a['accepted'].')')
                ->implode(', ');

            $context = implode("\n", array_filter([
                'Édition : '.($edition->title ?: 'Édition').' ('.$edition->year.') · statut '.$edition->status
                    .($edition->submission_deadline ? ' · clôture soumissions le '.$edition->submission_deadline->translatedFormat('d/m/Y') : ''),
                'Participants : '.$s['participants'],
                'Communications : '.$s['submissions'].' (acceptées : '.$s['accepted'].')'.($byStatus ? ' — '.$byStatus : ''),
                $axes ? 'Axes principaux : '.$axes : null,
                'Paiements : '.$pay['ok'].' réglés, '.$pay['pending'].' en attente, '.$pay['failed'].' échoués/annulés',
                'Recettes : '.number_format($s['collected'], 0, ',', ' ').' '.$s['currency'].' encaissés · '
                    .number_format($s['pending'], 0, ',', ' ').' '.$s['currency'].' en attente',
            ]));

            $knowledge = ColloqueKnowledge::brief($edition);
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

        CHIFFRES RÉELS DE L'ÉDITION ACTIVE :
        {$context}

        BASE DE CONNAISSANCE DU COLLOQUE (paramétrage officiel : tarifs, axes, échéances, règles, FAQ…) :
        {$knowledge}

        RÈGLE DE PRÉCISION (impérative) : appuie-toi UNIQUEMENT sur les chiffres et la base de
        connaissance ci-dessus. Cite les valeurs exactes (montants, effectifs, dates). Si une
        donnée n'y figure pas, dis-le explicitement et propose où la vérifier ; n'invente aucun
        chiffre ni aucune procédure. Réponds précisément à la demande, sans digression.

        Historique récent :
        {$convo}

        Nouvelle demande de l'administrateur : {$question}

        Tu PROPOSES uniquement (synthèses, brouillons, points de vigilance) ; tu ne réalises aucune
        action. Structure ta réponse pour la rendre lisible : phrases courtes, un saut de ligne entre
        les idées, une liste à puces (« - ») pour énumérer, **gras** pour les points clés. N'utilise
        ni titres « # » ni tableaux. Reste synthétique.

        Quand ta réponse suggère une action concrète que l'administrateur pourrait approuver (relance
        d'e-mails, export, génération de document, plan d'actions…), ajoute une "proposal" décrivant
        cette action : "title" (intitulé court), "detail" (ce qui serait fait), "impact" (portée /
        garde-fou). Sinon mets "proposal" à null. La proposition n'est JAMAIS exécutée automatiquement.

        Réponds UNIQUEMENT par un objet JSON STRICT (aucun texte autour) :
        { "answer": string, "proposal": { "title": string, "detail": string, "impact": string } | null }.
        TXT;
    }
}
