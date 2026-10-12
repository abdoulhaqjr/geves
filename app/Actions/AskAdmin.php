<?php

namespace App\Actions;

use App\AI\AiManager;
use App\AI\AiRouter;
use App\AI\Copilot\CopilotActions;
use App\Models\Claim;
use App\Models\Event;
use App\Models\Formation;
use App\Models\FormationEnrollment;
use App\Models\ProjectMilestone;
use App\Models\ProjectTask;
use App\Models\SessionReport;
use App\Models\Submission;
use App\Models\User;
use App\Support\AdminDirectory;
use App\Support\AiAgents;
use App\Support\EventKnowledge;
use App\Support\EventReport;
use App\Support\ProgramKnowledge;
use App\Support\StaffRoles;
use App\Support\UserDirectory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Copilote d'administration : répond aux questions du staff (synthèses,
 * brouillons, vérifications) à partir de l'agent « admin » (cf. {@see AiAgents})
 * et des chiffres réels de l'événement actif. Quand la demande le justifie, il
 * propose UNE action concrète (parmi {@see CopilotActions}) que l'utilisateur
 * autorise explicitement avant qu'elle ne soit exécutée côté serveur - jamais
 * d'exécution d'office. Les conseils qu'il ne peut pas exécuter lui-même restent
 * dans son texte de réponse (pas de « proposition » à valider dans le vide).
 *
 * @phpstan-type ChatTurn array{role: string, text: string}
 */
class AskAdmin
{
    public function __construct(protected AiManager $manager, protected AiRouter $router, protected CopilotActions $copilot) {}

    /**
     * @param  list<ChatTurn>  $history
     * @return array{answer: string, action: array{key: string, label: string, summary: string, params: array<string, mixed>}|null}
     */
    public function handle(array $history, string $question, ?Event $event = null, ?User $staff = null): array
    {
        if (! $this->manager->resolve(false)) {
            throw new \RuntimeException('Le copilote IA est momentanément indisponible (voir Réglages).');
        }

        // Palier de modèle choisi selon la complexité (économie sur les demandes simples).
        $tier = $this->router->tier('chat_admin', $question, historyTurns: count($history));

        // Bascule automatique entre fournisseurs configurés en cas d'échec.
        $raw = $this->manager->extract($this->instruction($history, $question, $event, $staff), [], $tier);
        $answer = trim((string) ($raw['answer'] ?? ''));

        return [
            'answer' => $answer !== '' ? Str::limit($answer, 4000, '') : 'Je n’ai pas d’élément suffisant pour répondre.',
            'action' => $this->sanitizeAction($raw['action'] ?? null, $staff, $event),
        ];
    }

    /**
     * Normalise l'action exécutable éventuellement choisie par l'IA : on ne retient
     * QUE si l'action existe ET que l'utilisateur a la capacité de l'exécuter sur
     * l'événement (double garde-fou : le serveur revérifie à l'exécution).
     *
     * @return array{key: string, label: string, summary: string, params: array<string, mixed>}|null
     */
    protected function sanitizeAction(mixed $action, ?User $staff, ?Event $event): ?array
    {
        if (! is_array($action) || $staff === null || $event === null) {
            return null;
        }

        $resolved = $this->copilot->resolveFor($staff, $event, (string) ($action['key'] ?? ''));
        if ($resolved === null) {
            return null;
        }

        $params = is_array($action['params'] ?? null) ? $action['params'] : [];

        return [
            'key' => $resolved->key(),
            'label' => $resolved->label(),
            'summary' => Str::limit($resolved->summary($params, $event), 300, ''),
            'params' => $params,
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
    protected function role(?User $staff, ?Event $event): array
    {
        if ($staff === null) {
            return ['fonction' => 'Membre du staff', 'perimetre' => 'périmètre limité'];
        }
        if ($staff->isColloqueSuperAdmin()) {
            return ['fonction' => 'Administrateur du colloque (accès complet)', 'perimetre' => 'toutes les fonctions'];
        }

        $roles = $event ? $staff->rolesOnEdition($event) : [];
        if ($roles === []) {
            $roles = DB::table('event_user')->where('user_id', $staff->id)->distinct()->pluck('role')->all();
        }
        $fonction = collect($roles)->map(fn ($r) => StaffRoles::label((string) $r))->unique()->implode(' + ');

        $caps = $event ? $staff->capabilitiesOnEdition($event) : [];
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

    protected function instruction(array $history, string $question, ?Event $event, ?User $staff = null): string
    {
        $agent = AiAgents::find('admin') ?? AiAgents::defaults()[1];
        $role = $this->role($staff, $event);

        $context = 'Aucun événement actif.';
        $knowledge = '';
        if ($event) {
            $report = new EventReport($event);
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

            // Relectures : charge et avancement (comité scientifique).
            $rv = $report->reviewStats();
            $reviewLine = ($rv['done'] + $rv['pending'] + $rv['late'] + $rv['declined']) > 0
                ? 'Relectures : '.$rv['done'].' rendues, '.$rv['pending'].' en attente, '.$rv['late'].' en retard, '
                    .$rv['declined'].' refusées ('.$rv['reviewers'].' relecteurs'
                    .($rv['avgDays'] > 0 ? ', délai moyen '.$rv['avgDays'].' j' : '').')'
                : null;

            // Rapports de séance : validés / attendus (secrétariat).
            $cnt = $report->counts();
            $reportsExpected = (int) $cnt['session_reports'];
            $reportsValidated = $reportsExpected > 0
                ? SessionReport::where('event_id', $event->id)->where('status', 'validated')->count()
                : 0;
            $reportLine = $reportsExpected > 0
                ? 'Rapports de séance : '.$reportsValidated.' validé(s) sur '.$reportsExpected.' séance(s) avec rapporteur'
                : null;

            // Formations (si le module est proposé sur l'événement).
            $formationLine = null;
            if (Formation::offeredIn($event)) {
                $enr = FormationEnrollment::whereHas('formation', fn ($q) => $q->where('event_id', $event->id))->get();
                if ($enr->isNotEmpty()) {
                    $paid = $enr->where('status', 'paid')->count();
                    $formationLine = 'Formations : '.Formation::where('event_id', $event->id)->count()
                        .' proposée(s), '.$enr->count().' inscription(s) ('.$paid.' réglée(s))';
                }
            }

            // Réclamations / incidents ouverts (support).
            $openClaims = Claim::where('event_id', $event->id)
                ->whereNotIn('status', ['resolved', 'closed'])->count();
            $claimLine = $openClaims > 0 ? 'Réclamations/incidents en cours : '.$openClaims : null;

            // Pilotage du projet : jalons et tâches EN RETARD (échéance dépassée, non
            // terminés). De quoi répondre « qu'est-ce qui est en retard sur le projet ».
            $lateMilestones = ProjectMilestone::where('event_id', $event->id)->get()
                ->filter(fn (ProjectMilestone $m) => $m->isOverdue());
            $lateTasks = ProjectTask::where('event_id', $event->id)->get()
                ->filter(fn (ProjectTask $t) => $t->isOverdue());
            $projectLine = null;
            if ($lateMilestones->isNotEmpty() || $lateTasks->isNotEmpty()) {
                $detail = $lateMilestones->take(3)
                    ->map(fn (ProjectMilestone $m) => '« '.Str::limit($m->title, 50).' » (jalon, échéance '.optional($m->due_date)->format('d/m/Y').')')
                    ->merge($lateTasks->take(3)
                        ->map(fn (ProjectTask $t) => '« '.Str::limit($t->title, 50).' » (tâche, échéance '.optional($t->due_date)->format('d/m/Y').')'))
                    ->implode(', ');
                $projectLine = 'Projet - EN RETARD : '.$lateMilestones->count().' jalon(s), '
                    .$lateTasks->count().' tâche(s). '.$detail;
            }

            $context = implode("\n", array_filter([
                'Événement : '.($event->title ?: 'Événement').' ('.$event->year.') - statut '.$event->status
                    .($event->submission_deadline ? ' - clôture soumissions le '.$event->submission_deadline->translatedFormat('d/m/Y') : ''),
                'Participants : '.$s['participants'],
                'Communications : '.$s['submissions'].' (acceptées : '.$s['accepted'].')'.($byStatus ? ' - '.$byStatus : ''),
                $axes ? 'Axes principaux : '.$axes : null,
                $reviewLine,
                'Paiements : '.$pay['ok'].' réglés, '.$pay['pending'].' en attente, '.$pay['failed'].' échoués/annulés',
                'Recettes : '.number_format($s['collected'], 0, ',', ' ').' '.$s['currency'].' encaissés - '
                    .number_format($s['pending'], 0, ',', ' ').' '.$s['currency'].' en attente',
                $formationLine,
                $reportLine,
                $claimLine,
                $projectLine,
            ]));

            $knowledge = EventKnowledge::brief($event);

            // Planning DÉTAILLÉ des créneaux de communication (horaire, salle, titre,
            // réf, auteur, reprogrammations). La vue d'ensemble des séances vient déjà
            // de la base de connaissance ci-dessus ; ceci ajoute le niveau opérationnel.
            $planning = ProgramKnowledge::planning($event);
            if ($planning !== '') {
                $context .= "\n\n".$planning;
            }

            // Annuaire NOMINATIF (noms, prénoms, e-mails) des participants, paiements et
            // auteurs. ⚠️ Réservé au copilote admin ; borné (cf. AdminDirectory).
            $directory = AdminDirectory::digest($event);
            if ($directory !== '') {
                $context .= "\n\n".$directory;
            }
        }

        // Annuaire des COMPTES (niveaux d'accès, rôles, vérification, connexions).
        // ⚠️ Hors du bloc `if ($event)` : un compte n'appartient pas à un événement,
        // et la question « qui est gérant ? » se pose aussi sans colloque sélectionné.
        // ⚠️ La permission est vérifiée DANS UserDirectory, pas ici (cf. sa classe).
        $comptes = UserDirectory::digest($staff);
        if ($comptes !== '') {
            $context .= "\n\n".$comptes;
        }

        $convo = collect($history)->slice(-8)
            ->map(fn (array $t) => ($t['role'] === 'user' ? 'Admin' : 'Copilote').' : '.Str::limit((string) $t['text'], 500, ''))
            ->implode("\n");

        // Événement en contexte : toutes les réponses et actions s'y rapportent.
        $selected = $event !== null
            ? ($event->title ?: 'Événement').' ('.$event->year.')'
            : 'aucun colloque sélectionné';

        // Actions réellement exécutables pour CET utilisateur sur CETTE événement,
        // avec l'ÉTAT RÉEL de leur ciblage (pour décider sur les bonnes données).
        $available = $staff !== null ? $this->copilot->availableFor($staff, $event) : [];
        $actionsBlock = $available === []
            ? 'ACTIONS EXÉCUTABLES : aucune action exécutable n’est disponible pour ta fonction sur ce colloque. '
                .'Mets toujours "action" à null. Si la demande appelle une opération que tu ne peux pas exécuter '
                .'toi-même, réponds en texte (dans "answer") en expliquant la marche à suivre et l’onglet concerné.'
            : "ACTIONS EXÉCUTABLES DISPONIBLES (tu peux en proposer UNE, et une seule, quand la demande de l’utilisateur le justifie) :\n"
                .collect($available)->map(function ($a) use ($event) {
                    $ctx = $event !== null ? $a->context($event) : null;

                    return '- key "'.$a->key().'" : '.$a->description()
                        .($ctx !== null && $ctx !== '' ? "\n  ÉTAT ACTUEL (ciblage réel de l’action) : ".$ctx : '');
                })->implode("\n")."\n"
                .'Règles d’action : pour décider s’il faut proposer une action, APPUIE-TOI SUR SON « ÉTAT ACTUEL » '
                .'ci-dessus (le ciblage réel de l’action), et NON sur les seules statistiques du tableau de bord, qui '
                .'peuvent couvrir un périmètre différent. DÈS QUE la demande de l’utilisateur correspond à l’une de '
                .'ces actions (ex. « relance les paiements en attente » ↔ key "send_payment_reminders"), PROPOSE '
                .'cette action (renseigne "action") au lieu de décrire une démarche manuelle ou d’inviter '
                .'l’utilisateur à le faire lui-même. N’invente JAMAIS une "key" hors de la liste ; propose au plus '
                .'UNE action. Elle n’est JAMAIS exécutée d’office - l’utilisateur devra l’autoriser explicitement '
                .'(il cliquera sur « Autoriser et exécuter »). Dans "answer", dis en une phrase ce que tu vas faire '
                .'et demande l’autorisation. Si AUCUNE action de la liste ne correspond, mets "action" à null et '
                .'donne directement la réponse ou la marche à suivre en texte - ne fabrique pas de fausse étape à valider.';

        // Horloge : date/heure courantes pour les questions temporelles (échéances,
        // délais, « aujourd'hui »).
        $now = now()->locale('fr')->translatedFormat('l j F Y, H:i');

        // ⚠️ Vide pour qui n'a pas la permission « Utilisateurs & rôles » : le prompt
        // ne mentionne alors AUCUN annuaire des comptes. Ne pas en parler vaut mieux
        // que l'annoncer puis le refuser - un modèle à qui l'on décrit une donnée
        // finit par la deviner de travers.
        $regleComptes = UserDirectory::resume($staff);
        if ($regleComptes !== '') {
            $regleComptes = "COMPTES DE LA PLATEFORME : {$regleComptes}\n"
                ."        ⚠️ Les comptes ne sont PAS rattachés à un colloque : la règle « jamais les données\n"
                ."        d'un autre colloque » ne s'y applique pas. En revanche, ne donne jamais un mot de\n"
                .'        passe ni un jeton (tu n’y as pas accès), et reste dans la finalité d’administration.';
        }

        return <<<TXT
        {$agent['persona']}

        À TOUJOURS FAIRE :
        {$agent['positive']}

        À NE JAMAIS FAIRE :
        {$agent['negative']}

        DATE ET HEURE ACTUELLES : {$now}. Appuie-toi dessus pour toute question temporelle
        (échéances de soumission/inscription à venir ou passées, délais restants, planning).

        FONCTION DE L'UTILISATEUR : {$role['fonction']}
        PÉRIMÈTRE AUTORISÉ (ses fonctions) : {$role['perimetre']}
        RÈGLE DE PÉRIMÈTRE (impérative) : réponds STRICTEMENT dans le cadre de la fonction et du
        périmètre ci-dessus, en adoptant ce rôle (ex. un comptable / assistant comptable est cadré
        sur les paiements, encaissements et finances). Si la demande porte sur un domaine hors de ce
        périmètre (ex. contenu scientifique, relectures, soumissions pour un comptable), explique
        poliment que cela relève d'une autre fonction et oriente vers la personne ou l'onglet
        compétent, sans divulguer ces informations.

        COLLOQUE SÉLECTIONNÉ (contexte de TOUTES tes réponses, analyses et actions) : {$selected}.
        Raisonne exclusivement sur ce colloque et ses données ci-dessous ; ne mélange jamais avec
        d'autres événements.

        CHIFFRES RÉELS DE L'ÉDITION ACTIVE :
        {$context}

        BASE DE CONNAISSANCE DU COLLOQUE (paramétrage officiel : tarifs, axes, échéances, règles, FAQ…) :
        {$knowledge}

        RÈGLE DE PRÉCISION (impérative) : appuie-toi UNIQUEMENT sur les données de CE colloque
        sélectionné (chiffres, base de connaissance et « état actuel » des actions ci-dessous). Cite les
        valeurs exactes (montants, effectifs, dates). Pour juger de la pertinence d'une action, privilégie
        l'« état actuel » propre à l'action plutôt qu'une statistique agrégée du tableau de bord. Si une
        donnée n'y figure pas, dis-le explicitement et propose où la vérifier ; n'invente aucun chiffre ni
        aucune procédure. Réponds précisément à la demande, sans digression.

        RÈGLE DE COHÉRENCE DES NOMBRES (impérative) : quand tu proposes une action, le CHIFFRE QUI FAIT
        FOI est le compte réel de l'action (son « état actuel » ci-dessus), jamais un sous-total que tu
        recalcules. NE FABRIQUE PAS de sous-découpage que tu ne peux pas justifier exactement (« N ont une
        communication refusée », répartitions, listes nominatives de « ceux qui… ») : c'est là que des
        comptes faux et des contradictions apparaissent (un nombre annoncé qui ne correspond pas aux noms
        cités). Si tu détailles malgré tout, VÉRIFIE que ton nombre annoncé = le nombre d'éléments que tu
        cites, et que la somme des sous-groupes = le total ; au moindre doute, n'énonce que le total réel et
        renvoie vers l'onglet/export concerné pour le détail. Mieux vaut un seul chiffre sûr qu'une analyse
        détaillée mais fausse.

        DONNÉES NOMINATIVES : tu disposes de l'annuaire (noms, prénoms, e-mails) des participants,
        inscrits, paiements et auteurs de CE colloque ci-dessus. Tu PEUX les communiquer à
        l'administrateur pour son travail (retrouver un e-mail, lister les non-réglés, identifier un
        payeur…), DANS LE CADRE de sa fonction et de ce colloque uniquement. Cite nom et e-mail
        EXACTEMENT comme dans l'annuaire. Ne divulgue jamais les données d'un AUTRE colloque, et ne
        traite pas ces données hors d'une finalité d'organisation (pas de liste à des fins étrangères).

        {$regleComptes}

        Historique récent :
        {$convo}

        Nouvelle demande de l'administrateur : {$question}

        Tu assistes l'utilisateur (synthèses, brouillons, points de vigilance) ET tu peux DÉCLENCHER
        certaines actions concrètes - mais uniquement via la liste ci-dessous et après son autorisation
        explicite. Structure ta réponse pour la rendre lisible : phrases courtes, un saut de ligne entre
        les idées, une liste à puces (« - ») pour énumérer, **gras** pour les points clés. N'utilise ni
        titres « # » ni tableaux. Reste synthétique et va droit au but - n'invite pas l'utilisateur à
        faire lui-même ce que tu peux exécuter via une action disponible.

        {$actionsBlock}

        Si une opération ne correspond à AUCUNE action de la liste (ex. export manuel, décision
        d'organisation), ne crée pas de bouton à valider : donne simplement la réponse ou la marche à
        suivre dans "answer" (étapes, onglet concerné), et laisse "action" à null.

        Réponds UNIQUEMENT par un objet JSON STRICT (aucun texte autour) :
        { "answer": string,
          "action": { "key": string, "params": object } | null }.
        TXT;
    }
}
