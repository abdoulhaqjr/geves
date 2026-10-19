<?php

namespace App\Support;

use App\Models\EventSession;
use App\Models\Participant;
use App\Models\Submission;
use Illuminate\Support\Collection;

/**
 * Qui est attendu à une séance : les **auteurs et co-auteurs des communications
 * qui y sont programmées**, et eux seuls.
 *
 * ⚠️ Ce n'est PAS l'effectif de l'événement. Un modérateur ne constate que ce qu'il
 * a sous les yeux : la liste des inscrits (plusieurs centaines) ne se pointe pas,
 * et elle lui demanderait de se prononcer sur des gens qui n'ont rien à faire
 * dans sa salle.
 *
 * ⚠️ Un co-auteur n'a pas forcément d'inscription sur l'événement (il n'est pas
 * venu, ou pas encore réglé). Il figure quand même dans la liste - c'est bien un
 * auteur de la communication - mais sans inscription il n'y a rien à quoi
 * rattacher une présence : la ligne est alors **non pointable**, et le dit.
 */
class SessionRoster
{
    /**
     * Personnes attendues, dédoublonnées, classées par nom.
     *
     * Une personne qui signe deux communications de la même séance n'apparaît
     * qu'**une fois** : deux lignes pour quelqu'un donneraient deux verdicts
     * contradictoires sur une même présence.
     *
     * @return list<array{
     *     participant: Participant|null,
     *     name: string,
     *     institution: string,
     *     corresponding: bool,
     *     communications: list<string>,
     * }>
     */
    public static function for(EventSession $session): array
    {
        $submissions = self::scheduledSubmissions($session);

        if ($submissions->isEmpty()) {
            return [];
        }

        // Inscrits de l'événement indexés par e-mail : un seul accès en base pour
        // rattacher les co-auteurs, comme le fait déjà CommunicationAttestations.
        $parEmail = Participant::where('event_id', $session->event_id)
            ->get()
            ->keyBy(fn (Participant $p) => self::key($p->email));

        /** @var array<string, array{participant: Participant|null, name: string, institution: string, corresponding: bool, communications: list<string>}> $lignes */
        $lignes = [];

        foreach ($submissions as $submission) {
            $titre = (string) $submission->title;

            // Auteur correspondant = participant déposant. Il a toujours une
            // inscription (il a déposé), donc toujours pointable.
            if ($submission->participant) {
                self::push($lignes, 'p:'.$submission->participant->id, [
                    'participant' => $submission->participant,
                    'name' => $submission->participant->full_name,
                    'institution' => (string) ($submission->participant->institution ?? ''),
                    'corresponding' => true,
                ], $titre);
            }

            foreach ((array) ($submission->authors ?? []) as $auteur) {
                $nom = EventNameFormat::author($session->event, $auteur);
                $email = self::key($auteur['email'] ?? '');

                if ($nom === '' && $email === '') {
                    continue;
                }

                $participant = $email !== '' ? $parEmail->get($email) : null;

                // Déjà posé comme auteur correspondant : on n'en fait pas une
                // seconde ligne (la liste JSON le répète).
                if ($participant && $submission->participant && $participant->id === $submission->participant->id) {
                    self::push($lignes, 'p:'.$participant->id, [], $titre);

                    continue;
                }

                // L'inscription fait foi quand elle existe : elle est tenue par
                // l'intéressé, la liste d'auteurs est saisie par le déposant.
                $institution = $participant
                    ? (string) $participant->institution
                    : (string) ($auteur['affiliation'] ?? '');

                self::push($lignes, $participant ? 'p:'.$participant->id : 'a:'.($email ?: mb_strtolower($nom)), [
                    'participant' => $participant,
                    'name' => $participant?->full_name ?: $nom,
                    'institution' => $institution,
                    'corresponding' => (bool) ($auteur['corresponding'] ?? false),
                ], $titre);
            }
        }

        $liste = array_values($lignes);
        usort($liste, fn (array $a, array $b) => strcoll($a['name'], $b['name']));

        return $liste;
    }

    /** Identifiants des inscrits pointables de la séance (garde d'écriture). */
    public static function participantIds(EventSession $session): Collection
    {
        return collect(self::for($session))
            ->pluck('participant.id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();
    }

    /**
     * Communications programmées dans la séance, via ses créneaux.
     *
     * @return Collection<int, Submission>
     */
    private static function scheduledSubmissions(EventSession $session): Collection
    {
        $ids = $session->slots()->where('submission_id', '>', 0)->pluck('submission_id')->unique();

        if ($ids->isEmpty()) {
            return collect();
        }

        return Submission::whereIn('id', $ids)->with('participant')->orderBy('id')->get();
    }

    /**
     * Pose ou complète une ligne. Les données ne sont écrites qu'à la création :
     * un passage ultérieur n'ajoute que la communication, pour ne pas écraser le
     * rattachement trouvé la première fois.
     *
     * @param  array<string, mixed>  $lignes
     * @param  array<string, mixed>  $donnees
     */
    private static function push(array &$lignes, string $cle, array $donnees, string $communication): void
    {
        if (! isset($lignes[$cle])) {
            if ($donnees === []) {
                return;
            }
            $lignes[$cle] = $donnees + ['communications' => []];
        }

        // Auteur correspondant quelque part = la qualité la plus forte l'emporte.
        if ($donnees['corresponding'] ?? false) {
            $lignes[$cle]['corresponding'] = true;
        }

        if (! in_array($communication, $lignes[$cle]['communications'], true)) {
            $lignes[$cle]['communications'][] = $communication;
        }
    }

    private static function key(mixed $email): string
    {
        return mb_strtolower(trim((string) $email));
    }
}
