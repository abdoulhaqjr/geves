<?php declare(strict_types = 1);

// osfsl-C:/Users/TOURE/Herd/gesco/vendor/composer/../laravel/framework/src/Illuminate/Foundation/Console/NotificationMakeCommand.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Foundation\Console\NotificationMakeCommand
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-7bc670c93c06c675bf30852157b54f516ce0104cee24ecbfbf23d8f697390aee-8.4.24-6.70.0.3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Foundation\\Console\\NotificationMakeCommand',
        'filename' => 'C:/Users/TOURE/Herd/gesco/vendor/composer/../laravel/framework/src/Illuminate/Foundation/Console/NotificationMakeCommand.php',
      ),
    ),
    'namespace' => 'Illuminate\\Foundation\\Console',
    'name' => 'Illuminate\\Foundation\\Console\\NotificationMakeCommand',
    'shortName' => 'NotificationMakeCommand',
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
            'code' => '\'make:notification\'',
            'attributes' => 
            array (
              'startLine' => 16,
              'endLine' => 16,
              'startTokenPos' => 62,
              'startFilePos' => 456,
              'endTokenPos' => 62,
              'endFilePos' => 474,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 16,
    'endLine' => 166,
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
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\NotificationMakeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\NotificationMakeCommand',
        'name' => 'signature',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'make:notification
                    {name : The name of the notification}
                    {--f|force : Create the class even if the notification already exists}
                    {--m|markdown= : Create a new Markdown template for the notification}\'',
          'attributes' => 
          array (
            'startLine' => 26,
            'endLine' => 29,
            'startTokenPos' => 89,
            'startFilePos' => 688,
            'endTokenPos' => 89,
            'endFilePos' => 945,
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
        'startLine' => 26,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 91,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'description' => 
      array (
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\NotificationMakeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\NotificationMakeCommand',
        'name' => 'description',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'Create a new notification class\'',
          'attributes' => 
          array (
            'startLine' => 36,
            'endLine' => 36,
            'startTokenPos' => 100,
            'startFilePos' => 1060,
            'endTokenPos' => 100,
            'endFilePos' => 1092,
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
      'type' => 
      array (
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\NotificationMakeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\NotificationMakeCommand',
        'name' => 'type',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'Notification\'',
          'attributes' => 
          array (
            'startLine' => 43,
            'endLine' => 43,
            'startTokenPos' => 111,
            'startFilePos' => 1202,
            'endTokenPos' => 111,
            'endFilePos' => 1215,
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
        'startLine' => 43,
        'endLine' => 43,
        'startColumn' => 5,
        'endColumn' => 37,
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
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\NotificationMakeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\NotificationMakeCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\NotificationMakeCommand',
        'aliasName' => NULL,
      ),
      'writeMarkdownTemplate' => 
      array (
        'name' => 'writeMarkdownTemplate',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Write the Markdown template for the mailable.
 *
 * @return void
 */',
        'startLine' => 66,
        'endLine' => 85,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\NotificationMakeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\NotificationMakeCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\NotificationMakeCommand',
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
            'startLine' => 93,
            'endLine' => 93,
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
 */',
        'startLine' => 93,
        'endLine' => 102,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\NotificationMakeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\NotificationMakeCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\NotificationMakeCommand',
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
        'startLine' => 109,
        'endLine' => 114,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\NotificationMakeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\NotificationMakeCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\NotificationMakeCommand',
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
            'startLine' => 122,
            'endLine' => 122,
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
        'startLine' => 122,
        'endLine' => 127,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\NotificationMakeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\NotificationMakeCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\NotificationMakeCommand',
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
            'startLine' => 135,
            'endLine' => 135,
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
        'startLine' => 135,
        'endLine' => 138,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\NotificationMakeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\NotificationMakeCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\NotificationMakeCommand',
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
            'startLine' => 147,
            'endLine' => 147,
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
            'startLine' => 147,
            'endLine' => 147,
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
 * Perform actions after the user was prompted for missing arguments.
 *
 * @param  \\Symfony\\Component\\Console\\Input\\InputInterface  $input
 * @param  \\Symfony\\Component\\Console\\Output\\OutputInterface  $output
 * @return void
 */',
        'startLine' => 147,
        'endLine' => 165,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\NotificationMakeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\NotificationMakeCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\NotificationMakeCommand',
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