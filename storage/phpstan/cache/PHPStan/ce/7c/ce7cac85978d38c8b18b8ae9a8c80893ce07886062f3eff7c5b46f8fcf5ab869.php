<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Http\Controllers\Admin\ImpersonationController.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Http\Controllers\Admin\ImpersonationController
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-3f9883321760079cea25c75a857f0925d918c1fea92fc15a3471182d0a285f36',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Http\\Controllers\\Admin\\ImpersonationController',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Http/Controllers/Admin/ImpersonationController.php',
      ),
    ),
    'namespace' => 'App\\Http\\Controllers\\Admin',
    'name' => 'App\\Http\\Controllers\\Admin\\ImpersonationController',
    'shortName' => 'ImpersonationController',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Impersonation d\'un compte participant par le staff (assistance) : « se
 * connecter en tant que ». Cross-guard : le staff (web) devient le compte
 * (account) via lab404/laravel-impersonate. Retour par « quitter ».
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 15,
    'endLine' => 51,
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
      'take' => 
      array (
        'name' => 'take',
        'parameters' => 
        array (
          'participant' => 
          array (
            'name' => 'participant',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Participant',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 18,
            'endLine' => 18,
            'startColumn' => 26,
            'endColumn' => 49,
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
            'name' => 'Illuminate\\Http\\RedirectResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Démarre l\'impersonation du compte lié au participant. */',
        'startLine' => 18,
        'endLine' => 34,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Controllers\\Admin',
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\ImpersonationController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\ImpersonationController',
        'currentClassName' => 'App\\Http\\Controllers\\Admin\\ImpersonationController',
        'aliasName' => NULL,
      ),
      'leave' => 
      array (
        'name' => 'leave',
        'parameters' => 
        array (
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
        'docComment' => '/** Quitte l\'impersonation et rend la main au staff. */',
        'startLine' => 37,
        'endLine' => 50,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Controllers\\Admin',
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\ImpersonationController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\ImpersonationController',
        'currentClassName' => 'App\\Http\\Controllers\\Admin\\ImpersonationController',
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