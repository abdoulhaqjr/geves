<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Http\Controllers\Admin\Concerns\ScopesEditionAccess.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Http\Controllers\Admin\Concerns\ScopesEditionAccess
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-bb97f8926e83bf7c5201fd6bbf6d98eb83767a0b87a4ba1e8b79b8c25934c56a',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Http/Controllers/Admin/Concerns/ScopesEditionAccess.php',
      ),
    ),
    'namespace' => 'App\\Http\\Controllers\\Admin\\Concerns',
    'name' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
    'shortName' => 'ScopesEditionAccess',
    'isInterface' => false,
    'isTrait' => true,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Étanchéité par événement côté contrôleur (complément du middleware
 * {@see EnsureEditionAccess}). Restreint les listes et les
 * sélecteurs d\'événement aux événements accessibles au membre du staff - sauf
 * super-administrateur, qui voit tout.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 16,
    'endLine' => 68,
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
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'scopedEditionIds' => 
      array (
        'name' => 'scopedEditionIds',
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
            'startLine' => 24,
            'endLine' => 24,
            'startColumn' => 41,
            'endColumn' => 56,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'capability' => 
          array (
            'name' => 'capability',
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
            'startLine' => 24,
            'endLine' => 24,
            'startColumn' => 59,
            'endColumn' => 76,
            'parameterIndex' => 1,
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
                  'name' => 'array',
                  'isIdentifier' => true,
                ),
              ),
              1 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'null',
                  'isIdentifier' => true,
                ),
              ),
            ),
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Identifiants d\'événements à imposer dans les requêtes de liste, ou null quand
 * l\'utilisateur est super-admin (aucune restriction).
 *
 * @return list<int>|null
 */',
        'startLine' => 24,
        'endLine' => 33,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Http\\Controllers\\Admin\\Concerns',
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
        'currentClassName' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
        'aliasName' => NULL,
      ),
      'accessibleEditions' => 
      array (
        'name' => 'accessibleEditions',
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
            'startLine' => 41,
            'endLine' => 41,
            'startColumn' => 43,
            'endColumn' => 58,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'capability' => 
          array (
            'name' => 'capability',
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
            'startLine' => 41,
            'endLine' => 41,
            'startColumn' => 61,
            'endColumn' => 78,
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
            'name' => 'Illuminate\\Support\\Collection',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Événements à proposer dans un sélecteur (toutes pour un super-admin, sinon les
 * événements accessibles pour la capacité donnée).
 *
 * @return Collection<int, Event>
 */',
        'startLine' => 41,
        'endLine' => 52,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Http\\Controllers\\Admin\\Concerns',
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
        'currentClassName' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
        'aliasName' => NULL,
      ),
      'currentEditionId' => 
      array (
        'name' => 'currentEditionId',
        'parameters' => 
        array (
          'events' => 
          array (
            'name' => 'events',
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
            'startLine' => 60,
            'endLine' => 60,
            'startColumn' => 41,
            'endColumn' => 58,
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
        'docComment' => '/**
 * Identifiant de l\'événement proposée par défaut dans un sélecteur : l\'événement
 * ouverte, sinon la plus récente accessible. Vide si aucune n\'est accessible.
 *
 * @param  Collection<int, Event>  $events
 */',
        'startLine' => 60,
        'endLine' => 67,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Http\\Controllers\\Admin\\Concerns',
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
        'currentClassName' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
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