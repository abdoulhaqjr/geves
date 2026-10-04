<?php

namespace App\Support;

use App\Models\Attestation;
use App\Models\AttestationEvent;

/**
 * **Ce que devient une attestation après sa délivrance**, en clair.
 *
 * ⚠️ **Les libellés disent ce qui a été CONSTATÉ, pas ce qu'on aimerait savoir.**
 * On sait qu'une page a été ouverte, qu'un partage est allé au bout, qu'un
 * titulaire est parti vers le formulaire « Ajouter au profil » de LinkedIn. On
 * ne sait PAS si l'attestation figure à ce profil : LinkedIn ne rappelle
 * personne. Écrire « Ajoutée à LinkedIn » serait une affirmation que rien ne
 * soutient, et le gestionnaire s'en servirait pour conclure.
 */
class AttestationTrail
{
    /**
     * Les états de suivi proposés en filtre (clé d'URL => libellé).
     *
     * ⚠️ Partagés par la liste et les exports via `AttestationQuery` : on imprime
     * ce qu'on regarde.
     */
    public const FILTRES = [
        'unopened' => 'Jamais ouvertes',
        'opened' => 'Ouvertes',
        'acknowledged' => 'Reçues (accusé du titulaire)',
        'unacknowledged' => 'Sans accusé de réception',
        'shared' => 'Partagées',
        'linkedin' => 'Portées vers LinkedIn',
    ];

    /**
     * Pastilles du suivi d'une attestation, dans l'ordre de lecture : a-t-elle
     * été reçue, ouverte, partagée, emportée vers LinkedIn.
     *
     * @return list<array{label: string, tone: string, title: string}>
     */
    public static function pastilles(Attestation $attestation): array
    {
        $pastilles = [];

        // 1. Réception. L'accusé du titulaire d'abord : c'est le seul qui dise
        // que la personne a bien son document.
        if ($attestation->acknowledged_at) {
            $pastilles[] = [
                'label' => 'Reçue le '.$attestation->acknowledged_at->format('d/m/Y'),
                'tone' => 'ok',
                'title' => 'Le titulaire, connecté à son espace, a ouvert son attestation le '
                    .$attestation->acknowledged_at->format('d/m/Y à H:i').'.',
            ];
        } elseif ($attestation->delivered_at) {
            $pastilles[] = [
                'label' => 'Remise le '.$attestation->delivered_at->format('d/m/Y'),
                'tone' => 'ok',
                'title' => 'Remise en main propre par le secrétariat (délivrance papier).',
            ];
        }

        // 2. Ouverture : la question de la relance.
        if ($attestation->first_viewed_at) {
            $nombre = (int) $attestation->views_count;
            $pastilles[] = [
                'label' => 'Ouverte'.($nombre > 1 ? ' ×'.$nombre : ''),
                'tone' => 'info',
                'title' => 'Première ouverture le '.$attestation->first_viewed_at->format('d/m/Y à H:i')
                    .($attestation->last_viewed_at
                        ? ', dernière le '.$attestation->last_viewed_at->format('d/m/Y à H:i')
                        : '')
                    .'. Les consultations du back-office ne sont pas comptées.',
            ];
        } else {
            $pastilles[] = [
                'label' => 'Jamais ouverte',
                'tone' => 'muted',
                'title' => 'Personne n’a encore ouvert cette attestation en ligne.',
            ];
        }

        // 3. Partage.
        if ($attestation->shared_at) {
            $nombre = (int) $attestation->shares_count;
            $pastilles[] = [
                'label' => 'Partagée'.($nombre > 1 ? ' ×'.$nombre : ''),
                'tone' => 'info',
                'title' => 'Dernier partage le '.$attestation->shared_at->format('d/m/Y à H:i')
                    .' (feuille de partage du système ou lien copié).',
            ];
        }

        // 4. LinkedIn - une intention constatée, pas un résultat.
        if ($attestation->linkedin_at) {
            $pastilles[] = [
                'label' => 'LinkedIn',
                'tone' => 'info',
                'title' => 'Le '.$attestation->linkedin_at->format('d/m/Y à H:i')
                    .', départ vers le formulaire « Ajouter au profil » de LinkedIn, pré-rempli.'
                    .' LinkedIn ne nous dit pas si l’ajout a été confirmé.',
            ];
        }

        return $pastilles;
    }

    /**
     * Recalcule la synthèse d'une attestation depuis son journal.
     *
     * ⚠️ C'est ce qui rend la dénormalisation tenable : le journal reste la
     * source de vérité, et les colonnes de synthèse ne sont qu'une vue qu'on
     * sait reconstruire. Les gestes du STAFF sont écartés ici comme ils le sont
     * à l'écriture - sinon un recalcul ferait apparaître des ouvertures que la
     * liste n'avait jamais comptées.
     */
    public static function recompute(Attestation $attestation): void
    {
        $journal = AttestationEvent::where('attestation_id', $attestation->id)
            ->where('actor', '!=', AttestationEvent::ACTOR_STAFF)
            ->orderBy('occurred_at')->get();

        $consultations = $journal->whereIn('kind', ['view', 'download']);
        $partages = $journal->whereIn('kind', ['share', 'copy']);
        $recus = $consultations->where('actor', AttestationEvent::ACTOR_HOLDER);
        $linkedin = $journal->where('kind', 'linkedin');

        $attestation->forceFill([
            'acknowledged_at' => $recus->first()?->occurred_at,
            'first_viewed_at' => $consultations->first()?->occurred_at,
            'last_viewed_at' => $consultations->last()?->occurred_at,
            'views_count' => $consultations->count(),
            'shared_at' => $partages->last()?->occurred_at,
            'shares_count' => $partages->count(),
            'linkedin_at' => $linkedin->first()?->occurred_at,
        ])->save();
    }
}
