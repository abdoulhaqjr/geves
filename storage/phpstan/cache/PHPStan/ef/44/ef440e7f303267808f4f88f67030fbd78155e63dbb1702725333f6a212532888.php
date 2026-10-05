<?php declare(strict_types = 1);

// phpinternal-PHPStan\BetterReflection\Reflection\ReflectionClass-reflectionparameter
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-dev-master@709e512-8.4.24',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\InternalLocatedSource',
      'data' => 
      array (
        'name' => 'ReflectionParameter',
        'filename' => 'phpstorm-stubs:Reflection/ReflectionParameter.stub',
        'extensionName' => 'Reflection',
        'aliasName' => NULL,
      ),
    ),
    'namespace' => NULL,
    'name' => 'ReflectionParameter',
    'shortName' => 'ReflectionParameter',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * The <b>ReflectionParameter</b> class retrieves
 * information about function\'s or method\'s parameters.
 *
 * @link https://php.net/manual/en/class.reflectionparameter.php
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 10,
    'endLine' => 320,
    'startColumn' => 5,
    'endColumn' => 5,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'Reflector',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'name' => 
      array (
        'declaringClassName' => 'ReflectionParameter',
        'implementingClassName' => 'ReflectionParameter',
        'name' => 'name',
        'modifiers' => 1,
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
        'docComment' => '/**
 * @var string Name of the parameter, same as calling the {@see ReflectionParameter::getName()} method
 */',
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Immutable',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
          1 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '[\'8.1\' => \'string\']',
                'attributes' => 
                array (
                  'startLine' => 16,
                  'endLine' => 16,
                  'startTokenPos' => 27,
                  'startFilePos' => 521,
                  'endTokenPos' => 33,
                  'endFilePos' => 539,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 16,
                  'endLine' => 16,
                  'startTokenPos' => 39,
                  'startFilePos' => 551,
                  'endTokenPos' => 39,
                  'endFilePos' => 552,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 15,
        'endLine' => 17,
        'startColumn' => 9,
        'endColumn' => 28,
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
          'function' => 
          array (
            'name' => 'function',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 28,
            'endLine' => 28,
            'startColumn' => 13,
            'endColumn' => 21,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'param' => 
          array (
            'name' => 'param',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
              'data' => 
              array (
                'types' => 
                array (
                  0 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'string',
                      'isIdentifier' => true,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'int',
                      'isIdentifier' => true,
                    ),
                  ),
                ),
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string|int\']',
                    'attributes' => 
                    array (
                      'startLine' => 29,
                      'endLine' => 29,
                      'startTokenPos' => 65,
                      'startFilePos' => 1169,
                      'endTokenPos' => 71,
                      'endFilePos' => 1191,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 29,
                      'endLine' => 29,
                      'startTokenPos' => 77,
                      'startFilePos' => 1203,
                      'endTokenPos' => 77,
                      'endFilePos' => 1204,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 29,
            'endLine' => 30,
            'startColumn' => 13,
            'endColumn' => 29,
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
 * Construct
 *
 * @link https://php.net/manual/en/reflectionparameter.construct.php
 * @param callable $function The function to reflect parameters from.
 * @param string|int $param Either an integer specifying the position
 * of the parameter (starting with zero), or a the parameter name as string.
 * @throws ReflectionException if the function or parameter does not exist.
 */',
        'startLine' => 27,
        'endLine' => 33,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ReflectionParameter',
        'implementingClassName' => 'ReflectionParameter',
        'currentClassName' => 'ReflectionParameter',
        'aliasName' => NULL,
      ),
      '__toString' => 
      array (
        'name' => '__toString',
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
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '[\'7.0\' => \'string\']',
                'attributes' => 
                array (
                  'startLine' => 40,
                  'endLine' => 40,
                  'startTokenPos' => 98,
                  'startFilePos' => 1547,
                  'endTokenPos' => 104,
                  'endFilePos' => 1565,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 40,
                  'endLine' => 40,
                  'startTokenPos' => 110,
                  'startFilePos' => 1577,
                  'endTokenPos' => 110,
                  'endFilePos' => 1578,
                ),
              ),
            ),
          ),
        ),
        'docComment' => '/**
 * Returns the string representation of the ReflectionParameter object.
 *
 * @link https://php.net/manual/en/reflectionparameter.tostring.php
 * @return string
 */',
        'startLine' => 40,
        'endLine' => 43,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ReflectionParameter',
        'implementingClassName' => 'ReflectionParameter',
        'currentClassName' => 'ReflectionParameter',
        'aliasName' => NULL,
      ),
      'getName' => 
      array (
        'name' => 'getName',
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
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Pure',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
          1 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * Gets parameter name
 *
 * @link https://php.net/manual/en/reflectionparameter.getname.php
 * @return string The name of the reflected parameter.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 51,
        'endLine' => 55,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ReflectionParameter',
        'implementingClassName' => 'ReflectionParameter',
        'currentClassName' => 'ReflectionParameter',
        'aliasName' => NULL,
      ),
      'isPassedByReference' => 
      array (
        'name' => 'isPassedByReference',
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
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Pure',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
          1 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * Checks if passed by reference
 *
 * @link https://php.net/manual/en/reflectionparameter.ispassedbyreference.php
 * @return bool {@see true} if the parameter is passed in by reference, otherwise {@see false}
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 63,
        'endLine' => 67,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ReflectionParameter',
        'implementingClassName' => 'ReflectionParameter',
        'currentClassName' => 'ReflectionParameter',
        'aliasName' => NULL,
      ),
      'canBePassedByValue' => 
      array (
        'name' => 'canBePassedByValue',
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
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * Returns whether this parameter can be passed by value
 *
 * @link https://php.net/manual/en/reflectionparameter.canbepassedbyvalue.php
 * @return bool|null {@see true} if the parameter can be passed by value, {@see false} otherwise.
 * Returns {@see null} in case of an error.
 * @since 5.4
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 77,
        'endLine' => 80,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ReflectionParameter',
        'implementingClassName' => 'ReflectionParameter',
        'currentClassName' => 'ReflectionParameter',
        'aliasName' => NULL,
      ),
      'getDeclaringFunction' => 
      array (
        'name' => 'getDeclaringFunction',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'ReflectionFunctionAbstract',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Pure',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
          1 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * Gets declaring function
 *
 * @link https://php.net/manual/en/reflectionparameter.getdeclaringfunction.php
 * @return ReflectionFunctionAbstract A {@see ReflectionFunctionAbstract} object.
 * @since 5.2
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 89,
        'endLine' => 93,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ReflectionParameter',
        'implementingClassName' => 'ReflectionParameter',
        'currentClassName' => 'ReflectionParameter',
        'aliasName' => NULL,
      ),
      'getDeclaringClass' => 
      array (
        'name' => 'getDeclaringClass',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
          'data' => 
          array (
            'types' => 
            array (
              0 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'ReflectionClass',
                  'isIdentifier' => false,
                ),
              ),
              1 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'null',
                  'isIdentifier' => true,
                ),
              ),
            ),
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Pure',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
          1 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * Gets declaring class
 *
 * @link https://php.net/manual/en/reflectionparameter.getdeclaringclass.php
 * @return ReflectionClass|null A {@see ReflectionClass} object or {@see null} if
 * called on function.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 102,
        'endLine' => 106,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ReflectionParameter',
        'implementingClassName' => 'ReflectionParameter',
        'currentClassName' => 'ReflectionParameter',
        'aliasName' => NULL,
      ),
      'getClass' => 
      array (
        'name' => 'getClass',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
          'data' => 
          array (
            'types' => 
            array (
              0 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'ReflectionClass',
                  'isIdentifier' => false,
                ),
              ),
              1 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'null',
                  'isIdentifier' => true,
                ),
              ),
            ),
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Deprecated',
            'isRepeated' => false,
            'arguments' => 
            array (
              'reason' => 
              array (
                'code' => '"Use ReflectionParameter::getType() and the ReflectionType APIs should be used instead."',
                'attributes' => 
                array (
                  'startLine' => 116,
                  'endLine' => 116,
                  'startTokenPos' => 259,
                  'startFilePos' => 4500,
                  'endTokenPos' => 259,
                  'endFilePos' => 4587,
                ),
              ),
              'since' => 
              array (
                'code' => '"8.0"',
                'attributes' => 
                array (
                  'startLine' => 116,
                  'endLine' => 116,
                  'startTokenPos' => 265,
                  'startFilePos' => 4597,
                  'endTokenPos' => 265,
                  'endFilePos' => 4601,
                ),
              ),
            ),
          ),
          1 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Pure',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
          2 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * Gets the class type hinted for the parameter as a ReflectionClass object.
 *
 * @link https://php.net/manual/en/reflectionparameter.getclass.php
 * @return ReflectionClass|null A {@see ReflectionClass} object.
 * @see ReflectionParameter::getType()
 * @betterReflectionTentativeReturnType
 * @deprecated
 */',
        'startLine' => 116,
        'endLine' => 121,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ReflectionParameter',
        'implementingClassName' => 'ReflectionParameter',
        'currentClassName' => 'ReflectionParameter',
        'aliasName' => NULL,
      ),
      'hasType' => 
      array (
        'name' => 'hasType',
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
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * Checks if the parameter has a type associated with it.
 *
 * @link https://php.net/manual/en/reflectionparameter.hastype.php
 * @return bool {@see true} if a type is specified, {@see false} otherwise.
 * @since 7.0
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 130,
        'endLine' => 133,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ReflectionParameter',
        'implementingClassName' => 'ReflectionParameter',
        'currentClassName' => 'ReflectionParameter',
        'aliasName' => NULL,
      ),
      'getType' => 
      array (
        'name' => 'getType',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
          'data' => 
          array (
            'types' => 
            array (
              0 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'ReflectionType',
                  'isIdentifier' => false,
                ),
              ),
              1 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'null',
                  'isIdentifier' => true,
                ),
              ),
            ),
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Pure',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
          1 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '[\'7.1\' => \'ReflectionNamedType|null\', \'8.0\' => \'ReflectionNamedType|ReflectionUnionType|null\', \'8.1\' => \'ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType|null\']',
                'attributes' => 
                array (
                  'startLine' => 144,
                  'endLine' => 144,
                  'startTokenPos' => 323,
                  'startFilePos' => 5672,
                  'endTokenPos' => 343,
                  'endFilePos' => 5849,
                ),
              ),
              'default' => 
              array (
                'code' => '\'ReflectionType|null\'',
                'attributes' => 
                array (
                  'startLine' => 144,
                  'endLine' => 144,
                  'startTokenPos' => 349,
                  'startFilePos' => 5861,
                  'endTokenPos' => 349,
                  'endFilePos' => 5881,
                ),
              ),
            ),
          ),
          2 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * Gets a parameter\'s type
 *
 * @link https://php.net/manual/en/reflectionparameter.gettype.php
 * @return ReflectionType|null Returns a {@see ReflectionType} object if a
 * parameter type is specified, {@see null} otherwise.
 * @since 7.0
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 143,
        'endLine' => 148,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ReflectionParameter',
        'implementingClassName' => 'ReflectionParameter',
        'currentClassName' => 'ReflectionParameter',
        'aliasName' => NULL,
      ),
      'isArray' => 
      array (
        'name' => 'isArray',
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
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Deprecated',
            'isRepeated' => false,
            'arguments' => 
            array (
              'reason' => 
              array (
                'code' => '"Use ReflectionParameter::getType() and the ReflectionType APIs should be used instead."',
                'attributes' => 
                array (
                  'startLine' => 158,
                  'endLine' => 158,
                  'startTokenPos' => 381,
                  'startFilePos' => 6421,
                  'endTokenPos' => 381,
                  'endFilePos' => 6508,
                ),
              ),
              'since' => 
              array (
                'code' => '"8.0"',
                'attributes' => 
                array (
                  'startLine' => 158,
                  'endLine' => 158,
                  'startTokenPos' => 387,
                  'startFilePos' => 6518,
                  'endTokenPos' => 387,
                  'endFilePos' => 6522,
                ),
              ),
            ),
          ),
          1 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Pure',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
          2 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * Checks if parameter expects an array
 *
 * @link https://php.net/manual/en/reflectionparameter.isarray.php
 * @return bool {@see true} if an array is expected, {@see false} otherwise.
 * @see ReflectionParameter::getType()
 * @betterReflectionTentativeReturnType
 * @deprecated
 */',
        'startLine' => 158,
        'endLine' => 163,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ReflectionParameter',
        'implementingClassName' => 'ReflectionParameter',
        'currentClassName' => 'ReflectionParameter',
        'aliasName' => NULL,
      ),
      'isCallable' => 
      array (
        'name' => 'isCallable',
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
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Deprecated',
            'isRepeated' => false,
            'arguments' => 
            array (
              'reason' => 
              array (
                'code' => '"Use ReflectionParameter::getType() and the ReflectionType APIs should be used instead."',
                'attributes' => 
                array (
                  'startLine' => 175,
                  'endLine' => 175,
                  'startTokenPos' => 422,
                  'startFilePos' => 7175,
                  'endTokenPos' => 422,
                  'endFilePos' => 7262,
                ),
              ),
              'since' => 
              array (
                'code' => '"8.0"',
                'attributes' => 
                array (
                  'startLine' => 175,
                  'endLine' => 175,
                  'startTokenPos' => 428,
                  'startFilePos' => 7272,
                  'endTokenPos' => 428,
                  'endFilePos' => 7276,
                ),
              ),
            ),
          ),
          1 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Pure',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
          2 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * Returns whether parameter MUST be callable
 *
 * @link https://php.net/manual/en/reflectionparameter.iscallable.php
 * @return bool|null Returns {@see true} if the parameter is callable, {@see false}
 * if it is not or {@see null} on failure.
 * @since 5.4
 * @see ReflectionParameter::getType()
 * @betterReflectionTentativeReturnType
 * @deprecated
 */',
        'startLine' => 175,
        'endLine' => 180,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ReflectionParameter',
        'implementingClassName' => 'ReflectionParameter',
        'currentClassName' => 'ReflectionParameter',
        'aliasName' => NULL,
      ),
      'allowsNull' => 
      array (
        'name' => 'allowsNull',
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
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * Checks if null is allowed
 *
 * @link https://php.net/manual/en/reflectionparameter.allowsnull.php
 * @return bool Returns {@see true} if {@see null} is allowed,
 * otherwise {@see false}
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 189,
        'endLine' => 192,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ReflectionParameter',
        'implementingClassName' => 'ReflectionParameter',
        'currentClassName' => 'ReflectionParameter',
        'aliasName' => NULL,
      ),
      'getPosition' => 
      array (
        'name' => 'getPosition',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Pure',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
          1 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * Gets parameter position
 *
 * @link https://php.net/manual/en/reflectionparameter.getposition.php
 * @return int The position of the parameter, left to right, starting at position #0.
 * @since 5.2
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 201,
        'endLine' => 205,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ReflectionParameter',
        'implementingClassName' => 'ReflectionParameter',
        'currentClassName' => 'ReflectionParameter',
        'aliasName' => NULL,
      ),
      'isOptional' => 
      array (
        'name' => 'isOptional',
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
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Pure',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
          1 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * Checks if optional
 *
 * @link https://php.net/manual/en/reflectionparameter.isoptional.php
 * @return bool Returns {@see true} if the parameter is optional, otherwise {@see false}
 * @since 5.0
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 214,
        'endLine' => 218,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ReflectionParameter',
        'implementingClassName' => 'ReflectionParameter',
        'currentClassName' => 'ReflectionParameter',
        'aliasName' => NULL,
      ),
      'isDefaultValueAvailable' => 
      array (
        'name' => 'isDefaultValueAvailable',
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
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Pure',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
          1 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * Checks if a default value is available
 *
 * @link https://php.net/manual/en/reflectionparameter.isdefaultvalueavailable.php
 * @return bool Returns {@see true} if a default value is available, otherwise {@see false}
 * @since 5.0
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 227,
        'endLine' => 231,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ReflectionParameter',
        'implementingClassName' => 'ReflectionParameter',
        'currentClassName' => 'ReflectionParameter',
        'aliasName' => NULL,
      ),
      'getDefaultValue' => 
      array (
        'name' => 'getDefaultValue',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'mixed',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Pure',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
          1 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * Gets default parameter value
 *
 * @link https://php.net/manual/en/reflectionparameter.getdefaultvalue.php
 * @return mixed The parameters default value.
 * @throws ReflectionException if the parameter is not optional
 * @since 5.0
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 241,
        'endLine' => 245,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ReflectionParameter',
        'implementingClassName' => 'ReflectionParameter',
        'currentClassName' => 'ReflectionParameter',
        'aliasName' => NULL,
      ),
      'isDefaultValueConstant' => 
      array (
        'name' => 'isDefaultValueConstant',
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
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Pure',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
          1 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * Returns whether the default value of this parameter is constant
 *
 * @link https://php.net/manual/en/reflectionparameter.isdefaultvalueconstant.php
 * @return bool Returns {@see true} if the default value is constant, and {@see false} otherwise.
 * @since 5.4
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 254,
        'endLine' => 258,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ReflectionParameter',
        'implementingClassName' => 'ReflectionParameter',
        'currentClassName' => 'ReflectionParameter',
        'aliasName' => NULL,
      ),
      'getDefaultValueConstantName' => 
      array (
        'name' => 'getDefaultValueConstantName',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
          'data' => 
          array (
            'types' => 
            array (
              0 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'string',
                  'isIdentifier' => true,
                ),
              ),
              1 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'null',
                  'isIdentifier' => true,
                ),
              ),
            ),
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Pure',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
          1 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * Returns the default value\'s constant name if default value is constant or null
 *
 * @link https://php.net/manual/en/reflectionparameter.getdefaultvalueconstantname.php
 * @return string|null Returns string on success or {@see null} on failure.
 * @throws ReflectionException if the parameter is not optional
 * @since 5.4
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 268,
        'endLine' => 272,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ReflectionParameter',
        'implementingClassName' => 'ReflectionParameter',
        'currentClassName' => 'ReflectionParameter',
        'aliasName' => NULL,
      ),
      'isVariadic' => 
      array (
        'name' => 'isVariadic',
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
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Pure',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
          1 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * Returns whether this function is variadic
 *
 * @link https://php.net/manual/en/reflectionparameter.isvariadic.php
 * @return bool Returns {@see true} if the function is variadic, otherwise {@see false}
 * @since 5.6
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 281,
        'endLine' => 285,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ReflectionParameter',
        'implementingClassName' => 'ReflectionParameter',
        'currentClassName' => 'ReflectionParameter',
        'aliasName' => NULL,
      ),
      'isPromoted' => 
      array (
        'name' => 'isPromoted',
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
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Pure',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * Returns information about whether the parameter is a promoted.
 *
 * @return bool Returns {@see true} if the parameter promoted or {@see false} instead
 * @since 8.0
 */',
        'startLine' => 292,
        'endLine' => 295,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ReflectionParameter',
        'implementingClassName' => 'ReflectionParameter',
        'currentClassName' => 'ReflectionParameter',
        'aliasName' => NULL,
      ),
      'getAttributes' => 
      array (
        'name' => 'getAttributes',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
            'default' => 
            array (
              'code' => '\\null',
              'attributes' => 
              array (
                'startLine' => 307,
                'endLine' => 307,
                'startTokenPos' => 692,
                'startFilePos' => 12202,
                'endTokenPos' => 692,
                'endFilePos' => 12205,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
              'data' => 
              array (
                'types' => 
                array (
                  0 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'string',
                      'isIdentifier' => true,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'null',
                      'isIdentifier' => true,
                    ),
                  ),
                ),
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 307,
            'endLine' => 307,
            'startColumn' => 39,
            'endColumn' => 58,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
          'flags' => 
          array (
            'name' => 'flags',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 307,
                'endLine' => 307,
                'startTokenPos' => 701,
                'startFilePos' => 12221,
                'endTokenPos' => 701,
                'endFilePos' => 12221,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 307,
            'endLine' => 307,
            'startColumn' => 61,
            'endColumn' => 74,
            'parameterIndex' => 1,
            'isOptional' => true,
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
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Pure',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * @template T
 *
 * Returns an array of parameter attributes.
 *
 * @param class-string<T>|null $name Name of an attribute class
 * @param int $flags Сriteria by which the attribute is searched.
 * @return ReflectionAttribute<T>[]
 * @since 8.0
 */',
        'startLine' => 306,
        'endLine' => 309,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ReflectionParameter',
        'implementingClassName' => 'ReflectionParameter',
        'currentClassName' => 'ReflectionParameter',
        'aliasName' => NULL,
      ),
      '__clone' => 
      array (
        'name' => '__clone',
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
            'name' => 'JetBrains\\PhpStorm\\Internal\\PhpStormStubsElementAvailable',
            'isRepeated' => false,
            'arguments' => 
            array (
              'from' => 
              array (
                'code' => '"8.1"',
                'attributes' => 
                array (
                  'startLine' => 316,
                  'endLine' => 316,
                  'startTokenPos' => 719,
                  'startFilePos' => 12475,
                  'endTokenPos' => 719,
                  'endFilePos' => 12479,
                ),
              ),
            ),
          ),
        ),
        'docComment' => '/**
 * Clone
 *
 * @link https://php.net/manual/en/reflectionparameter.clone.php
 * @return void
 */',
        'startLine' => 316,
        'endLine' => 319,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => NULL,
        'declaringClassName' => 'ReflectionParameter',
        'implementingClassName' => 'ReflectionParameter',
        'currentClassName' => 'ReflectionParameter',
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