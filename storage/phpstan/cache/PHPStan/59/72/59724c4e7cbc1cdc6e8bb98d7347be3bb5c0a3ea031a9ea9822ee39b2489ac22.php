<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Payments\Gateways\ManualGateway.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Payments\Gateways\ManualGateway
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-dc41cc16eb10c74ead514614436ebe1e7000a997c4edd77a19d0c82d65dabe8f',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Payments\\Gateways\\ManualGateway',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Payments/Gateways/ManualGateway.php',
      ),
    ),
    'namespace' => 'App\\Payments\\Gateways',
    'name' => 'App\\Payments\\Gateways\\ManualGateway',
    'shortName' => 'ManualGateway',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Paiement hors ligne : le participant règle sur place ou par virement ; le
 * paiement reste « en attente » jusqu\'à confirmation par l\'administration.
 * Toujours disponible (aucun identifiant requis).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 13,
    'endLine' => 38,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'App\\Payments\\Contracts\\PaymentGateway',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'key' => 
      array (
        'name' => 'key',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 15,
        'endLine' => 18,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Payments\\Gateways',
        'declaringClassName' => 'App\\Payments\\Gateways\\ManualGateway',
        'implementingClassName' => 'App\\Payments\\Gateways\\ManualGateway',
        'currentClassName' => 'App\\Payments\\Gateways\\ManualGateway',
        'aliasName' => NULL,
      ),
      'label' => 
      array (
        'name' => 'label',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 20,
        'endLine' => 23,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Payments\\Gateways',
        'declaringClassName' => 'App\\Payments\\Gateways\\ManualGateway',
        'implementingClassName' => 'App\\Payments\\Gateways\\ManualGateway',
        'currentClassName' => 'App\\Payments\\Gateways\\ManualGateway',
        'aliasName' => NULL,
      ),
      'isAvailable' => 
      array (
        'name' => 'isAvailable',
        'parameters' => 
        array (
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
        'docComment' => NULL,
        'startLine' => 25,
        'endLine' => 28,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Payments\\Gateways',
        'declaringClassName' => 'App\\Payments\\Gateways\\ManualGateway',
        'implementingClassName' => 'App\\Payments\\Gateways\\ManualGateway',
        'currentClassName' => 'App\\Payments\\Gateways\\ManualGateway',
        'aliasName' => NULL,
      ),
      'initiate' => 
      array (
        'name' => 'initiate',
        'parameters' => 
        array (
          'payment' => 
          array (
            'name' => 'payment',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Payment',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 30,
            'endLine' => 30,
            'startColumn' => 30,
            'endColumn' => 45,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 30,
        'endLine' => 37,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Payments\\Gateways',
        'declaringClassName' => 'App\\Payments\\Gateways\\ManualGateway',
        'implementingClassName' => 'App\\Payments\\Gateways\\ManualGateway',
        'currentClassName' => 'App\\Payments\\Gateways\\ManualGateway',
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