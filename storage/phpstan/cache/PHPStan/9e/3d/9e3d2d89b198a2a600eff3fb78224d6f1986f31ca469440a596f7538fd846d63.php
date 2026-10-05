<?php declare(strict_types = 1);

// osfsl-C:/Users/TOURE/Herd/gesco/vendor/composer/../laravel/framework/src/Illuminate/Cache/Console/PruneStaleTagsCommand.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Cache\Console\PruneStaleTagsCommand
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-30c2e0b5b9351d9a59362a2cf87f796d0535fd35528c35134f4b97b5da72e307-8.4.24-6.70.0.3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Cache\\Console\\PruneStaleTagsCommand',
        'filename' => 'C:/Users/TOURE/Herd/gesco/vendor/composer/../laravel/framework/src/Illuminate/Cache/Console/PruneStaleTagsCommand.php',
      ),
    ),
    'namespace' => 'Illuminate\\Cache\\Console',
    'name' => 'Illuminate\\Cache\\Console\\PruneStaleTagsCommand',
    'shortName' => 'PruneStaleTagsCommand',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
      0 => 
      array (
        'name' => 'Symfony\\Component\\Console\\Attribute\\AsCommand',
        'isRepeated' => false,
        'arguments' => 
        array (
          'name' => 
          array (
            'code' => '\'cache:prune-stale-tags\'',
            'attributes' => 
            array (
              'startLine' => 9,
              'endLine' => 9,
              'startTokenPos' => 28,
              'startFilePos' => 181,
              'endTokenPos' => 28,
              'endFilePos' => 204,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 9,
    'endLine' => 42,
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
        'declaringClassName' => 'Illuminate\\Cache\\Console\\PruneStaleTagsCommand',
        'implementingClassName' => 'Illuminate\\Cache\\Console\\PruneStaleTagsCommand',
        'name' => 'signature',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'cache:prune-stale-tags {store? : The name of the store you would like to prune tags from}\'',
          'attributes' => 
          array (
            'startLine' => 17,
            'endLine' => 17,
            'startTokenPos' => 50,
            'startFilePos' => 377,
            'endTokenPos' => 50,
            'endFilePos' => 467,
          ),
        ),
        'docComment' => '/**
 * The name and signature of the console command.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 17,
        'endLine' => 17,
        'startColumn' => 5,
        'endColumn' => 119,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'description' => 
      array (
        'declaringClassName' => 'Illuminate\\Cache\\Console\\PruneStaleTagsCommand',
        'implementingClassName' => 'Illuminate\\Cache\\Console\\PruneStaleTagsCommand',
        'name' => 'description',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'Prune stale cache tags from the cache (Redis only)\'',
          'attributes' => 
          array (
            'startLine' => 24,
            'endLine' => 24,
            'startTokenPos' => 61,
            'startFilePos' => 582,
            'endTokenPos' => 61,
            'endFilePos' => 633,
          ),
        ),
        'docComment' => '/**
 * The console command description.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 24,
        'endLine' => 24,
        'startColumn' => 5,
        'endColumn' => 82,
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
          'cache' => 
          array (
            'name' => 'cache',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Cache\\CacheManager',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 32,
            'endLine' => 32,
            'startColumn' => 28,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Execute the console command.
 *
 * @param  \\Illuminate\\Cache\\CacheManager  $cache
 * @return int|null
 */',
        'startLine' => 32,
        'endLine' => 41,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Cache\\Console',
        'declaringClassName' => 'Illuminate\\Cache\\Console\\PruneStaleTagsCommand',
        'implementingClassName' => 'Illuminate\\Cache\\Console\\PruneStaleTagsCommand',
        'currentClassName' => 'Illuminate\\Cache\\Console\\PruneStaleTagsCommand',
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