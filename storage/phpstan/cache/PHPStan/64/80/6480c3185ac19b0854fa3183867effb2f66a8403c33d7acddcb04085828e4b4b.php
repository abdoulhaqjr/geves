<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Console\Commands\CheckDuplicatePhones.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Console\Commands\CheckDuplicatePhones
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-ec0146cb9589dbc392f540f32efe8a777247e969835a139acb2525c34c94008a',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Console\\Commands\\CheckDuplicatePhones',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Console/Commands/CheckDuplicatePhones.php',
      ),
    ),
    'namespace' => 'App\\Console\\Commands',
    'name' => 'App\\Console\\Commands\\CheckDuplicatePhones',
    'shortName' => 'CheckDuplicatePhones',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Rapport des numéros de téléphone partagés par plusieurs comptes.
 *
 * L\'index unique empêche désormais d\'en créer, mais cette commande reste la
 * pièce à consulter avant une reprise de données (import, fusion de comptes) :
 * elle ne modifie rien et signale aussi les numéros mal formés, qui échappent à
 * l\'unicité tant qu\'ils ne sont pas normalisés.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 18,
    'endLine' => 56,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Console\\Command',
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
      'signature' => 
      array (
        'declaringClassName' => 'App\\Console\\Commands\\CheckDuplicatePhones',
        'implementingClassName' => 'App\\Console\\Commands\\CheckDuplicatePhones',
        'name' => 'signature',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'users:phones {--fix : normalise la mise en forme des numéros (ne touche pas aux doublons)}\'',
          'attributes' => 
          array (
            'startLine' => 20,
            'endLine' => 20,
            'startTokenPos' => 45,
            'startFilePos' => 606,
            'endTokenPos' => 45,
            'endFilePos' => 698,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 20,
        'endLine' => 20,
        'startColumn' => 5,
        'endColumn' => 121,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'description' => 
      array (
        'declaringClassName' => 'App\\Console\\Commands\\CheckDuplicatePhones',
        'implementingClassName' => 'App\\Console\\Commands\\CheckDuplicatePhones',
        'name' => 'description',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'Signale les téléphones partagés par plusieurs comptes et les numéros non normalisés\'',
          'attributes' => 
          array (
            'startLine' => 22,
            'endLine' => 22,
            'startTokenPos' => 54,
            'startFilePos' => 731,
            'endTokenPos' => 54,
            'endFilePos' => 820,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 22,
        'endLine' => 22,
        'startColumn' => 5,
        'endColumn' => 120,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
    ),
    'immediateMethods' => 
    array (
      'handle' => 
      array (
        'name' => 'handle',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 24,
        'endLine' => 55,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Console\\Commands',
        'declaringClassName' => 'App\\Console\\Commands\\CheckDuplicatePhones',
        'implementingClassName' => 'App\\Console\\Commands\\CheckDuplicatePhones',
        'currentClassName' => 'App\\Console\\Commands\\CheckDuplicatePhones',
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