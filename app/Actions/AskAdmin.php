<?php

namespace App\Actions;

use App\AI\AiManager;
use App\AI\AiRouter;
use App\AI\Copilot\CopilotActions;
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
 * et des chiffres réels de l'édition active. Il peut aussi PROPOSER une action
 * concrète (parmi {@see CopilotActions}) que l'utilisateur autorise explicitement
 * avant qu'elle ne soit exécutée côté serveur — jamais d'exécution d'office.
 *
 * @phpstan-type ChatTurn array{role: string, text: string}
 */
class AskAdmin
{
    public function __construct(protected AiManager $manager, protected AiRouter $router, protected CopilotActions $copilot) {}

    /**
     * @param  list<ChatTurn>  $history
     * @return array{answer: string, proposal: array{title: string, detail: string, impact: string}|null, action: array{key: string, label: string, summary: string, params: array<string, mixed>}|null}
     */
    public function handle(array $history, string $question, ?Edition $edition = null, ?User $staff = null): array
    {
        if (! $this->manager->resolve(false)) {
            throw new \RuntimeException('Le copilote IA est momentanément indisponible (voir Réglages).');
        }

        // Palier de modèle choisi selon la complexité (économie sur les demandes simples).
        $tier = $this->router->tier('chat_admin', $question, historyTurns: count($history));

        // Bascule automatique entre fournisseurs configurés en cas d'échec.
        $raw = $this->manager->extract($this->instruction($history, $question, $edition, $staff), [], $tier);
        $answer = trim((string) ($raw['answer'] ?? ''));

        return [
            'answer' => $answer !== '' ? Str::limit($answer, 4000, '') : 'Je n’ai pas d’élément suffisant pour répondre.',
            'proposal' => $this->sanitizeProposal($raw['proposal'] ?? null),
            'action' => $this->sanitizeAction($raw['action'] ?? null, $staff, $edition),
        ];
    }

    /**
     * Normalise l'action exécutable éventuellement choisie par l'IA : on ne retient
     * QUE si l'action existe ET que l'utilisateur a la capacité de l'exécuter sur
     * l'édition (double garde-fou : le serveur revérifie à l'exécution).
     *
     * @return array{key: string, label: string, summary: string, params: array<string, mixed>}|null
     */
    protected function sanitizeAction(mixed $action, ?User $staff, ?Edition $edition): ?array
    {
        if (! is_array($action) || $staff === null || $edition === null) {
            return null;
        }

        $resolved = $this->copilot->resolveFor($staff, $edition, (string) ($action['key'] ?? ''));
        if ($resolved === null) {
            return null;
        }

        $params = is_array($action['params'] ?? null) ? $action['params'] : [];

        return [
            'key' => $resolved->key(),
            'label' => $resolved->label(),
            'summary' => Str::limit($resolved->summary($params, $edition), 300, ''),
            'params' => $params,
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

        // Actions réellement exécutables pour CET utilisateur sur CETTE édition.
        $available = $staff !== null ? $this->copilot->availableFor($staff, $edition) : [];
        $actionsBlock = $available === []
            ? 'ACTIONS EXÉCUTABLES : aucune action exécutable n’est disponible pour ta fonction sur ce colloque. Mets toujours "action" à null.'
            : "ACTIONS EXÉCUTABLES DISPONIBLES (tu peux en proposer UNE, et une seule, quand la demande de l’utilisateur le justifie clairement) :\n"
                .collect($available)->map(fn ($a) => '- key "'.$a->key().'" : '.$a->description())->implode("\n")."\n"
                .'Règles d’action : ne propose une "action" QUE si l’utilisateur exprime clairement cette intention ; '
                .'n’invente JAMAIS une "key" hors de la liste ci-dessus ; propose au plus UNE action. '
                .'Elle n’est JAMAIS exécutée d’office — l’utilisateur devra l’autoriser explicitement (il cliquera pour l’exécuter). '
                .'Dans "answer", dis en une phrase ce que tu vas faire et demande l’autorisation. Sinon mets "action" à null.';

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

        Tu assistes l'utilisateur (synthèses, brouillons, points de vigilance) ET tu peux DÉCLENCHER
        certaines actions concrètes — mais uniquement via la liste ci-dessous et après son autorisation
        explicite. Structure ta réponse pour la rendre lisible : phrases courtes, un saut de ligne entre
        les idées, une liste à puces (« - ») pour énumérer, **gras** pour les points clés. N'utilise ni
        titres « # » ni tableaux. Reste synthétique et va droit au but — n'invite pas l'utilisateur à
        faire lui-même ce que tu peux exécuter via une action disponible.

        {$actionsBlock}

        Si tu suggères une piste d'action SANS outil correspondant dans la liste (ex. export manuel,
        décision d'organisation), tu peux ajouter une "proposal" descriptive : "title" (intitulé court),
        "detail" (ce qui serait fait), "impact" (portée / garde-fou). Sinon mets "proposal" à null.

        Réponds UNIQUEMENT par un objet JSON STRICT (aucun texte autour) :
        { "answer": string,
          "proposal": { "title": string, "detail": string, "impact": string } | null,
          "action": { "key": string, "params": object } | null }.
        TXT;
    }
}
