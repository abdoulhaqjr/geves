<?php declare(strict_types = 1);

// osfsl-C:/Users/TOURE/Herd/gesco/vendor/composer/../lab404/laravel-impersonate/src/Models/Impersonate.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Lab404\Impersonate\Models\Impersonate
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-f40e2e2b476f4c11e254a5eeafc1781644c6b8c8966070028ba0ba99f409cc2c-8.4.24-6.70.0.3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Lab404\\Impersonate\\Models\\Impersonate',
        'filename' => 'C:/Users/TOURE/Herd/gesco/vendor/composer/../lab404/laravel-impersonate/src/Models/Impersonate.php',
      ),
    ),
    'namespace' => 'Lab404\\Impersonate\\Models',
    'name' => 'Lab404\\Impersonate\\Models\\Impersonate',
    'shortName' => 'Impersonate',
    'isInterface' => false,
    'isTrait' => true,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 8,
    'endLine' => 67,
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
      'canImpersonate' => 
      array (
        'name' => 'canImpersonate',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Return true or false if the user can impersonate an other user.
 *
 * @param void
 * @return  bool
 */',
        'startLine' => 16,
        'endLine' => 19,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Lab404\\Impersonate\\Models',
        'declaringClassName' => 'Lab404\\Impersonate\\Models\\Impersonate',
        'implementingClassName' => 'Lab404\\Impersonate\\Models\\Impersonate',
        'currentClassName' => 'Lab404\\Impersonate\\Models\\Impersonate',
        'aliasName' => NULL,
      ),
      'canBeImpersonated' => 
      array (
        'name' => 'canBeImpersonated',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Return true or false if the user can be impersonate.
 *
 * @param void
 * @return  bool
 */',
        'startLine' => 27,
        'endLine' => 30,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Lab404\\Impersonate\\Models',
        'declaringClassName' => 'Lab404\\Impersonate\\Models\\Impersonate',
        'implementingClassName' => 'Lab404\\Impersonate\\Models\\Impersonate',
        'currentClassName' => 'Lab404\\Impersonate\\Models\\Impersonate',
        'aliasName' => NULL,
      ),
      'impersonate' => 
      array (
        'name' => 'impersonate',
        'parameters' => 
        array (
          'user' => 
          array (
            'name' => 'user',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Database\\Eloquent\\Model',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 39,
            'endLine' => 39,
            'startColumn' => 33,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'guardName' => 
          array (
            'name' => 'guardName',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 39,
                'endLine' => 39,
                'startTokenPos' => 78,
                'startFilePos' => 766,
                'endTokenPos' => 78,
                'endFilePos' => 769,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 39,
            'endLine' => 39,
            'startColumn' => 46,
            'endColumn' => 62,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Impersonate the given user.
 *
 * @param Model       $user
 * @param string|null $guardName
 * @return  bool
 */',
        'startLine' => 39,
        'endLine' => 42,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Lab404\\Impersonate\\Models',
        'declaringClassName' => 'Lab404\\Impersonate\\Models\\Impersonate',
        'implementingClassName' => 'Lab404\\Impersonate\\Models\\Impersonate',
        'currentClassName' => 'Lab404\\Impersonate\\Models\\Impersonate',
        'aliasName' => NULL,
      ),
      'isImpersonated' => 
      array (
        'name' => 'isImpersonated',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Check if the current user is impersonated.
 *
 * @param void
 * @return  bool
 */',
        'startLine' => 50,
        'endLine' => 53,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Lab404\\Impersonate\\Models',
        'declaringClassName' => 'Lab404\\Impersonate\\Models\\Impersonate',
        'implementingClassName' => 'Lab404\\Impersonate\\Models\\Impersonate',
        'currentClassName' => 'Lab404\\Impersonate\\Models\\Impersonate',
        'aliasName' => NULL,
      ),
      'leaveImpersonation' => 
      array (
        'name' => 'leaveImpersonation',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Leave the current impersonation.
 *
 * @param void
 * @return  bool
 */',
        'startLine' => 61,
        'endLine' => 66,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Lab404\\Impersonate\\Models',
        'declaringClassName' => 'Lab404\\Impersonate\\Models\\Impersonate',
        'implementingClassName' => 'Lab404\\Impersonate\\Models\\Impersonate',
        'currentClassName' => 'Lab404\\Impersonate\\Models\\Impersonate',
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