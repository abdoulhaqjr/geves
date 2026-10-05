<?php declare(strict_types = 1);

// osfsl-C:/Users/TOURE/Herd/gesco/vendor/composer/../laravel/framework/src/Illuminate/Foundation/Console/ServeCommand.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Foundation\Console\ServeCommand
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-681db5e7b815cd092e63c5a3599e19a839e9087ae2bde0ab0ad1824709ecb316-8.4.24-6.70.0.3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'filename' => 'C:/Users/TOURE/Herd/gesco/vendor/composer/../laravel/framework/src/Illuminate/Foundation/Console/ServeCommand.php',
      ),
    ),
    'namespace' => 'Illuminate\\Foundation\\Console',
    'name' => 'Illuminate\\Foundation\\Console\\ServeCommand',
    'shortName' => 'ServeCommand',
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
            'code' => '\'serve\'',
            'attributes' => 
            array (
              'startLine' => 19,
              'endLine' => 19,
              'startTokenPos' => 77,
              'startFilePos' => 544,
              'endTokenPos' => 77,
              'endFilePos' => 550,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 19,
    'endLine' => 451,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Console\\Command',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
      0 => 'Illuminate\\Support\\InteractsWithTime',
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'signature' => 
      array (
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'name' => 'signature',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'serve
                    {--host= : The host address to serve the application on}
                    {--port= : The port to serve the application on}
                    {--tries=10 : The max number of ports to attempt to serve from}
                    {--no-reload : Do not reload the development server on .env file changes}\'',
          'attributes' => 
          array (
            'startLine' => 29,
            'endLine' => 33,
            'startTokenPos' => 104,
            'startFilePos' => 742,
            'endTokenPos' => 104,
            'endFilePos' => 1072,
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
        'startLine' => 29,
        'endLine' => 33,
        'startColumn' => 5,
        'endColumn' => 95,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'description' => 
      array (
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'name' => 'description',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'Serve the application on the PHP development server\'',
          'attributes' => 
          array (
            'startLine' => 40,
            'endLine' => 40,
            'startTokenPos' => 115,
            'startFilePos' => 1187,
            'endTokenPos' => 115,
            'endFilePos' => 1239,
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
        'startLine' => 40,
        'endLine' => 40,
        'startColumn' => 5,
        'endColumn' => 83,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'phpServerWorkers' => 
      array (
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'name' => 'phpServerWorkers',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '1',
          'attributes' => 
          array (
            'startLine' => 47,
            'endLine' => 47,
            'startTokenPos' => 126,
            'startFilePos' => 1375,
            'endTokenPos' => 126,
            'endFilePos' => 1375,
          ),
        ),
        'docComment' => '/**
 * The number of PHP CLI server workers.
 *
 * @var int<2, max>|false
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 47,
        'endLine' => 47,
        'startColumn' => 5,
        'endColumn' => 36,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'portOffset' => 
      array (
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'name' => 'portOffset',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '0',
          'attributes' => 
          array (
            'startLine' => 54,
            'endLine' => 54,
            'startTokenPos' => 137,
            'startFilePos' => 1478,
            'endTokenPos' => 137,
            'endFilePos' => 1478,
          ),
        ),
        'docComment' => '/**
 * The current port offset.
 *
 * @var int
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
      'outputBuffer' => 
      array (
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'name' => 'outputBuffer',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'\'',
          'attributes' => 
          array (
            'startLine' => 61,
            'endLine' => 61,
            'startTokenPos' => 148,
            'startFilePos' => 1610,
            'endTokenPos' => 148,
            'endFilePos' => 1611,
          ),
        ),
        'docComment' => '/**
 * The list of lines that are pending to be output.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 61,
        'endLine' => 61,
        'startColumn' => 5,
        'endColumn' => 33,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'requestsPool' => 
      array (
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'name' => 'requestsPool',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The list of requests being handled and their start time.
 *
 * @var array<int, \\Illuminate\\Support\\Carbon>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 68,
        'endLine' => 68,
        'startColumn' => 5,
        'endColumn' => 28,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'serverRunningHasBeenDisplayed' => 
      array (
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'name' => 'serverRunningHasBeenDisplayed',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'false',
          'attributes' => 
          array (
            'startLine' => 75,
            'endLine' => 75,
            'startTokenPos' => 166,
            'startFilePos' => 1952,
            'endTokenPos' => 166,
            'endFilePos' => 1956,
          ),
        ),
        'docComment' => '/**
 * Indicates if the "Server running on..." output message has been displayed.
 *
 * @var bool
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 75,
        'endLine' => 75,
        'startColumn' => 5,
        'endColumn' => 53,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'passthroughVariables' => 
      array (
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'name' => 'passthroughVariables',
        'modifiers' => 17,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'APP_ENV\', \'HERD_PHP_81_INI_SCAN_DIR\', \'HERD_PHP_82_INI_SCAN_DIR\', \'HERD_PHP_83_INI_SCAN_DIR\', \'HERD_PHP_84_INI_SCAN_DIR\', \'HERD_PHP_85_INI_SCAN_DIR\', \'IGNITION_LOCAL_SITES_PATH\', \'LARAVEL_SAIL\', \'PATH\', \'PHP_IDE_CONFIG\', \'SYSTEMROOT\', \'XDEBUG_CONFIG\', \'XDEBUG_MODE\', \'XDEBUG_SESSION\']',
          'attributes' => 
          array (
            'startLine' => 82,
            'endLine' => 97,
            'startTokenPos' => 179,
            'startFilePos' => 2146,
            'endTokenPos' => 223,
            'endFilePos' => 2550,
          ),
        ),
        'docComment' => '/**
 * The environment variables that should be passed from host machine to the PHP server process.
 *
 * @var string[]
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 82,
        'endLine' => 97,
        'startColumn' => 5,
        'endColumn' => 6,
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
        'startLine' => 99,
        'endLine' => 106,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'aliasName' => NULL,
      ),
      'initialize' => 
      array (
        'name' => 'initialize',
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
            'startLine' => 110,
            'endLine' => 110,
            'startColumn' => 35,
            'endColumn' => 55,
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
            'startLine' => 110,
            'endLine' => 110,
            'startColumn' => 58,
            'endColumn' => 80,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
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
        'docComment' => '/** {@inheritdoc} */',
        'startLine' => 109,
        'endLine' => 129,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
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
 *
 * @throws \\Exception
 */',
        'startLine' => 138,
        'endLine' => 185,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'aliasName' => NULL,
      ),
      'startProcess' => 
      array (
        'name' => 'startProcess',
        'parameters' => 
        array (
          'hasEnvironment' => 
          array (
            'name' => 'hasEnvironment',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 193,
            'endLine' => 193,
            'startColumn' => 37,
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
 * Start a new server process.
 *
 * @param  bool  $hasEnvironment
 * @return \\Symfony\\Component\\Process\\Process
 */',
        'startLine' => 193,
        'endLine' => 214,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'aliasName' => NULL,
      ),
      'serverCommand' => 
      array (
        'name' => 'serverCommand',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the full server command.
 *
 * @return array
 */',
        'startLine' => 221,
        'endLine' => 233,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'aliasName' => NULL,
      ),
      'host' => 
      array (
        'name' => 'host',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the host for the command.
 *
 * @return string
 */',
        'startLine' => 240,
        'endLine' => 245,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'aliasName' => NULL,
      ),
      'port' => 
      array (
        'name' => 'port',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the port for the command.
 *
 * @return string
 */',
        'startLine' => 252,
        'endLine' => 263,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'aliasName' => NULL,
      ),
      'getHostAndPort' => 
      array (
        'name' => 'getHostAndPort',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the host and port from the host option string.
 *
 * @return array
 */',
        'startLine' => 270,
        'endLine' => 285,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'aliasName' => NULL,
      ),
      'canTryAnotherPort' => 
      array (
        'name' => 'canTryAnotherPort',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Check if the command has reached its maximum number of port tries.
 *
 * @return bool
 */',
        'startLine' => 292,
        'endLine' => 296,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'aliasName' => NULL,
      ),
      'shouldPassThroughEnvironmentVariable' => 
      array (
        'name' => 'shouldPassThroughEnvironmentVariable',
        'parameters' => 
        array (
          'key' => 
          array (
            'name' => 'key',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 304,
            'endLine' => 304,
            'startColumn' => 61,
            'endColumn' => 64,
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
 * Determine if the environment variable should be passed to the PHP server process.
 *
 * @param  string  $key
 * @return bool
 */',
        'startLine' => 304,
        'endLine' => 311,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'aliasName' => NULL,
      ),
      'handleProcessOutput' => 
      array (
        'name' => 'handleProcessOutput',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Returns a "callable" to handle the process output.
 *
 * @return callable(string, string): void
 */',
        'startLine' => 318,
        'endLine' => 325,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'aliasName' => NULL,
      ),
      'flushOutputBuffer' => 
      array (
        'name' => 'flushOutputBuffer',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Flush the output buffer.
 *
 * @return void
 */',
        'startLine' => 332,
        'endLine' => 412,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'aliasName' => NULL,
      ),
      'getDateFromLine' => 
      array (
        'name' => 'getDateFromLine',
        'parameters' => 
        array (
          'line' => 
          array (
            'name' => 'line',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 420,
            'endLine' => 420,
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
 * Get the date from the given PHP server output.
 *
 * @param  string  $line
 * @return \\Illuminate\\Support\\Carbon
 */',
        'startLine' => 420,
        'endLine' => 431,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'aliasName' => NULL,
      ),
      'getRequestPortFromLine' => 
      array (
        'name' => 'getRequestPortFromLine',
        'parameters' => 
        array (
          'line' => 
          array (
            'name' => 'line',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 441,
            'endLine' => 441,
            'startColumn' => 51,
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
 * Get the request port from the given PHP server output.
 *
 * @param  string  $line
 * @return int
 *
 * @throws \\InvalidArgumentException
 */',
        'startLine' => 441,
        'endLine' => 450,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\ServeCommand',
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