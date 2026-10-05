<?php declare(strict_types = 1);

// osfsl-C:/Users/TOURE/Herd/gesco/vendor/composer/../laravel/framework/src/Illuminate/Database/Console/Migrations/RefreshCommand.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Database\Console\Migrations\RefreshCommand
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-7fff45e5e8c8c94bde674d38a19bed37204ddf26a8bac0c1dbbe2a5db8b308d1-8.4.24-6.70.0.3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Database\\Console\\Migrations\\RefreshCommand',
        'filename' => 'C:/Users/TOURE/Herd/gesco/vendor/composer/../laravel/framework/src/Illuminate/Database/Console/Migrations/RefreshCommand.php',
      ),
    ),
    'namespace' => 'Illuminate\\Database\\Console\\Migrations',
    'name' => 'Illuminate\\Database\\Console\\Migrations\\RefreshCommand',
    'shortName' => 'RefreshCommand',
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
            'code' => '\'migrate:refresh\'',
            'attributes' => 
            array (
              'startLine' => 12,
              'endLine' => 12,
              'startTokenPos' => 43,
              'startFilePos' => 332,
              'endTokenPos' => 43,
              'endFilePos' => 348,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 12,
    'endLine' => 150,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Console\\Command',
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
        'declaringClassName' => 'Illuminate\\Database\\Console\\Migrations\\RefreshCommand',
        'implementingClassName' => 'Illuminate\\Database\\Console\\Migrations\\RefreshCommand',
        'name' => 'signature',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'migrate:refresh
                    {--database= : The database connection to use}
                    {--force : Force the operation to run when in production}
                    {--path=* : The path(s) to the migrations files to be executed}
                    {--realpath : Indicate any provided migration file paths are pre-resolved absolute paths}
                    {--seed : Indicates if the seed task should be re-run}
                    {--seeder= : The class name of the root seeder}
                    {--step= : The number of migrations to be reverted & re-run}\'',
          'attributes' => 
          array (
            'startLine' => 22,
            'endLine' => 29,
            'startTokenPos' => 73,
            'startFilePos' => 555,
            'endTokenPos' => 73,
            'endFilePos' => 1134,
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
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 82,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'description' => 
      array (
        'declaringClassName' => 'Illuminate\\Database\\Console\\Migrations\\RefreshCommand',
        'implementingClassName' => 'Illuminate\\Database\\Console\\Migrations\\RefreshCommand',
        'name' => 'description',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'Reset and re-run all migrations\'',
          'attributes' => 
          array (
            'startLine' => 36,
            'endLine' => 36,
            'startTokenPos' => 84,
            'startFilePos' => 1249,
            'endTokenPos' => 84,
            'endFilePos' => 1281,
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
        'startLine' => 36,
        'endLine' => 36,
        'startColumn' => 5,
        'endColumn' => 63,
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
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Execute the console command.
 *
 * @return int
 */',
        'startLine' => 43,
        'endLine' => 88,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Console\\Migrations',
        'declaringClassName' => 'Illuminate\\Database\\Console\\Migrations\\RefreshCommand',
        'implementingClassName' => 'Illuminate\\Database\\Console\\Migrations\\RefreshCommand',
        'currentClassName' => 'Illuminate\\Database\\Console\\Migrations\\RefreshCommand',
        'aliasName' => NULL,
      ),
      'runRollback' => 
      array (
        'name' => 'runRollback',
        'parameters' => 
        array (
          'database' => 
          array (
            'name' => 'database',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 98,
            'endLine' => 98,
            'startColumn' => 36,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'path' => 
          array (
            'name' => 'path',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 98,
            'endLine' => 98,
            'startColumn' => 47,
            'endColumn' => 51,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'step' => 
          array (
            'name' => 'step',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 98,
            'endLine' => 98,
            'startColumn' => 54,
            'endColumn' => 58,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Run the rollback command.
 *
 * @param  string  $database
 * @param  string  $path
 * @param  int  $step
 * @return void
 */',
        'startLine' => 98,
        'endLine' => 107,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Console\\Migrations',
        'declaringClassName' => 'Illuminate\\Database\\Console\\Migrations\\RefreshCommand',
        'implementingClassName' => 'Illuminate\\Database\\Console\\Migrations\\RefreshCommand',
        'currentClassName' => 'Illuminate\\Database\\Console\\Migrations\\RefreshCommand',
        'aliasName' => NULL,
      ),
      'runReset' => 
      array (
        'name' => 'runReset',
        'parameters' => 
        array (
          'database' => 
          array (
            'name' => 'database',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 116,
            'endLine' => 116,
            'startColumn' => 33,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'path' => 
          array (
            'name' => 'path',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 116,
            'endLine' => 116,
            'startColumn' => 44,
            'endColumn' => 48,
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
 * Run the reset command.
 *
 * @param  string  $database
 * @param  string  $path
 * @return void
 */',
        'startLine' => 116,
        'endLine' => 124,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Console\\Migrations',
        'declaringClassName' => 'Illuminate\\Database\\Console\\Migrations\\RefreshCommand',
        'implementingClassName' => 'Illuminate\\Database\\Console\\Migrations\\RefreshCommand',
        'currentClassName' => 'Illuminate\\Database\\Console\\Migrations\\RefreshCommand',
        'aliasName' => NULL,
      ),
      'needsSeeding' => 
      array (
        'name' => 'needsSeeding',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Determine if the developer has requested database seeding.
 *
 * @return bool
 */',
        'startLine' => 131,
        'endLine' => 134,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Console\\Migrations',
        'declaringClassName' => 'Illuminate\\Database\\Console\\Migrations\\RefreshCommand',
        'implementingClassName' => 'Illuminate\\Database\\Console\\Migrations\\RefreshCommand',
        'currentClassName' => 'Illuminate\\Database\\Console\\Migrations\\RefreshCommand',
        'aliasName' => NULL,
      ),
      'runSeeder' => 
      array (
        'name' => 'runSeeder',
        'parameters' => 
        array (
          'database' => 
          array (
            'name' => 'database',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 142,
            'endLine' => 142,
            'startColumn' => 34,
            'endColumn' => 42,
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
 * Run the database seeder command.
 *
 * @param  string  $database
 * @return void
 */',
        'startLine' => 142,
        'endLine' => 149,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Console\\Migrations',
        'declaringClassName' => 'Illuminate\\Database\\Console\\Migrations\\RefreshCommand',
        'implementingClassName' => 'Illuminate\\Database\\Console\\Migrations\\RefreshCommand',
        'currentClassName' => 'Illuminate\\Database\\Console\\Migrations\\RefreshCommand',
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