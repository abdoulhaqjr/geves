<?php declare(strict_types = 1);

// osfsl-C:/Users/TOURE/Herd/gesco/vendor/composer/../laravel/framework/src/Illuminate/Database/Console/Migrations/InstallCommand.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Database\Console\Migrations\InstallCommand
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-d2e6035f43024a12fa26817f9d6112507263efc00615cf5c1bbe5f7c07768f26-8.4.24-6.70.0.3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Database\\Console\\Migrations\\InstallCommand',
        'filename' => 'C:/Users/TOURE/Herd/gesco/vendor/composer/../laravel/framework/src/Illuminate/Database/Console/Migrations/InstallCommand.php',
      ),
    ),
    'namespace' => 'Illuminate\\Database\\Console\\Migrations',
    'name' => 'Illuminate\\Database\\Console\\Migrations\\InstallCommand',
    'shortName' => 'InstallCommand',
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
            'code' => '\'migrate:install\'',
            'attributes' => 
            array (
              'startLine' => 9,
              'endLine' => 9,
              'startTokenPos' => 28,
              'startFilePos' => 225,
              'endTokenPos' => 28,
              'endFilePos' => 241,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 9,
    'endLine' => 60,
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
        'declaringClassName' => 'Illuminate\\Database\\Console\\Migrations\\InstallCommand',
        'implementingClassName' => 'Illuminate\\Database\\Console\\Migrations\\InstallCommand',
        'name' => 'signature',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'migrate:install {--database= : The database connection to use}\'',
          'attributes' => 
          array (
            'startLine' => 17,
            'endLine' => 17,
            'startTokenPos' => 50,
            'startFilePos' => 407,
            'endTokenPos' => 50,
            'endFilePos' => 470,
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
        'endColumn' => 92,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'description' => 
      array (
        'declaringClassName' => 'Illuminate\\Database\\Console\\Migrations\\InstallCommand',
        'implementingClassName' => 'Illuminate\\Database\\Console\\Migrations\\InstallCommand',
        'name' => 'description',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'Create the migration repository\'',
          'attributes' => 
          array (
            'startLine' => 24,
            'endLine' => 24,
            'startTokenPos' => 61,
            'startFilePos' => 585,
            'endTokenPos' => 61,
            'endFilePos' => 617,
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
        'endColumn' => 63,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'repository' => 
      array (
        'declaringClassName' => 'Illuminate\\Database\\Console\\Migrations\\InstallCommand',
        'implementingClassName' => 'Illuminate\\Database\\Console\\Migrations\\InstallCommand',
        'name' => 'repository',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The repository instance.
 *
 * @var \\Illuminate\\Database\\Migrations\\MigrationRepositoryInterface
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 31,
        'endLine' => 31,
        'startColumn' => 5,
        'endColumn' => 26,
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
          'repository' => 
          array (
            'name' => 'repository',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Database\\Migrations\\MigrationRepositoryInterface',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 38,
            'endLine' => 38,
            'startColumn' => 33,
            'endColumn' => 72,
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
 * Create a new migration install command instance.
 *
 * @param  \\Illuminate\\Database\\Migrations\\MigrationRepositoryInterface  $repository
 */',
        'startLine' => 38,
        'endLine' => 43,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Console\\Migrations',
        'declaringClassName' => 'Illuminate\\Database\\Console\\Migrations\\InstallCommand',
        'implementingClassName' => 'Illuminate\\Database\\Console\\Migrations\\InstallCommand',
        'currentClassName' => 'Illuminate\\Database\\Console\\Migrations\\InstallCommand',
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
 * @return void
 */',
        'startLine' => 50,
        'endLine' => 59,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Console\\Migrations',
        'declaringClassName' => 'Illuminate\\Database\\Console\\Migrations\\InstallCommand',
        'implementingClassName' => 'Illuminate\\Database\\Console\\Migrations\\InstallCommand',
        'currentClassName' => 'Illuminate\\Database\\Console\\Migrations\\InstallCommand',
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