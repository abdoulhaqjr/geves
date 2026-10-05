<?php

namespace App\Support;

use App\Models\Attestation;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\Participant;
use App\Models\SessionAttendance;
use App\Models\Slot;
use App\Models\Submission;
use Illuminate\Support\Collection;

/**
 * Ayants droit à une attestation de communication.
 *
 * Règle métier : sur une communication acceptée, l'auteur correspondant ET chaque
 * co-auteur ont droit à une attestation à leur nom. Deux plafonds :
 *  - par communication : au plus `coauthors_max` co-auteurs reçoivent une attestation ;
 *  - par personne sur l'événement : au plus `attestation_max` attestations de
 *    communication - quand une personne dépasse ce plafond, on privilégie d'abord
 *    les communications où elle est auteur correspondant, puis les autres dans
 *    l'ordre chronologique.
 *
 * Les co-auteurs ne sont éligibles que s'ils ont **payé** leurs frais (ils ont donc
 * déjà un Participant sur l'événement). L'auteur correspondant est le participant
 * déposant, sans condition de paiement supplémentaire ici.
 *
 * ⚠️ **Deux chemins, deux moments.** {@see self::entitlements()} calcule le lot
 * complet d'un événement (émission en masse par le gestionnaire).
 * {@see self::forSubmission()} répond pour UNE communication, au moment où le
 * modérateur constate son passage. Les plafonds sont les mêmes, mais le second
 * ne voit pas l'avenir : il compte ce qui est **déjà émis** au lieu de classer
 * l'ensemble. Conséquence assumée : en séance, le plafond par personne se
 * consomme **dans l'ordre des passages** - et c'est le bon ordre, puisqu'une
 * attestation déjà envoyée ne se reprend pas.
 */
class CommunicationAttestations
{
    /**
     * @return Collection<int, array{participant: Participant, submission: Submission, corresponding: bool}>
     */
    public static function entitlements(Event $event, bool $avecPresence = true): Collection
    {
        $attestationMax = self::attestationMax($event);
        $coauthorsMax = self::coauthorsMax($event);

        $accepted = Submission::where('event_id', $event->id)
            ->where('revision_of', 0)
            ->where('status', 'accepted')
            ->orderBy('id')
            ->get();

        $paidByEmail = self::paidByEmail($event);

        /** @var Collection<int, array{participant: Participant, submission: Submission, corresponding: bool}> $items */
        $items = collect();

        foreach ($accepted as $sub) {
            foreach (self::authorsOf($sub, $paidByEmail, $coauthorsMax) as $item) {
                $items->push($item);
            }
        }

        // Dédoublonnage (participant, communication) : garde la version « auteur correspondant » si présente.
        $deduped = [];
        foreach ($items as $item) {
            $key = $item['participant']->id.'-'.$item['submission']->id;
            if (! isset($deduped[$key]) || $item['corresponding']) {
                $deduped[$key] = $item;
            }
        }

        $retenus = collect($deduped);

        // ⚠️ Filtre de présence appliqué AVANT le plafond par personne : écarter
        // d'abord les absents laisse la place à une communication où la personne
        // était là, plutôt que de lui faire consommer son quota sur une séance
        // qu'elle a manquée.
        if ($avecPresence && AttestationPolicy::requiresPresence($event)) {
            $seances = self::sessionsOf($retenus->pluck('submission.id')->unique()->all());
            $presents = self::presentsOf($seances);
            $retenus = $retenus->filter(fn (array $i) => self::estPresent($i, $seances, $presents));
        }

        // Plafond par personne : priorité aux communications où elle est auteur correspondant.
        return $retenus
            ->groupBy(fn (array $i) => $i['participant']->id)
            ->flatMap(fn (Collection $grp) => $grp
                ->sortBy(fn (array $i) => sprintf('%d-%012d', $i['corresponding'] ? 0 : 1, $i['submission']->id))
                ->take($attestationMax)
                ->values())
            ->values();
    }

    /**
     * Ayants droit d'UNE communication dont le passage vient d'être constaté.
     *
     * ⚠️ Le plafond par personne se compte sur les attestations **déjà émises** :
     * en séance, on ne peut pas classer les communications à venir, et ce qui est
     * parti est parti. Premier passé, premier servi.
     *
     * @return Collection<int, array{participant: Participant, submission: Submission, corresponding: bool}>
     */
    public static function forSubmission(Submission $submission, ?EventSession $session = null): Collection
    {
        $event = $submission->event;

        if (! $event) {
            return collect();
        }

        $attestationMax = self::attestationMax($event);
        $retenus = collect(self::authorsOf($submission, self::paidByEmail($event), self::coauthorsMax($event)));

        if (AttestationPolicy::requiresPresence($event) && $session) {
            $presents = SessionAttendance::where('session_id', $session->id)
                ->where('status', 'present')
                ->pluck('participant_id')
                ->map(fn ($id) => (int) $id);

            $retenus = $retenus->filter(fn (array $i) => $presents->contains($i['participant']->id));
        }

        // Déjà émises pour ces personnes, cette communication exclue : c'est elle
        // qu'on est en train d'ouvrir, et IssueAttestation est de toute façon
        // idempotent sur le couple (personne, communication).
        $deja = Attestation::where('event_id', $event->id)
            ->where('type', 'communication')
            ->whereIn('participant_id', $retenus->pluck('participant.id')->all())
            ->where(fn ($q) => $q->whereNull('submission_id')->orWhere('submission_id', '!=', $submission->id))
            ->selectRaw('participant_id, COUNT(*) as n')
            ->groupBy('participant_id')
            ->pluck('n', 'participant_id');

        return $retenus
            ->filter(fn (array $i) => (int) ($deja[$i['participant']->id] ?? 0) < $attestationMax)
            ->values();
    }

    /** Nombre d'attestations de communication à émettre pour l'événement (aperçu). */
    public static function count(Event $event): int
    {
        return self::entitlements($event)->count();
    }

    /**
     * Ce qu'une émission en masse ferait : combien partent, combien sont
     * écartés, et pourquoi.
     *
     * ⚠️ Le bouton « Émettre » engageait un lot sans jamais dire ce qu'il
     * contenait. Avec la réserve aux présents, un nombre qui baisse sans
     * explication se lit comme une panne - et on réimprime pour rien.
     *
     * @return array{retenus: int, ecartes: int}
     */
    public static function preview(Event $event): array
    {
        $retenus = self::entitlements($event)->count();

        if (! AttestationPolicy::requiresPresence($event)) {
            return ['retenus' => $retenus, 'ecartes' => 0];
        }

        // ⚠️ Le plafond par personne s'applique APRÈS le filtre de présence : le
        // total sans filtre n'est donc pas « retenus + absents » mais bien ce
        // que l'événement émettrait si elle n'exigeait pas la présence. C'est cette
        // différence-là qui intéresse le gestionnaire.
        return [
            'retenus' => $retenus,
            'ecartes' => max(0, self::entitlements($event, avecPresence: false)->count() - $retenus),
        ];
    }

    /**
     * Auteurs d'une communication ayant droit à une attestation : le correspondant,
     * puis les co-auteurs payés dans l'ordre de la liste, plafonnés.
     *
     * @param  Collection<string, Participant>  $paidByEmail
     * @return list<array{participant: Participant, submission: Submission, corresponding: bool}>
     */
    private static function authorsOf(Submission $sub, Collection $paidByEmail, int $coauthorsMax): array
    {
        $out = [];
        $corresponding = $sub->participant; // participant déposant = auteur correspondant

        if ($corresponding) {
            $out[] = ['participant' => $corresponding, 'submission' => $sub, 'corresponding' => true];
        }

        $taken = 0;
        foreach (collect($sub->authors ?? []) as $author) {
            if ($author['corresponding'] ?? false) {
                continue;
            }
            $email = mb_strtolower(trim((string) ($author['email'] ?? '')));
            if ($email === '') {
                continue;
            }
            $participant = $paidByEmail->get($email);
            if (! $participant) {
                continue; // co-auteur non payé → pas d'attestation
            }
            if ($corresponding && $participant->id === $corresponding->id) {
                continue; // déjà compté comme auteur correspondant
            }
            $out[] = ['participant' => $participant, 'submission' => $sub, 'corresponding' => false];
            if (++$taken >= $coauthorsMax) {
                break;
            }
        }

        return $out;
    }

    /** Participants payés de l'événement, indexés par e-mail (pour matcher les co-auteurs). */
    private static function paidByEmail(Event $event): Collection
    {
        return Participant::where('event_id', $event->id)
            ->whereHas('participationPayments', fn ($q) => $q->where('status', 'paid'))
            ->get()
            ->keyBy(fn (Participant $p) => mb_strtolower(trim((string) $p->email)));
    }

    /**
     * Séance où chaque communication est programmée (première trouvée).
     *
     * @param  list<int>  $submissionIds
     * @return array<int, int> submission_id => session_id
     */
    private static function sessionsOf(array $submissionIds): array
    {
        if ($submissionIds === []) {
            return [];
        }

        $out = [];
        foreach (Slot::whereIn('submission_id', $submissionIds)
            ->whereNotNull('session_id')
            ->orderBy('id')
            ->get(['submission_id', 'session_id']) as $slot) {
            // Première séance trouvée : une communication reprogrammée garde son
            // premier créneau comme référence de présence.
            $out[(int) $slot->submission_id] ??= (int) $slot->session_id;
        }

        return $out;
    }

    /**
     * Présents pointés, par séance.
     *
     * @param  array<int, int>  $seances
     * @return array<int, list<int>> session_id => [participant_id]
     */
    private static function presentsOf(array $seances): array
    {
        $ids = array_values(array_unique($seances));

        if ($ids === []) {
            return [];
        }

        $out = [];
        foreach (SessionAttendance::whereIn('session_id', $ids)
            ->where('status', 'present')
            ->get(['session_id', 'participant_id']) as $ligne) {
            $out[(int) $ligne->session_id][] = (int) $ligne->participant_id;
        }

        return $out;
    }

    /**
     * ⚠️ Une communication **non programmée** n'a pas de séance où se pointer :
     * la réserve aux présents l'écarterait pour de bon. On la laisse passer -
     * c'est au programme qu'il manque quelque chose, pas à l'auteur.
     *
     * @param  array{participant: Participant, submission: Submission, corresponding: bool}  $item
     * @param  array<int, int>  $seances
     * @param  array<int, list<int>>  $presents
     */
    private static function estPresent(array $item, array $seances, array $presents): bool
    {
        $sessionId = $seances[$item['submission']->id] ?? null;

        if ($sessionId === null) {
            return true;
        }

        return in_array((int) $item['participant']->id, $presents[$sessionId] ?? [], true);
    }

    private static function attestationMax(Event $event): int
    {
        return (int) ($event->settings['attestation_max'] ?? 5);
    }

    private static function coauthorsMax(Event $event): int
    {
        return (int) ($event->settings['coauthors_max'] ?? 8);
    }
}
