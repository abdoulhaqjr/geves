<?php declare(strict_types = 1);

// osfsl-C:/Users/TOURE/Herd/gesco/vendor/composer/../laravel/framework/src/Illuminate/Database/Console/Seeds/SeedCommand.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Database\Console\Seeds\SeedCommand
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-4777ad90056ad64c130eab4fe9dcb7a81c7a3066d6c81625242af7ffcfbac55f-8.4.24-6.70.0.3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Database\\Console\\Seeds\\SeedCommand',
        'filename' => 'C:/Users/TOURE/Herd/gesco/vendor/composer/../laravel/framework/src/Illuminate/Database/Console/Seeds/SeedCommand.php',
      ),
    ),
    'namespace' => 'Illuminate\\Database\\Console\\Seeds',
    'name' => 'Illuminate\\Database\\Console\\Seeds\\SeedCommand',
    'shortName' => 'SeedCommand',
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
            'code' => '\'db:seed\'',
            'attributes' => 
            array (
              'startLine' => 12,
              'endLine' => 12,
              'startTokenPos' => 47,
              'startFilePos' => 338,
              'endTokenPos' => 47,
              'endFilePos' => 346,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 12,
    'endLine' => 116,
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
        'declaringClassName' => 'Illuminate\\Database\\Console\\Seeds\\SeedCommand',
        'implementingClassName' => 'Illuminate\\Database\\Console\\Seeds\\SeedCommand',
        'name' => 'signature',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'db:seed
                    {class? : The class name of the root seeder}
                    {--class=Database\\Seeders\\DatabaseSeeder : The class name of the root seeder}
                    {--database= : The database connection to seed}
                    {--force : Force the operation to run when in production}\'',
          'attributes' => 
          array (
            'startLine' => 22,
            'endLine' => 26,
            'startTokenPos' => 77,
            'startFilePos' => 550,
            'endTokenPos' => 77,
            'endFilePos' => 869,
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
        'endLine' => 26,
        'startColumn' => 5,
        'endColumn' => 79,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'description' => 
      array (
        'declaringClassName' => 'Illuminate\\Database\\Console\\Seeds\\SeedCommand',
        'implementingClassName' => 'Illuminate\\Database\\Console\\Seeds\\SeedCommand',
        'name' => 'description',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'Seed the database with records\'',
          'attributes' => 
          array (
            'startLine' => 33,
            'endLine' => 33,
            'startTokenPos' => 88,
            'startFilePos' => 984,
            'endTokenPos' => 88,
            'endFilePos' => 1015,
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
        'endColumn' => 62,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'resolver' => 
      array (
        'declaringClassName' => 'Illuminate\\Database\\Console\\Seeds\\SeedCommand',
        'implementingClassName' => 'Illuminate\\Database\\Console\\Seeds\\SeedCommand',
        'name' => 'resolver',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The connection resolver instance.
 *
 * @var \\Illuminate\\Database\\ConnectionResolverInterface
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
          'resolver' => 
          array (
            'name' => 'resolver',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Database\\ConnectionResolverInterface',
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
 * Create a new database seed command instance.
 *
 * @param  \\Illuminate\\Database\\ConnectionResolverInterface  $resolver
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
        'namespace' => 'Illuminate\\Database\\Console\\Seeds',
        'declaringClassName' => 'Illuminate\\Database\\Console\\Seeds\\SeedCommand',
        'implementingClassName' => 'Illuminate\\Database\\Console\\Seeds\\SeedCommand',
        'currentClassName' => 'Illuminate\\Database\\Console\\Seeds\\SeedCommand',
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
        'endLine' => 80,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Console\\Seeds',
        'declaringClassName' => 'Illuminate\\Database\\Console\\Seeds\\SeedCommand',
        'implementingClassName' => 'Illuminate\\Database\\Console\\Seeds\\SeedCommand',
        'currentClassName' => 'Illuminate\\Database\\Console\\Seeds\\SeedCommand',
        'aliasName' => NULL,
      ),
      'getSeeder' => 
      array (
        'name' => 'getSeeder',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get a seeder instance from the container.
 *
 * @return \\Illuminate\\Database\\Seeder
 */',
        'startLine' => 87,
        'endLine' => 103,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Console\\Seeds',
        'declaringClassName' => 'Illuminate\\Database\\Console\\Seeds\\SeedCommand',
        'implementingClassName' => 'Illuminate\\Database\\Console\\Seeds\\SeedCommand',
        'currentClassName' => 'Illuminate\\Database\\Console\\Seeds\\SeedCommand',
        'aliasName' => NULL,
      ),
      'getDatabase' => 
      array (
        'name' => 'getDatabase',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the name of the database connection to use.
 *
 * @return string
 */',
        'startLine' => 110,
        'endLine' => 115,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Console\\Seeds',
        'declaringClassName' => 'Illuminate\\Database\\Console\\Seeds\\SeedCommand',
        'implementingClassName' => 'Illuminate\\Database\\Console\\Seeds\\SeedCommand',
        'currentClassName' => 'Illuminate\\Database\\Console\\Seeds\\SeedCommand',
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