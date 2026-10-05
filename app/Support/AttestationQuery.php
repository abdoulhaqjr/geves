<?php

namespace App\Support;

use App\Models\Attestation;
use App\Models\Event;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * **Filtres de la liste des attestations délivrées**, en un seul endroit.
 *
 * ⚠️ La liste à l'écran et les exports (lot PDF, liste d'émargement) doivent
 * porter sur **exactement** le même ensemble : on exporte ce qu'on regarde. Deux
 * constructions de requête finiraient par diverger, et le gestionnaire
 * imprimerait un lot qui ne correspond pas à ce qu'il a sous les yeux - sans
 * rien pour s'en apercevoir.
 */
class AttestationQuery
{
    /** @return Builder<Attestation> */
    public static function filtered(Request $request, ?Event $event): Builder
    {
        // Pas d'événement = rien. Mieux vaut une liste vide qu'un export de toutes
        // les événements de la plateforme.
        $query = $event
            ? Attestation::where('event_id', $event->id)
            : Attestation::query()->whereRaw('1 = 0');

        if (($type = (string) $request->query('type', '')) !== '' && array_key_exists($type, Attestation::TYPES)) {
            $query->where('type', $type);
        }

        if (($state = (string) $request->query('state', '')) === 'valid') {
            $query->where('revoked', false);
        } elseif ($state === 'revoked') {
            $query->where('revoked', true);
        }

        // Remise en main propre (délivrance papier) : « ce qui reste à remettre »
        // est la question du guichet.
        if (($remise = (string) $request->query('delivery', '')) === 'done') {
            $query->whereNotNull('delivered_at');
        } elseif ($remise === 'pending') {
            $query->whereNull('delivered_at');
        }

        // Recherche nom / e-mail : sur l'attestation (figés à l'émission) ET sur le
        // participant lié, car une attestation peut viser quelqu'un sans compte.
        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $term = '%'.$search.'%';
            $query->where(fn ($q) => $q
                ->where('recipient_name', 'like', $term)
                ->orWhere('recipient_email', 'like', $term)
                ->orWhereHas('participant', fn ($p) => $p
                    ->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('email', 'like', $term)));
        }

        // Période d'émission.
        if (($from = (string) $request->query('from', '')) !== '') {
            $query->whereDate('issued_at', '>=', $from);
        }
        if (($to = (string) $request->query('to', '')) !== '') {
            $query->whereDate('issued_at', '<=', $to);
        }

        return $query;
    }

    /**
     * Les filtres en cours, à reporter sur les liens d'export.
     *
     * @return array<string, string>
     */
    public static function params(Request $request): array
    {
        $out = [];

        foreach (['type', 'state', 'delivery', 'search', 'from', 'to'] as $cle) {
            $valeur = trim((string) $request->query($cle, ''));
            if ($valeur !== '') {
                $out[$cle] = $valeur;
            }
        }

        return $out;
    }
}
