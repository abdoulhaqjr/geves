<?php declare(strict_types = 1);

// osfsl-C:/Users/TOURE/Herd/gesco/vendor/composer/../laravel/framework/src/Illuminate/Cache/Console/ClearCommand.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Cache\Console\ClearCommand
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-46e859caa9320989608c4e4ae28e46ca9efe6eeede5579f0c64a5e68c7000ca0-8.4.24-6.70.0.3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'filename' => 'C:/Users/TOURE/Herd/gesco/vendor/composer/../laravel/framework/src/Illuminate/Cache/Console/ClearCommand.php',
      ),
    ),
    'namespace' => 'Illuminate\\Cache\\Console',
    'name' => 'Illuminate\\Cache\\Console\\ClearCommand',
    'shortName' => 'ClearCommand',
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
            'code' => '\'cache:clear\'',
            'attributes' => 
            array (
              'startLine' => 12,
              'endLine' => 12,
              'startTokenPos' => 43,
              'startFilePos' => 284,
              'endTokenPos' => 43,
              'endFilePos' => 296,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 12,
    'endLine' => 171,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Console\\Command',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
      0 => 'Illuminate\\Console\\Prohibitable',
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'signature' => 
      array (
        'declaringClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'implementingClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'name' => 'signature',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'cache:clear
                    {store? : The name of the store you would like to clear}
                    {--tags= : The cache tags you would like to clear}
                    {--locks : Only clear cache locks}\'',
          'attributes' => 
          array (
            'startLine' => 22,
            'endLine' => 25,
            'startTokenPos' => 70,
            'startFilePos' => 483,
            'endTokenPos' => 70,
            'endFilePos' => 698,
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
        'startLine' => 22,
        'endLine' => 25,
        'startColumn' => 5,
        'endColumn' => 56,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'description' => 
      array (
        'declaringClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'implementingClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'name' => 'description',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'Flush the application cache\'',
          'attributes' => 
          array (
            'startLine' => 32,
            'endLine' => 32,
            'startTokenPos' => 81,
            'startFilePos' => 813,
            'endTokenPos' => 81,
            'endFilePos' => 841,
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
        'startLine' => 32,
        'endLine' => 32,
        'startColumn' => 5,
        'endColumn' => 59,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'cache' => 
      array (
        'declaringClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'implementingClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'name' => 'cache',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The cache manager instance.
 *
 * @var \\Illuminate\\Cache\\CacheManager
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 39,
        'endLine' => 39,
        'startColumn' => 5,
        'endColumn' => 21,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'files' => 
      array (
        'declaringClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'implementingClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'name' => 'files',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The filesystem instance.
 *
 * @var \\Illuminate\\Filesystem\\Filesystem
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 46,
        'endLine' => 46,
        'startColumn' => 5,
        'endColumn' => 21,
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
      '__construct' => 
      array (
        'name' => '__construct',
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
            'startLine' => 54,
            'endLine' => 54,
            'startColumn' => 33,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'files' => 
          array (
            'name' => 'files',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Filesystem\\Filesystem',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 54,
            'endLine' => 54,
            'startColumn' => 54,
            'endColumn' => 70,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Create a new cache clear command instance.
 *
 * @param  \\Illuminate\\Cache\\CacheManager  $cache
 * @param  \\Illuminate\\Filesystem\\Filesystem  $files
 */',
        'startLine' => 54,
        'endLine' => 60,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Cache\\Console',
        'declaringClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'implementingClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'currentClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'aliasName' => NULL,
      ),
      'handle' => 
      array (
        'name' => 'handle',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Execute the console command.
 *
 * @return int
 */',
        'startLine' => 67,
        'endLine' => 98,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Cache\\Console',
        'declaringClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'implementingClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'currentClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'aliasName' => NULL,
      ),
      'clearLocks' => 
      array (
        'name' => 'clearLocks',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Clear all locks from the cache store.
 *
 * @return int
 */',
        'startLine' => 105,
        'endLine' => 130,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Cache\\Console',
        'declaringClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'implementingClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'currentClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'aliasName' => NULL,
      ),
      'flushFacades' => 
      array (
        'name' => 'flushFacades',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Flush the real-time facades stored in the cache directory.
 *
 * @return void
 */',
        'startLine' => 137,
        'endLine' => 148,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Cache\\Console',
        'declaringClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'implementingClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'currentClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'aliasName' => NULL,
      ),
      'cache' => 
      array (
        'name' => 'cache',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the cache instance for the command.
 *
 * @return \\Illuminate\\Cache\\Repository
 */',
        'startLine' => 155,
        'endLine' => 160,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Cache\\Console',
        'declaringClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'implementingClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'currentClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'aliasName' => NULL,
      ),
      'tags' => 
      array (
        'name' => 'tags',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the tags passed to the command.
 *
 * @return array
 */',
        'startLine' => 167,
        'endLine' => 170,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Cache\\Console',
        'declaringClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'implementingClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'currentClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
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