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
            'fonctionnement-participant', 'fonctionnement', 'colloque', 'configuration', 'secretariat' => 'pilotage',
            'visio', 'rapport', 'moderation' => 'seances',
            default => 'accompagnement',
        };
    }

    public static function iconFor(string $key): string
    {
        return match ($key) {
            'fonctionnement-participant', 'fonctionnement' => 'home', 'soumission' => 'inbox', 'visio' => 'video',
            'rapport' => 'sheet', 'moderation' => 'mic', 'colloque' => 'calendar',
            'configuration' => 'settings', 'secretariat' => 'folder', 'chat-public' => 'message',
            'chat-admin' => 'sparkles', default => 'book',
        };
    }

    /** Initialise le catalogue et remplace uniquement les anciens textes restés intacts. */
    public static function ensureDefaults(): void
    {
        foreach (self::defaults() as $guide) {
            $current = TutorialGuide::firstOrCreate(['key' => $guide['key']], $guide);
            $legacy = self::legacyDefaults()[$guide['key']] ?? null;
            if ($legacy === null || ! self::isUneditedLegacy($current, $legacy)) {
                continue;
            }

            $attributes = collect($guide)->except(['key'])->all();
            if ($current->hasVideo()) {
                // Une vidéo fondée sur les anciennes étapes ne doit plus être
                // présentée comme un tutoriel à jour dans l'espace participant.
                $attributes['video_generation_status'] = 'stale';
            }
            $current->update($attributes);
        }
    }

    /** @param array<string, mixed> $legacy */
    private static function isUneditedLegacy(TutorialGuide $current, array $legacy): bool
    {
        foreach (['title', 'intro', 'steps', 'audience', 'participant_roles'] as $field) {
            if ($current->{$field} !== $legacy[$field]) {
                return false;
            }
        }

        return true;
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

    /** Textes publiés avant la réécriture : utilisés seulement pour protéger les adaptations humaines. */
    private static function legacyDefaults(): array
    {
        return [
            'fonctionnement' => ['title' => 'Comprendre la plateforme', 'intro' => 'Repérez les espaces, les rôles et le fil de travail d’un événement scientifique.', 'steps' => ['Choisissez le colloque sur lequel vous intervenez.', 'Utilisez les modules liés à votre fonction.', 'Vos droits suivent votre rôle dans chaque événement.'], 'audience' => 'both', 'participant_roles' => ['all']],
            'soumission' => ['title' => 'Soumettre et suivre une communication', 'intro' => 'Le dépôt se fait depuis l’espace participant ; le suivi scientifique se fait dans le back-office.', 'steps' => ['Sélectionnez l’appel ouvert puis confirmez votre soumission.', 'Suivez les demandes de correction et la décision.', 'Retrouvez votre programmation depuis votre compte.'], 'audience' => 'both', 'participant_roles' => ['all']],
            'visio' => ['title' => 'Présenter une communication en visio', 'intro' => 'Préparez la séance, rejoignez la salle et utilisez les contrôles adaptés à votre rôle.', 'steps' => ['Préparez caméra, micro et connexion avant la séance.', 'Rejoignez la salle depuis la séance programmée.', 'Le modérateur anime les échanges et le rapporteur consigne la séance.'], 'audience' => 'both', 'participant_roles' => ['author', 'coauthor', 'moderator', 'rapporteur']],
            'rapport' => ['title' => 'Rapporter une session', 'intro' => 'Le rapporteur documente le déroulement réel d’une séance afin de produire un compte rendu exploitable.', 'steps' => ['Accédez à votre espace Rapporteur.', 'Renseignez les observations et recommandations.', 'Le secrétariat peut relire et valider le rapport.'], 'audience' => 'both', 'participant_roles' => ['rapporteur']],
            'moderation' => ['title' => 'Modérer une session', 'intro' => 'Le modérateur garantit le rythme de la séance et facilite les échanges avec les participants.', 'steps' => ['Vérifiez votre affectation et rejoignez la séance.', 'Gérez les temps de parole et les questions.', 'Consignez le passage des communications si nécessaire.'], 'audience' => 'both', 'participant_roles' => ['moderator']],
            'colloque' => ['title' => 'Gérer un colloque', 'intro' => 'Organisez les éditions d’une série, leur équipe et les étapes qui structurent l’événement.', 'steps' => ['Créez ou sélectionnez la série et son événement.', 'Définissez l’équipe et ses capacités.', 'Suivez l’appel, les inscriptions, le programme et les documents.'], 'audience' => 'admin', 'participant_roles' => []],
            'configuration' => ['title' => 'Configurer un événement', 'intro' => 'Réglez les informations publiques, les échéances et les options opérationnelles.', 'steps' => ['Ouvrez la fiche de l’événement.', 'Renseignez les dates et paramètres de publication.', 'Contrôlez les échéances et les informations de paiement.'], 'audience' => 'admin', 'participant_roles' => []],
            'secretariat' => ['title' => 'Travailler au secrétariat', 'intro' => 'Centralisez les documents, les opérations de séance et le suivi administratif.', 'steps' => ['Préparez les documents utiles aux équipes.', 'Suivez inscriptions, paiements et demandes.', 'Contrôlez les documents officiels avant diffusion.'], 'audience' => 'admin', 'participant_roles' => []],
            'chat-public' => ['title' => 'Utiliser le chatbot public', 'intro' => 'L’assistante publique répond aux questions des visiteurs et oriente les participants.', 'steps' => ['Testez l’assistante comme un visiteur.', 'Maintenez à jour les informations publiées.', 'Transmettez les demandes non résolues au back-office.'], 'audience' => 'both', 'participant_roles' => ['all']],
            'chat-admin' => ['title' => 'Utiliser le chatbot admin', 'intro' => 'Le copilote aide à analyser la situation et à préparer des actions dans l’événement sélectionné.', 'steps' => ['Décrivez votre besoin.', 'Vérifiez les résultats proposés.', 'Autorisez explicitement toute action.'], 'audience' => 'admin', 'participant_roles' => []],
        ];
    }

    /** @return list<array<string, mixed>> */
    private static function defaults(): array
    {
        return [
            [
                'key' => 'fonctionnement-participant', 'title' => 'Découvrir GEVES comme participant',
                'intro' => 'GEVES réunit les appels à communications, les inscriptions, le programme et vos documents dans un espace personnel. Ce parcours vous aide à trouver chaque service au bon moment.',
                'steps' => [
                    'Depuis l’accueil public, consultez les colloques en cours, leurs dates, leurs thèmes et le programme publié avant de choisir celui qui vous intéresse.',
                    'Cliquez sur Créer un compte, renseignez vos coordonnées et confirmez votre adresse e-mail avec le message reçu.',
                    'Connectez-vous, complétez votre profil (notamment votre affiliation), puis sélectionnez le colloque concerné dans votre espace.',
                    'Ouvrez le tableau de bord : il rassemble vos soumissions, vos prochaines séances et les actions qui demandent votre attention.',
                    'Utilisez Soumissions pour déposer ou suivre un résumé ; consultez ensuite votre agenda, votre inscription et vos paiements selon les options de l’événement.',
                    'Retrouvez badges, reçus et attestations dans Mes documents lorsqu’ils sont disponibles ; utilisez l’assistante publique pour une question ou une réclamation.',
                ],
                'audience' => 'participant', 'participant_roles' => ['all'], 'sort_order' => 5,
            ],
            [
                'key' => 'fonctionnement', 'title' => 'Découvrir GEVES comme administrateur',
                'intro' => 'Le back-office pilote un colloque de sa création à son bilan. Ce parcours situe les modules et explique dans quel ordre les utiliser.',
                'steps' => [
                    'Connectez-vous au back-office et ouvrez le tableau de bord pour repérer les indicateurs et les alertes du colloque.',
                    'Sélectionnez l’événement à gérer ; vos droits et les données visibles dépendent de votre fonction dans cet événement.',
                    'Ouvrez Événements pour créer ou modifier le colloque, son équipe, ses échéances et les informations destinées au public.',
                    'Préparez l’appel dans Soumissions, puis suivez les dépôts, les évaluations, les décisions et les corrections demandées aux auteurs.',
                    'Construisez le Programme : ateliers, sessions, créneaux, espaces et accès aux séances de visioconférence.',
                    'Suivez Participants, Paiements, Secrétariat et Documents pour les inscriptions, les opérations et les pièces officielles.',
                    'Utilisez Projet pour les tâches et jalons, Rapports pour le bilan et Assistant IA pour préparer des analyses ou actions à valider.',
                ],
                'audience' => 'admin', 'participant_roles' => [], 'sort_order' => 10,
            ],
            [
                'key' => 'soumission', 'title' => 'Déposer et suivre un résumé',
                'intro' => 'De la création du compte à la programmation, suivez chaque étape du dépôt de votre résumé depuis l’espace participant.',
                'steps' => [
                    'Créez votre compte depuis Inscription, confirmez votre adresse e-mail, puis connectez-vous à l’espace participant.',
                    'Ouvrez Profil et complétez votre affiliation : l’e-mail vérifié et le profil complété sont requis pour soumettre.',
                    'Choisissez le colloque dont l’appel est ouvert, puis ouvrez Soumissions et cliquez sur Déposer un résumé.',
                    'Dans Général, renseignez le titre et les champs demandés par le colloque, notamment la thématique lorsqu’elle est proposée.',
                    'Dans Auteurs, ajoutez les co-auteurs et vérifiez leurs noms, affiliations et ordre de présentation.',
                    'Dans Résumé, saisissez le texte et les mots-clés requis ; joignez les fichiers si une étape Fichiers est proposée.',
                    'Relisez toutes les données dans Validation, puis confirmez l’envoi. Vérifiez que la communication apparaît dans Mes soumissions.',
                    'Ouvrez la fiche de la soumission pour suivre les demandes de correction et la décision ; après programmation, consultez votre créneau et le lien de la séance.',
                ],
                'audience' => 'participant', 'participant_roles' => ['all'], 'sort_order' => 20,
            ],
            [
                'key' => 'visio', 'title' => 'Présenter sa communication en visio',
                'intro' => 'Retrouvez votre séance programmée, préparez vos équipements et utilisez les commandes essentielles pendant votre présentation.',
                'steps' => [
                    'Connectez-vous à votre espace participant et ouvrez votre communication ou votre agenda pour repérer la date, l’heure et la séance programmée.',
                    'À l’heure indiquée, cliquez sur Rejoindre depuis votre tableau de bord ou le programme : l’accès dépend de l’ouverture de la salle.',
                    'Sur l’écran de préparation, autorisez le navigateur à utiliser caméra et micro, puis contrôlez l’image et le niveau sonore avant de rejoindre la salle.',
                    'Dans la salle, utilisez le bouton Micro pour activer ou couper votre voix ; gardez-le coupé lorsque vous ne parlez pas.',
                    'Utilisez le bouton Caméra pour afficher ou masquer votre image et vérifiez son aperçu avant votre intervention.',
                    'Au moment de présenter, cliquez sur Partager l’écran, choisissez la fenêtre ou l’onglet de vos diapositives, puis arrêtez le partage à la fin.',
                    'Suivez les indications du modérateur, répondez aux questions avec le micro, puis quittez la salle quand votre participation est terminée.',
                ],
                'audience' => 'participant', 'participant_roles' => ['author', 'coauthor'], 'sort_order' => 30,
            ],
            [
                'key' => 'rapport', 'title' => 'Rapporter une séance',
                'intro' => 'Le rapporteur prépare la séance, consigne son déroulement réel et remet un compte rendu exploitable au secrétariat.',
                'steps' => [
                    'Avant la séance, connectez-vous et ouvrez Mes séances à rapporter pour vérifier votre affectation, l’horaire, la salle et les communications prévues.',
                    'Consultez le programme et préparez les points à relever : interventions, questions, décisions et éventuels incidents.',
                    'Dès le début de la séance, ouvrez Présence et pointez les personnes présentes si cette tâche vous revient ; le pointage sert au procès-verbal.',
                    'Pendant la séance, notez le déroulé, les temps forts, les échanges et les recommandations, puis vérifiez avec le modérateur les passages réellement effectués.',
                    'Ouvrez Rédiger le rapport, renseignez la synthèse et les points saillants ; enregistrez un brouillon si le compte rendu doit encore être complété.',
                    'Après la séance, relisez les présences et le texte, soumettez le rapport, puis consultez son statut jusqu’à la validation par le secrétariat.',
                ],
                'audience' => 'participant', 'participant_roles' => ['rapporteur'], 'sort_order' => 40,
            ],
            [
                'key' => 'moderation', 'title' => 'Modérer une séance',
                'intro' => 'Le modérateur prépare les interventions, anime la salle et valide les présences et les communications réellement passées.',
                'steps' => [
                    'Avant la séance, ouvrez Mes séances à modérer et vérifiez votre affectation, les horaires, la salle et la liste des communications programmées.',
                    'Préparez l’ordre des passages et les temps de parole avec le rapporteur ; rejoignez la visio si une salle en ligne est prévue.',
                    'À l’ouverture, utilisez Présence pour marquer chaque auteur présent ou absent ; la saisie est disponible à partir du début de la séance.',
                    'Pendant les interventions, accueillez les orateurs, annoncez l’ordre de passage, surveillez le temps et donnez la parole pour les questions.',
                    'Dans la salle de visio, gérez les prises de parole et les micros lorsque votre rôle le permet ; signalez au rapporteur les incidents ou changements.',
                    'Après chaque intervention réellement effectuée, ouvrez Communications programmées et cliquez sur Valider le passage pour la communication concernée. Ce constat ne se retire pas.',
                    'En clôture, vérifiez la présence, les passages et le compte rendu avec le rapporteur avant de quitter la séance.',
                ],
                'audience' => 'participant', 'participant_roles' => ['moderator'], 'sort_order' => 50,
            ],
            [
                'key' => 'colloque', 'title' => 'Gérer un colloque de bout en bout',
                'intro' => 'Pour les gestionnaires : créez l’événement, coordonnez son déroulement et suivez les résultats jusqu’au bilan.',
                'steps' => [
                    'Dans Événements, créez le colloque ou choisissez une série existante ; renseignez son titre, ses dates et son équipe.',
                    'Complétez la fiche de l’événement : informations publiques, calendrier, appel à communications, modalités d’inscription et paiement si applicable.',
                    'Dans Soumissions, préparez le formulaire et les règles, puis suivez les dépôts, les évaluations et les décisions scientifiques.',
                    'Dans Programme, organisez ateliers et sessions, affectez les créneaux, les espaces, les intervenants et les accès visio.',
                    'Suivez Participants, Paiements et Secrétariat pour contrôler les inscriptions, les demandes et les documents opérationnels.',
                    'Dans Projet, attribuez les tâches, fixez les jalons et mettez leurs états à jour avec l’équipe jusqu’à la clôture.',
                    'Dans Rapports, consultez les indicateurs et exportez le bilan de l’événement ; vérifiez les données avant diffusion.',
                ],
                'audience' => 'admin', 'participant_roles' => [], 'sort_order' => 60,
            ],
            [
                'key' => 'configuration', 'title' => 'Configurer les paramètres d’un événement',
                'intro' => 'Détaillez les réglages qui conditionnent l’appel, le paiement, les séances en ligne et la publication du colloque.',
                'steps' => [
                    'Ouvrez Événements, choisissez le colloque, puis cliquez sur Modifier pour accéder à sa fiche de configuration.',
                    'Renseignez les informations de présentation, les dates, le lieu et les éléments qui seront affichés sur la page publique.',
                    'Définissez les échéances de l’appel et les règles de soumission ; contrôlez le formulaire que les auteurs verront.',
                    'Configurez les inscriptions et les tarifs de paiement seulement si l’événement en a besoin ; vérifiez les dates et les montants.',
                    'Dans les options de visioconférence, choisissez le fournisseur et la qualité adaptés aux connexions attendues.',
                    'Ajoutez les membres de l’équipe et vérifiez leurs capacités pour que chacun accède aux modules nécessaires.',
                    'Enregistrez, consultez la page publique et faites un dernier contrôle avant d’annoncer l’ouverture du colloque.',
                ],
                'audience' => 'admin', 'participant_roles' => [], 'sort_order' => 70,
            ],
            [
                'key' => 'secretariat', 'title' => 'Tenir le secrétariat de l’événement',
                'intro' => 'Préparez les documents, accompagnez les participants pendant le colloque et validez les dossiers de séance.',
                'steps' => [
                    'Sélectionnez l’événement, puis ouvrez Secrétariat pour retrouver les dossiers à suivre, les rapports de séance et les documents logistiques.',
                    'Préparez l’en-tête commun et ouvrez Documents pour produire listes de présence, feuilles d’émargement, ordres du jour et supports utiles aux salles.',
                    'Dans le module Documents, vérifiez les modèles, puis préparez les badges et attestations selon les règles de délivrance de l’événement.',
                    'Avant chaque séance, rassemblez le kit de modération : programme, affectations, liste de présence et fiches nécessaires aux modérateurs et rapporteurs.',
                    'Pendant l’événement, contrôlez les inscriptions et paiements, orientez les participants et traitez les demandes qui arrivent au secrétariat.',
                    'Après les séances, ouvrez Rapports de séance, relisez les présences et les comptes rendus, puis validez les rapports complets.',
                    'Utilisez Secrétariat assisté pour préparer un brouillon de document avec l’IA, relisez-le et corrigez-le avant téléchargement ou diffusion.',
                ],
                'audience' => 'admin', 'participant_roles' => [], 'sort_order' => 80,
            ],
            [
                'key' => 'chat-public', 'title' => 'Obtenir de l’aide avec l’assistante publique',
                'intro' => 'L’assistante répond sur le colloque et, si vous êtes connecté, peut expliquer vos propres démarches et enregistrer une réclamation non résolue.',
                'steps' => [
                    'Depuis la page publique ou votre espace participant, ouvrez l’assistante et indiquez le colloque ou la démarche qui vous intéresse.',
                    'Demandez les informations pratiques publiées : dates, programme, inscription, soumission ou accès aux séances.',
                    'Connectez-vous pour poser une question personnelle sur votre soumission, votre programmation, un paiement ou vos documents ; seules vos données sont utilisées.',
                    'Si la réponse ne résout pas votre problème, décrivez précisément la réclamation ou l’incident et fournissez les informations de contact demandées.',
                    'Conservez la référence de suivi donnée lorsque la demande est enregistrée ; poursuivez la même discussion pour ajouter des précisions.',
                ],
                'audience' => 'participant', 'participant_roles' => ['all'], 'sort_order' => 90,
            ],
            [
                'key' => 'chat-admin', 'title' => 'Travailler avec le copilote administrateur',
                'intro' => 'Le copilote aide les gestionnaires à analyser les données du colloque et à préparer des actions qu’ils contrôlent avant exécution.',
                'steps' => [
                    'Sélectionnez le bon événement dans le back-office, puis ouvrez Assistant IA pour travailler dans son contexte.',
                    'Formulez une demande précise : synthèse des soumissions, contrôle d’une situation, préparation d’un message ou d’une tâche.',
                    'Lisez la réponse et vérifiez les chiffres, les noms, les dates et les sources dans les modules concernés avant de l’utiliser.',
                    'Si le copilote propose une action, examinez ses paramètres et ses effets ; autorisez-la explicitement seulement après vérification.',
                    'Revenez dans le module concerné pour contrôler le résultat et corriger manuellement ce qui doit l’être.',
                ],
                'audience' => 'admin', 'participant_roles' => [], 'sort_order' => 100,
            ],
        ];
    }
}
