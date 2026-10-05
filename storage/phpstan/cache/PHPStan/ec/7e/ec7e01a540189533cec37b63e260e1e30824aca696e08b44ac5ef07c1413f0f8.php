<?php declare(strict_types = 1);

// osfsl-C:/Users/TOURE/Herd/gesco/vendor/composer/../laravel/framework/src/Illuminate/Database/Console/ShowModelCommand.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Database\Console\ShowModelCommand
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-47bb60c38f96921ec332c84fff84eb5f53602f873a7f7e864330f511ea6faa50-8.4.24-6.70.0.3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Database\\Console\\ShowModelCommand',
        'filename' => 'C:/Users/TOURE/Herd/gesco/vendor/composer/../laravel/framework/src/Illuminate/Database/Console/ShowModelCommand.php',
      ),
    ),
    'namespace' => 'Illuminate\\Database\\Console',
    'name' => 'Illuminate\\Database\\Console\\ShowModelCommand',
    'shortName' => 'ShowModelCommand',
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
            'code' => '\'model:show\'',
            'attributes' => 
            array (
              'startLine' => 16,
              'endLine' => 16,
              'startTokenPos' => 60,
              'startFilePos' => 512,
              'endTokenPos' => 60,
              'endFilePos' => 523,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 16,
    'endLine' => 184,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Database\\Console\\DatabaseInspectionCommand',
    'implementsClassNames' => 
    array (
      0 => 'Illuminate\\Contracts\\Console\\PromptsForMissingInput',
    ),
    'traitClassNames' => 
    array (
      0 => 'Illuminate\\Console\\Concerns\\FindsAvailableModels',
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'description' => 
      array (
        'declaringClassName' => 'Illuminate\\Database\\Console\\ShowModelCommand',
        'implementingClassName' => 'Illuminate\\Database\\Console\\ShowModelCommand',
        'name' => 'description',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'Show information about an Eloquent model\'',
          'attributes' => 
          array (
            'startLine' => 26,
            'endLine' => 26,
            'startTokenPos' => 91,
            'startFilePos' => 762,
            'endTokenPos' => 91,
            'endFilePos' => 803,
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
        'endColumn' => 72,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'signature' => 
      array (
        'declaringClassName' => 'Illuminate\\Database\\Console\\ShowModelCommand',
        'implementingClassName' => 'Illuminate\\Database\\Console\\ShowModelCommand',
        'name' => 'signature',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'model:show {model : The model to show}
                {--database= : The database connection to use}
                {--json : Output the model as JSON}\'',
          'attributes' => 
          array (
            'startLine' => 33,
            'endLine' => 35,
            'startTokenPos' => 102,
            'startFilePos' => 914,
            'endTokenPos' => 102,
            'endFilePos' => 1068,
          ),
        ),
        'docComment' => '/**
 * The console command signature.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 33,
        'endLine' => 35,
        'startColumn' => 5,
        'endColumn' => 53,
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
          'modelInspector' => 
          array (
            'name' => 'modelInspector',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Database\\Eloquent\\ModelInspector',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 42,
            'endLine' => 42,
            'startColumn' => 28,
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
 * Execute the console command.
 *
 * @return int
 */',
        'startLine' => 42,
        'endLine' => 58,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Console',
        'declaringClassName' => 'Illuminate\\Database\\Console\\ShowModelCommand',
        'implementingClassName' => 'Illuminate\\Database\\Console\\ShowModelCommand',
        'currentClassName' => 'Illuminate\\Database\\Console\\ShowModelCommand',
        'aliasName' => NULL,
      ),
      'display' => 
      array (
        'name' => 'display',
        'parameters' => 
        array (
          'modelData' => 
          array (
            'name' => 'modelData',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Database\\Eloquent\\ModelInfo',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 65,
            'endLine' => 65,
            'startColumn' => 32,
            'endColumn' => 51,
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
 * Render the model information.
 *
 * @return void
 */',
        'startLine' => 65,
        'endLine' => 70,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Console',
        'declaringClassName' => 'Illuminate\\Database\\Console\\ShowModelCommand',
        'implementingClassName' => 'Illuminate\\Database\\Console\\ShowModelCommand',
        'currentClassName' => 'Illuminate\\Database\\Console\\ShowModelCommand',
        'aliasName' => NULL,
      ),
      'displayJson' => 
      array (
        'name' => 'displayJson',
        'parameters' => 
        array (
          'modelData' => 
          array (
            'name' => 'modelData',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Database\\Eloquent\\ModelInfo',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 77,
            'endLine' => 77,
            'startColumn' => 36,
            'endColumn' => 55,
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
 * Render the model information as JSON.
 *
 * @return void
 */',
        'startLine' => 77,
        'endLine' => 82,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Console',
        'declaringClassName' => 'Illuminate\\Database\\Console\\ShowModelCommand',
        'implementingClassName' => 'Illuminate\\Database\\Console\\ShowModelCommand',
        'currentClassName' => 'Illuminate\\Database\\Console\\ShowModelCommand',
        'aliasName' => NULL,
      ),
      'displayCli' => 
      array (
        'name' => 'displayCli',
        'parameters' => 
        array (
          'modelData' => 
          array (
            'name' => 'modelData',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Database\\Eloquent\\ModelInfo',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 89,
            'endLine' => 89,
            'startColumn' => 35,
            'endColumn' => 54,
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
 * Render the model information for the CLI.
 *
 * @return void
 */',
        'startLine' => 89,
        'endLine' => 171,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Console',
        'declaringClassName' => 'Illuminate\\Database\\Console\\ShowModelCommand',
        'implementingClassName' => 'Illuminate\\Database\\Console\\ShowModelCommand',
        'currentClassName' => 'Illuminate\\Database\\Console\\ShowModelCommand',
        'aliasName' => NULL,
      ),
      'promptForMissingArgumentsUsing' => 
      array (
        'name' => 'promptForMissingArgumentsUsing',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Prompt for missing input arguments using the returned questions.
 *
 * @return array<string, \\Closure(): string>
 */',
        'startLine' => 178,
        'endLine' => 183,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Console',
        'declaringClassName' => 'Illuminate\\Database\\Console\\ShowModelCommand',
        'implementingClassName' => 'Illuminate\\Database\\Console\\ShowModelCommand',
        'currentClassName' => 'Illuminate\\Database\\Console\\ShowModelCommand',
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