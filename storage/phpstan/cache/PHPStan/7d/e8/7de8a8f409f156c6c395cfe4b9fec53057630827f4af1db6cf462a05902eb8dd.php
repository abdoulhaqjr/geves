<?php declare(strict_types = 1);

// osfsl-C:/Users/TOURE/Herd/gesco/vendor/composer/../laravel/framework/src/Illuminate/Foundation/Console/RouteListCommand.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Foundation\Console\RouteListCommand
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-c15c96d806f6406643e35e5442780692934f1d4ffd6e3aeb7ff45f7df5b4156c-8.4.24-6.70.0.3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'filename' => 'C:/Users/TOURE/Herd/gesco/vendor/composer/../laravel/framework/src/Illuminate/Foundation/Console/RouteListCommand.php',
      ),
    ),
    'namespace' => 'Illuminate\\Foundation\\Console',
    'name' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
    'shortName' => 'RouteListCommand',
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
            'code' => '\'route:list\'',
            'attributes' => 
            array (
              'startLine' => 20,
              'endLine' => 20,
              'startTokenPos' => 83,
              'startFilePos' => 522,
              'endTokenPos' => 83,
              'endFilePos' => 533,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 20,
    'endLine' => 546,
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
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'name' => 'signature',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'route:list
                    {--json : Output the route list as JSON}
                    {--method= : Filter the routes by method}
                    {--action= : Filter the routes by action}
                    {--name= : Filter the routes by name}
                    {--domain= : Filter the routes by domain}
                    {--middleware= : Filter the routes by middleware}
                    {--path= : Only show routes matching the given path pattern}
                    {--except-path= : Do not display the routes matching the given path pattern}
                    {--r|reverse : Reverse the ordering of the routes}
                    {--sort=uri : The column (domain, method, uri, name, action, middleware, definition) to sort by}
                    {--except-vendor : Do not display routes defined by vendor packages}
                    {--only-vendor : Only display routes defined by vendor packages}\'',
          'attributes' => 
          array (
            'startLine' => 28,
            'endLine' => 40,
            'startTokenPos' => 105,
            'startFilePos' => 701,
            'endTokenPos' => 105,
            'endFilePos' => 1627,
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
        'startLine' => 28,
        'endLine' => 40,
        'startColumn' => 5,
        'endColumn' => 86,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'description' => 
      array (
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'name' => 'description',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'List all registered routes\'',
          'attributes' => 
          array (
            'startLine' => 47,
            'endLine' => 47,
            'startTokenPos' => 116,
            'startFilePos' => 1742,
            'endTokenPos' => 116,
            'endFilePos' => 1769,
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
        'endColumn' => 58,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'router' => 
      array (
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'name' => 'router',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The router instance.
 *
 * @var \\Illuminate\\Routing\\Router
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 54,
        'endLine' => 54,
        'startColumn' => 5,
        'endColumn' => 22,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'headers' => 
      array (
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'name' => 'headers',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'Domain\', \'Method\', \'URI\', \'Name\', \'Action\', \'Middleware\', \'Path\']',
          'attributes' => 
          array (
            'startLine' => 61,
            'endLine' => 61,
            'startTokenPos' => 134,
            'startFilePos' => 1998,
            'endTokenPos' => 154,
            'endFilePos' => 2064,
          ),
        ),
        'docComment' => '/**
 * The table headers for the command.
 *
 * @var string[]
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 61,
        'endLine' => 61,
        'startColumn' => 5,
        'endColumn' => 93,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'terminalWidthResolver' => 
      array (
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'name' => 'terminalWidthResolver',
        'modifiers' => 18,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The terminal width resolver callback.
 *
 * @var \\Closure|null
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 68,
        'endLine' => 68,
        'startColumn' => 5,
        'endColumn' => 44,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'verbColors' => 
      array (
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'name' => 'verbColors',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'ANY\' => \'red\', \'GET\' => \'blue\', \'HEAD\' => \'#6C7280\', \'OPTIONS\' => \'#6C7280\', \'POST\' => \'yellow\', \'PUT\' => \'yellow\', \'PATCH\' => \'yellow\', \'DELETE\' => \'red\']',
          'attributes' => 
          array (
            'startLine' => 75,
            'endLine' => 84,
            'startTokenPos' => 174,
            'startFilePos' => 2317,
            'endTokenPos' => 232,
            'endFilePos' => 2544,
          ),
        ),
        'docComment' => '/**
 * The verb colors for the command.
 *
 * @var array
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 75,
        'endLine' => 84,
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
      '__construct' => 
      array (
        'name' => '__construct',
        'parameters' => 
        array (
          'router' => 
          array (
            'name' => 'router',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Routing\\Router',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 91,
            'endLine' => 91,
            'startColumn' => 33,
            'endColumn' => 46,
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
 * Create a new route command instance.
 *
 * @param  \\Illuminate\\Routing\\Router  $router
 */',
        'startLine' => 91,
        'endLine' => 96,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
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
        'startLine' => 103,
        'endLine' => 118,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'aliasName' => NULL,
      ),
      'getRoutes' => 
      array (
        'name' => 'getRoutes',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Compile the routes into a displayable format.
 *
 * @return array
 */',
        'startLine' => 125,
        'endLine' => 143,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'aliasName' => NULL,
      ),
      'getRouteInformation' => 
      array (
        'name' => 'getRouteInformation',
        'parameters' => 
        array (
          'route' => 
          array (
            'name' => 'route',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Routing\\Route',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 151,
            'endLine' => 151,
            'startColumn' => 44,
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
 * Get the route information for a given route.
 *
 * @param  \\Illuminate\\Routing\\Route  $route
 * @return array
 */',
        'startLine' => 151,
        'endLine' => 163,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'aliasName' => NULL,
      ),
      'sortRoutes' => 
      array (
        'name' => 'sortRoutes',
        'parameters' => 
        array (
          'sort' => 
          array (
            'name' => 'sort',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 172,
            'endLine' => 172,
            'startColumn' => 35,
            'endColumn' => 39,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'routes' => 
          array (
            'name' => 'routes',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 172,
            'endLine' => 172,
            'startColumn' => 42,
            'endColumn' => 54,
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
 * Sort the routes by a given element.
 *
 * @param  string  $sort
 * @param  array  $routes
 * @return array
 */',
        'startLine' => 172,
        'endLine' => 185,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'aliasName' => NULL,
      ),
      'pluckColumns' => 
      array (
        'name' => 'pluckColumns',
        'parameters' => 
        array (
          'routes' => 
          array (
            'name' => 'routes',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 193,
            'endLine' => 193,
            'startColumn' => 37,
            'endColumn' => 49,
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
 * Remove unnecessary columns from the routes.
 *
 * @param  array  $routes
 * @return array
 */',
        'startLine' => 193,
        'endLine' => 198,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'aliasName' => NULL,
      ),
      'displayRoutes' => 
      array (
        'name' => 'displayRoutes',
        'parameters' => 
        array (
          'routes' => 
          array (
            'name' => 'routes',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 206,
            'endLine' => 206,
            'startColumn' => 38,
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
 * Display the route information on the console.
 *
 * @param  array  $routes
 * @return void
 */',
        'startLine' => 206,
        'endLine' => 213,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'aliasName' => NULL,
      ),
      'resolveUri' => 
      array (
        'name' => 'resolveUri',
        'parameters' => 
        array (
          'route' => 
          array (
            'name' => 'route',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Routing\\Route',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 221,
            'endLine' => 221,
            'startColumn' => 35,
            'endColumn' => 46,
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
 * Get the URI for the given route, including any binding fields.
 *
 * @param  \\Illuminate\\Routing\\Route  $route
 * @return string
 */',
        'startLine' => 221,
        'endLine' => 230,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'aliasName' => NULL,
      ),
      'getMiddleware' => 
      array (
        'name' => 'getMiddleware',
        'parameters' => 
        array (
          'route' => 
          array (
            'name' => 'route',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 238,
            'endLine' => 238,
            'startColumn' => 38,
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
 * Get the middleware for the route.
 *
 * @param  \\Illuminate\\Routing\\Route  $route
 * @return string
 */',
        'startLine' => 238,
        'endLine' => 243,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'aliasName' => NULL,
      ),
      'getClosurePath' => 
      array (
        'name' => 'getClosurePath',
        'parameters' => 
        array (
          'route' => 
          array (
            'name' => 'route',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Routing\\Route',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 253,
            'endLine' => 253,
            'startColumn' => 39,
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
 * Get the file path and line number for a closure-based route.
 *
 * @param  \\Illuminate\\Routing\\Route  $route
 * @return string|null
 *
 * @throws \\ReflectionException
 */',
        'startLine' => 253,
        'endLine' => 264,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'aliasName' => NULL,
      ),
      'isVendorRoute' => 
      array (
        'name' => 'isVendorRoute',
        'parameters' => 
        array (
          'route' => 
          array (
            'name' => 'route',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Routing\\Route',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 272,
            'endLine' => 272,
            'startColumn' => 38,
            'endColumn' => 49,
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
 * Determine if the route has been defined outside of the application.
 *
 * @param  \\Illuminate\\Routing\\Route  $route
 * @return bool
 */',
        'startLine' => 272,
        'endLine' => 292,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'aliasName' => NULL,
      ),
      'isFrameworkController' => 
      array (
        'name' => 'isFrameworkController',
        'parameters' => 
        array (
          'route' => 
          array (
            'name' => 'route',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Routing\\Route',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 300,
            'endLine' => 300,
            'startColumn' => 46,
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
 * Determine if the route uses a framework controller.
 *
 * @param  \\Illuminate\\Routing\\Route  $route
 * @return bool
 */',
        'startLine' => 300,
        'endLine' => 306,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'aliasName' => NULL,
      ),
      'filterRoute' => 
      array (
        'name' => 'filterRoute',
        'parameters' => 
        array (
          'route' => 
          array (
            'name' => 'route',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 314,
            'endLine' => 314,
            'startColumn' => 36,
            'endColumn' => 47,
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
 * Filter the route by URI and / or name.
 *
 * @param  array  $route
 * @return array|null
 */',
        'startLine' => 314,
        'endLine' => 336,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'aliasName' => NULL,
      ),
      'getHeaders' => 
      array (
        'name' => 'getHeaders',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the table headers for the visible columns.
 *
 * @return array
 */',
        'startLine' => 343,
        'endLine' => 346,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'aliasName' => NULL,
      ),
      'getColumns' => 
      array (
        'name' => 'getColumns',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the column names to show (lowercase table headers).
 *
 * @return array
 */',
        'startLine' => 353,
        'endLine' => 356,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'aliasName' => NULL,
      ),
      'parseColumns' => 
      array (
        'name' => 'parseColumns',
        'parameters' => 
        array (
          'columns' => 
          array (
            'name' => 'columns',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 364,
            'endLine' => 364,
            'startColumn' => 37,
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
 * Parse the column list.
 *
 * @param  array  $columns
 * @return array
 */',
        'startLine' => 364,
        'endLine' => 377,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'aliasName' => NULL,
      ),
      'asJson' => 
      array (
        'name' => 'asJson',
        'parameters' => 
        array (
          'routes' => 
          array (
            'name' => 'routes',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 385,
            'endLine' => 385,
            'startColumn' => 31,
            'endColumn' => 37,
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
 * Convert the given routes to JSON.
 *
 * @param  \\Illuminate\\Support\\Collection  $routes
 * @return string
 */',
        'startLine' => 385,
        'endLine' => 395,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'aliasName' => NULL,
      ),
      'forCli' => 
      array (
        'name' => 'forCli',
        'parameters' => 
        array (
          'routes' => 
          array (
            'name' => 'routes',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 403,
            'endLine' => 403,
            'startColumn' => 31,
            'endColumn' => 37,
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
 * Convert the given routes to regular CLI output.
 *
 * @param  \\Illuminate\\Support\\Collection  $routes
 * @return array
 */',
        'startLine' => 403,
        'endLine' => 464,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'aliasName' => NULL,
      ),
      'formatActionForCli' => 
      array (
        'name' => 'formatActionForCli',
        'parameters' => 
        array (
          'route' => 
          array (
            'name' => 'route',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 472,
            'endLine' => 472,
            'startColumn' => 43,
            'endColumn' => 48,
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
 * Get the formatted action for display on the CLI.
 *
 * @param  array  $route
 * @return string|null
 */',
        'startLine' => 472,
        'endLine' => 504,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'aliasName' => NULL,
      ),
      'determineRouteCountOutput' => 
      array (
        'name' => 'determineRouteCountOutput',
        'parameters' => 
        array (
          'routes' => 
          array (
            'name' => 'routes',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 513,
            'endLine' => 513,
            'startColumn' => 50,
            'endColumn' => 56,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'terminalWidth' => 
          array (
            'name' => 'terminalWidth',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 513,
            'endLine' => 513,
            'startColumn' => 59,
            'endColumn' => 72,
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
 * Determine and return the output for displaying the number of routes in the CLI output.
 *
 * @param  \\Illuminate\\Support\\Collection  $routes
 * @param  int  $terminalWidth
 * @return string
 */',
        'startLine' => 513,
        'endLine' => 522,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'aliasName' => NULL,
      ),
      'getTerminalWidth' => 
      array (
        'name' => 'getTerminalWidth',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the terminal width.
 *
 * @return int
 */',
        'startLine' => 529,
        'endLine' => 534,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'aliasName' => NULL,
      ),
      'resolveTerminalWidthUsing' => 
      array (
        'name' => 'resolveTerminalWidthUsing',
        'parameters' => 
        array (
          'resolver' => 
          array (
            'name' => 'resolver',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 542,
            'endLine' => 542,
            'startColumn' => 54,
            'endColumn' => 62,
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
 * Set a callback that should be used when resolving the terminal width.
 *
 * @param  \\Closure|null  $resolver
 * @return void
 */',
        'startLine' => 542,
        'endLine' => 545,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\RouteListCommand',
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