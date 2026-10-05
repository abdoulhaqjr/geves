<?php declare(strict_types = 1);

// osfsl-C:/Users/TOURE/Herd/gesco/vendor/composer/../laravel/framework/src/Illuminate/Queue/Console/ClearCommand.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Queue\Console\ClearCommand
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-57c45c5a0018b33628dec0ac4190fadf059b9cc970d1a07c20e9a03710780783-8.4.24-6.70.0.3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Queue\\Console\\ClearCommand',
        'filename' => 'C:/Users/TOURE/Herd/gesco/vendor/composer/../laravel/framework/src/Illuminate/Queue/Console/ClearCommand.php',
      ),
    ),
    'namespace' => 'Illuminate\\Queue\\Console',
    'name' => 'Illuminate\\Queue\\Console\\ClearCommand',
    'shortName' => 'ClearCommand',
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
            'code' => '\'queue:clear\'',
            'attributes' => 
            array (
              'startLine' => 14,
              'endLine' => 14,
              'startTokenPos' => 53,
              'startFilePos' => 355,
              'endTokenPos' => 53,
              'endFilePos' => 367,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 14,
    'endLine' => 93,
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
        'declaringClassName' => 'Illuminate\\Queue\\Console\\ClearCommand',
        'implementingClassName' => 'Illuminate\\Queue\\Console\\ClearCommand',
        'name' => 'signature',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'queue:clear
                    {connection? : The name of the queue connection to clear}
                    {--queue= : The names of the queues to clear}
                    {--force : Force the operation to run when in production}\'',
          'attributes' => 
          array (
            'startLine' => 24,
            'endLine' => 27,
            'startTokenPos' => 83,
            'startFilePos' => 572,
            'endTokenPos' => 83,
            'endFilePos' => 806,
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
        'startLine' => 24,
        'endLine' => 27,
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
        'declaringClassName' => 'Illuminate\\Queue\\Console\\ClearCommand',
        'implementingClassName' => 'Illuminate\\Queue\\Console\\ClearCommand',
        'name' => 'description',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'Delete all of the jobs from the specified queues\'',
          'attributes' => 
          array (
            'startLine' => 34,
            'endLine' => 34,
            'startTokenPos' => 94,
            'startFilePos' => 921,
            'endTokenPos' => 94,
            'endFilePos' => 970,
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
        'startLine' => 34,
        'endLine' => 34,
        'startColumn' => 5,
        'endColumn' => 80,
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
 * @return int|null
 */',
        'startLine' => 41,
        'endLine' => 78,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Queue\\Console',
        'declaringClassName' => 'Illuminate\\Queue\\Console\\ClearCommand',
        'implementingClassName' => 'Illuminate\\Queue\\Console\\ClearCommand',
        'currentClassName' => 'Illuminate\\Queue\\Console\\ClearCommand',
        'aliasName' => NULL,
      ),
      'getQueue' => 
      array (
        'name' => 'getQueue',
        'parameters' => 
        array (
          'connection' => 
          array (
            'name' => 'connection',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 86,
            'endLine' => 86,
            'startColumn' => 33,
            'endColumn' => 43,
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
 * Get the queue name to clear.
 *
 * @param  string  $connection
 * @return string
 */',
        'startLine' => 86,
        'endLine' => 92,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Queue\\Console',
        'declaringClassName' => 'Illuminate\\Queue\\Console\\ClearCommand',
        'implementingClassName' => 'Illuminate\\Queue\\Console\\ClearCommand',
        'currentClassName' => 'Illuminate\\Queue\\Console\\ClearCommand',
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