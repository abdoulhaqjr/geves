<?php

namespace App\Support;

/**
 * Rôles métier UK Colloques et matrice de permissions du back-office
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
        'editions_read' => 'Éditions (lecture)',
        'editions_write' => 'Éditions (écriture)',
        'submissions_list' => 'Soumissions — liste',
        'assign_reviewers' => 'Assigner relecteurs',
        'evaluate' => 'Évaluer (avis)',
        'final_decision' => 'Décision finale',
        'participants' => 'Participants',
        'payments' => 'Paiements',
        'documents' => 'Documents (attestations…)',
        'emails' => 'E-mails',
        'badge_scan' => 'Scan badges / QR',
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
            'description' => 'Accès complet : éditions, soumissions, décisions, documents, paiements, e-mails, réglages.',
            'permissions' => [
                'view_dashboard', 'editions_read', 'editions_write', 'submissions_list', 'assign_reviewers',
                'evaluate', 'final_decision', 'participants', 'payments', 'documents', 'emails', 'badge_scan',
                'users_roles', 'settings',
            ],
        ],
        'gerant' => [
            'label' => 'Gérant du colloque',
            'category' => 'Système', 'pill' => 'ok', 'abbr' => 'Gérant',
            'description' => 'Responsable d’un colloque : accès complet à CE colloque (soumissions, décisions, documents, paiements, e-mails, équipe).',
            'permissions' => [
                'view_dashboard', 'editions_read', 'editions_write', 'submissions_list', 'assign_reviewers',
                'evaluate', 'final_decision', 'participants', 'payments', 'documents', 'emails', 'badge_scan',
            ],
        ],
        'co_gerant' => [
            'label' => 'Co-gérant du colloque',
            'category' => 'Système', 'pill' => 'ok', 'abbr' => 'Co-gér.',
            'description' => 'Co-responsable d’un colloque : mêmes accès que le gérant sur CE colloque.',
            'permissions' => [
                'view_dashboard', 'editions_read', 'editions_write', 'submissions_list', 'assign_reviewers',
                'evaluate', 'final_decision', 'participants', 'payments', 'documents', 'emails', 'badge_scan',
            ],
        ],
        'scientific' => [
            'label' => 'Comité scientifique',
            'category' => 'Système', 'pill' => 'review', 'abbr' => 'Comité sci.',
            'description' => 'Synthèse des avis, décisions d’acceptation / refus / corrections, vue sur les relectures.',
            'permissions' => ['view_dashboard', 'editions_read', 'submissions_list', 'assign_reviewers', 'evaluate', 'final_decision'],
        ],
        'comite_organisation' => [
            'label' => 'Comité d’organisation',
            'category' => 'Support', 'pill' => 'warn', 'abbr' => 'Comité org.',
            'description' => 'Membre du comité d’organisation : suivi des participants, documents et programme du colloque.',
            'permissions' => ['view_dashboard', 'editions_read', 'submissions_list', 'participants', 'documents', 'emails'],
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
            'description' => 'Participants, paiements, e-mails, documents (attestations / badges) — sans décisions scientifiques.',
            'permissions' => ['view_dashboard', 'editions_read', 'submissions_list', 'participants', 'payments', 'documents', 'emails', 'badge_scan'],
        ],
        'secretariat' => [
            'label' => 'Secrétariat',
            'category' => 'Support', 'pill' => 'sec', 'abbr' => 'Sécr.',
            'description' => 'Suivi administratif des soumissions et participants, relances, envoi de documents, assistance aux auteurs.',
            'permissions' => ['view_dashboard', 'editions_read', 'submissions_list', 'participants', 'documents', 'emails'],
        ],
        'accounting' => [
            'label' => 'Comptabilité',
            'category' => 'Support', 'pill' => 'compta', 'abbr' => 'Compta',
            'description' => 'Consultation et suivi des paiements, rapprochements, exports financiers — sans modification des tarifs.',
            'permissions' => ['view_dashboard', 'payments'],
        ],
        'support' => [
            'label' => 'Appui à l’organisation',
            'category' => 'Support', 'pill' => 'appui', 'abbr' => 'Appui',
            'description' => 'Aide logistique (programme, salles, partenaires), consultation des listes participants, documents de session.',
            'permissions' => ['view_dashboard', 'editions_read', 'participants', 'documents', 'badge_scan'],
        ],
        'reception' => [
            'label' => 'Accueil',
            'category' => 'Support', 'pill' => 'accueil', 'abbr' => 'Accueil',
            'description' => 'Vérification des badges / QR codes le jour J, pointage des présents, orientation des participants.',
            'permissions' => ['view_dashboard', 'participants', 'badge_scan'],
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
     * Fonctions assignables à une équipe d'édition (staff ↔ édition). On exclut :
     *  - `administrator` : capacité globale (transverse à tous les colloques) ;
     *  - `reviewer` : la relecture scientifique passe par le Participant relecteur
     *    (par édition, portail à jeton) — pas par un rôle staff global.
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

    /** Vrai si la fonction peut être affectée à une équipe d'édition. */
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
