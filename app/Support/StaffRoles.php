<?php

namespace App\Support;

/**
 * Rôles métier GEVES et matrice de permissions du back-office
 * (fidèle au mockup admin-users). Source unique consommée par le seeder
 * (crée les rôles/permissions spatie, guard « web ») et par la page
 * Utilisateurs (onglets Rôles & Accès). Les libellés/pastilles/catégories
 * alimentent l'affichage ; les listes de permissions alimentent la matrice.
 */
class StaffRoles
{
    /** Permissions (clé technique → libellé lisible), dans l'ordre de la matrice. */
    public const PERMISSIONS = [
        'view_dashboard' => 'Tableau de bord',
        'editions_read' => 'Événements (lecture)',
        'editions_write' => 'Événements (écriture)',
        'submissions_list' => 'Soumissions - liste',
        'assign_reviewers' => 'Assigner des évaluateurs',
        'evaluate' => 'Évaluer les soumissions',
        'final_decision' => 'Décision finale',
        'participants' => 'Participants',
        'payments' => 'Paiements',
        'documents' => 'Documents (attestations…)',
        'formation' => 'Formations',
        'emails' => 'E-mails',
        'project' => 'Projet (gestion)',
        'announcements' => 'Annonces (déroulement)',
        'claims' => 'Réclamations & incidents',
        'testimonials' => 'Témoignages (accueil)',
        'users_roles' => 'Utilisateurs & rôles',
        'settings' => 'Réglages',
    ];

    /**
     * Rôles UKC : libellé, catégorie (Système/Support), pastille, description,
     * abréviation de colonne (matrice), et permissions accordées.
     *
     * @var array<string, array{label: string, category: string, pill: string, abbr: string, description: string, permissions: list<string>}>
     */
    public const ROLES = [
        'administrator' => [
            // ⚠️ « (rôle hérité) » n'est pas décoratif : ce rôle spatie porte
            // `manage_colloques`, donc `isColloqueSuperAdmin()` - il ouvre TOUT,
            // au même titre que le niveau super administrateur. Le confondre
            // avec le niveau « Administrateur », qui est le cran en dessous,
            // reviendrait à donner la plateforme entière en croyant nommer un
            // administrateur ordinaire. Plus rien ne l'attribue.
            'label' => 'Administrateur (rôle hérité)',
            'category' => 'Organisation', 'pill' => 'ok', 'abbr' => 'Admin',
            'description' => 'Ancien rôle global, équivalent au niveau super administrateur : il ouvre toute la plateforme. Conservé pour les comptes qui le portent déjà ; les niveaux d’accès l’ont remplacé.',
            'permissions' => [
                'view_dashboard', 'editions_read', 'editions_write', 'submissions_list', 'assign_reviewers',
                'evaluate', 'final_decision', 'participants', 'payments', 'documents', 'formation', 'emails', 'project',
                'announcements', 'claims', 'testimonials', 'users_roles', 'settings',
            ],
        ],
        'gerant' => [
            'label' => 'Gestionnaire de l’événement',
            'category' => 'Organisation', 'pill' => 'ok', 'abbr' => 'Gestion.',
            'description' => 'Responsable d’un événement : accès complet à CET événement (soumissions, décisions, documents, paiements, e-mails, équipe).',
            'permissions' => [
                'view_dashboard', 'editions_read', 'editions_write', 'submissions_list', 'assign_reviewers',
                'evaluate', 'final_decision', 'participants', 'payments', 'documents', 'formation', 'emails', 'project', 'announcements', 'claims',
            ],
        ],
        'co_gerant' => [
            'label' => 'Co-gestionnaire de l’événement',
            'category' => 'Organisation', 'pill' => 'ok', 'abbr' => 'Co-gest.',
            'description' => 'Co-responsable d’un événement : mêmes accès que le gestionnaire sur CET événement.',
            'permissions' => [
                'view_dashboard', 'editions_read', 'editions_write', 'submissions_list', 'assign_reviewers',
                'evaluate', 'final_decision', 'participants', 'payments', 'documents', 'formation', 'emails', 'project', 'announcements', 'claims',
            ],
        ],
        'scientific' => [
            'label' => 'Comité scientifique',
            'category' => 'Organisation', 'pill' => 'review', 'abbr' => 'Comité sci.',
            'description' => 'Synthèse des évaluations, décisions d’acceptation / refus / corrections.',
            'permissions' => ['view_dashboard', 'editions_read', 'submissions_list', 'assign_reviewers', 'evaluate', 'final_decision'],
        ],
        'comite_supervision' => [
            'label' => 'Comité de supervision',
            'category' => 'Organisation', 'pill' => 'info', 'abbr' => 'Comité sup.',
            'description' => 'Supervision de l’événement : vue d’ensemble et suivi (soumissions, participants, paiements), sans décision ni modification.',
            'permissions' => ['view_dashboard', 'editions_read', 'submissions_list', 'participants', 'payments'],
        ],
        'comite_organisation' => [
            'label' => 'Comité d’organisation',
            'category' => 'Appui', 'pill' => 'warn', 'abbr' => 'Comité org.',
            'description' => 'Membre du comité d’organisation : suivi des participants, documents et programme de l’événement.',
            'permissions' => ['view_dashboard', 'editions_read', 'submissions_list', 'participants', 'documents', 'emails', 'project', 'claims'],
        ],
        'reviewer' => [
            'label' => 'Évaluateur',
            'category' => 'Organisation', 'pill' => 'info', 'abbr' => 'Éval.',
            'description' => 'Évaluation des soumissions assignées, déclaration COI, commentaires et recommandation.',
            'permissions' => ['view_dashboard', 'submissions_list', 'evaluate'],
        ],
        'organizer' => [
            'label' => 'Organisateur',
            'category' => 'Organisation', 'pill' => 'warn', 'abbr' => 'Organis.',
            'description' => 'Participants, paiements, e-mails, documents (attestations / badges) - sans décisions scientifiques.',
            'permissions' => ['view_dashboard', 'editions_read', 'submissions_list', 'participants', 'payments', 'documents', 'emails', 'project'],
        ],
        'secretariat' => [
            'label' => 'Secrétariat',
            'category' => 'Appui', 'pill' => 'sec', 'abbr' => 'Sécr.',
            'description' => 'Suivi administratif des soumissions et participants, relances, envoi de documents, assistance aux auteurs.',
            'permissions' => ['view_dashboard', 'editions_read', 'submissions_list', 'participants', 'documents', 'emails', 'project', 'announcements', 'claims'],
        ],
        'accounting' => [
            'label' => 'Comptabilité',
            'category' => 'Appui', 'pill' => 'compta', 'abbr' => 'Compta',
            'description' => 'Consultation et suivi des paiements, rapprochements, exports financiers - sans modification des tarifs.',
            'permissions' => ['view_dashboard', 'payments'],
        ],
        'support' => [
            'label' => 'Appui à l’organisation',
            'category' => 'Appui', 'pill' => 'appui', 'abbr' => 'Appui',
            'description' => 'Aide logistique (programme, salles, partenaires), consultation des listes participants, documents de session.',
            'permissions' => ['view_dashboard', 'editions_read', 'participants', 'documents', 'project', 'claims'],
        ],
        'reception' => [
            'label' => 'Accueil',
            'category' => 'Appui', 'pill' => 'accueil', 'abbr' => 'Accueil',
            'description' => 'Accueil et orientation des participants le jour J, consultation des listes d’inscrits.',
            'permissions' => ['view_dashboard', 'participants'],
        ],
    ];

    /** Libellé lisible d'un rôle (repli sur le nom brut). */
    public static function label(string $role): string
    {
        return self::ROLES[$role]['label'] ?? ucfirst($role);
    }

    /** Pastille d'un rôle pour l'UI (repli « muted »). */
    public static function pill(string $role): string
    {
        return self::ROLES[$role]['pill'] ?? 'muted';
    }

    /**
     * Pastille de la FAMILLE (« Organisation » / « Appui »), une couleur par
     * famille.
     *
     * ⚠️ L'onglet Rôles affichait le nom de la famille mais prenait, pour les
     * « Appui », la couleur propre à chaque fonction : Comptabilité ressortait en
     * vert, exactement comme les « Organisation ». La couleur ne portait donc
     * aucune information - elle en suggérait une fausse. {@see pill()} reste la
     * couleur de la FONCTION, employée ailleurs (liste des utilisateurs).
     */
    public static function categoryPill(string $role): string
    {
        return (self::ROLES[$role]['category'] ?? '') === 'Organisation' ? 'ok' : 'info';
    }

    /** Options {name => label} pour les sélecteurs de rôle. */
    public static function options(): array
    {
        return array_map(fn ($r) => $r['label'], self::ROLES);
    }

    /**
     * Fonctions assignables à une équipe d'événement (staff ↔ événement). On exclut :
     *  - `administrator` : capacité globale (transverse à tous les événements) ;
     *  - `reviewer` : l’évaluation scientifique passe par le Participant évaluateur
     *    (par événement, portail à jeton) - pas par un rôle staff global.
     *
     * @return array<string, string> nom => libellé
     */
    public static function teamRoles(): array
    {
        return collect(self::ROLES)
            ->except(['administrator', 'reviewer'])
            ->map(fn ($r) => $r['label'])
            ->all();
    }

    /** Vrai si la fonction peut être affectée à une équipe d'événement. */
    public static function isTeamRole(string $role): bool
    {
        return array_key_exists($role, self::teamRoles());
    }

    /**
     * Permissions accordées par une fonction (vide si inconnue).
     *
     * @return list<string>
     */
    public static function permissionsFor(string $role): array
    {
        return app(StaffRolePermissions::class)->forRole($role);
    }
}
