<?php

namespace App\Support;

use App\Models\Edition;
use App\Models\Participant;
use App\Models\Submission;
use Illuminate\Support\Collection;

/**
 * Ayants droit à une attestation de communication.
 *
 * Règle métier : sur une communication acceptée, l'auteur correspondant ET chaque
 * co-auteur ont droit à une attestation à leur nom. Deux plafonds :
 *  - par communication : au plus `coauthors_max` co-auteurs reçoivent une attestation ;
 *  - par personne sur l'événement : au plus `attestation_max` attestations de
 *    communication — quand une personne dépasse ce plafond, on privilégie d'abord
 *    les communications où elle est auteur correspondant, puis les autres dans
 *    l'ordre chronologique.
 *
 * Les co-auteurs ne sont éligibles que s'ils ont **payé** leurs frais (ils ont donc
 * déjà un Participant sur l'édition). L'auteur correspondant est le participant
 * déposant, sans condition de paiement supplémentaire ici.
 */
class CommunicationAttestations
{
    /**
     * @return Collection<int, array{participant: Participant, submission: Submission, corresponding: bool}>
     */
    public static function entitlements(Edition $edition): Collection
    {
        $attestationMax = (int) ($edition->settings['attestation_max'] ?? 5);
        $coauthorsMax = (int) ($edition->settings['coauthors_max'] ?? 8);

        $accepted = Submission::where('edition_id', $edition->id)
            ->where('revision_of', 0)
            ->where('status', 'accepted')
            ->orderBy('id')
            ->get();

        // Participants payés de l'édition, indexés par e-mail (pour matcher les co-auteurs).
        $paidByEmail = Participant::where('edition_id', $edition->id)
            ->whereHas('payments', fn ($q) => $q->where('status', 'paid'))
            ->get()
            ->keyBy(fn (Participant $p) => mb_strtolower(trim((string) $p->email)));

        /** @var Collection<int, array{participant: Participant, submission: Submission, corresponding: bool}> $items */
        $items = collect();

        foreach ($accepted as $sub) {
            $corresponding = $sub->participant; // participant déposant = auteur correspondant
            if ($corresponding) {
                $items->push(['participant' => $corresponding, 'submission' => $sub, 'corresponding' => true]);
            }

            // Co-auteurs payés, dans l'ordre de la liste, plafonnés à coauthors_max.
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
                $items->push(['participant' => $participant, 'submission' => $sub, 'corresponding' => false]);
                if (++$taken >= $coauthorsMax) {
                    break;
                }
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

        // Plafond par personne : priorité aux communications où elle est auteur correspondant.
        return collect($deduped)
            ->groupBy(fn (array $i) => $i['participant']->id)
            ->flatMap(fn (Collection $grp) => $grp
                ->sortBy(fn (array $i) => sprintf('%d-%012d', $i['corresponding'] ? 0 : 1, $i['submission']->id))
                ->take($attestationMax)
                ->values())
            ->values();
    }

    /** Nombre d'attestations de communication à émettre pour l'édition (aperçu). */
    public static function count(Edition $edition): int
    {
        return self::entitlements($edition)->count();
    }
}
