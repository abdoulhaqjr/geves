<?php declare(strict_types = 1);

// phpinternal-PHPStan\BetterReflection\Reflection\ReflectionFunction-parse_str
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-dev-master@709e512-8.4.24',
   'data' => 
  array (
    'name' => 'parse_str',
    'parameters' => 
    array (
      'string' => 
      array (
        'name' => 'string',
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
        'isPromoted' => false,
        'attributes' => 
        array (
        ),
        'startLine' => 18,
        'endLine' => 18,
        'startColumn' => 9,
        'endColumn' => 22,
        'parameterIndex' => 0,
        'isOptional' => false,
      ),
      'result' => 
      array (
        'name' => 'result',
        'default' => NULL,
        'type' => NULL,
        'isVariadic' => false,
        'byRef' => true,
        'isPromoted' => false,
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\PhpStormStubsElementAvailable',
            'isRepeated' => false,
            'arguments' => 
            array (
              'from' => 
              array (
                'code' => '\'8.0\'',
                'attributes' => 
                array (
                  'startLine' => 19,
                  'endLine' => 19,
                  'startTokenPos' => 24,
                  'startFilePos' => 570,
                  'endTokenPos' => 24,
                  'endFilePos' => 574,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 19,
        'endLine' => 20,
        'startColumn' => 9,
        'endColumn' => 16,
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
    ),
    'docComment' => '/**
 * Parses the string into variables
 * @link https://php.net/manual/en/function.parse-str.php
 * @param string $string <p>
 * The input string.
 * </p>
 * @param array &$result <p>
 * If the second parameter arr is present,
 * variables are stored in this variable as array elements instead.<br/>
 * Since 7.2.0 this parameter is not optional.
 * </p>
 * @return void
 */',
    'startLine' => 17,
    'endLine' => 23,
    'startColumn' => 5,
    'endColumn' => 5,
    'couldThrow' => false,
    'isClosure' => false,
    'isGenerator' => false,
    'isVariadic' => false,
    'isStatic' => false,
    'namespace' => NULL,
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\InternalLocatedSource',
      'data' => 
      array (
        'name' => 'parse_str',
        'filename' => 'phpstorm-stubs:standard/standard_2.stub',
        'extensionName' => 'standard',
        'aliasName' => NULL,
      ),
    ),
  ),
));