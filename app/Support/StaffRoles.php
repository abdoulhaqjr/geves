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
        'assign_reviewers' => 'Assigner relecteurs',
        'evaluate' => 'Évaluer (relectures)',
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
            'label' => 'Administrateur colloque',
            'category' => 'Système', 'pill' => 'ok', 'abbr' => 'Admin',
            'description' => 'Accès complet : événements, soumissions, décisions, documents, paiements, e-mails, réglages.',
            'permissions' => [
                'view_dashboard', 'editions_read', 'editions_write', 'submissions_list', 'assign_reviewers',
                'evaluate', 'final_decision', 'participants', 'payments', 'documents', 'formation', 'emails', 'project',
                'announcements', 'claims', 'testimonials', 'users_roles', 'settings',
            ],
        ],
        'gerant' => [
            'label' => 'Gestionnaire du colloque',
            'category' => 'Système', 'pill' => 'ok', 'abbr' => 'Gestion.',
            'description' => 'Responsable d’un colloque : accès complet à CE colloque (soumissions, décisions, documents, paiements, e-mails, équipe).',
            'permissions' => [
                'view_dashboard', 'editions_read', 'editions_write', 'submissions_list', 'assign_reviewers',
                'evaluate', 'final_decision', 'participants', 'payments', 'documents', 'formation', 'emails', 'project', 'announcements', 'claims',
            ],
        ],
        'co_gerant' => [
            'label' => 'Co-gestionnaire du colloque',
            'category' => 'Système', 'pill' => 'ok', 'abbr' => 'Co-gest.',
            'description' => 'Co-responsable d’un colloque : mêmes accès que le gestionnaire sur CE colloque.',
            'permissions' => [
                'view_dashboard', 'editions_read', 'editions_write', 'submissions_list', 'assign_reviewers',
                'evaluate', 'final_decision', 'participants', 'payments', 'documents', 'formation', 'emails', 'project', 'announcements', 'claims',
            ],
        ],
        'scientific' => [
            'label' => 'Comité scientifique',
            'category' => 'Système', 'pill' => 'review', 'abbr' => 'Comité sci.',
            'description' => 'Synthèse des relectures, décisions d’acceptation / refus / corrections.',
            'permissions' => ['view_dashboard', 'editions_read', 'submissions_list', 'assign_reviewers', 'evaluate', 'final_decision'],
        ],
        'comite_supervision' => [
            'label' => 'Comité de supervision',
            'category' => 'Système', 'pill' => 'info', 'abbr' => 'Comité sup.',
            'description' => 'Supervision du colloque : vue d’ensemble et suivi (soumissions, participants, paiements), sans décision ni modification.',
            'permissions' => ['view_dashboard', 'editions_read', 'submissions_list', 'participants', 'payments'],
        ],
        'comite_organisation' => [
            'label' => 'Comité d’organisation',
            'category' => 'Support', 'pill' => 'warn', 'abbr' => 'Comité org.',
            'description' => 'Membre du comité d’organisation : suivi des participants, documents et programme du colloque.',
            'permissions' => ['view_dashboard', 'editions_read', 'submissions_list', 'participants', 'documents', 'emails', 'project', 'claims'],
        ],
        'reviewer' => [
            'label' => 'Relecteur',
            'category' => 'Système', 'pill' => 'info', 'abbr' => 'Relect.',
            'description' => 'Évaluation des soumissions assignées, déclaration COI, commentaires et recommandation.',
            'permissions' => ['view_dashboard', 'submissions_list', 'evaluate'],
        ],
        'organizer' => [
            'label' => 'Organisateur',
            'category' => 'Système', 'pill' => 'warn', 'abbr' => 'Organis.',
            'description' => 'Participants, paiements, e-mails, documents (attestations / badges) - sans décisions scientifiques.',
            'permissions' => ['view_dashboard', 'editions_read', 'submissions_list', 'participants', 'payments', 'documents', 'emails', 'project'],
        ],
        'secretariat' => [
            'label' => 'Secrétariat',
            'category' => 'Support', 'pill' => 'sec', 'abbr' => 'Sécr.',
            'description' => 'Suivi administratif des soumissions et participants, relances, envoi de documents, assistance aux auteurs.',
            'permissions' => ['view_dashboard', 'editions_read', 'submissions_list', 'participants', 'documents', 'emails', 'project', 'announcements', 'claims'],
        ],
        'accounting' => [
            'label' => 'Comptabilité',
            'category' => 'Support', 'pill' => 'compta', 'abbr' => 'Compta',
            'description' => 'Consultation et suivi des paiements, rapprochements, exports financiers - sans modification des tarifs.',
            'permissions' => ['view_dashboard', 'payments'],
        ],
        'support' => [
            'label' => 'Appui à l’organisation',
            'category' => 'Support', 'pill' => 'appui', 'abbr' => 'Appui',
            'description' => 'Aide logistique (programme, salles, partenaires), consultation des listes participants, documents de session.',
            'permissions' => ['view_dashboard', 'editions_read', 'participants', 'documents', 'project', 'claims'],
        ],
        'reception' => [
            'label' => 'Accueil',
            'category' => 'Support', 'pill' => 'accueil', 'abbr' => 'Accueil',
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

    /** Options {name => label} pour les sélecteurs de rôle. */
    public static function options(): array
    {
        return array_map(fn ($r) => $r['label'], self::ROLES);
    }

    /**
     * Fonctions assignables à une équipe d'événement (staff ↔ événement). On exclut :
     *  - `administrator` : capacité globale (transverse à tous les colloques) ;
     *  - `reviewer` : la relecture scientifique passe par le Participant relecteur
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
        return self::ROLES[$role]['permissions'] ?? [];
    }
}
