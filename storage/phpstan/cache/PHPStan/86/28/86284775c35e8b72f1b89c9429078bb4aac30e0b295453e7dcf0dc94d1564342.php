<?php declare(strict_types = 1);

// osfsl-C:/Users/TOURE/Herd/gesco/vendor/composer/../laravel/framework/src/Illuminate/Foundation/Console/ModelMakeCommand.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Foundation\Console\ModelMakeCommand
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-4a1e76710265fcec3a239f98ad55467f45a98bd604a91b7132d3245b9b8539a0-8.4.24-6.70.0.3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Foundation\\Console\\ModelMakeCommand',
        'filename' => 'C:/Users/TOURE/Herd/gesco/vendor/composer/../laravel/framework/src/Illuminate/Foundation/Console/ModelMakeCommand.php',
      ),
    ),
    'namespace' => 'Illuminate\\Foundation\\Console',
    'name' => 'Illuminate\\Foundation\\Console\\ModelMakeCommand',
    'shortName' => 'ModelMakeCommand',
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
            'code' => '\'make:model\'',
            'attributes' => 
            array (
              'startLine' => 17,
              'endLine' => 17,
              'startTokenPos' => 67,
              'startFilePos' => 498,
              'endTokenPos' => 67,
              'endFilePos' => 509,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 17,
    'endLine' => 320,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Console\\GeneratorCommand',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
      0 => 'Illuminate\\Console\\Concerns\\CreatesMatchingTest',
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'signature' => 
      array (
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ModelMakeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ModelMakeCommand',
        'name' => 'signature',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'make:model
                    {name : The name of the model}
                    {--a|all : Generate a migration, seeder, factory, policy, resource controller, and form request classes for the model}
                    {--c|controller : Create a new controller for the model}
                    {--f|factory : Create a new factory for the model}
                    {--force : Create the class even if the model already exists}
                    {--m|migration : Create a new migration file for the model}
                    {--morph-pivot : Indicates if the generated model should be a custom polymorphic intermediate table model}
                    {--policy : Create a new policy for the model}
                    {--s|seed : Create a new seeder for the model}
                    {--p|pivot : Indicates if the generated model should be a custom intermediate table model}
                    {--r|resource : Indicates if the generated controller should be a resource controller}
                    {--api : Indicates if the generated controller should be an API resource controller}
                    {--R|requests : Create new form request classes and use them in the resource controller}\'',
          'attributes' => 
          array (
            'startLine' => 27,
            'endLine' => 40,
            'startTokenPos' => 94,
            'startFilePos' => 716,
            'endTokenPos' => 94,
            'endFilePos' => 1920,
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
        'startLine' => 27,
        'endLine' => 40,
        'startColumn' => 5,
        'endColumn' => 110,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'description' => 
      array (
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ModelMakeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ModelMakeCommand',
        'name' => 'description',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'Create a new Eloquent model class\'',
          'attributes' => 
          array (
            'startLine' => 47,
            'endLine' => 47,
            'startTokenPos' => 105,
            'startFilePos' => 2035,
            'endTokenPos' => 105,
            'endFilePos' => 2069,
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
        'startLine' => 47,
        'endLine' => 47,
        'startColumn' => 5,
        'endColumn' => 65,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'type' => 
      array (
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ModelMakeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ModelMakeCommand',
        'name' => 'type',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'Model\'',
          'attributes' => 
          array (
            'startLine' => 54,
            'endLine' => 54,
            'startTokenPos' => 116,
            'startFilePos' => 2179,
            'endTokenPos' => 116,
            'endFilePos' => 2185,
          ),
        ),
        'docComment' => '/**
 * The type of class being generated.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 54,
        'endLine' => 54,
        'startColumn' => 5,
        'endColumn' => 30,
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
 * @return bool|null
 */',
        'startLine' => 61,
        'endLine' => 105,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ModelMakeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ModelMakeCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\ModelMakeCommand',
        'aliasName' => NULL,
      ),
      'createFactory' => 
      array (
        'name' => 'createFactory',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Create a model factory for the model.
 *
 * @return void
 */',
        'startLine' => 112,
        'endLine' => 120,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ModelMakeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ModelMakeCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\ModelMakeCommand',
        'aliasName' => NULL,
      ),
      'createMigration' => 
      array (
        'name' => 'createMigration',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Create a migration file for the model.
 *
 * @return void
 */',
        'startLine' => 127,
        'endLine' => 139,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ModelMakeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ModelMakeCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\ModelMakeCommand',
        'aliasName' => NULL,
      ),
      'createSeeder' => 
      array (
        'name' => 'createSeeder',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Create a seeder file for the model.
 *
 * @return void
 */',
        'startLine' => 146,
        'endLine' => 153,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ModelMakeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ModelMakeCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\ModelMakeCommand',
        'aliasName' => NULL,
      ),
      'createController' => 
      array (
        'name' => 'createController',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Create a controller for the model.
 *
 * @return void
 */',
        'startLine' => 160,
        'endLine' => 174,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ModelMakeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ModelMakeCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\ModelMakeCommand',
        'aliasName' => NULL,
      ),
      'createFormRequests' => 
      array (
        'name' => 'createFormRequests',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Create the form requests for the model.
 *
 * @return void
 */',
        'startLine' => 181,
        'endLine' => 192,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ModelMakeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ModelMakeCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\ModelMakeCommand',
        'aliasName' => NULL,
      ),
      'createPolicy' => 
      array (
        'name' => 'createPolicy',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Create a policy file for the model.
 *
 * @return void
 */',
        'startLine' => 199,
        'endLine' => 207,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ModelMakeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ModelMakeCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\ModelMakeCommand',
        'aliasName' => NULL,
      ),
      'getStub' => 
      array (
        'name' => 'getStub',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the stub file for the generator.
 *
 * @return string
 */',
        'startLine' => 214,
        'endLine' => 225,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ModelMakeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ModelMakeCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\ModelMakeCommand',
        'aliasName' => NULL,
      ),
      'resolveStubPath' => 
      array (
        'name' => 'resolveStubPath',
        'parameters' => 
        array (
          'stub' => 
          array (
            'name' => 'stub',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 233,
            'endLine' => 233,
            'startColumn' => 40,
            'endColumn' => 44,
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
 * Resolve the fully-qualified path to the stub.
 *
 * @param  string  $stub
 * @return string
 */',
        'startLine' => 233,
        'endLine' => 238,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ModelMakeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ModelMakeCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\ModelMakeCommand',
        'aliasName' => NULL,
      ),
      'getDefaultNamespace' => 
      array (
        'name' => 'getDefaultNamespace',
        'parameters' => 
        array (
          'rootNamespace' => 
          array (
            'name' => 'rootNamespace',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 246,
            'endLine' => 246,
            'startColumn' => 44,
            'endColumn' => 57,
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
 * Get the default namespace for the class.
 *
 * @param  string  $rootNamespace
 * @return string
 */',
        'startLine' => 246,
        'endLine' => 249,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ModelMakeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ModelMakeCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\ModelMakeCommand',
        'aliasName' => NULL,
      ),
      'buildClass' => 
      array (
        'name' => 'buildClass',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 259,
            'endLine' => 259,
            'startColumn' => 35,
            'endColumn' => 39,
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
 * Build the class with the given name.
 *
 * @param  string  $name
 * @return string
 *
 * @throws \\Illuminate\\Contracts\\Filesystem\\FileNotFoundException
 */',
        'startLine' => 259,
        'endLine' => 266,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ModelMakeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ModelMakeCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\ModelMakeCommand',
        'aliasName' => NULL,
      ),
      'buildFactoryReplacements' => 
      array (
        'name' => 'buildFactoryReplacements',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Build the replacements for a factory.
 *
 * @return array<string, string>
 */',
        'startLine' => 273,
        'endLine' => 296,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ModelMakeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ModelMakeCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\ModelMakeCommand',
        'aliasName' => NULL,
      ),
      'afterPromptingForMissingArguments' => 
      array (
        'name' => 'afterPromptingForMissingArguments',
        'parameters' => 
        array (
          'input' => 
          array (
            'name' => 'input',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Symfony\\Component\\Console\\Input\\InputInterface',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 305,
            'endLine' => 305,
            'startColumn' => 58,
            'endColumn' => 78,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'output' => 
          array (
            'name' => 'output',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Symfony\\Component\\Console\\Output\\OutputInterface',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 305,
            'endLine' => 305,
            'startColumn' => 81,
            'endColumn' => 103,
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
 * Interact further with the user if they were prompted for missing arguments.
 *
 * @param  \\Symfony\\Component\\Console\\Input\\InputInterface  $input
 * @param  \\Symfony\\Component\\Console\\Output\\OutputInterface  $output
 * @return void
 */',
        'startLine' => 305,
        'endLine' => 319,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ModelMakeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ModelMakeCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\ModelMakeCommand',
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