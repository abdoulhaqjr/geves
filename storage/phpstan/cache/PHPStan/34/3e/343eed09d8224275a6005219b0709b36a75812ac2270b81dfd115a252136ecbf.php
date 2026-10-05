<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Support\StaffRoles.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Support\StaffRoles
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-9095045fbfbd54393a2fbfe3def55a3db75c0c88c647b280966d80c5fc37fde5',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Support\\StaffRoles',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Support/StaffRoles.php',
      ),
    ),
    'namespace' => 'App\\Support',
    'name' => 'App\\Support\\StaffRoles',
    'shortName' => 'StaffRoles',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Rôles métier GEVES et matrice de permissions du back-office
 * (fidèle au mockup admin-users). Source unique consommée par le seeder
 * (crée les rôles/permissions spatie, guard « web ») et par la page
 * Utilisateurs (onglets Rôles & Accès). Les libellés/pastilles/catégories
 * alimentent l\'affichage ; les listes de permissions alimentent la matrice.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 12,
    'endLine' => 176,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'PERMISSIONS' => 
      array (
        'declaringClassName' => 'App\\Support\\StaffRoles',
        'implementingClassName' => 'App\\Support\\StaffRoles',
        'name' => 'PERMISSIONS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'view_dashboard\' => \'Tableau de bord\', \'editions_read\' => \'Événements (lecture)\', \'editions_write\' => \'Événements (écriture)\', \'submissions_list\' => \'Soumissions - liste\', \'assign_reviewers\' => \'Assigner relecteurs\', \'evaluate\' => \'Évaluer (relectures)\', \'final_decision\' => \'Décision finale\', \'participants\' => \'Participants\', \'payments\' => \'Paiements\', \'documents\' => \'Documents (attestations…)\', \'formation\' => \'Formations\', \'emails\' => \'E-mails\', \'project\' => \'Projet (gestion)\', \'announcements\' => \'Annonces (déroulement)\', \'claims\' => \'Réclamations & incidents\', \'testimonials\' => \'Témoignages (accueil)\', \'users_roles\' => \'Utilisateurs & rôles\', \'settings\' => \'Réglages\']',
          'attributes' => 
          array (
            'startLine' => 15,
            'endLine' => 34,
            'startTokenPos' => 25,
            'startFilePos' => 548,
            'endTokenPos' => 153,
            'endFilePos' => 1391,
          ),
        ),
        'docComment' => '/** Permissions (clé technique → libellé lisible), dans l\'ordre de la matrice. */',
        'attributes' => 
        array (
        ),
        'startLine' => 15,
        'endLine' => 34,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
      'ROLES' => 
      array (
        'declaringClassName' => 'App\\Support\\StaffRoles',
        'implementingClassName' => 'App\\Support\\StaffRoles',
        'name' => 'ROLES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'administrator\' => [\'label\' => \'Administrateur colloque\', \'category\' => \'Système\', \'pill\' => \'ok\', \'abbr\' => \'Admin\', \'description\' => \'Accès complet : événements, soumissions, décisions, documents, paiements, e-mails, réglages.\', \'permissions\' => [\'view_dashboard\', \'editions_read\', \'editions_write\', \'submissions_list\', \'assign_reviewers\', \'evaluate\', \'final_decision\', \'participants\', \'payments\', \'documents\', \'formation\', \'emails\', \'project\', \'announcements\', \'claims\', \'testimonials\', \'users_roles\', \'settings\']], \'gerant\' => [\'label\' => \'Gestionnaire du colloque\', \'category\' => \'Système\', \'pill\' => \'ok\', \'abbr\' => \'Gestion.\', \'description\' => \'Responsable d’un colloque : accès complet à CE colloque (soumissions, décisions, documents, paiements, e-mails, équipe).\', \'permissions\' => [\'view_dashboard\', \'editions_read\', \'editions_write\', \'submissions_list\', \'assign_reviewers\', \'evaluate\', \'final_decision\', \'participants\', \'payments\', \'documents\', \'formation\', \'emails\', \'project\', \'announcements\', \'claims\']], \'co_gerant\' => [\'label\' => \'Co-gestionnaire du colloque\', \'category\' => \'Système\', \'pill\' => \'ok\', \'abbr\' => \'Co-gest.\', \'description\' => \'Co-responsable d’un colloque : mêmes accès que le gestionnaire sur CE colloque.\', \'permissions\' => [\'view_dashboard\', \'editions_read\', \'editions_write\', \'submissions_list\', \'assign_reviewers\', \'evaluate\', \'final_decision\', \'participants\', \'payments\', \'documents\', \'formation\', \'emails\', \'project\', \'announcements\', \'claims\']], \'scientific\' => [\'label\' => \'Comité scientifique\', \'category\' => \'Système\', \'pill\' => \'review\', \'abbr\' => \'Comité sci.\', \'description\' => \'Synthèse des relectures, décisions d’acceptation / refus / corrections.\', \'permissions\' => [\'view_dashboard\', \'editions_read\', \'submissions_list\', \'assign_reviewers\', \'evaluate\', \'final_decision\']], \'comite_supervision\' => [\'label\' => \'Comité de supervision\', \'category\' => \'Système\', \'pill\' => \'info\', \'abbr\' => \'Comité sup.\', \'description\' => \'Supervision du colloque : vue d’ensemble et suivi (soumissions, participants, paiements), sans décision ni modification.\', \'permissions\' => [\'view_dashboard\', \'editions_read\', \'submissions_list\', \'participants\', \'payments\']], \'comite_organisation\' => [\'label\' => \'Comité d’organisation\', \'category\' => \'Support\', \'pill\' => \'warn\', \'abbr\' => \'Comité org.\', \'description\' => \'Membre du comité d’organisation : suivi des participants, documents et programme du colloque.\', \'permissions\' => [\'view_dashboard\', \'editions_read\', \'submissions_list\', \'participants\', \'documents\', \'emails\', \'project\', \'claims\']], \'reviewer\' => [\'label\' => \'Relecteur\', \'category\' => \'Système\', \'pill\' => \'info\', \'abbr\' => \'Relect.\', \'description\' => \'Évaluation des soumissions assignées, déclaration COI, commentaires et recommandation.\', \'permissions\' => [\'view_dashboard\', \'submissions_list\', \'evaluate\']], \'organizer\' => [\'label\' => \'Organisateur\', \'category\' => \'Système\', \'pill\' => \'warn\', \'abbr\' => \'Organis.\', \'description\' => \'Participants, paiements, e-mails, documents (attestations / badges) - sans décisions scientifiques.\', \'permissions\' => [\'view_dashboard\', \'editions_read\', \'submissions_list\', \'participants\', \'payments\', \'documents\', \'emails\', \'project\']], \'secretariat\' => [\'label\' => \'Secrétariat\', \'category\' => \'Support\', \'pill\' => \'sec\', \'abbr\' => \'Sécr.\', \'description\' => \'Suivi administratif des soumissions et participants, relances, envoi de documents, assistance aux auteurs.\', \'permissions\' => [\'view_dashboard\', \'editions_read\', \'submissions_list\', \'participants\', \'documents\', \'emails\', \'project\', \'announcements\', \'claims\']], \'accounting\' => [\'label\' => \'Comptabilité\', \'category\' => \'Support\', \'pill\' => \'compta\', \'abbr\' => \'Compta\', \'description\' => \'Consultation et suivi des paiements, rapprochements, exports financiers - sans modification des tarifs.\', \'permissions\' => [\'view_dashboard\', \'payments\']], \'support\' => [\'label\' => \'Appui à l’organisation\', \'category\' => \'Support\', \'pill\' => \'appui\', \'abbr\' => \'Appui\', \'description\' => \'Aide logistique (programme, salles, partenaires), consultation des listes participants, documents de session.\', \'permissions\' => [\'view_dashboard\', \'editions_read\', \'participants\', \'documents\', \'project\', \'claims\']], \'reception\' => [\'label\' => \'Accueil\', \'category\' => \'Support\', \'pill\' => \'accueil\', \'abbr\' => \'Accueil\', \'description\' => \'Accueil et orientation des participants le jour J, consultation des listes d’inscrits.\', \'permissions\' => [\'view_dashboard\', \'participants\']]]',
          'attributes' => 
          array (
            'startLine' => 42,
            'endLine' => 125,
            'startTokenPos' => 166,
            'startFilePos' => 1738,
            'endTokenPos' => 1068,
            'endFilePos' => 7260,
          ),
        ),
        'docComment' => '/**
 * Rôles UKC : libellé, catégorie (Système/Support), pastille, description,
 * abréviation de colonne (matrice), et permissions accordées.
 *
 * @var array<string, array{label: string, category: string, pill: string, abbr: string, description: string, permissions: list<string>}>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 42,
        'endLine' => 125,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'label' => 
      array (
        'name' => 'label',
        'parameters' => 
        array (
          'role' => 
          array (
            'name' => 'role',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 128,
            'endLine' => 128,
            'startColumn' => 34,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Libellé lisible d\'un rôle (repli sur le nom brut). */',
        'startLine' => 128,
        'endLine' => 131,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\StaffRoles',
        'implementingClassName' => 'App\\Support\\StaffRoles',
        'currentClassName' => 'App\\Support\\StaffRoles',
        'aliasName' => NULL,
      ),
      'pill' => 
      array (
        'name' => 'pill',
        'parameters' => 
        array (
          'role' => 
          array (
            'name' => 'role',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 134,
            'endLine' => 134,
            'startColumn' => 33,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Pastille d\'un rôle pour l\'UI (repli « muted »). */',
        'startLine' => 134,
        'endLine' => 137,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\StaffRoles',
        'implementingClassName' => 'App\\Support\\StaffRoles',
        'currentClassName' => 'App\\Support\\StaffRoles',
        'aliasName' => NULL,
      ),
      'options' => 
      array (
        'name' => 'options',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Options {name => label} pour les sélecteurs de rôle. */',
        'startLine' => 140,
        'endLine' => 143,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\StaffRoles',
        'implementingClassName' => 'App\\Support\\StaffRoles',
        'currentClassName' => 'App\\Support\\StaffRoles',
        'aliasName' => NULL,
      ),
      'teamRoles' => 
      array (
        'name' => 'teamRoles',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Fonctions assignables à une équipe d\'événement (staff ↔ événement). On exclut :
 *  - `administrator` : capacité globale (transverse à tous les colloques) ;
 *  - `reviewer` : la relecture scientifique passe par le Participant relecteur
 *    (par événement, portail à jeton) - pas par un rôle staff global.
 *
 * @return array<string, string> nom => libellé
 */',
        'startLine' => 153,
        'endLine' => 159,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\StaffRoles',
        'implementingClassName' => 'App\\Support\\StaffRoles',
        'currentClassName' => 'App\\Support\\StaffRoles',
        'aliasName' => NULL,
      ),
      'isTeamRole' => 
      array (
        'name' => 'isTeamRole',
        'parameters' => 
        array (
          'role' => 
          array (
            'name' => 'role',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 162,
            'endLine' => 162,
            'startColumn' => 39,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Vrai si la fonction peut être affectée à une équipe d\'événement. */',
        'startLine' => 162,
        'endLine' => 165,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\StaffRoles',
        'implementingClassName' => 'App\\Support\\StaffRoles',
        'currentClassName' => 'App\\Support\\StaffRoles',
        'aliasName' => NULL,
      ),
      'permissionsFor' => 
      array (
        'name' => 'permissionsFor',
        'parameters' => 
        array (
          'role' => 
          array (
            'name' => 'role',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 172,
            'endLine' => 172,
            'startColumn' => 43,
            'endColumn' => 54,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Permissions accordées par une fonction (vide si inconnue).
 *
 * @return list<string>
 */',
        'startLine' => 172,
        'endLine' => 175,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\StaffRoles',
        'implementingClassName' => 'App\\Support\\StaffRoles',
        'currentClassName' => 'App\\Support\\StaffRoles',
        'aliasName' => NULL,
      ),
    ),
    'traitsData' => 
    array (
      'aliases' => 
      array (
      ),
      'modifiers' => 
      array (
      ),
      'precedences' => 
      array (
      ),
      'hashes' => 
      array (
      ),
    ),
  ),
));