<?php

namespace App\Actions;

use App\AI\AiManager;
use App\Models\Edition;
use App\Models\Submission;
use App\Models\User;
use App\Support\AiAgents;
use App\Support\ColloqueKnowledge;
use App\Support\EditionReport;
use App\Support\StaffRoles;
use Illuminate\Support\Facades\DB;
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
    public function handle(array $history, string $question, ?Edition $edition = null, ?User $staff = null): array
    {
        $provider = $this->manager->resolve(false);
        if (! $provider) {
            throw new \RuntimeException('Le copilote IA est momentanément indisponible (voir Réglages).');
        }

        $raw = $provider->extract($this->instruction($history, $question, $edition, $staff), []);
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
    /**
     * Décrit la fonction du membre du staff et son périmètre autorisé, pour que
     * le copilote réponde dans le cadre de sa fonction (ex. un comptable est
     * cadré sur les paiements/finances).
     *
     * @return array{fonction: string, perimetre: string}
     */
    protected function role(?User $staff, ?Edition $edition): array
    {
        if ($staff === null) {
            return ['fonction' => 'Membre du staff', 'perimetre' => 'périmètre limité'];
        }
        if ($staff->isColloqueSuperAdmin()) {
            return ['fonction' => 'Administrateur du colloque (accès complet)', 'perimetre' => 'toutes les fonctions'];
        }

        $roles = $edition ? $staff->rolesOnEdition($edition) : [];
        if ($roles === []) {
            $roles = DB::table('edition_user')->where('user_id', $staff->id)->distinct()->pluck('role')->all();
        }
        $fonction = collect($roles)->map(fn ($r) => StaffRoles::label((string) $r))->unique()->implode(' + ');

        $caps = $edition ? $staff->capabilitiesOnEdition($edition) : [];
        if ($caps === []) {
            foreach ($roles as $r) {
                $caps = array_merge($caps, StaffRoles::permissionsFor((string) $r));
            }
            $caps = array_values(array_unique($caps));
        }
        $perimetre = collect($caps)->map(fn ($c) => StaffRoles::PERMISSIONS[$c] ?? $c)->implode(', ');

        return [
            'fonction' => $fonction !== '' ? $fonction : 'Membre du staff',
            'perimetre' => $perimetre !== '' ? $perimetre : 'périmètre limité',
        ];
    }

    protected function instruction(array $history, string $question, ?Edition $edition, ?User $staff = null): string
    {
        $agent = AiAgents::find('admin') ?? AiAgents::defaults()[1];
        $role = $this->role($staff, $edition);

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

        FONCTION DE L'UTILISATEUR : {$role['fonction']}
        PÉRIMÈTRE AUTORISÉ (ses fonctions) : {$role['perimetre']}
        RÈGLE DE PÉRIMÈTRE (impérative) : réponds STRICTEMENT dans le cadre de la fonction et du
        périmètre ci-dessus, en adoptant ce rôle (ex. un comptable / assistant comptable est cadré
        sur les paiements, encaissements et finances). Si la demande porte sur un domaine hors de ce
        périmètre (ex. contenu scientifique, relectures, soumissions pour un comptable), explique
        poliment que cela relève d'une autre fonction et oriente vers la personne ou l'onglet
        compétent, sans divulguer ces informations.

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
