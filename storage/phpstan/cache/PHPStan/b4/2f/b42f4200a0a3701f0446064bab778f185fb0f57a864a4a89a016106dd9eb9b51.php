<?php declare(strict_types = 1);

// osfsl-C:/Users/TOURE/Herd/gesco/vendor/composer/../laravel/framework/src/Illuminate/Database/Console/Migrations/StatusCommand.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Database\Console\Migrations\StatusCommand
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-ab9ad033abb17734551636b3ad2c910667027e5a323c3fb6969d561bb39b4a4f-8.4.24-6.70.0.3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Database\\Console\\Migrations\\StatusCommand',
        'filename' => 'C:/Users/TOURE/Herd/gesco/vendor/composer/../laravel/framework/src/Illuminate/Database/Console/Migrations/StatusCommand.php',
      ),
    ),
    'namespace' => 'Illuminate\\Database\\Console\\Migrations',
    'name' => 'Illuminate\\Database\\Console\\Migrations\\StatusCommand',
    'shortName' => 'StatusCommand',
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
            'code' => '\'migrate:status\'',
            'attributes' => 
            array (
              'startLine' => 10,
              'endLine' => 10,
              'startTokenPos' => 33,
              'startFilePos' => 243,
              'endTokenPos' => 33,
              'endFilePos' => 258,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 10,
    'endLine' => 139,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Database\\Console\\Migrations\\BaseCommand',
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
        'declaringClassName' => 'Illuminate\\Database\\Console\\Migrations\\StatusCommand',
        'implementingClassName' => 'Illuminate\\Database\\Console\\Migrations\\StatusCommand',
        'name' => 'signature',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'migrate:status
                    {--database= : The database connection to use}
                    {--pending= : Only list pending migrations}
                    {--path=* : The path(s) to the migrations files to use}
                    {--realpath : Indicate any provided migration file paths are pre-resolved absolute paths}\'',
          'attributes' => 
          array (
            'startLine' => 18,
            'endLine' => 22,
            'startTokenPos' => 55,
            'startFilePos' => 427,
            'endTokenPos' => 55,
            'endFilePos' => 759,
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
        'startLine' => 18,
        'endLine' => 22,
        'startColumn' => 5,
        'endColumn' => 111,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'description' => 
      array (
        'declaringClassName' => 'Illuminate\\Database\\Console\\Migrations\\StatusCommand',
        'implementingClassName' => 'Illuminate\\Database\\Console\\Migrations\\StatusCommand',
        'name' => 'description',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'Show the status of each migration\'',
          'attributes' => 
          array (
            'startLine' => 29,
            'endLine' => 29,
            'startTokenPos' => 66,
            'startFilePos' => 874,
            'endTokenPos' => 66,
            'endFilePos' => 908,
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
        'startLine' => 29,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 65,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'migrator' => 
      array (
        'declaringClassName' => 'Illuminate\\Database\\Console\\Migrations\\StatusCommand',
        'implementingClassName' => 'Illuminate\\Database\\Console\\Migrations\\StatusCommand',
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
        'startLine' => 36,
        'endLine' => 36,
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
            'startLine' => 43,
            'endLine' => 43,
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
        'startLine' => 43,
        'endLine' => 48,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Console\\Migrations',
        'declaringClassName' => 'Illuminate\\Database\\Console\\Migrations\\StatusCommand',
        'implementingClassName' => 'Illuminate\\Database\\Console\\Migrations\\StatusCommand',
        'currentClassName' => 'Illuminate\\Database\\Console\\Migrations\\StatusCommand',
        'aliasName' => NULL,
      ),
      'configureDefaults' => 
      array (
        'name' => 'configureDefaults',
        'parameters' => 
        array (
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
          0 => 
          array (
            'name' => 'Override',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 50,
        'endLine' => 57,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Console\\Migrations',
        'declaringClassName' => 'Illuminate\\Database\\Console\\Migrations\\StatusCommand',
        'implementingClassName' => 'Illuminate\\Database\\Console\\Migrations\\StatusCommand',
        'currentClassName' => 'Illuminate\\Database\\Console\\Migrations\\StatusCommand',
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
 * @return int|null
 */',
        'startLine' => 64,
        'endLine' => 103,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Console\\Migrations',
        'declaringClassName' => 'Illuminate\\Database\\Console\\Migrations\\StatusCommand',
        'implementingClassName' => 'Illuminate\\Database\\Console\\Migrations\\StatusCommand',
        'currentClassName' => 'Illuminate\\Database\\Console\\Migrations\\StatusCommand',
        'aliasName' => NULL,
      ),
      'getStatusFor' => 
      array (
        'name' => 'getStatusFor',
        'parameters' => 
        array (
          'ran' => 
          array (
            'name' => 'ran',
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
            'startLine' => 112,
            'endLine' => 112,
            'startColumn' => 37,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'batches' => 
          array (
            'name' => 'batches',
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
            'startLine' => 112,
            'endLine' => 112,
            'startColumn' => 49,
            'endColumn' => 62,
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
 * Get the status for the given run migrations.
 *
 * @param  array  $ran
 * @param  array  $batches
 * @return \\Illuminate\\Support\\Collection
 */',
        'startLine' => 112,
        'endLine' => 128,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Console\\Migrations',
        'declaringClassName' => 'Illuminate\\Database\\Console\\Migrations\\StatusCommand',
        'implementingClassName' => 'Illuminate\\Database\\Console\\Migrations\\StatusCommand',
        'currentClassName' => 'Illuminate\\Database\\Console\\Migrations\\StatusCommand',
        'aliasName' => NULL,
      ),
      'getAllMigrationFiles' => 
      array (
        'name' => 'getAllMigrationFiles',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get an array of all of the migration files.
 *
 * @return array
 */',
        'startLine' => 135,
        'endLine' => 138,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Console\\Migrations',
        'declaringClassName' => 'Illuminate\\Database\\Console\\Migrations\\StatusCommand',
        'implementingClassName' => 'Illuminate\\Database\\Console\\Migrations\\StatusCommand',
        'currentClassName' => 'Illuminate\\Database\\Console\\Migrations\\StatusCommand',
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