<?php declare(strict_types = 1);

// osfsl-C:/Users/TOURE/Herd/gesco/vendor/composer/../laravel/framework/src/Illuminate/Foundation/Console/RuleMakeCommand.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Foundation\Console\RuleMakeCommand
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-c93387657c340c4aeace09d3bcf49f5d44dac939f54a8ef939686696fe759d03-8.4.24-6.70.0.3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Foundation\\Console\\RuleMakeCommand',
        'filename' => 'C:/Users/TOURE/Herd/gesco/vendor/composer/../laravel/framework/src/Illuminate/Foundation/Console/RuleMakeCommand.php',
      ),
    ),
    'namespace' => 'Illuminate\\Foundation\\Console',
    'name' => 'Illuminate\\Foundation\\Console\\RuleMakeCommand',
    'shortName' => 'RuleMakeCommand',
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
            'code' => '\'make:rule\'',
            'attributes' => 
            array (
              'startLine' => 8,
              'endLine' => 8,
              'startTokenPos' => 23,
              'startFilePos' => 160,
              'endTokenPos' => 23,
              'endFilePos' => 170,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 8,
    'endLine' => 78,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Console\\GeneratorCommand',
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
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\RuleMakeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\RuleMakeCommand',
        'name' => 'signature',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'make:rule
                    {name : The name of the rule}
                    {--f|force : Create the class even if the rule already exists}
                    {--i|implicit : Generate an implicit rule}\'',
          'attributes' => 
          array (
            'startLine' => 16,
            'endLine' => 19,
            'startTokenPos' => 45,
            'startFilePos' => 346,
            'endTokenPos' => 45,
            'endFilePos' => 552,
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
        'startLine' => 16,
        'endLine' => 19,
        'startColumn' => 5,
        'endColumn' => 64,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'description' => 
      array (
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\RuleMakeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\RuleMakeCommand',
        'name' => 'description',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'Create a new validation rule\'',
          'attributes' => 
          array (
            'startLine' => 26,
            'endLine' => 26,
            'startTokenPos' => 56,
            'startFilePos' => 667,
            'endTokenPos' => 56,
            'endFilePos' => 696,
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
        'startLine' => 26,
        'endLine' => 26,
        'startColumn' => 5,
        'endColumn' => 60,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'type' => 
      array (
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\RuleMakeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\RuleMakeCommand',
        'name' => 'type',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'Rule\'',
          'attributes' => 
          array (
            'startLine' => 33,
            'endLine' => 33,
            'startTokenPos' => 67,
            'startFilePos' => 806,
            'endTokenPos' => 67,
            'endFilePos' => 811,
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
        'startLine' => 33,
        'endLine' => 33,
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
            'startLine' => 43,
            'endLine' => 43,
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
        'startLine' => 43,
        'endLine' => 50,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\RuleMakeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\RuleMakeCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\RuleMakeCommand',
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
        'startLine' => 57,
        'endLine' => 66,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\RuleMakeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\RuleMakeCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\RuleMakeCommand',
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
            'startLine' => 74,
            'endLine' => 74,
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
        'startLine' => 74,
        'endLine' => 77,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\RuleMakeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\RuleMakeCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\RuleMakeCommand',
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