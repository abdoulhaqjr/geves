<?php

namespace App\Support;

use App\Models\Event;
use App\Models\Participant;
use App\Models\TutorialGuide;
use App\Models\User;
use Illuminate\Support\Collection;

/** Catalogue initial et règles de ciblage des guides de prise en main. */
class TutorialGuideCatalog
{
    /** @return array<string, array{label: string, icon: string}> */
    public static function categories(): array
    {
        return [
            'pilotage' => ['label' => 'Organisation', 'icon' => 'calendar'],
            'seances' => ['label' => 'Séances', 'icon' => 'mic'],
            'accompagnement' => ['label' => 'Accompagnement', 'icon' => 'book'],
        ];
    }

    public static function categoryFor(string $key): string
    {
        return match ($key) {
            'fonctionnement', 'colloque', 'configuration', 'secretariat' => 'pilotage',
            'visio', 'rapport', 'moderation' => 'seances',
            default => 'accompagnement',
        };
    }

    public static function iconFor(string $key): string
    {
        return match ($key) {
            'fonctionnement' => 'home', 'soumission' => 'inbox', 'visio' => 'video',
            'rapport' => 'sheet', 'moderation' => 'mic', 'colloque' => 'calendar',
            'configuration' => 'settings', 'secretariat' => 'folder', 'chat-public' => 'message',
            'chat-admin' => 'sparkles', default => 'book',
        };
    }

    /** Initialise le catalogue sans jamais écraser les adaptations administratives. */
    public static function ensureDefaults(): void
    {
        foreach (self::defaults() as $guide) {
            TutorialGuide::firstOrCreate(['key' => $guide['key']], $guide);
        }
    }

    /** @return Collection<int, TutorialGuide> */
    public static function forParticipant(User $user, ?Event $event): Collection
    {
        $roles = self::participantRoles($user, $event);

        return TutorialGuide::query()
            ->whereIn('audience', ['participant', 'both'])
            ->orderBy('sort_order')
            ->get()
            ->filter(function (TutorialGuide $guide) use ($roles): bool {
                $required = $guide->participant_roles ?: ['all'];

                return in_array('all', $required, true)
                    || count(array_intersect($required, $roles)) > 0;
            })->values();
    }

    /** @return list<string> */
    private static function participantRoles(User $user, ?Event $event): array
    {
        $roles = ['all'];
        if ($event === null) {
            return $roles;
        }

        $participantRoles = Participant::query()
            ->where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->pluck('role')->all();
        $roles = array_merge($roles, $participantRoles);

        if ($user->sessionsAsModerator()->where('event_id', $event->id)->exists()) {
            $roles[] = 'moderator';
        }
        if ($user->sessionsAsRapporteur()->where('event_id', $event->id)->exists()) {
            $roles[] = 'rapporteur';
        }

        return array_values(array_unique($roles));
    }

    /** @return list<array<string, mixed>> */
    private static function defaults(): array
    {
        return [
            ['key' => 'fonctionnement', 'title' => 'Comprendre la plateforme', 'intro' => 'Repérez les espaces, les rôles et le fil de travail d’un événement scientifique.', 'steps' => ['Choisissez le colloque sur lequel vous intervenez.', 'Utilisez les modules liés à votre fonction.', 'Vos droits suivent votre rôle dans chaque événement.'], 'audience' => 'both', 'participant_roles' => ['all'], 'sort_order' => 10],
            ['key' => 'soumission', 'title' => 'Soumettre et suivre une communication', 'intro' => 'Le dépôt se fait depuis l’espace participant ; le suivi scientifique se fait dans le back-office.', 'steps' => ['Sélectionnez l’appel ouvert puis confirmez votre soumission.', 'Suivez les demandes de correction et la décision.', 'Retrouvez votre programmation depuis votre compte.'], 'audience' => 'both', 'participant_roles' => ['all'], 'sort_order' => 20],
            ['key' => 'visio', 'title' => 'Présenter une communication en visio', 'intro' => 'Préparez la séance, rejoignez la salle et utilisez les contrôles adaptés à votre rôle.', 'steps' => ['Préparez caméra, micro et connexion avant la séance.', 'Rejoignez la salle depuis la séance programmée.', 'Le modérateur anime les échanges et le rapporteur consigne la séance.'], 'audience' => 'both', 'participant_roles' => ['author', 'coauthor', 'moderator', 'rapporteur'], 'sort_order' => 30],
            ['key' => 'rapport', 'title' => 'Rapporter une session', 'intro' => 'Le rapporteur documente le déroulement réel d’une séance afin de produire un compte rendu exploitable.', 'steps' => ['Accédez à votre espace Rapporteur.', 'Renseignez les observations et recommandations.', 'Le secrétariat peut relire et valider le rapport.'], 'audience' => 'both', 'participant_roles' => ['rapporteur'], 'sort_order' => 40],
            ['key' => 'moderation', 'title' => 'Modérer une session', 'intro' => 'Le modérateur garantit le rythme de la séance et facilite les échanges avec les participants.', 'steps' => ['Vérifiez votre affectation et rejoignez la séance.', 'Gérez les temps de parole et les questions.', 'Consignez le passage des communications si nécessaire.'], 'audience' => 'both', 'participant_roles' => ['moderator'], 'sort_order' => 50],
            ['key' => 'colloque', 'title' => 'Gérer un colloque', 'intro' => 'Organisez les éditions d’une série, leur équipe et les étapes qui structurent l’événement.', 'steps' => ['Créez ou sélectionnez la série et son événement.', 'Définissez l’équipe et ses capacités.', 'Suivez l’appel, les inscriptions, le programme et les documents.'], 'audience' => 'admin', 'participant_roles' => [], 'sort_order' => 60],
            ['key' => 'configuration', 'title' => 'Configurer un événement', 'intro' => 'Réglez les informations publiques, les échéances et les options opérationnelles.', 'steps' => ['Ouvrez la fiche de l’événement.', 'Renseignez les dates et paramètres de publication.', 'Contrôlez les échéances et les informations de paiement.'], 'audience' => 'admin', 'participant_roles' => [], 'sort_order' => 70],
            ['key' => 'secretariat', 'title' => 'Travailler au secrétariat', 'intro' => 'Centralisez les documents, les opérations de séance et le suivi administratif.', 'steps' => ['Préparez les documents utiles aux équipes.', 'Suivez inscriptions, paiements et demandes.', 'Contrôlez les documents officiels avant diffusion.'], 'audience' => 'admin', 'participant_roles' => [], 'sort_order' => 80],
            ['key' => 'chat-public', 'title' => 'Utiliser le chatbot public', 'intro' => 'L’assistante publique répond aux questions des visiteurs et oriente les participants.', 'steps' => ['Testez l’assistante comme un visiteur.', 'Maintenez à jour les informations publiées.', 'Transmettez les demandes non résolues au back-office.'], 'audience' => 'both', 'participant_roles' => ['all'], 'sort_order' => 90],
            ['key' => 'chat-admin', 'title' => 'Utiliser le chatbot admin', 'intro' => 'Le copilote aide à analyser la situation et à préparer des actions dans l’événement sélectionné.', 'steps' => ['Décrivez votre besoin.', 'Vérifiez les résultats proposés.', 'Autorisez explicitement toute action.'], 'audience' => 'admin', 'participant_roles' => [], 'sort_order' => 100],
        ];
    }
}
