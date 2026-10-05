<?php declare(strict_types = 1);

// osfsl-C:/Users/TOURE/Herd/gesco/vendor/composer/../laravel/framework/src/Illuminate/Database/Console/Migrations/ResetCommand.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Database\Console\Migrations\ResetCommand
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-eb77b775e7840ad8efd21eb9522af805aeed2dabdfd1c22c9efc5be717640d8a-8.4.24-6.70.0.3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Database\\Console\\Migrations\\ResetCommand',
        'filename' => 'C:/Users/TOURE/Herd/gesco/vendor/composer/../laravel/framework/src/Illuminate/Database/Console/Migrations/ResetCommand.php',
      ),
    ),
    'namespace' => 'Illuminate\\Database\\Console\\Migrations',
    'name' => 'Illuminate\\Database\\Console\\Migrations\\ResetCommand',
    'shortName' => 'ResetCommand',
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
            'code' => '\'migrate:reset\'',
            'attributes' => 
            array (
              'startLine' => 11,
              'endLine' => 11,
              'startTokenPos' => 38,
              'startFilePos' => 283,
              'endTokenPos' => 38,
              'endFilePos' => 297,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 11,
    'endLine' => 78,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Database\\Console\\Migrations\\BaseCommand',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
      0 => 'Illuminate\\Console\\ConfirmableTrait',
      1 => 'Illuminate\\Console\\Prohibitable',
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'signature' => 
      array (
        'declaringClassName' => 'Illuminate\\Database\\Console\\Migrations\\ResetCommand',
        'implementingClassName' => 'Illuminate\\Database\\Console\\Migrations\\ResetCommand',
        'name' => 'signature',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'migrate:reset
                    {--database= : The database connection to use}
                    {--force : Force the operation to run when in production}
                    {--path=* : The path(s) to the migrations files to be executed}
                    {--realpath : Indicate any provided migration file paths are pre-resolved absolute paths}
                    {--pretend : Dump the SQL queries that would be run}\'',
          'attributes' => 
          array (
            'startLine' => 21,
            'endLine' => 26,
            'startTokenPos' => 68,
            'startFilePos' => 506,
            'endTokenPos' => 68,
            'endFilePos' => 932,
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
        'startLine' => 21,
        'endLine' => 26,
        'startColumn' => 5,
        'endColumn' => 74,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'description' => 
      array (
        'declaringClassName' => 'Illuminate\\Database\\Console\\Migrations\\ResetCommand',
        'implementingClassName' => 'Illuminate\\Database\\Console\\Migrations\\ResetCommand',
        'name' => 'description',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'Rollback all database migrations\'',
          'attributes' => 
          array (
            'startLine' => 33,
            'endLine' => 33,
            'startTokenPos' => 79,
            'startFilePos' => 1047,
            'endTokenPos' => 79,
            'endFilePos' => 1080,
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
        'startLine' => 33,
        'endLine' => 33,
        'startColumn' => 5,
        'endColumn' => 64,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'migrator' => 
      array (
        'declaringClassName' => 'Illuminate\\Database\\Console\\Migrations\\ResetCommand',
        'implementingClassName' => 'Illuminate\\Database\\Console\\Migrations\\ResetCommand',
        'name' => 'migrator',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The migrator instance.
 *
 * @var \\Illuminate\\Database\\Migrations\\Migrator
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 40,
        'endLine' => 40,
        'startColumn' => 5,
        'endColumn' => 24,
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
          'migrator' => 
          array (
            'name' => 'migrator',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Database\\Migrations\\Migrator',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 47,
            'endLine' => 47,
            'startColumn' => 33,
            'endColumn' => 50,
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
 * Create a new migration rollback command instance.
 *
 * @param  \\Illuminate\\Database\\Migrations\\Migrator  $migrator
 */',
        'startLine' => 47,
        'endLine' => 52,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Console\\Migrations',
        'declaringClassName' => 'Illuminate\\Database\\Console\\Migrations\\ResetCommand',
        'implementingClassName' => 'Illuminate\\Database\\Console\\Migrations\\ResetCommand',
        'currentClassName' => 'Illuminate\\Database\\Console\\Migrations\\ResetCommand',
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
        'startLine' => 59,
        'endLine' => 77,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Console\\Migrations',
        'declaringClassName' => 'Illuminate\\Database\\Console\\Migrations\\ResetCommand',
        'implementingClassName' => 'Illuminate\\Database\\Console\\Migrations\\ResetCommand',
        'currentClassName' => 'Illuminate\\Database\\Console\\Migrations\\ResetCommand',
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