<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Http\Controllers\Public\ArchiveController.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Http\Controllers\Public\ArchiveController
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-5bcc412cb3de258ba92d01c24796e4ef825fdb08667db0ec7f0c88cffb1eb77d',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Http\\Controllers\\Public\\ArchiveController',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Http/Controllers/Public/ArchiveController.php',
      ),
    ),
    'namespace' => 'App\\Http\\Controllers\\Public',
    'name' => 'App\\Http\\Controllers\\Public\\ArchiveController',
    'shortName' => 'ArchiveController',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Archives publiques des colloques : liste des événements passées et détail d\'une
 * événement (thème, présentation, axes, galerie, documents). Vues statiques
 * rendues par contrôleur + Blade (cacheables) ; la galerie/lightbox est du JS
 * client (public.js), alimenté par les médias réels de l\'événement.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 17,
    'endLine' => 72,
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
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Contracts\\View\\View',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Liste des événements passées (phase « Événement passé », cf. §2.2). */',
        'startLine' => 20,
        'endLine' => 31,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Controllers\\Public',
        'declaringClassName' => 'App\\Http\\Controllers\\Public\\ArchiveController',
        'implementingClassName' => 'App\\Http\\Controllers\\Public\\ArchiveController',
        'currentClassName' => 'App\\Http\\Controllers\\Public\\ArchiveController',
        'aliasName' => NULL,
      ),
      'show' => 
      array (
        'name' => 'show',
        'parameters' => 
        array (
          'slug' => 
          array (
            'name' => 'slug',
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
            'startLine' => 34,
            'endLine' => 34,
            'startColumn' => 26,
            'endColumn' => 37,
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
            'name' => 'Illuminate\\Contracts\\View\\View',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Détail d\'un événement passé. */',
        'startLine' => 34,
        'endLine' => 63,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Controllers\\Public',
        'declaringClassName' => 'App\\Http\\Controllers\\Public\\ArchiveController',
        'implementingClassName' => 'App\\Http\\Controllers\\Public\\ArchiveController',
        'currentClassName' => 'App\\Http\\Controllers\\Public\\ArchiveController',
        'aliasName' => NULL,
      ),
      'kind' => 
      array (
        'name' => 'kind',
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
            'startLine' => 66,
            'endLine' => 66,
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
        'docComment' => '/** Nature de la manifestation (heuristique pour le filtre de la liste). */',
        'startLine' => 66,
        'endLine' => 71,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Http\\Controllers\\Public',
        'declaringClassName' => 'App\\Http\\Controllers\\Public\\ArchiveController',
        'implementingClassName' => 'App\\Http\\Controllers\\Public\\ArchiveController',
        'currentClassName' => 'App\\Http\\Controllers\\Public\\ArchiveController',
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