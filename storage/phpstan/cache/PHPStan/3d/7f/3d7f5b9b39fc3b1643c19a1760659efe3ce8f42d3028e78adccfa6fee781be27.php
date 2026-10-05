<?php declare(strict_types = 1);

// osfsl-C:/Users/TOURE/Herd/gesco/vendor/composer/../firebase/php-jwt/src/Key.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Firebase\JWT\Key
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-7ce0ce63763a4c1104279c50eec08b920214f62426774aea7d77a26f084e1023-8.4.24-6.70.0.3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Firebase\\JWT\\Key',
        'filename' => 'C:/Users/TOURE/Herd/gesco/vendor/composer/../firebase/php-jwt/src/Key.php',
      ),
    ),
    'namespace' => 'Firebase\\JWT',
    'name' => 'Firebase\\JWT\\Key',
    'shortName' => 'Key',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 10,
    'endLine' => 54,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
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
      'keyMaterial' => 
      array (
        'declaringClassName' => 'Firebase\\JWT\\Key',
        'implementingClassName' => 'Firebase\\JWT\\Key',
        'name' => 'keyMaterial',
        'modifiers' => 4,
        'type' => NULL,
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'SensitiveParameter',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'startLine' => 17,
        'endLine' => 17,
        'startColumn' => 9,
        'endColumn' => 51,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'algorithm' => 
      array (
        'declaringClassName' => 'Firebase\\JWT\\Key',
        'implementingClassName' => 'Firebase\\JWT\\Key',
        'name' => 'algorithm',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 18,
        'endLine' => 18,
        'startColumn' => 9,
        'endColumn' => 33,
        'isPromoted' => true,
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
          'keyMaterial' => 
          array (
            'name' => 'keyMaterial',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
              0 => 
              array (
                'name' => 'SensitiveParameter',
                'isRepeated' => false,
                'arguments' => 
                array (
                ),
              ),
            ),
            'startLine' => 17,
            'endLine' => 17,
            'startColumn' => 9,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'algorithm' => 
          array (
            'name' => 'algorithm',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 18,
            'endLine' => 18,
            'startColumn' => 9,
            'endColumn' => 33,
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
 * @param string|OpenSSLAsymmetricKey|OpenSSLCertificate $keyMaterial
 * @param string $algorithm
 */',
        'startLine' => 16,
        'endLine' => 35,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Firebase\\JWT',
        'declaringClassName' => 'Firebase\\JWT\\Key',
        'implementingClassName' => 'Firebase\\JWT\\Key',
        'currentClassName' => 'Firebase\\JWT\\Key',
        'aliasName' => NULL,
      ),
      'getAlgorithm' => 
      array (
        'name' => 'getAlgorithm',
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
        'docComment' => '/**
 * Return the algorithm valid for this key
 *
 * @return string
 */',
        'startLine' => 42,
        'endLine' => 45,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Firebase\\JWT',
        'declaringClassName' => 'Firebase\\JWT\\Key',
        'implementingClassName' => 'Firebase\\JWT\\Key',
        'currentClassName' => 'Firebase\\JWT\\Key',
        'aliasName' => NULL,
      ),
      'getKeyMaterial' => 
      array (
        'name' => 'getKeyMaterial',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return string|OpenSSLAsymmetricKey|OpenSSLCertificate
 */',
        'startLine' => 50,
        'endLine' => 53,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Firebase\\JWT',
        'declaringClassName' => 'Firebase\\JWT\\Key',
        'implementingClassName' => 'Firebase\\JWT\\Key',
        'currentClassName' => 'Firebase\\JWT\\Key',
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