<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Http\Controllers\Admin\SecretariatController.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Http\Controllers\Admin\SecretariatController
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-0ecdb827ad2ed3ecd63da9b34330a589c8500229d5627e7b5063745e5f5878fa',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Http\\Controllers\\Admin\\SecretariatController',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Http/Controllers/Admin/SecretariatController.php',
      ),
    ),
    'namespace' => 'App\\Http\\Controllers\\Admin',
    'name' => 'App\\Http\\Controllers\\Admin\\SecretariatController',
    'shortName' => 'SecretariatController',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Poste de travail du secrétariat (back-office, Blade) : file des rapports de
 * séance à valider, séances en attente de rédaction, rapports validés (onglet
 * « Rapports de séance ») ET un « Secrétariat assisté » qui génère des documents
 * (listes de présence, feuilles d\'émargement, affiches directionnelles, ordre du
 * jour…) par prompt ou modèle, via l\'IA, ancrés sur les données réelles de
 * l\'événement. Scopé aux événements accessibles à l\'utilisateur.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 29,
    'endLine' => 459,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'App\\Http\\Controllers\\Controller',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
      0 => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
    ),
    'immediateConstants' => 
    array (
      'PRESETS' => 
      array (
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\SecretariatController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\SecretariatController',
        'name' => 'PRESETS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'presence\' => [\'label\' => \'Liste de présence\', \'format\' => \'xlsx\', \'dataset\' => \'presence\', \'prompt\' => \'Génère une liste de présence des participants de l’événement, avec un en-tête au nom du colloque, et des colonnes : Nom, Institution, Catégorie, Émargement (case à signer).\'], \'emargement\' => [\'label\' => \'Feuille d’émargement\', \'format\' => \'pdf\', \'dataset\' => \'presence\', \'prompt\' => \'Génère une feuille d’émargement imprimable (en-tête au nom du colloque, date et séance à compléter) avec les colonnes Nom, Institution, Signature.\'], \'affiche\' => [\'label\' => \'Affiche directionnelle\', \'format\' => \'pdf\', \'dataset\' => \'none\', \'prompt\' => \'Rédige le contenu d’affiches directionnelles pour orienter les participants vers les salles et ateliers, à partir du programme réel (une affiche par direction, avec de gros intitulés « Atelier → Salle »).\'], \'ordre_jour\' => [\'label\' => \'Ordre du jour\', \'format\' => \'pdf\', \'dataset\' => \'none\', \'prompt\' => \'Rédige l’ordre du jour d’une journée du colloque à partir du programme réel : séances, horaires, salles, modérateurs et rapporteurs.\'], \'chevalets\' => [\'label\' => \'Chevalets nominatifs\', \'format\' => \'docx\', \'dataset\' => \'none\', \'prompt\' => \'Génère des chevalets nominatifs (nom et fonction) pour les modérateurs et rapporteurs des séances, un par carte, à partir du programme réel.\']]',
          'attributes' => 
          array (
            'startLine' => 39,
            'endLine' => 65,
            'startTokenPos' => 109,
            'startFilePos' => 1419,
            'endTokenPos' => 296,
            'endFilePos' => 3085,
          ),
        ),
        'docComment' => '/**
 * Modèles de documents proposés au secrétariat. Chaque preset pré-remplit le
 * prompt, le format de sortie et le jeu de données joint.
 *
 * @var array<string, array{label: string, format: string, dataset: string, prompt: string}>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 39,
        'endLine' => 65,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
      'PRINT_TYPES' => 
      array (
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\SecretariatController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\SecretariatController',
        'name' => 'PRINT_TYPES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'presence\' => [\'label\' => \'Liste de présence\', \'icon\' => \'users\', \'hint\' => \'Participants de l’événement, avec colonne d’émargement.\'], \'emargement\' => [\'label\' => \'Feuille d’émargement\', \'icon\' => \'pen\', \'hint\' => \'Feuille à signer, en-tête séance/date à compléter.\'], \'affiche\' => [\'label\' => \'Affiche directionnelle\', \'icon\' => \'pin\', \'hint\' => \'Panneaux « Atelier → Salle » par salle, en gros.\'], \'ordre_jour\' => [\'label\' => \'Ordre du jour\', \'icon\' => \'calendar\', \'hint\' => \'Déroulé d’une journée : séances, horaires, salles, rôles.\'], \'chevalets\' => [\'label\' => \'Chevalets nominatifs\', \'icon\' => \'card\', \'hint\' => \'Cartes pliables nom + fonction (modérateurs, rapporteurs).\']]',
          'attributes' => 
          array (
            'startLine' => 74,
            'endLine' => 80,
            'startTokenPos' => 309,
            'startFilePos' => 3444,
            'endTokenPos' => 446,
            'endFilePos' => 4200,
          ),
        ),
        'docComment' => '/**
 * Documents logistiques standard de l\'onglet « Documents », rendus en pages
 * imprimables à partir des données réelles (sans IA). Chaque type a sa vue
 * `secretariat/print/{type}` et quelques paramètres.
 *
 * @var array<string, array{label: string, icon: string, hint: string}>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 74,
        'endLine' => 80,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'index' => 
      array (
        'name' => 'index',
        'parameters' => 
        array (
          'request' => 
          array (
            'name' => 'request',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Http\\Request',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 82,
            'endLine' => 82,
            'startColumn' => 27,
            'endColumn' => 42,
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
            'name' => 'Illuminate\\View\\View',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 82,
        'endLine' => 140,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Controllers\\Admin',
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\SecretariatController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\SecretariatController',
        'currentClassName' => 'App\\Http\\Controllers\\Admin\\SecretariatController',
        'aliasName' => NULL,
      ),
      'assistIndex' => 
      array (
        'name' => 'assistIndex',
        'parameters' => 
        array (
          'request' => 
          array (
            'name' => 'request',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Http\\Request',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 146,
            'endLine' => 146,
            'startColumn' => 33,
            'endColumn' => 48,
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
            'name' => 'Illuminate\\View\\View',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Onglet « Secrétariat assisté » (IA) : écran de génération de documents sur
 * mesure à partir d\'un prompt ou d\'un modèle joint.
 */',
        'startLine' => 146,
        'endLine' => 157,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Controllers\\Admin',
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\SecretariatController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\SecretariatController',
        'currentClassName' => 'App\\Http\\Controllers\\Admin\\SecretariatController',
        'aliasName' => NULL,
      ),
      'documents' => 
      array (
        'name' => 'documents',
        'parameters' => 
        array (
          'request' => 
          array (
            'name' => 'request',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Http\\Request',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 165,
            'endLine' => 165,
            'startColumn' => 31,
            'endColumn' => 46,
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
            'name' => 'Illuminate\\View\\View',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Onglet « Documents » : documents logistiques standard (liste de présence,
 * feuille d\'émargement, affiche directionnelle, ordre du jour, chevalets),
 * remplis à partir des données réelles de l\'événement et imprimables - sans
 * IA, en renseignant quelques paramètres (cf. {@see printDocument()}).
 */',
        'startLine' => 165,
        'endLine' => 185,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Controllers\\Admin',
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\SecretariatController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\SecretariatController',
        'currentClassName' => 'App\\Http\\Controllers\\Admin\\SecretariatController',
        'aliasName' => NULL,
      ),
      'printDocument' => 
      array (
        'name' => 'printDocument',
        'parameters' => 
        array (
          'request' => 
          array (
            'name' => 'request',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Http\\Request',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 192,
            'endLine' => 192,
            'startColumn' => 35,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'event' => 
          array (
            'name' => 'event',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Event',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 192,
            'endLine' => 192,
            'startColumn' => 53,
            'endColumn' => 64,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'type' => 
          array (
            'name' => 'type',
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
            'startLine' => 192,
            'endLine' => 192,
            'startColumn' => 67,
            'endColumn' => 78,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\View\\View',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Rendu imprimable d\'un document logistique standard (nouvel onglet). Les
 * paramètres (séance, jour, portée…) arrivent en query ; les données viennent
 * de l\'événement réel. La vue est autonome et déclenche l\'impression.
 */',
        'startLine' => 192,
        'endLine' => 201,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Controllers\\Admin',
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\SecretariatController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\SecretariatController',
        'currentClassName' => 'App\\Http\\Controllers\\Admin\\SecretariatController',
        'aliasName' => NULL,
      ),
      'reports' => 
      array (
        'name' => 'reports',
        'parameters' => 
        array (
          'request' => 
          array (
            'name' => 'request',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Http\\Request',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 212,
            'endLine' => 212,
            'startColumn' => 29,
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
            'name' => 'Illuminate\\View\\View',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Rapports de séance d\'un événement : lecture et amélioration par le
 * secrétariat, avant validation.
 *
 * ⚠️ Les rapports relèvent du secrétariat et de lui seul - le module
 * Programme n\'en porte plus de copie : deux écrans pour le même objet, c\'est
 * deux endroits où chercher et un risque d\'états divergents.
 */',
        'startLine' => 212,
        'endLine' => 227,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Controllers\\Admin',
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\SecretariatController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\SecretariatController',
        'currentClassName' => 'App\\Http\\Controllers\\Admin\\SecretariatController',
        'aliasName' => NULL,
      ),
      'generate' => 
      array (
        'name' => 'generate',
        'parameters' => 
        array (
          'request' => 
          array (
            'name' => 'request',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Http\\Request',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 229,
            'endLine' => 229,
            'startColumn' => 30,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'action' => 
          array (
            'name' => 'action',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Actions\\GenerateReport',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 229,
            'endLine' => 229,
            'startColumn' => 48,
            'endColumn' => 69,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Http\\RedirectResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 229,
        'endLine' => 289,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Controllers\\Admin',
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\SecretariatController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\SecretariatController',
        'currentClassName' => 'App\\Http\\Controllers\\Admin\\SecretariatController',
        'aliasName' => NULL,
      ),
      'download' => 
      array (
        'name' => 'download',
        'parameters' => 
        array (
          'request' => 
          array (
            'name' => 'request',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Http\\Request',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 292,
            'endLine' => 292,
            'startColumn' => 30,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'event' => 
          array (
            'name' => 'event',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Event',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 292,
            'endLine' => 292,
            'startColumn' => 48,
            'endColumn' => 59,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'id' => 
          array (
            'name' => 'id',
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
            'startLine' => 292,
            'endLine' => 292,
            'startColumn' => 62,
            'endColumn' => 71,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Symfony\\Component\\HttpFoundation\\Response',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Télécharge un document généré, dans son format d\'origine. */',
        'startLine' => 292,
        'endLine' => 351,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Controllers\\Admin',
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\SecretariatController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\SecretariatController',
        'currentClassName' => 'App\\Http\\Controllers\\Admin\\SecretariatController',
        'aliasName' => NULL,
      ),
      'pushHistory' => 
      array (
        'name' => 'pushHistory',
        'parameters' => 
        array (
          'entry' => 
          array (
            'name' => 'entry',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 354,
            'endLine' => 354,
            'startColumn' => 36,
            'endColumn' => 47,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Ajoute une entrée en tête de l\'historique (max 30 conservées). */',
        'startLine' => 354,
        'endLine' => 359,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Http\\Controllers\\Admin',
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\SecretariatController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\SecretariatController',
        'currentClassName' => 'App\\Http\\Controllers\\Admin\\SecretariatController',
        'aliasName' => NULL,
      ),
      'sessionDays' => 
      array (
        'name' => 'sessionDays',
        'parameters' => 
        array (
          'sessions' => 
          array (
            'name' => 'sessions',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Support\\Collection',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 367,
            'endLine' => 367,
            'startColumn' => 36,
            'endColumn' => 55,
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
            'name' => 'Illuminate\\Support\\Collection',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Jours distincts (dates ISO) couverts par les séances.
 *
 * @param  Collection<int, EventSession>  $sessions
 * @return Collection<int, string>
 */',
        'startLine' => 367,
        'endLine' => 373,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Http\\Controllers\\Admin',
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\SecretariatController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\SecretariatController',
        'currentClassName' => 'App\\Http\\Controllers\\Admin\\SecretariatController',
        'aliasName' => NULL,
      ),
      'printData' => 
      array (
        'name' => 'printData',
        'parameters' => 
        array (
          'request' => 
          array (
            'name' => 'request',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Http\\Request',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 380,
            'endLine' => 380,
            'startColumn' => 34,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'event' => 
          array (
            'name' => 'event',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Event',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 380,
            'endLine' => 380,
            'startColumn' => 52,
            'endColumn' => 63,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'type' => 
          array (
            'name' => 'type',
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
            'startLine' => 380,
            'endLine' => 380,
            'startColumn' => 66,
            'endColumn' => 77,
            'parameterIndex' => 2,
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
 * Données d\'un document imprimable selon son type (sans IA, données réelles).
 *
 * @return array<string, mixed>
 */',
        'startLine' => 380,
        'endLine' => 437,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Http\\Controllers\\Admin',
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\SecretariatController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\SecretariatController',
        'currentClassName' => 'App\\Http\\Controllers\\Admin\\SecretariatController',
        'aliasName' => NULL,
      ),
      'programContext' => 
      array (
        'name' => 'programContext',
        'parameters' => 
        array (
          'event' => 
          array (
            'name' => 'event',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Event',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 440,
            'endLine' => 440,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Résumé texte du programme réel de l\'événement (pour ancrer les documents). */',
        'startLine' => 440,
        'endLine' => 458,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Http\\Controllers\\Admin',
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\SecretariatController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\SecretariatController',
        'currentClassName' => 'App\\Http\\Controllers\\Admin\\SecretariatController',
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