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

    /**
     * Le pointage est-il ouvert à l'écriture ? Séance commencée, édition non
     * archivée, rapport non validé (le PV cite ces chiffres).
     *
     * ⚠️ **Règle unique**, partagée par la page de présence, la salle de visio et
     * le pointage automatique : trois endroits qui décideraient chacun de leur
     * côté finiraient par diverger.
     */
    public static function open(EventSession $session): bool
    {
        if (! $session->hasStarted() || EditionLock::frozen($session->edition)) {
            return false;
        }

        return ! (SessionReport::where('session_id', $session->id)->first()?->isValidated() ?? false);
    }

    /**
     * Qui peut tenir la présence d'une séance : son modérateur **ou** son
     * rapporteur, rapprochés par e-mail comme dans tous les espaces de fonction.
     *
     * ⚠️ Le staff n'est PAS inclus, bien qu'il soit modérateur dans la salle : la
     * présence est un constat de celui qui tient la séance, et le back-office a
     * ses propres écrans. Une seule règle, partout.
     */
    public static function canManage(EventSession $session, ?User $account): bool
    {
        $compte = mb_strtolower((string) $account?->email);

        if ($compte === '') {
            return false;
        }

        foreach ([$session->moderator_email, $session->rapporteur_email] as $email) {
            if (filled($email) && mb_strtolower((string) $email) === $compte) {
                return true;
            }
        }

        return false;
    }

    /**
     * Porte présents les inscrits **réellement connectés** à la salle (liste
     * LiveKit). Renvoie le nombre de présences posées.
     *
     * ⚠️ C'est la version forte du pointage automatique : elle constate une
     * connexion en cours, là où {@see self::markFromMeeting()} ne constate qu'un
     * passage par la porte. Mêmes garde-fous : seulement les attendus de la
     * séance, et **jamais par-dessus un verdict déjà posé**.
     *
     * @param  list<int>  $participantIds
     */
    public static function markConnected(EventSession $session, array $participantIds): int
    {
        if ($participantIds === [] || ! self::open($session)) {
            return 0;
        }

        $attendus = SessionRoster::participantIds($session);
        $deja = SessionAttendance::where('session_id', $session->id)->pluck('participant_id');
        $poses = 0;

        foreach ($participantIds as $id) {
            if (! $attendus->contains($id) || $deja->contains($id)) {
                continue;
            }

            SessionAttendance::create([
                'session_id' => $session->id,
                'participant_id' => $id,
                'status' => 'present',
                'source' => SessionAttendance::SOURCE_MEETING,
                'marked_at' => now(),
            ]);
            $poses++;
        }

        return $poses;
    }

    /**
     * Pose un verdict à la main sur une inscription de la séance. Renvoie faux si
     * la personne n'est pas attendue à cette séance.
     */
    public static function mark(EventSession $session, int $participantId, string $statut, ?User $par): bool
    {
        if (! SessionRoster::participantIds($session)->contains($participantId)) {
            return false;
        }

        if ($statut === '') {
            SessionAttendance::where('session_id', $session->id)
                ->where('participant_id', $participantId)->delete();

            return true;
        }

        // ⚠️ `source` repasse à « manual » : dès que quelqu'un enregistre la ligne,
        // elle est de sa main - même s'il ne fait que confirmer la visio.
        SessionAttendance::updateOrCreate(
            ['session_id' => $session->id, 'participant_id' => $participantId],
            [
                'status' => $statut,
                'source' => SessionAttendance::SOURCE_MANUAL,
                'marked_by' => $par?->id,
                'marked_at' => now(),
            ],
        );

        return true;
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
