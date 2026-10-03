<?php

namespace App\Support;

use App\Models\EventSession;
use App\Models\Participant;
use App\Models\SessionAttendance;
use App\Models\SessionReport;
use App\Models\User;

/**
 * Pointage **automatique** par la visio : qui entre dans la salle d'une séance
 * est porté présent, sans que le modérateur ait à le faire.
 *
 * ⚠️ Ce qui est enregistré, c'est une **entrée dans la visio depuis GEVES**, pas
 * une occupation de la salle en temps réel. Aucun des fournisseurs utilisés ici
 * ne la donne : Jitsi public n'expose aucune API de liste de participants, et
 * LiveKit ne le ferait qu'avec des identifiants d'API configurés. Le libellé
 * affiché dit donc « entré en visio », et non « actuellement connecté » - on ne
 * fait pas dire à une donnée plus que ce qu'elle sait.
 */
class SessionPresence
{
    /**
     * Porte présent le compte qui vient d'entrer dans la visio de la séance.
     * Renvoie vrai si une présence a été posée.
     */
    public static function markFromMeeting(EventSession $session, ?User $account): bool
    {
        if (! $account || ! self::open($session)) {
            return false;
        }

        $participant = self::participantFor($session, $account);

        if (! $participant) {
            return false;
        }

        // ⚠️ Même cadrage que le pointage manuel : seuls les auteurs des
        // communications de la séance sont attendus. Un visiteur qui passe dans
        // la salle n'a pas de ligne où s'inscrire.
        if (! SessionRoster::participantIds($session)->contains($participant->id)) {
            return false;
        }

        $existante = SessionAttendance::where('session_id', $session->id)
            ->where('participant_id', $participant->id)
            ->first();

        // ⚠️ Un verdict déjà posé n'est JAMAIS écrasé. Quelqu'un a regardé la
        // salle ; un clic sur « Rejoindre » ne vaut pas contre ce constat - et
        // repasser « absent » à « présent » dans le dos du modérateur lui ferait
        // signer un chiffre qu'il n'a pas vu.
        if ($existante) {
            return false;
        }

        SessionAttendance::create([
            'session_id' => $session->id,
            'participant_id' => $participant->id,
            'status' => 'present',
            'source' => SessionAttendance::SOURCE_MEETING,
            'marked_by' => $account->id,
            'marked_at' => now(),
        ]);

        return true;
    }

    /** Mêmes bornes que le pointage à la main : séance commencée et non figée. */
    private static function open(EventSession $session): bool
    {
        if (! $session->hasStarted() || EditionLock::frozen($session->edition)) {
            return false;
        }

        return ! (SessionReport::where('session_id', $session->id)->first()?->isValidated() ?? false);
    }

    /**
     * Inscription de ce compte sur l'édition de la séance. Rapprochement par
     * compte lié d'abord, par e-mail ensuite : un co-auteur n'est souvent relié à
     * son inscription que par son adresse.
     */
    private static function participantFor(EventSession $session, User $account): ?Participant
    {
        return Participant::where('edition_id', $session->edition_id)
            ->where(fn ($q) => $q->where('user_id', $account->id)
                ->orWhereRaw('LOWER(email) = ?', [mb_strtolower((string) $account->email)]))
            ->first();
    }
}
