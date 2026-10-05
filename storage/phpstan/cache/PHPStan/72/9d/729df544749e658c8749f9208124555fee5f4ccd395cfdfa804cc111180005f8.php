<?php declare(strict_types = 1);

// osfsl-C:/Users/TOURE/Herd/gesco/vendor/composer/../laravel/framework/src/Illuminate/Foundation/Console/ViewMakeCommand.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Foundation\Console\ViewMakeCommand
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-1dada510b1b7684abb4c951ec1baabe72a7d77715d3a987c6ae018b2c226dd4d-8.4.24-6.70.0.3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Foundation\\Console\\ViewMakeCommand',
        'filename' => 'C:/Users/TOURE/Herd/gesco/vendor/composer/../laravel/framework/src/Illuminate/Foundation/Console/ViewMakeCommand.php',
      ),
    ),
    'namespace' => 'Illuminate\\Foundation\\Console',
    'name' => 'Illuminate\\Foundation\\Console\\ViewMakeCommand',
    'shortName' => 'ViewMakeCommand',
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
            'code' => '\'make:view\'',
            'attributes' => 
            array (
              'startLine' => 13,
              'endLine' => 13,
              'startTokenPos' => 48,
              'startFilePos' => 350,
              'endTokenPos' => 48,
              'endFilePos' => 360,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 13,
    'endLine' => 246,
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
      'description' => 
      array (
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ViewMakeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ViewMakeCommand',
        'name' => 'description',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'Create a new view\'',
          'attributes' => 
          array (
            'startLine' => 23,
            'endLine' => 23,
            'startTokenPos' => 75,
            'startFilePos' => 554,
            'endTokenPos' => 75,
            'endFilePos' => 572,
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
        'startLine' => 23,
        'endLine' => 23,
        'startColumn' => 5,
        'endColumn' => 49,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'signature' => 
      array (
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ViewMakeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ViewMakeCommand',
        'name' => 'signature',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'make:view
                    {name : The name of the view}
                    {--extension=blade.php : The extension of the generated view}
                    {--f|force : Create the view even if the view already exists}\'',
          'attributes' => 
          array (
            'startLine' => 30,
            'endLine' => 33,
            'startTokenPos' => 86,
            'startFilePos' => 699,
            'endTokenPos' => 86,
            'endFilePos' => 923,
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
        'startLine' => 30,
        'endLine' => 33,
        'startColumn' => 5,
        'endColumn' => 83,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'type' => 
      array (
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ViewMakeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ViewMakeCommand',
        'name' => 'type',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'View\'',
          'attributes' => 
          array (
            'startLine' => 40,
            'endLine' => 40,
            'startTokenPos' => 97,
            'startFilePos' => 1032,
            'endTokenPos' => 97,
            'endFilePos' => 1037,
          ),
        ),
        'docComment' => '/**
 * The type of file being generated.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 40,
        'endLine' => 40,
        'startColumn' => 5,
        'endColumn' => 29,
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
            'startLine' => 50,
            'endLine' => 50,
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
        'startLine' => 50,
        'endLine' => 59,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ViewMakeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ViewMakeCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\ViewMakeCommand',
        'aliasName' => NULL,
      ),
      'getPath' => 
      array (
        'name' => 'getPath',
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
            'startLine' => 67,
            'endLine' => 67,
            'startColumn' => 32,
            'endColumn' => 36,
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
 * Get the destination view path.
 *
 * @param  string  $name
 * @return string
 */',
        'startLine' => 67,
        'endLine' => 72,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ViewMakeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ViewMakeCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\ViewMakeCommand',
        'aliasName' => NULL,
      ),
      'getNameInput' => 
      array (
        'name' => 'getNameInput',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the desired view name from the input.
 *
 * @return string
 */',
        'startLine' => 79,
        'endLine' => 86,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ViewMakeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ViewMakeCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\ViewMakeCommand',
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
        'startLine' => 93,
        'endLine' => 98,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ViewMakeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ViewMakeCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\ViewMakeCommand',
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
            'startLine' => 106,
            'endLine' => 106,
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
        'startLine' => 106,
        'endLine' => 111,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ViewMakeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ViewMakeCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\ViewMakeCommand',
        'aliasName' => NULL,
      ),
      'getTestPath' => 
      array (
        'name' => 'getTestPath',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the destination test case path.
 *
 * @return string
 */',
        'startLine' => 118,
        'endLine' => 127,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ViewMakeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ViewMakeCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\ViewMakeCommand',
        'aliasName' => NULL,
      ),
      'handleTestCreation' => 
      array (
        'name' => 'handleTestCreation',
        'parameters' => 
        array (
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
            'startLine' => 134,
            'endLine' => 134,
            'startColumn' => 43,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Create the matching test case if requested.
 *
 * @param  string  $path
 */',
        'startLine' => 134,
        'endLine' => 153,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ViewMakeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ViewMakeCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\ViewMakeCommand',
        'aliasName' => NULL,
      ),
      'testNamespace' => 
      array (
        'name' => 'testNamespace',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the namespace for the test.
 *
 * @return string
 */',
        'startLine' => 160,
        'endLine' => 165,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ViewMakeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ViewMakeCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\ViewMakeCommand',
        'aliasName' => NULL,
      ),
      'testClassName' => 
      array (
        'name' => 'testClassName',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the class name for the test.
 *
 * @return string
 */',
        'startLine' => 172,
        'endLine' => 178,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ViewMakeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ViewMakeCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\ViewMakeCommand',
        'aliasName' => NULL,
      ),
      'testClassFullyQualifiedName' => 
      array (
        'name' => 'testClassFullyQualifiedName',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the class fully-qualified name for the test.
 *
 * @return string
 */',
        'startLine' => 185,
        'endLine' => 202,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ViewMakeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ViewMakeCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\ViewMakeCommand',
        'aliasName' => NULL,
      ),
      'getTestStub' => 
      array (
        'name' => 'getTestStub',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the test stub file for the generator.
 *
 * @return string
 */',
        'startLine' => 209,
        'endLine' => 216,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ViewMakeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ViewMakeCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\ViewMakeCommand',
        'aliasName' => NULL,
      ),
      'testViewName' => 
      array (
        'name' => 'testViewName',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the view name for the test.
 *
 * @return string
 */',
        'startLine' => 223,
        'endLine' => 229,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ViewMakeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ViewMakeCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\ViewMakeCommand',
        'aliasName' => NULL,
      ),
      'usingPest' => 
      array (
        'name' => 'usingPest',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Determine if Pest is being used by the application.
 *
 * @return bool
 */',
        'startLine' => 236,
        'endLine' => 245,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ViewMakeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ViewMakeCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\ViewMakeCommand',
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