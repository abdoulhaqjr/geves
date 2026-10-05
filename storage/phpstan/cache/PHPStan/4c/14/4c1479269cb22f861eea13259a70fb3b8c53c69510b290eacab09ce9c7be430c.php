<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Http\Controllers\Admin\AttestationExportController.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Http\Controllers\Admin\AttestationExportController
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-bec2dbfb199baa4e409f4abec69750aacbed5bc89f04c9ad0ffaf76c854e5491',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Http\\Controllers\\Admin\\AttestationExportController',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Http/Controllers/Admin/AttestationExportController.php',
      ),
    ),
    'namespace' => 'App\\Http\\Controllers\\Admin',
    'name' => 'App\\Http\\Controllers\\Admin\\AttestationExportController',
    'shortName' => 'AttestationExportController',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * **Sortie papier des attestations** : le lot à imprimer, la liste d\'émargement,
 * et la trace de ce qui a été remis.
 *
 * ⚠️ Sans cela, la délivrance physique revenait à ouvrir les attestations **une
 * par une** à leur adresse de vérification. Pour deux cents communications,
 * c\'est deux cents onglets - le mode papier existait sans le geste qui le rend
 * tenable.
 *
 * ⚠️ Les exports portent sur **les filtres de l\'écran** (voir
 * {@see AttestationQuery}) : on imprime ce qu\'on regarde. Un export qui
 * refiltrerait de son côté produirait un lot différent de la liste, sans rien
 * pour s\'en apercevoir.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 33,
    'endLine' => 145,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'App\\Http\\Controllers\\Controller',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'LOT' => 
      array (
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\AttestationExportController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\AttestationExportController',
        'name' => 'LOT',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '50',
          'attributes' => 
          array (
            'startLine' => 43,
            'endLine' => 43,
            'startTokenPos' => 94,
            'startFilePos' => 1599,
            'endTokenPos' => 94,
            'endFilePos' => 1600,
          ),
        ),
        'docComment' => '/**
 * Attestations par fichier.
 *
 * ⚠️ Un plafond, et il est volontaire : chaque attestation est une page
 * composée élément par élément, et un millier d\'un coup épuiserait la mémoire
 * du générateur **au milieu** du rendu - on obtiendrait une page blanche après
 * dix minutes d\'attente. Le lot borné échoue vite ou réussit vite.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 43,
        'endLine' => 43,
        'startColumn' => 5,
        'endColumn' => 26,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'pdf' => 
      array (
        'name' => 'pdf',
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
            'startLine' => 46,
            'endLine' => 46,
            'startColumn' => 25,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
          'data' => 
          array (
            'types' => 
            array (
              0 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'Symfony\\Component\\HttpFoundation\\Response',
                  'isIdentifier' => false,
                ),
              ),
              1 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'Illuminate\\Http\\RedirectResponse',
                  'isIdentifier' => false,
                ),
              ),
            ),
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Lot d\'attestations prêt à imprimer, une par page. */',
        'startLine' => 46,
        'endLine' => 93,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Controllers\\Admin',
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\AttestationExportController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\AttestationExportController',
        'currentClassName' => 'App\\Http\\Controllers\\Admin\\AttestationExportController',
        'aliasName' => NULL,
      ),
      'emargement' => 
      array (
        'name' => 'emargement',
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
            'startLine' => 102,
            'endLine' => 102,
            'startColumn' => 32,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
          'data' => 
          array (
            'types' => 
            array (
              0 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'Symfony\\Component\\HttpFoundation\\Response',
                  'isIdentifier' => false,
                ),
              ),
              1 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'Illuminate\\Http\\RedirectResponse',
                  'isIdentifier' => false,
                ),
              ),
            ),
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Liste d\'émargement : le pendant papier de l\'accusé de réception.
 *
 * ⚠️ Toutes les lignes du filtre, sans découpage en lots : c\'est un tableau,
 * pas une page par personne - et une liste coupée en morceaux se perd. Elle
 * sert au guichet, en face du lot imprimé.
 */',
        'startLine' => 102,
        'endLine' => 125,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Controllers\\Admin',
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\AttestationExportController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\AttestationExportController',
        'currentClassName' => 'App\\Http\\Controllers\\Admin\\AttestationExportController',
        'aliasName' => NULL,
      ),
      'deliver' => 
      array (
        'name' => 'deliver',
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
            'startLine' => 134,
            'endLine' => 134,
            'startColumn' => 29,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'attestation' => 
          array (
            'name' => 'attestation',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Attestation',
                'isIdentifier' => false,
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
            'startColumn' => 47,
            'endColumn' => 70,
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
        'docComment' => '/**
 * Pose ou retire la remise en main propre.
 *
 * ⚠️ Réversible, contrairement au passage d\'une communication : se tromper de
 * ligne au guichet est banal, et une remise fausse ferait croire que
 * quelqu\'un a son document alors qu\'il attend encore.
 */',
        'startLine' => 134,
        'endLine' => 144,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Controllers\\Admin',
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\AttestationExportController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\AttestationExportController',
        'currentClassName' => 'App\\Http\\Controllers\\Admin\\AttestationExportController',
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