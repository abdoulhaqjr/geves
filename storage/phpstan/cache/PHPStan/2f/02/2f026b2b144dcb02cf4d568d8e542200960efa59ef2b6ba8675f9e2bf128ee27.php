<?php declare(strict_types = 1);

// phpinternal-PHPStan\BetterReflection\Reflection\ReflectionClass-domdocument
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-dev-master@709e512-8.4.24',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\InternalLocatedSource',
      'data' => 
      array (
        'name' => 'DOMDocument',
        'filename' => 'phpstorm-stubs:dom/dom_c.stub',
        'extensionName' => 'dom',
        'aliasName' => NULL,
      ),
    ),
    'namespace' => NULL,
    'name' => 'DOMDocument',
    'shortName' => 'DOMDocument',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * The DOMDocument class represents an entire HTML or XML
 * document; serves as the root of the document tree.
 * @link https://php.net/manual/en/class.domdocument.php
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 9,
    'endLine' => 788,
    'startColumn' => 5,
    'endColumn' => 5,
    'parentClassName' => 'DOMNode',
    'implementsClassNames' => 
    array (
      0 => 'DOMParentNode',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'actualEncoding' => 
      array (
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'name' => 'actualEncoding',
        'modifiers' => 1,
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
        'default' => NULL,
        'docComment' => '/**
 * @var string|null
 * @link https://php.net/manual/en/class.domdocument.php#domdocument.props.actualencoding
 * @deprecated
 */',
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Deprecated',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '"The property is deprecated"',
                'attributes' => 
                array (
                  'startLine' => 16,
                  'endLine' => 16,
                  'startTokenPos' => 27,
                  'startFilePos' => 501,
                  'endTokenPos' => 27,
                  'endFilePos' => 528,
                ),
              ),
              'since' => 
              array (
                'code' => '"8.4"',
                'attributes' => 
                array (
                  'startLine' => 16,
                  'endLine' => 16,
                  'startTokenPos' => 33,
                  'startFilePos' => 538,
                  'endTokenPos' => 33,
                  'endFilePos' => 542,
                ),
              ),
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
                'code' => '[\'8.1\' => \'string|null\']',
                'attributes' => 
                array (
                  'startLine' => 17,
                  'endLine' => 17,
                  'startTokenPos' => 40,
                  'startFilePos' => 608,
                  'endTokenPos' => 46,
                  'endFilePos' => 631,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 17,
                  'endLine' => 17,
                  'startTokenPos' => 52,
                  'startFilePos' => 643,
                  'endTokenPos' => 52,
                  'endFilePos' => 644,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 16,
        'endLine' => 18,
        'startColumn' => 9,
        'endColumn' => 43,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'config' => 
      array (
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'name' => 'config',
        'modifiers' => 1,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'mixed',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * @var DOMConfiguration
 * @link https://php.net/manual/en/class.domdocument.php#domdocument.props.config
 * @see DOMDocument::normalizeDocument()
 * @deprecated
 */',
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Deprecated',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '"The property is deprecated"',
                'attributes' => 
                array (
                  'startLine' => 25,
                  'endLine' => 25,
                  'startTokenPos' => 70,
                  'startFilePos' => 952,
                  'endTokenPos' => 70,
                  'endFilePos' => 979,
                ),
              ),
              'since' => 
              array (
                'code' => '"8.4"',
                'attributes' => 
                array (
                  'startLine' => 25,
                  'endLine' => 25,
                  'startTokenPos' => 76,
                  'startFilePos' => 989,
                  'endTokenPos' => 76,
                  'endFilePos' => 993,
                ),
              ),
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
                'code' => '[\'8.1\' => \'mixed\']',
                'attributes' => 
                array (
                  'startLine' => 26,
                  'endLine' => 26,
                  'startTokenPos' => 83,
                  'startFilePos' => 1059,
                  'endTokenPos' => 89,
                  'endFilePos' => 1076,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 26,
                  'endLine' => 26,
                  'startTokenPos' => 95,
                  'startFilePos' => 1088,
                  'endTokenPos' => 95,
                  'endFilePos' => 1089,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 25,
        'endLine' => 27,
        'startColumn' => 9,
        'endColumn' => 29,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'doctype' => 
      array (
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'name' => 'doctype',
        'modifiers' => 1,
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
                  'name' => 'DOMDocumentType',
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
        'default' => NULL,
        'docComment' => '/**
 * @var DOMDocumentType
 * The Document Type Declaration associated with this document.
 * @link https://php.net/manual/en/class.domdocument.php#domdocument.props.doctype
 */',
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
                'code' => '[\'8.1\' => \'DOMDocumentType|null\']',
                'attributes' => 
                array (
                  'startLine' => 33,
                  'endLine' => 33,
                  'startTokenPos' => 111,
                  'startFilePos' => 1404,
                  'endTokenPos' => 117,
                  'endFilePos' => 1436,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 33,
                  'endLine' => 33,
                  'startTokenPos' => 123,
                  'startFilePos' => 1448,
                  'endTokenPos' => 123,
                  'endFilePos' => 1449,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 33,
        'endLine' => 34,
        'startColumn' => 9,
        'endColumn' => 45,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'documentElement' => 
      array (
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'name' => 'documentElement',
        'modifiers' => 1,
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
                  'name' => 'DOMElement',
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
        'default' => NULL,
        'docComment' => '/**
 * @var DOMElement
 * This is a convenience attribute that allows direct access to the child node
 * that is the document element of the document.
 * @link https://php.net/manual/en/class.domdocument.php#domdocument.props.documentelement
 */',
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
                'code' => '[\'8.1\' => \'DOMElement|null\']',
                'attributes' => 
                array (
                  'startLine' => 41,
                  'endLine' => 41,
                  'startTokenPos' => 141,
                  'startFilePos' => 1855,
                  'endTokenPos' => 147,
                  'endFilePos' => 1882,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 41,
                  'endLine' => 41,
                  'startTokenPos' => 153,
                  'startFilePos' => 1894,
                  'endTokenPos' => 153,
                  'endFilePos' => 1895,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 41,
        'endLine' => 42,
        'startColumn' => 9,
        'endColumn' => 48,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'documentURI' => 
      array (
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'name' => 'documentURI',
        'modifiers' => 1,
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
        'default' => NULL,
        'docComment' => '/**
 * @var string|null
 * The location of the document or NULL if undefined.
 * @link https://php.net/manual/en/class.domdocument.php#domdocument.props.documenturi
 */',
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
                'code' => '[\'8.1\' => \'string|null\']',
                'attributes' => 
                array (
                  'startLine' => 48,
                  'endLine' => 48,
                  'startTokenPos' => 171,
                  'startFilePos' => 2219,
                  'endTokenPos' => 177,
                  'endFilePos' => 2242,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 48,
                  'endLine' => 48,
                  'startTokenPos' => 183,
                  'startFilePos' => 2254,
                  'endTokenPos' => 183,
                  'endFilePos' => 2255,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 48,
        'endLine' => 49,
        'startColumn' => 9,
        'endColumn' => 40,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'encoding' => 
      array (
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'name' => 'encoding',
        'modifiers' => 1,
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
        'default' => NULL,
        'docComment' => '/**
 * @var string|null
 * Encoding of the document, as specified by the XML declaration. This attribute is not present
 * in the final DOM Level 3 specification, but is the only way of manipulating XML document
 * encoding in this implementation.
 * @link https://php.net/manual/en/class.domdocument.php#domdocument.props.encoding
 */',
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
                'code' => '[\'8.1\' => \'string|null\']',
                'attributes' => 
                array (
                  'startLine' => 57,
                  'endLine' => 57,
                  'startTokenPos' => 201,
                  'startFilePos' => 2754,
                  'endTokenPos' => 207,
                  'endFilePos' => 2777,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 57,
                  'endLine' => 57,
                  'startTokenPos' => 213,
                  'startFilePos' => 2789,
                  'endTokenPos' => 213,
                  'endFilePos' => 2790,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 57,
        'endLine' => 58,
        'startColumn' => 9,
        'endColumn' => 37,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'formatOutput' => 
      array (
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'name' => 'formatOutput',
        'modifiers' => 1,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * @var bool
 * Nicely formats output with indentation and extra space.
 * @link https://php.net/manual/en/class.domdocument.php#domdocument.props.formatoutput
 */',
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
                'code' => '[\'8.1\' => \'bool\']',
                'attributes' => 
                array (
                  'startLine' => 64,
                  'endLine' => 64,
                  'startTokenPos' => 231,
                  'startFilePos' => 3102,
                  'endTokenPos' => 237,
                  'endFilePos' => 3118,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 64,
                  'endLine' => 64,
                  'startTokenPos' => 243,
                  'startFilePos' => 3130,
                  'endTokenPos' => 243,
                  'endFilePos' => 3131,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 64,
        'endLine' => 65,
        'startColumn' => 9,
        'endColumn' => 34,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'implementation' => 
      array (
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'name' => 'implementation',
        'modifiers' => 1,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'DOMImplementation',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * @var DOMImplementation
 * The <classname>DOMImplementation</classname> object that handles this document.
 * @link https://php.net/manual/en/class.domdocument.php#domdocument.props.implementation
 */',
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
                'code' => '[\'8.1\' => \'DOMImplementation\']',
                'attributes' => 
                array (
                  'startLine' => 71,
                  'endLine' => 71,
                  'startTokenPos' => 259,
                  'startFilePos' => 3479,
                  'endTokenPos' => 265,
                  'endFilePos' => 3508,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 71,
                  'endLine' => 71,
                  'startTokenPos' => 271,
                  'startFilePos' => 3520,
                  'endTokenPos' => 271,
                  'endFilePos' => 3521,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 71,
        'endLine' => 72,
        'startColumn' => 9,
        'endColumn' => 49,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'preserveWhiteSpace' => 
      array (
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'name' => 'preserveWhiteSpace',
        'modifiers' => 1,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '\\true',
          'attributes' => 
          array (
            'startLine' => 79,
            'endLine' => 79,
            'startTokenPos' => 311,
            'startFilePos' => 3924,
            'endTokenPos' => 311,
            'endFilePos' => 3928,
          ),
        ),
        'docComment' => '/**
 * @var bool
 * Do not remove redundant white space. Default to TRUE.
 * @link https://php.net/manual/en/class.domdocument.php#domdocument.props.preservewhitespace
 */',
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
                'code' => '[\'8.1\' => \'bool\']',
                'attributes' => 
                array (
                  'startLine' => 78,
                  'endLine' => 78,
                  'startTokenPos' => 287,
                  'startFilePos' => 3849,
                  'endTokenPos' => 293,
                  'endFilePos' => 3865,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 78,
                  'endLine' => 78,
                  'startTokenPos' => 299,
                  'startFilePos' => 3877,
                  'endTokenPos' => 299,
                  'endFilePos' => 3878,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 78,
        'endLine' => 79,
        'startColumn' => 9,
        'endColumn' => 48,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'recover' => 
      array (
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'name' => 'recover',
        'modifiers' => 1,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * @var bool
 * Proprietary. Enables recovery mode, i.e. trying to parse non-well formed documents.
 * This attribute is not part of the DOM specification and is specific to libxml.
 * @link https://php.net/manual/en/class.domdocument.php#domdocument.props.recover
 */',
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
                'code' => '[\'8.1\' => \'bool\']',
                'attributes' => 
                array (
                  'startLine' => 86,
                  'endLine' => 86,
                  'startTokenPos' => 319,
                  'startFilePos' => 4314,
                  'endTokenPos' => 325,
                  'endFilePos' => 4330,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 86,
                  'endLine' => 86,
                  'startTokenPos' => 331,
                  'startFilePos' => 4342,
                  'endTokenPos' => 331,
                  'endFilePos' => 4343,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 86,
        'endLine' => 87,
        'startColumn' => 9,
        'endColumn' => 29,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'resolveExternals' => 
      array (
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'name' => 'resolveExternals',
        'modifiers' => 1,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * @var bool
 * Set it to TRUE to load external entities from a doctype declaration. This is useful for
 * including character entities in your XML document.
 * @link https://php.net/manual/en/class.domdocument.php#domdocument.props.resolveexternals
 */',
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
                'code' => '[\'8.1\' => \'bool\']',
                'attributes' => 
                array (
                  'startLine' => 94,
                  'endLine' => 94,
                  'startTokenPos' => 347,
                  'startFilePos' => 4745,
                  'endTokenPos' => 353,
                  'endFilePos' => 4761,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 94,
                  'endLine' => 94,
                  'startTokenPos' => 359,
                  'startFilePos' => 4773,
                  'endTokenPos' => 359,
                  'endFilePos' => 4774,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 94,
        'endLine' => 95,
        'startColumn' => 9,
        'endColumn' => 38,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'standalone' => 
      array (
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'name' => 'standalone',
        'modifiers' => 1,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * @var bool
 * @link https://php.net/manual/en/class.domdocument.php#domdocument.props.standalone
 * @deprecated
 */',
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Deprecated',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '"Whether or not the document is standalone, as specified by the XML declaration, corresponds to xmlStandalone."',
                'attributes' => 
                array (
                  'startLine' => 101,
                  'endLine' => 101,
                  'startTokenPos' => 375,
                  'startFilePos' => 5020,
                  'endTokenPos' => 375,
                  'endFilePos' => 5130,
                ),
              ),
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
                'code' => '[\'8.1\' => \'bool\']',
                'attributes' => 
                array (
                  'startLine' => 102,
                  'endLine' => 102,
                  'startTokenPos' => 382,
                  'startFilePos' => 5196,
                  'endTokenPos' => 388,
                  'endFilePos' => 5212,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 102,
                  'endLine' => 102,
                  'startTokenPos' => 394,
                  'startFilePos' => 5224,
                  'endTokenPos' => 394,
                  'endFilePos' => 5225,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 101,
        'endLine' => 103,
        'startColumn' => 9,
        'endColumn' => 32,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'strictErrorChecking' => 
      array (
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'name' => 'strictErrorChecking',
        'modifiers' => 1,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '\\true',
          'attributes' => 
          array (
            'startLine' => 110,
            'endLine' => 110,
            'startTokenPos' => 434,
            'startFilePos' => 5630,
            'endTokenPos' => 434,
            'endFilePos' => 5634,
          ),
        ),
        'docComment' => '/**
 * @var bool
 * Throws <classname>DOMException</classname> on errors. Default to TRUE.
 * @link https://php.net/manual/en/class.domdocument.php#domdocument.props.stricterrorchecking
 */',
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
                'code' => '[\'8.1\' => \'bool\']',
                'attributes' => 
                array (
                  'startLine' => 109,
                  'endLine' => 109,
                  'startTokenPos' => 410,
                  'startFilePos' => 5554,
                  'endTokenPos' => 416,
                  'endFilePos' => 5570,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 109,
                  'endLine' => 109,
                  'startTokenPos' => 422,
                  'startFilePos' => 5582,
                  'endTokenPos' => 422,
                  'endFilePos' => 5583,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 109,
        'endLine' => 110,
        'startColumn' => 9,
        'endColumn' => 49,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'substituteEntities' => 
      array (
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'name' => 'substituteEntities',
        'modifiers' => 1,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * @var bool
 * Proprietary. Whether or not to substitute entities. This attribute is not part of the DOM
 * specification and is specific to libxml.
 * @link https://php.net/manual/en/class.domdocument.php#domdocument.props.substituteentities
 */',
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
                'code' => '[\'8.1\' => \'bool\']',
                'attributes' => 
                array (
                  'startLine' => 117,
                  'endLine' => 117,
                  'startTokenPos' => 442,
                  'startFilePos' => 5999,
                  'endTokenPos' => 448,
                  'endFilePos' => 6015,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 117,
                  'endLine' => 117,
                  'startTokenPos' => 454,
                  'startFilePos' => 6027,
                  'endTokenPos' => 454,
                  'endFilePos' => 6028,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 117,
        'endLine' => 118,
        'startColumn' => 9,
        'endColumn' => 40,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'validateOnParse' => 
      array (
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'name' => 'validateOnParse',
        'modifiers' => 1,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '\\false',
          'attributes' => 
          array (
            'startLine' => 125,
            'endLine' => 125,
            'startTokenPos' => 494,
            'startFilePos' => 6417,
            'endTokenPos' => 494,
            'endFilePos' => 6422,
          ),
        ),
        'docComment' => '/**
 * @var bool
 * Loads and validates against the DTD. Default to FALSE.
 * @link https://php.net/manual/en/class.domdocument.php#domdocument.props.validateonparse
 */',
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
                'code' => '[\'8.1\' => \'bool\']',
                'attributes' => 
                array (
                  'startLine' => 124,
                  'endLine' => 124,
                  'startTokenPos' => 470,
                  'startFilePos' => 6345,
                  'endTokenPos' => 476,
                  'endFilePos' => 6361,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 124,
                  'endLine' => 124,
                  'startTokenPos' => 482,
                  'startFilePos' => 6373,
                  'endTokenPos' => 482,
                  'endFilePos' => 6374,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 124,
        'endLine' => 125,
        'startColumn' => 9,
        'endColumn' => 46,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'version' => 
      array (
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'name' => 'version',
        'modifiers' => 1,
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
        'default' => NULL,
        'docComment' => '/**
 * @var string
 * @link https://php.net/manual/en/class.domdocument.php#domdocument.props.version
 * @deprecated
 */',
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Deprecated',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '\'Version of XML, corresponds to xmlVersion\'',
                'attributes' => 
                array (
                  'startLine' => 131,
                  'endLine' => 131,
                  'startTokenPos' => 502,
                  'startFilePos' => 6627,
                  'endTokenPos' => 502,
                  'endFilePos' => 6669,
                ),
              ),
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
                'code' => '[\'8.1\' => \'string|null\']',
                'attributes' => 
                array (
                  'startLine' => 132,
                  'endLine' => 132,
                  'startTokenPos' => 509,
                  'startFilePos' => 6735,
                  'endTokenPos' => 515,
                  'endFilePos' => 6758,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 132,
                  'endLine' => 132,
                  'startTokenPos' => 521,
                  'startFilePos' => 6770,
                  'endTokenPos' => 521,
                  'endFilePos' => 6771,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 131,
        'endLine' => 133,
        'startColumn' => 9,
        'endColumn' => 36,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'xmlEncoding' => 
      array (
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'name' => 'xmlEncoding',
        'modifiers' => 1,
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
        'default' => NULL,
        'docComment' => '/**
 * @var string|null
 * An attribute specifying, as part of the XML declaration, the encoding of this document. This is NULL when
 * unspecified or when it is not known, such as when the Document was created in memory.
 * @link https://php.net/manual/en/class.domdocument.php#domdocument.props.xmlencoding
 */',
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
                'code' => '[\'8.1\' => \'string|null\']',
                'attributes' => 
                array (
                  'startLine' => 140,
                  'endLine' => 140,
                  'startTokenPos' => 539,
                  'startFilePos' => 7235,
                  'endTokenPos' => 545,
                  'endFilePos' => 7258,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 140,
                  'endLine' => 140,
                  'startTokenPos' => 551,
                  'startFilePos' => 7270,
                  'endTokenPos' => 551,
                  'endFilePos' => 7271,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 140,
        'endLine' => 141,
        'startColumn' => 9,
        'endColumn' => 40,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'xmlStandalone' => 
      array (
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'name' => 'xmlStandalone',
        'modifiers' => 1,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * @var bool
 * An attribute specifying, as part of the XML declaration, whether this document is standalone.
 * This is FALSE when unspecified.
 * @link https://php.net/manual/en/class.domdocument.php#domdocument.props.xmlstandalone
 */',
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
                'code' => '[\'8.1\' => \'bool\']',
                'attributes' => 
                array (
                  'startLine' => 148,
                  'endLine' => 148,
                  'startTokenPos' => 569,
                  'startFilePos' => 7668,
                  'endTokenPos' => 575,
                  'endFilePos' => 7684,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 148,
                  'endLine' => 148,
                  'startTokenPos' => 581,
                  'startFilePos' => 7696,
                  'endTokenPos' => 581,
                  'endFilePos' => 7697,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 148,
        'endLine' => 149,
        'startColumn' => 9,
        'endColumn' => 35,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'xmlVersion' => 
      array (
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'name' => 'xmlVersion',
        'modifiers' => 1,
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
        'default' => NULL,
        'docComment' => '/**
 * @var string|null
 * An attribute specifying, as part of the XML declaration, the version number of this document. If there is no
 * declaration and if this document supports the "XML" feature, the value is "1.0".
 * @link https://php.net/manual/en/class.domdocument.php#domdocument.props.xmlversion
 */',
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
                'code' => '[\'8.1\' => \'string|null\']',
                'attributes' => 
                array (
                  'startLine' => 156,
                  'endLine' => 156,
                  'startTokenPos' => 597,
                  'startFilePos' => 8157,
                  'endTokenPos' => 603,
                  'endFilePos' => 8180,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 156,
                  'endLine' => 156,
                  'startTokenPos' => 609,
                  'startFilePos' => 8192,
                  'endTokenPos' => 609,
                  'endFilePos' => 8193,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 156,
        'endLine' => 157,
        'startColumn' => 9,
        'endColumn' => 39,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'childElementCount' => 
      array (
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'name' => 'childElementCount',
        'modifiers' => 1,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
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
                'code' => '[\'8.1\' => \'int\']',
                'attributes' => 
                array (
                  'startLine' => 158,
                  'endLine' => 158,
                  'startTokenPos' => 625,
                  'startFilePos' => 8299,
                  'endTokenPos' => 631,
                  'endFilePos' => 8314,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 158,
                  'endLine' => 158,
                  'startTokenPos' => 637,
                  'startFilePos' => 8326,
                  'endTokenPos' => 637,
                  'endFilePos' => 8327,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 158,
        'endLine' => 159,
        'startColumn' => 9,
        'endColumn' => 38,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'lastElementChild' => 
      array (
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'name' => 'lastElementChild',
        'modifiers' => 1,
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
                  'name' => 'DOMElement',
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
        'default' => NULL,
        'docComment' => NULL,
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
                'code' => '[\'8.1\' => \'DOMElement|null\']',
                'attributes' => 
                array (
                  'startLine' => 160,
                  'endLine' => 160,
                  'startTokenPos' => 651,
                  'startFilePos' => 8432,
                  'endTokenPos' => 657,
                  'endFilePos' => 8459,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 160,
                  'endLine' => 160,
                  'startTokenPos' => 663,
                  'startFilePos' => 8471,
                  'endTokenPos' => 663,
                  'endFilePos' => 8472,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 160,
        'endLine' => 161,
        'startColumn' => 9,
        'endColumn' => 49,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'firstElementChild' => 
      array (
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'name' => 'firstElementChild',
        'modifiers' => 1,
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
                  'name' => 'DOMElement',
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
        'default' => NULL,
        'docComment' => NULL,
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
                'code' => '[\'8.1\' => \'DOMElement|null\']',
                'attributes' => 
                array (
                  'startLine' => 162,
                  'endLine' => 162,
                  'startTokenPos' => 679,
                  'startFilePos' => 8588,
                  'endTokenPos' => 685,
                  'endFilePos' => 8615,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 162,
                  'endLine' => 162,
                  'startTokenPos' => 691,
                  'startFilePos' => 8627,
                  'endTokenPos' => 691,
                  'endFilePos' => 8628,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 162,
        'endLine' => 163,
        'startColumn' => 9,
        'endColumn' => 50,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'ownerDocument' => 
      array (
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'name' => 'ownerDocument',
        'modifiers' => 1,
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
                  'name' => 'DOMDocument',
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
        'default' => NULL,
        'docComment' => '/**
 * @var null
 * The <classname>DOMDocument</classname> object associated with this node, or NULL if this node is a <classname>DOMDocument</classname>.
 * @link https://php.net/manual/en/class.domnode.php#domnode.props.ownerdocument
 */',
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
                'code' => '[\'8.1\' => \'DOMDocument|null\']',
                'attributes' => 
                array (
                  'startLine' => 169,
                  'endLine' => 169,
                  'startTokenPos' => 709,
                  'startFilePos' => 9025,
                  'endTokenPos' => 715,
                  'endFilePos' => 9053,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 169,
                  'endLine' => 169,
                  'startTokenPos' => 721,
                  'startFilePos' => 9065,
                  'endTokenPos' => 721,
                  'endFilePos' => 9066,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 169,
        'endLine' => 170,
        'startColumn' => 9,
        'endColumn' => 47,
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
      'createElement' => 
      array (
        'name' => 'createElement',
        'parameters' => 
        array (
          'localName' => 
          array (
            'name' => 'localName',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 186,
                      'endLine' => 186,
                      'startTokenPos' => 746,
                      'startFilePos' => 9836,
                      'endTokenPos' => 752,
                      'endFilePos' => 9854,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 186,
                      'endLine' => 186,
                      'startTokenPos' => 758,
                      'startFilePos' => 9866,
                      'endTokenPos' => 758,
                      'endFilePos' => 9867,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 186,
            'endLine' => 187,
            'startColumn' => 13,
            'endColumn' => 29,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'value' => 
          array (
            'name' => 'value',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 189,
                'endLine' => 189,
                'startTokenPos' => 792,
                'startFilePos' => 10031,
                'endTokenPos' => 792,
                'endFilePos' => 10032,
              ),
            ),
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 188,
                      'endLine' => 188,
                      'startTokenPos' => 770,
                      'startFilePos' => 9968,
                      'endTokenPos' => 776,
                      'endFilePos' => 9986,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 188,
                      'endLine' => 188,
                      'startTokenPos' => 782,
                      'startFilePos' => 9998,
                      'endTokenPos' => 782,
                      'endFilePos' => 9999,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 188,
            'endLine' => 189,
            'startColumn' => 13,
            'endColumn' => 30,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Create new element node
 * @link https://php.net/manual/en/domdocument.createelement.php
 * @param string $localName <p>
 * The tag name of the element.
 * </p>
 * @param string $value [optional] <p>
 * The value of the element. By default, an empty element will be created.
 * You can also set the value later with DOMElement->nodeValue.
 * </p>
 * @return DOMElement|false A new instance of class DOMElement or false
 * if an error occurred.
 * @throws DOMException If invalid $localName
 */',
        'startLine' => 185,
        'endLine' => 192,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'createDocumentFragment' => 
      array (
        'name' => 'createDocumentFragment',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'DOMDocumentFragment',
            'isIdentifier' => false,
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
 * Create new document fragment
 * @link https://php.net/manual/en/domdocument.createdocumentfragment.php
 * @return DOMDocumentFragment|false The new DOMDocumentFragment or false if an error occurred.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 199,
        'endLine' => 202,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'createTextNode' => 
      array (
        'name' => 'createTextNode',
        'parameters' => 
        array (
          'data' => 
          array (
            'name' => 'data',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 214,
                      'endLine' => 214,
                      'startTokenPos' => 837,
                      'startFilePos' => 11012,
                      'endTokenPos' => 843,
                      'endFilePos' => 11030,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 214,
                      'endLine' => 214,
                      'startTokenPos' => 849,
                      'startFilePos' => 11042,
                      'endTokenPos' => 849,
                      'endFilePos' => 11043,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 214,
            'endLine' => 215,
            'startColumn' => 13,
            'endColumn' => 24,
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
            'name' => 'DOMText',
            'isIdentifier' => false,
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
 * Create new text node
 * @link https://php.net/manual/en/domdocument.createtextnode.php
 * @param string $data <p>
 * The content of the text.
 * </p>
 * @return DOMText|false The new DOMText or false if an error occurred.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 212,
        'endLine' => 218,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'createComment' => 
      array (
        'name' => 'createComment',
        'parameters' => 
        array (
          'data' => 
          array (
            'name' => 'data',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 230,
                      'endLine' => 230,
                      'startTokenPos' => 882,
                      'startFilePos' => 11627,
                      'endTokenPos' => 888,
                      'endFilePos' => 11645,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 230,
                      'endLine' => 230,
                      'startTokenPos' => 894,
                      'startFilePos' => 11657,
                      'endTokenPos' => 894,
                      'endFilePos' => 11658,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 230,
            'endLine' => 231,
            'startColumn' => 13,
            'endColumn' => 24,
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
            'name' => 'DOMComment',
            'isIdentifier' => false,
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
 * Create new comment node
 * @link https://php.net/manual/en/domdocument.createcomment.php
 * @param string $data <p>
 * The content of the comment.
 * </p>
 * @return DOMComment|false The new DOMComment or false if an error occurred.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 228,
        'endLine' => 234,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'createCDATASection' => 
      array (
        'name' => 'createCDATASection',
        'parameters' => 
        array (
          'data' => 
          array (
            'name' => 'data',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 244,
                      'endLine' => 244,
                      'startTokenPos' => 923,
                      'startFilePos' => 12159,
                      'endTokenPos' => 929,
                      'endFilePos' => 12177,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 244,
                      'endLine' => 244,
                      'startTokenPos' => 935,
                      'startFilePos' => 12189,
                      'endTokenPos' => 935,
                      'endFilePos' => 12190,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 244,
            'endLine' => 245,
            'startColumn' => 13,
            'endColumn' => 24,
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
 * Create new cdata node
 * @link https://php.net/manual/en/domdocument.createcdatasection.php
 * @param string $data <p>
 * The content of the cdata.
 * </p>
 * @return DOMCDATASection|false The new DOMCDATASection or false if an error occurred.
 */',
        'startLine' => 243,
        'endLine' => 248,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'createProcessingInstruction' => 
      array (
        'name' => 'createProcessingInstruction',
        'parameters' => 
        array (
          'target' => 
          array (
            'name' => 'target',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 261,
                      'endLine' => 261,
                      'startTokenPos' => 961,
                      'startFilePos' => 12835,
                      'endTokenPos' => 967,
                      'endFilePos' => 12853,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 261,
                      'endLine' => 261,
                      'startTokenPos' => 973,
                      'startFilePos' => 12865,
                      'endTokenPos' => 973,
                      'endFilePos' => 12866,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 261,
            'endLine' => 262,
            'startColumn' => 13,
            'endColumn' => 26,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'data' => 
          array (
            'name' => 'data',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 265,
                'endLine' => 265,
                'startTokenPos' => 1017,
                'startFilePos' => 13113,
                'endTokenPos' => 1017,
                'endFilePos' => 13114,
              ),
            ),
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\PhpStormStubsElementAvailable',
                'isRepeated' => false,
                'arguments' => 
                array (
                  'from' => 
                  array (
                    'code' => '\'7.4\'',
                    'attributes' => 
                    array (
                      'startLine' => 263,
                      'endLine' => 263,
                      'startTokenPos' => 988,
                      'startFilePos' => 12977,
                      'endTokenPos' => 988,
                      'endFilePos' => 12981,
                    ),
                  ),
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
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 264,
                      'endLine' => 264,
                      'startTokenPos' => 995,
                      'startFilePos' => 13051,
                      'endTokenPos' => 1001,
                      'endFilePos' => 13069,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 264,
                      'endLine' => 264,
                      'startTokenPos' => 1007,
                      'startFilePos' => 13081,
                      'endTokenPos' => 1007,
                      'endFilePos' => 13082,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 263,
            'endLine' => 265,
            'startColumn' => 13,
            'endColumn' => 29,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Creates new PI node
 * @link https://php.net/manual/en/domdocument.createprocessinginstruction.php
 * @param string $target <p>
 * The target of the processing instruction.
 * </p>
 * @param string $data <p>
 * The content of the processing instruction.
 * </p>
 * @return DOMProcessingInstruction|false The new DOMProcessingInstruction or false if an error occurred.
 */',
        'startLine' => 260,
        'endLine' => 268,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'createAttribute' => 
      array (
        'name' => 'createAttribute',
        'parameters' => 
        array (
          'localName' => 
          array (
            'name' => 'localName',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 279,
                      'endLine' => 279,
                      'startTokenPos' => 1037,
                      'startFilePos' => 13612,
                      'endTokenPos' => 1043,
                      'endFilePos' => 13630,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 279,
                      'endLine' => 279,
                      'startTokenPos' => 1049,
                      'startFilePos' => 13642,
                      'endTokenPos' => 1049,
                      'endFilePos' => 13643,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 279,
            'endLine' => 280,
            'startColumn' => 13,
            'endColumn' => 29,
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
 * Create new attribute
 * @link https://php.net/manual/en/domdocument.createattribute.php
 * @param string $localName <p>
 * The name of the attribute.
 * </p>
 * @return DOMAttr|false The new DOMAttr or false if an error occurred.
 * @throws DOMException If invalid $localName
 */',
        'startLine' => 278,
        'endLine' => 283,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'createEntityReference' => 
      array (
        'name' => 'createEntityReference',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 296,
                      'endLine' => 296,
                      'startTokenPos' => 1075,
                      'startFilePos' => 14284,
                      'endTokenPos' => 1081,
                      'endFilePos' => 14302,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 296,
                      'endLine' => 296,
                      'startTokenPos' => 1087,
                      'startFilePos' => 14314,
                      'endTokenPos' => 1087,
                      'endFilePos' => 14315,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 296,
            'endLine' => 297,
            'startColumn' => 13,
            'endColumn' => 24,
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
 * Create new entity reference node
 * @link https://php.net/manual/en/domdocument.createentityreference.php
 * @param string $name <p>
 * The content of the entity reference, e.g. the entity reference minus
 * the leading &amp; and the trailing
 * ; characters.
 * </p>
 * @return DOMEntityReference|false The new DOMEntityReference or false if an error
 * occurred.
 */',
        'startLine' => 295,
        'endLine' => 300,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'getElementsByTagName' => 
      array (
        'name' => 'getElementsByTagName',
        'parameters' => 
        array (
          'qualifiedName' => 
          array (
            'name' => 'qualifiedName',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 314,
                      'endLine' => 314,
                      'startTokenPos' => 1117,
                      'startFilePos' => 15018,
                      'endTokenPos' => 1123,
                      'endFilePos' => 15036,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 314,
                      'endLine' => 314,
                      'startTokenPos' => 1129,
                      'startFilePos' => 15048,
                      'endTokenPos' => 1129,
                      'endFilePos' => 15049,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 314,
            'endLine' => 315,
            'startColumn' => 13,
            'endColumn' => 33,
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
            'name' => 'DOMNodeList',
            'isIdentifier' => false,
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
 * Searches for all elements with given tag name
 * @link https://php.net/manual/en/domdocument.getelementsbytagname.php
 * @param string $qualifiedName <p>
 * The name of the tag to match on. The special value *
 * matches all tags.
 * </p>
 * @return DOMNodeList<DOMElement> A new DOMNodeList object containing all the matched
 * elements.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 312,
        'endLine' => 318,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'importNode' => 
      array (
        'name' => 'importNode',
        'parameters' => 
        array (
          'node' => 
          array (
            'name' => 'node',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'DOMNode',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 336,
            'endLine' => 336,
            'startColumn' => 13,
            'endColumn' => 26,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'deep' => 
          array (
            'name' => 'deep',
            'default' => 
            array (
              'code' => '\\false',
              'attributes' => 
              array (
                'startLine' => 339,
                'endLine' => 339,
                'startTokenPos' => 1195,
                'startFilePos' => 15987,
                'endTokenPos' => 1195,
                'endFilePos' => 15991,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'bool',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
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
                    'code' => '\'7.4\'',
                    'attributes' => 
                    array (
                      'startLine' => 337,
                      'endLine' => 337,
                      'startTokenPos' => 1166,
                      'startFilePos' => 15855,
                      'endTokenPos' => 1166,
                      'endFilePos' => 15859,
                    ),
                  ),
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
                    'code' => '[\'8.0\' => \'bool\']',
                    'attributes' => 
                    array (
                      'startLine' => 338,
                      'endLine' => 338,
                      'startTokenPos' => 1173,
                      'startFilePos' => 15929,
                      'endTokenPos' => 1179,
                      'endFilePos' => 15945,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 338,
                      'endLine' => 338,
                      'startTokenPos' => 1185,
                      'startFilePos' => 15957,
                      'endTokenPos' => 1185,
                      'endFilePos' => 15958,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 337,
            'endLine' => 339,
            'startColumn' => 13,
            'endColumn' => 30,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Import node into current document
 * @link https://php.net/manual/en/domdocument.importnode.php
 * @param DOMNode $node <p>
 * The node to import.
 * </p>
 * @param bool $deep <p>
 * If set to true, this method will recursively import the subtree under
 * the importedNode.
 * </p>
 * <p>
 * To copy the nodes attributes deep needs to be set to true
 * </p>
 * @return DOMNode|false The copied node or false, if it cannot be copied.
 * @meta
 */',
        'startLine' => 335,
        'endLine' => 342,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'createElementNS' => 
      array (
        'name' => 'createElementNS',
        'parameters' => 
        array (
          'namespace' => 
          array (
            'name' => 'namespace',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string|null\']',
                    'attributes' => 
                    array (
                      'startLine' => 360,
                      'endLine' => 360,
                      'startTokenPos' => 1215,
                      'startFilePos' => 16892,
                      'endTokenPos' => 1221,
                      'endFilePos' => 16915,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 360,
                      'endLine' => 360,
                      'startTokenPos' => 1227,
                      'startFilePos' => 16927,
                      'endTokenPos' => 1227,
                      'endFilePos' => 16928,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 360,
            'endLine' => 361,
            'startColumn' => 13,
            'endColumn' => 34,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'qualifiedName' => 
          array (
            'name' => 'qualifiedName',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 362,
                      'endLine' => 362,
                      'startTokenPos' => 1241,
                      'startFilePos' => 17034,
                      'endTokenPos' => 1247,
                      'endFilePos' => 17052,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 362,
                      'endLine' => 362,
                      'startTokenPos' => 1253,
                      'startFilePos' => 17064,
                      'endTokenPos' => 1253,
                      'endFilePos' => 17065,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 362,
            'endLine' => 363,
            'startColumn' => 13,
            'endColumn' => 33,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'value' => 
          array (
            'name' => 'value',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 365,
                'endLine' => 365,
                'startTokenPos' => 1287,
                'startFilePos' => 17233,
                'endTokenPos' => 1287,
                'endFilePos' => 17234,
              ),
            ),
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 364,
                      'endLine' => 364,
                      'startTokenPos' => 1265,
                      'startFilePos' => 17170,
                      'endTokenPos' => 1271,
                      'endFilePos' => 17188,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 364,
                      'endLine' => 364,
                      'startTokenPos' => 1277,
                      'startFilePos' => 17200,
                      'endTokenPos' => 1277,
                      'endFilePos' => 17201,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 364,
            'endLine' => 365,
            'startColumn' => 13,
            'endColumn' => 30,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Create new element node with an associated namespace
 * @link https://php.net/manual/en/domdocument.createelementns.php
 * @param string|null $namespace <p>
 * The URI of the namespace.
 * </p>
 * @param string $qualifiedName <p>
 * The qualified name of the element, as prefix:tagname.
 * </p>
 * @param string $value [optional] <p>
 * The value of the element. By default, an empty element will be created.
 * You can also set the value later with DOMElement->nodeValue.
 * </p>
 * @return DOMElement|false The new DOMElement or false if an error occurred.
 * @throws DOMException If invalid $namespace or $qualifiedName
 */',
        'startLine' => 359,
        'endLine' => 368,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'createAttributeNS' => 
      array (
        'name' => 'createAttributeNS',
        'parameters' => 
        array (
          'namespace' => 
          array (
            'name' => 'namespace',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string|null\']',
                    'attributes' => 
                    array (
                      'startLine' => 382,
                      'endLine' => 382,
                      'startTokenPos' => 1307,
                      'startFilePos' => 17924,
                      'endTokenPos' => 1313,
                      'endFilePos' => 17947,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 382,
                      'endLine' => 382,
                      'startTokenPos' => 1319,
                      'startFilePos' => 17959,
                      'endTokenPos' => 1319,
                      'endFilePos' => 17960,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 382,
            'endLine' => 383,
            'startColumn' => 13,
            'endColumn' => 34,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'qualifiedName' => 
          array (
            'name' => 'qualifiedName',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 384,
                      'endLine' => 384,
                      'startTokenPos' => 1333,
                      'startFilePos' => 18066,
                      'endTokenPos' => 1339,
                      'endFilePos' => 18084,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 384,
                      'endLine' => 384,
                      'startTokenPos' => 1345,
                      'startFilePos' => 18096,
                      'endTokenPos' => 1345,
                      'endFilePos' => 18097,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 384,
            'endLine' => 385,
            'startColumn' => 13,
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
 * Create new attribute node with an associated namespace
 * @link https://php.net/manual/en/domdocument.createattributens.php
 * @param string|null $namespace <p>
 * The URI of the namespace.
 * </p>
 * @param string $qualifiedName <p>
 * The tag name and prefix of the attribute, as prefix:tagname.
 * </p>
 * @return DOMAttr|false The new DOMAttr or false if an error occurred.
 * @throws DOMException If invalid $namespace or $qualifiedName
 */',
        'startLine' => 381,
        'endLine' => 388,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'getElementsByTagNameNS' => 
      array (
        'name' => 'getElementsByTagNameNS',
        'parameters' => 
        array (
          'namespace' => 
          array (
            'name' => 'namespace',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string|null\']',
                    'attributes' => 
                    array (
                      'startLine' => 406,
                      'endLine' => 406,
                      'startTokenPos' => 1375,
                      'startFilePos' => 19019,
                      'endTokenPos' => 1381,
                      'endFilePos' => 19042,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 406,
                      'endLine' => 406,
                      'startTokenPos' => 1387,
                      'startFilePos' => 19054,
                      'endTokenPos' => 1387,
                      'endFilePos' => 19055,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 406,
            'endLine' => 407,
            'startColumn' => 13,
            'endColumn' => 34,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'localName' => 
          array (
            'name' => 'localName',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 408,
                      'endLine' => 408,
                      'startTokenPos' => 1401,
                      'startFilePos' => 19161,
                      'endTokenPos' => 1407,
                      'endFilePos' => 19179,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 408,
                      'endLine' => 408,
                      'startTokenPos' => 1413,
                      'startFilePos' => 19191,
                      'endTokenPos' => 1413,
                      'endFilePos' => 19192,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 408,
            'endLine' => 409,
            'startColumn' => 13,
            'endColumn' => 29,
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
            'name' => 'DOMNodeList',
            'isIdentifier' => false,
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
 * Searches for all elements with given tag name in specified namespace
 * @link https://php.net/manual/en/domdocument.getelementsbytagnamens.php
 * @param string $namespace <p>
 * The namespace URI of the elements to match on.
 * The special value * matches all namespaces.
 * </p>
 * @param string $localName <p>
 * The local name of the elements to match on.
 * The special value * matches all local names.
 * </p>
 * @return DOMNodeList<DOMElement> A new DOMNodeList object containing all the matched
 * elements.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 404,
        'endLine' => 412,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'getElementById' => 
      array (
        'name' => 'getElementById',
        'parameters' => 
        array (
          'elementId' => 
          array (
            'name' => 'elementId',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 425,
                      'endLine' => 425,
                      'startTokenPos' => 1446,
                      'startFilePos' => 19830,
                      'endTokenPos' => 1452,
                      'endFilePos' => 19848,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 425,
                      'endLine' => 425,
                      'startTokenPos' => 1458,
                      'startFilePos' => 19860,
                      'endTokenPos' => 1458,
                      'endFilePos' => 19861,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 425,
            'endLine' => 426,
            'startColumn' => 13,
            'endColumn' => 29,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
                  'name' => 'DOMElement',
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
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * Searches for an element with a certain id
 * @link https://php.net/manual/en/domdocument.getelementbyid.php
 * @param string $elementId <p>
 * The unique id value for an element.
 * </p>
 * @return DOMElement|null The DOMElement or null if the element is
 * not found.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 423,
        'endLine' => 429,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'adoptNode' => 
      array (
        'name' => 'adoptNode',
        'parameters' => 
        array (
          'node' => 
          array (
            'name' => 'node',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'DOMNode',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 433,
            'endLine' => 433,
            'startColumn' => 35,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
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
          1 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '[\'8.3\' => \'DOMNode|false\']',
                'attributes' => 
                array (
                  'startLine' => 432,
                  'endLine' => 432,
                  'startTokenPos' => 1485,
                  'startFilePos' => 20107,
                  'endTokenPos' => 1491,
                  'endFilePos' => 20132,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 432,
                  'endLine' => 432,
                  'startTokenPos' => 1497,
                  'startFilePos' => 20144,
                  'endTokenPos' => 1497,
                  'endFilePos' => 20145,
                ),
              ),
            ),
          ),
        ),
        'docComment' => '/** @betterReflectionTentativeReturnType */',
        'startLine' => 431,
        'endLine' => 435,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'append' => 
      array (
        'name' => 'append',
        'parameters' => 
        array (
          'nodes' => 
          array (
            'name' => 'nodes',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => true,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 439,
            'endLine' => 439,
            'startColumn' => 32,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => true,
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
 * {@inheritDoc}
 */',
        'startLine' => 439,
        'endLine' => 441,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => true,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'prepend' => 
      array (
        'name' => 'prepend',
        'parameters' => 
        array (
          'nodes' => 
          array (
            'name' => 'nodes',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => true,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 445,
            'endLine' => 445,
            'startColumn' => 33,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => true,
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
 * {@inheritDoc}
 */',
        'startLine' => 445,
        'endLine' => 447,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => true,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'replaceChildren' => 
      array (
        'name' => 'replaceChildren',
        'parameters' => 
        array (
          'nodes' => 
          array (
            'name' => 'nodes',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => true,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 452,
            'endLine' => 452,
            'startColumn' => 41,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => true,
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
 * @since 8.3
 * {@inheritDoc}
 */',
        'startLine' => 452,
        'endLine' => 454,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => true,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'normalizeDocument' => 
      array (
        'name' => 'normalizeDocument',
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
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * Normalizes the document
 * @link https://php.net/manual/en/domdocument.normalizedocument.php
 * @return void
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 461,
        'endLine' => 464,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'renameNode' => 
      array (
        'name' => 'renameNode',
        'parameters' => 
        array (
          'node' => 
          array (
            'name' => 'node',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'DOMNode',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 470,
            'endLine' => 470,
            'startColumn' => 36,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'namespace' => 
          array (
            'name' => 'namespace',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 470,
            'endLine' => 470,
            'startColumn' => 52,
            'endColumn' => 61,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'qualifiedName' => 
          array (
            'name' => 'qualifiedName',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 470,
            'endLine' => 470,
            'startColumn' => 64,
            'endColumn' => 77,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param DOMNode $node
 * @param $namespace
 * @param $qualifiedName
 */',
        'startLine' => 470,
        'endLine' => 472,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'load' => 
      array (
        'name' => 'load',
        'parameters' => 
        array (
          'filename' => 
          array (
            'name' => 'filename',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 491,
                      'endLine' => 491,
                      'startTokenPos' => 1652,
                      'startFilePos' => 21988,
                      'endTokenPos' => 1658,
                      'endFilePos' => 22006,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 491,
                      'endLine' => 491,
                      'startTokenPos' => 1664,
                      'startFilePos' => 22018,
                      'endTokenPos' => 1664,
                      'endFilePos' => 22019,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 491,
            'endLine' => 492,
            'startColumn' => 13,
            'endColumn' => 28,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'options' => 
          array (
            'name' => 'options',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 494,
                'endLine' => 494,
                'startTokenPos' => 1698,
                'startFilePos' => 22178,
                'endTokenPos' => 1698,
                'endFilePos' => 22178,
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 493,
                      'endLine' => 493,
                      'startTokenPos' => 1676,
                      'startFilePos' => 22119,
                      'endTokenPos' => 1682,
                      'endFilePos' => 22134,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 493,
                      'endLine' => 493,
                      'startTokenPos' => 1688,
                      'startFilePos' => 22146,
                      'endTokenPos' => 1688,
                      'endFilePos' => 22147,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 493,
            'endLine' => 494,
            'startColumn' => 13,
            'endColumn' => 28,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
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
          1 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '[\'8.3\' => \'bool\']',
                'attributes' => 
                array (
                  'startLine' => 489,
                  'endLine' => 489,
                  'startTokenPos' => 1626,
                  'startFilePos' => 21843,
                  'endTokenPos' => 1632,
                  'endFilePos' => 21859,
                ),
              ),
              'default' => 
              array (
                'code' => '\'DOMDocument|bool\'',
                'attributes' => 
                array (
                  'startLine' => 489,
                  'endLine' => 489,
                  'startTokenPos' => 1638,
                  'startFilePos' => 21871,
                  'endTokenPos' => 1638,
                  'endFilePos' => 21888,
                ),
              ),
            ),
          ),
        ),
        'docComment' => '/**
 * Load XML from a file
 * @link https://php.net/manual/en/domdocument.load.php
 * @param string $filename <p>
 * The path to the XML document.
 * </p>
 * @param int $options [optional] <p>
 * Bitwise OR
 * of the libxml option constants.
 * </p>
 * @return DOMDocument|bool true on success or false on failure. Prior to PHP 8.3 if called statically, returns a
 * DOMDocument and issues E_STRICT
 * warning.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 488,
        'endLine' => 497,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'save' => 
      array (
        'name' => 'save',
        'parameters' => 
        array (
          'filename' => 
          array (
            'name' => 'filename',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 512,
                      'endLine' => 512,
                      'startTokenPos' => 1722,
                      'startFilePos' => 22882,
                      'endTokenPos' => 1728,
                      'endFilePos' => 22900,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 512,
                      'endLine' => 512,
                      'startTokenPos' => 1734,
                      'startFilePos' => 22912,
                      'endTokenPos' => 1734,
                      'endFilePos' => 22913,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 512,
            'endLine' => 513,
            'startColumn' => 13,
            'endColumn' => 28,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'options' => 
          array (
            'name' => 'options',
            'default' => 
            array (
              'code' => '\\null',
              'attributes' => 
              array (
                'startLine' => 515,
                'endLine' => 515,
                'startTokenPos' => 1768,
                'startFilePos' => 23072,
                'endTokenPos' => 1768,
                'endFilePos' => 23075,
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
                      'name' => 'int',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 514,
                      'endLine' => 514,
                      'startTokenPos' => 1746,
                      'startFilePos' => 23013,
                      'endTokenPos' => 1752,
                      'endFilePos' => 23028,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 514,
                      'endLine' => 514,
                      'startTokenPos' => 1758,
                      'startFilePos' => 23040,
                      'endTokenPos' => 1758,
                      'endFilePos' => 23041,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 514,
            'endLine' => 515,
            'startColumn' => 13,
            'endColumn' => 31,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
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
                  'name' => 'int',
                  'isIdentifier' => true,
                ),
              ),
              1 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'false',
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
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * Dumps the internal XML tree back into a file
 * @link https://php.net/manual/en/domdocument.save.php
 * @param string $filename <p>
 * The path to the saved XML document.
 * </p>
 * @param int $options [optional] <p>
 * Additional Options. Currently only LIBXML_NOEMPTYTAG is supported.
 * </p>
 * @return int|false the number of bytes written or false if an error occurred.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 510,
        'endLine' => 518,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'loadXML' => 
      array (
        'name' => 'loadXML',
        'parameters' => 
        array (
          'source' => 
          array (
            'name' => 'source',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 537,
                      'endLine' => 537,
                      'startTokenPos' => 1816,
                      'startFilePos' => 23962,
                      'endTokenPos' => 1822,
                      'endFilePos' => 23980,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 537,
                      'endLine' => 537,
                      'startTokenPos' => 1828,
                      'startFilePos' => 23992,
                      'endTokenPos' => 1828,
                      'endFilePos' => 23993,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 537,
            'endLine' => 538,
            'startColumn' => 13,
            'endColumn' => 26,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'options' => 
          array (
            'name' => 'options',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 540,
                'endLine' => 540,
                'startTokenPos' => 1862,
                'startFilePos' => 24150,
                'endTokenPos' => 1862,
                'endFilePos' => 24150,
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 539,
                      'endLine' => 539,
                      'startTokenPos' => 1840,
                      'startFilePos' => 24091,
                      'endTokenPos' => 1846,
                      'endFilePos' => 24106,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 539,
                      'endLine' => 539,
                      'startTokenPos' => 1852,
                      'startFilePos' => 24118,
                      'endTokenPos' => 1852,
                      'endFilePos' => 24119,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 539,
            'endLine' => 540,
            'startColumn' => 13,
            'endColumn' => 28,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
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
          1 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '[\'8.3\' => \'bool\']',
                'attributes' => 
                array (
                  'startLine' => 535,
                  'endLine' => 535,
                  'startTokenPos' => 1790,
                  'startFilePos' => 23814,
                  'endTokenPos' => 1796,
                  'endFilePos' => 23830,
                ),
              ),
              'default' => 
              array (
                'code' => '\'DOMDocument|bool\'',
                'attributes' => 
                array (
                  'startLine' => 535,
                  'endLine' => 535,
                  'startTokenPos' => 1802,
                  'startFilePos' => 23842,
                  'endTokenPos' => 1802,
                  'endFilePos' => 23859,
                ),
              ),
            ),
          ),
        ),
        'docComment' => '/**
 * Load XML from a string
 * @link https://php.net/manual/en/domdocument.loadxml.php
 * @param string $source <p>
 * The string containing the XML.
 * </p>
 * @param int $options [optional] <p>
 * Bitwise OR
 * of the libxml option constants.
 * </p>
 * @return DOMDocument|bool true on success or false on failure. Prior to PHP 8.3 if called statically, returns a
 * DOMDocument and issues E_STRICT
 * warning.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 534,
        'endLine' => 543,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'saveXML' => 
      array (
        'name' => 'saveXML',
        'parameters' => 
        array (
          'node' => 
          array (
            'name' => 'node',
            'default' => 
            array (
              'code' => '\\null',
              'attributes' => 
              array (
                'startLine' => 560,
                'endLine' => 560,
                'startTokenPos' => 1910,
                'startFilePos' => 25015,
                'endTokenPos' => 1910,
                'endFilePos' => 25018,
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
                      'name' => 'DOMNode',
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
                    'code' => '[\'7.1\' => \'DOMNode|null\']',
                    'attributes' => 
                    array (
                      'startLine' => 559,
                      'endLine' => 559,
                      'startTokenPos' => 1886,
                      'startFilePos' => 24941,
                      'endTokenPos' => 1892,
                      'endFilePos' => 24965,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 559,
                      'endLine' => 559,
                      'startTokenPos' => 1898,
                      'startFilePos' => 24977,
                      'endTokenPos' => 1898,
                      'endFilePos' => 24978,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 559,
            'endLine' => 560,
            'startColumn' => 13,
            'endColumn' => 37,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
          'options' => 
          array (
            'name' => 'options',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 563,
                'endLine' => 563,
                'startTokenPos' => 1948,
                'startFilePos' => 25233,
                'endTokenPos' => 1948,
                'endFilePos' => 25233,
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\PhpStormStubsElementAvailable',
                'isRepeated' => false,
                'arguments' => 
                array (
                  'from' => 
                  array (
                    'code' => '\'7.0\'',
                    'attributes' => 
                    array (
                      'startLine' => 561,
                      'endLine' => 561,
                      'startTokenPos' => 1919,
                      'startFilePos' => 25100,
                      'endTokenPos' => 1919,
                      'endFilePos' => 25104,
                    ),
                  ),
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
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 562,
                      'endLine' => 562,
                      'startTokenPos' => 1926,
                      'startFilePos' => 25174,
                      'endTokenPos' => 1932,
                      'endFilePos' => 25189,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 562,
                      'endLine' => 562,
                      'startTokenPos' => 1938,
                      'startFilePos' => 25201,
                      'endTokenPos' => 1938,
                      'endFilePos' => 25202,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 561,
            'endLine' => 563,
            'startColumn' => 13,
            'endColumn' => 28,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
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
                  'name' => 'false',
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
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * Dumps the internal XML tree back into a string
 * @link https://php.net/manual/en/domdocument.savexml.php
 * @param null|DOMNode $node [optional] <p>
 * Use this parameter to output only a specific node without XML declaration
 * rather than the entire document.
 * </p>
 * @param int $options [optional] <p>
 * Additional Options. Currently only LIBXML_NOEMPTYTAG is supported.
 * </p>
 * @return string|false the XML, or false if an error occurred.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 557,
        'endLine' => 566,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      '__construct' => 
      array (
        'name' => '__construct',
        'parameters' => 
        array (
          'version' => 
          array (
            'name' => 'version',
            'default' => 
            array (
              'code' => '\'1.0\'',
              'attributes' => 
              array (
                'startLine' => 575,
                'endLine' => 575,
                'startTokenPos' => 1995,
                'startFilePos' => 25803,
                'endTokenPos' => 1995,
                'endFilePos' => 25807,
              ),
            ),
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 574,
                      'endLine' => 574,
                      'startTokenPos' => 1973,
                      'startFilePos' => 25738,
                      'endTokenPos' => 1979,
                      'endFilePos' => 25756,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 574,
                      'endLine' => 574,
                      'startTokenPos' => 1985,
                      'startFilePos' => 25768,
                      'endTokenPos' => 1985,
                      'endFilePos' => 25769,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 574,
            'endLine' => 575,
            'startColumn' => 13,
            'endColumn' => 35,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
          'encoding' => 
          array (
            'name' => 'encoding',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 577,
                'endLine' => 577,
                'startTokenPos' => 2023,
                'startFilePos' => 25942,
                'endTokenPos' => 2023,
                'endFilePos' => 25943,
              ),
            ),
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 576,
                      'endLine' => 576,
                      'startTokenPos' => 2001,
                      'startFilePos' => 25876,
                      'endTokenPos' => 2007,
                      'endFilePos' => 25894,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 576,
                      'endLine' => 576,
                      'startTokenPos' => 2013,
                      'startFilePos' => 25906,
                      'endTokenPos' => 2013,
                      'endFilePos' => 25907,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 576,
            'endLine' => 577,
            'startColumn' => 13,
            'endColumn' => 33,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Creates a new DOMDocument object
 * @link https://php.net/manual/en/domdocument.construct.php
 * @param string $version [optional] The version number of the document as part of the XML declaration.
 * @param string $encoding [optional] The encoding of the document as part of the XML declaration.
 */',
        'startLine' => 573,
        'endLine' => 580,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'validate' => 
      array (
        'name' => 'validate',
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
 * Validates the document based on its DTD
 * @link https://php.net/manual/en/domdocument.validate.php
 * @return bool true on success or false on failure.
 * If the document have no DTD attached, this method will return false.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 588,
        'endLine' => 591,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'xinclude' => 
      array (
        'name' => 'xinclude',
        'parameters' => 
        array (
          'options' => 
          array (
            'name' => 'options',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 605,
                'endLine' => 605,
                'startTokenPos' => 2090,
                'startFilePos' => 27049,
                'endTokenPos' => 2090,
                'endFilePos' => 27049,
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 604,
                      'endLine' => 604,
                      'startTokenPos' => 2068,
                      'startFilePos' => 26990,
                      'endTokenPos' => 2074,
                      'endFilePos' => 27005,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 604,
                      'endLine' => 604,
                      'startTokenPos' => 2080,
                      'startFilePos' => 27017,
                      'endTokenPos' => 2080,
                      'endFilePos' => 27018,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 604,
            'endLine' => 605,
            'startColumn' => 13,
            'endColumn' => 28,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
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
                  'name' => 'int',
                  'isIdentifier' => true,
                ),
              ),
              1 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'false',
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
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * Substitutes XIncludes in a DOMDocument Object
 * @link https://php.net/manual/en/domdocument.xinclude.php
 * @param int $options [optional] <p>
 * libxml parameters. Available
 * since PHP 5.1.0 and Libxml 2.6.7.
 * </p>
 * @return int|false the number of XIncludes in the document.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 602,
        'endLine' => 608,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'loadHTML' => 
      array (
        'name' => 'loadHTML',
        'parameters' => 
        array (
          'source' => 
          array (
            'name' => 'source',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 627,
                      'endLine' => 627,
                      'startTokenPos' => 2138,
                      'startFilePos' => 27996,
                      'endTokenPos' => 2144,
                      'endFilePos' => 28014,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 627,
                      'endLine' => 627,
                      'startTokenPos' => 2150,
                      'startFilePos' => 28026,
                      'endTokenPos' => 2150,
                      'endFilePos' => 28027,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 627,
            'endLine' => 628,
            'startColumn' => 13,
            'endColumn' => 26,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'options' => 
          array (
            'name' => 'options',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 630,
                'endLine' => 630,
                'startTokenPos' => 2184,
                'startFilePos' => 28184,
                'endTokenPos' => 2184,
                'endFilePos' => 28184,
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 629,
                      'endLine' => 629,
                      'startTokenPos' => 2162,
                      'startFilePos' => 28125,
                      'endTokenPos' => 2168,
                      'endFilePos' => 28140,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 629,
                      'endLine' => 629,
                      'startTokenPos' => 2174,
                      'startFilePos' => 28152,
                      'endTokenPos' => 2174,
                      'endFilePos' => 28153,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 629,
            'endLine' => 630,
            'startColumn' => 13,
            'endColumn' => 28,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
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
          1 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '[\'8.3\' => \'bool\']',
                'attributes' => 
                array (
                  'startLine' => 625,
                  'endLine' => 625,
                  'startTokenPos' => 2112,
                  'startFilePos' => 27847,
                  'endTokenPos' => 2118,
                  'endFilePos' => 27863,
                ),
              ),
              'default' => 
              array (
                'code' => '\'DOMDocument|bool\'',
                'attributes' => 
                array (
                  'startLine' => 625,
                  'endLine' => 625,
                  'startTokenPos' => 2124,
                  'startFilePos' => 27875,
                  'endTokenPos' => 2124,
                  'endFilePos' => 27892,
                ),
              ),
            ),
          ),
        ),
        'docComment' => '/**
 * Load HTML from a string
 * @link https://php.net/manual/en/domdocument.loadhtml.php
 * @param string $source <p>
 * The HTML string.
 * </p>
 * @param int $options [optional] <p>
 * Since PHP 5.4.0 and Libxml 2.6.0, you may also
 * use the options parameter to specify additional Libxml parameters.
 * </p>
 * @return DOMDocument|bool true on success or false on failure. Prior to PHP 8.3 if called statically, returns a
 * DOMDocument and issues E_STRICT
 * warning.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 624,
        'endLine' => 633,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'loadHTMLFile' => 
      array (
        'name' => 'loadHTMLFile',
        'parameters' => 
        array (
          'filename' => 
          array (
            'name' => 'filename',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 652,
                      'endLine' => 652,
                      'startTokenPos' => 2227,
                      'startFilePos' => 29138,
                      'endTokenPos' => 2233,
                      'endFilePos' => 29156,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 652,
                      'endLine' => 652,
                      'startTokenPos' => 2239,
                      'startFilePos' => 29168,
                      'endTokenPos' => 2239,
                      'endFilePos' => 29169,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 652,
            'endLine' => 653,
            'startColumn' => 13,
            'endColumn' => 28,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'options' => 
          array (
            'name' => 'options',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 655,
                'endLine' => 655,
                'startTokenPos' => 2273,
                'startFilePos' => 29328,
                'endTokenPos' => 2273,
                'endFilePos' => 29328,
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 654,
                      'endLine' => 654,
                      'startTokenPos' => 2251,
                      'startFilePos' => 29269,
                      'endTokenPos' => 2257,
                      'endFilePos' => 29284,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 654,
                      'endLine' => 654,
                      'startTokenPos' => 2263,
                      'startFilePos' => 29296,
                      'endTokenPos' => 2263,
                      'endFilePos' => 29297,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 654,
            'endLine' => 655,
            'startColumn' => 13,
            'endColumn' => 28,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
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
          1 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '[\'8.3\' => \'bool\']',
                'attributes' => 
                array (
                  'startLine' => 650,
                  'endLine' => 650,
                  'startTokenPos' => 2201,
                  'startFilePos' => 28985,
                  'endTokenPos' => 2207,
                  'endFilePos' => 29001,
                ),
              ),
              'default' => 
              array (
                'code' => '\'DOMDocument|bool\'',
                'attributes' => 
                array (
                  'startLine' => 650,
                  'endLine' => 650,
                  'startTokenPos' => 2213,
                  'startFilePos' => 29013,
                  'endTokenPos' => 2213,
                  'endFilePos' => 29030,
                ),
              ),
            ),
          ),
        ),
        'docComment' => '/**
 * Load HTML from a file
 * @link https://php.net/manual/en/domdocument.loadhtmlfile.php
 * @param string $filename <p>
 * The path to the HTML file.
 * </p>
 * @param int $options [optional] <p>
 * Since PHP 5.4.0 and Libxml 2.6.0, you may also
 * use the options parameter to specify additional Libxml parameters.
 * </p>
 * @return DOMDocument|bool true on success or false on failure. Prior to PHP 8.3 if called statically, returns a
 * DOMDocument and issues E_STRICT
 * warning.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 649,
        'endLine' => 658,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'saveHTML' => 
      array (
        'name' => 'saveHTML',
        'parameters' => 
        array (
          'node' => 
          array (
            'name' => 'node',
            'default' => 
            array (
              'code' => '\\null',
              'attributes' => 
              array (
                'startLine' => 669,
                'endLine' => 669,
                'startTokenPos' => 2321,
                'startFilePos' => 29970,
                'endTokenPos' => 2321,
                'endFilePos' => 29973,
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
                      'name' => 'DOMNode',
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
                    'code' => '[\'8.0\' => \'DOMNode|null\']',
                    'attributes' => 
                    array (
                      'startLine' => 668,
                      'endLine' => 668,
                      'startTokenPos' => 2297,
                      'startFilePos' => 29896,
                      'endTokenPos' => 2303,
                      'endFilePos' => 29920,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 668,
                      'endLine' => 668,
                      'startTokenPos' => 2309,
                      'startFilePos' => 29932,
                      'endTokenPos' => 2309,
                      'endFilePos' => 29933,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 668,
            'endLine' => 669,
            'startColumn' => 13,
            'endColumn' => 37,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
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
                  'name' => 'false',
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
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * Dumps the internal document into a string using HTML formatting
 * @link https://php.net/manual/en/domdocument.savehtml.php
 * @param null|DOMNode $node [optional] parameter to output a subset of the document.
 * @return string|false The HTML, or false if an error occurred.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 666,
        'endLine' => 672,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'saveHTMLFile' => 
      array (
        'name' => 'saveHTMLFile',
        'parameters' => 
        array (
          'filename' => 
          array (
            'name' => 'filename',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 684,
                      'endLine' => 684,
                      'startTokenPos' => 2350,
                      'startFilePos' => 30585,
                      'endTokenPos' => 2356,
                      'endFilePos' => 30603,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 684,
                      'endLine' => 684,
                      'startTokenPos' => 2362,
                      'startFilePos' => 30615,
                      'endTokenPos' => 2362,
                      'endFilePos' => 30616,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 684,
            'endLine' => 685,
            'startColumn' => 13,
            'endColumn' => 28,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
                  'name' => 'int',
                  'isIdentifier' => true,
                ),
              ),
              1 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'false',
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
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * Dumps the internal document into a file using HTML formatting
 * @link https://php.net/manual/en/domdocument.savehtmlfile.php
 * @param string $filename <p>
 * The path to the saved HTML document.
 * </p>
 * @return int|false the number of bytes written or false if an error occurred.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 682,
        'endLine' => 688,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'schemaValidate' => 
      array (
        'name' => 'schemaValidate',
        'parameters' => 
        array (
          'filename' => 
          array (
            'name' => 'filename',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 704,
                      'endLine' => 704,
                      'startTokenPos' => 2397,
                      'startFilePos' => 31324,
                      'endTokenPos' => 2403,
                      'endFilePos' => 31342,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 704,
                      'endLine' => 704,
                      'startTokenPos' => 2409,
                      'startFilePos' => 31354,
                      'endTokenPos' => 2409,
                      'endFilePos' => 31355,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 704,
            'endLine' => 705,
            'startColumn' => 13,
            'endColumn' => 28,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'flags' => 
          array (
            'name' => 'flags',
            'default' => 
            array (
              'code' => '\\null',
              'attributes' => 
              array (
                'startLine' => 707,
                'endLine' => 707,
                'startTokenPos' => 2443,
                'startFilePos' => 31512,
                'endTokenPos' => 2443,
                'endFilePos' => 31515,
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
                      'name' => 'int',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 706,
                      'endLine' => 706,
                      'startTokenPos' => 2421,
                      'startFilePos' => 31455,
                      'endTokenPos' => 2427,
                      'endFilePos' => 31470,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 706,
                      'endLine' => 706,
                      'startTokenPos' => 2433,
                      'startFilePos' => 31482,
                      'endTokenPos' => 2433,
                      'endFilePos' => 31483,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 706,
            'endLine' => 707,
            'startColumn' => 13,
            'endColumn' => 29,
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
 * Validates a document based on a schema
 * @link https://php.net/manual/en/domdocument.schemavalidate.php
 * @param string $filename <p>
 * The path to the schema.
 * </p>
 * @param int $options [optional] <p>
 * Bitwise OR
 * of the libxml option constants.
 * </p>
 * @return bool true on success or false on failure.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 702,
        'endLine' => 710,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'schemaValidateSource' => 
      array (
        'name' => 'schemaValidateSource',
        'parameters' => 
        array (
          'source' => 
          array (
            'name' => 'source',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 724,
                      'endLine' => 724,
                      'startTokenPos' => 2470,
                      'startFilePos' => 32293,
                      'endTokenPos' => 2476,
                      'endFilePos' => 32311,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 724,
                      'endLine' => 724,
                      'startTokenPos' => 2482,
                      'startFilePos' => 32323,
                      'endTokenPos' => 2482,
                      'endFilePos' => 32324,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 724,
            'endLine' => 725,
            'startColumn' => 13,
            'endColumn' => 26,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'flags' => 
          array (
            'name' => 'flags',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 727,
                'endLine' => 727,
                'startTokenPos' => 2516,
                'startFilePos' => 32479,
                'endTokenPos' => 2516,
                'endFilePos' => 32479,
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 726,
                      'endLine' => 726,
                      'startTokenPos' => 2494,
                      'startFilePos' => 32422,
                      'endTokenPos' => 2500,
                      'endFilePos' => 32437,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 726,
                      'endLine' => 726,
                      'startTokenPos' => 2506,
                      'startFilePos' => 32449,
                      'endTokenPos' => 2506,
                      'endFilePos' => 32450,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 726,
            'endLine' => 727,
            'startColumn' => 13,
            'endColumn' => 26,
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
 * Validates a document based on a schema
 * @link https://php.net/manual/en/domdocument.schemavalidatesource.php
 * @param string $source <p>
 * A string containing the schema.
 * </p>
 * @param int $flags [optional] <p>A bitmask of Libxml schema validation flags. Currently the only supported value is <b>LIBXML_SCHEMA_CREATE</b>.
 * Available since PHP 5.5.2 and Libxml 2.6.14.</p>
 * @return bool true on success or false on failure.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 722,
        'endLine' => 730,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'relaxNGValidate' => 
      array (
        'name' => 'relaxNGValidate',
        'parameters' => 
        array (
          'filename' => 
          array (
            'name' => 'filename',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 742,
                      'endLine' => 742,
                      'startTokenPos' => 2543,
                      'startFilePos' => 33021,
                      'endTokenPos' => 2549,
                      'endFilePos' => 33039,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 742,
                      'endLine' => 742,
                      'startTokenPos' => 2555,
                      'startFilePos' => 33051,
                      'endTokenPos' => 2555,
                      'endFilePos' => 33052,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 742,
            'endLine' => 743,
            'startColumn' => 13,
            'endColumn' => 28,
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
 * Performs relaxNG validation on the document
 * @link https://php.net/manual/en/domdocument.relaxngvalidate.php
 * @param string $filename <p>
 * The RNG file.
 * </p>
 * @return bool true on success or false on failure.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 740,
        'endLine' => 746,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'relaxNGValidateSource' => 
      array (
        'name' => 'relaxNGValidateSource',
        'parameters' => 
        array (
          'source' => 
          array (
            'name' => 'source',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 758,
                      'endLine' => 758,
                      'startTokenPos' => 2588,
                      'startFilePos' => 33657,
                      'endTokenPos' => 2594,
                      'endFilePos' => 33675,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 758,
                      'endLine' => 758,
                      'startTokenPos' => 2600,
                      'startFilePos' => 33687,
                      'endTokenPos' => 2600,
                      'endFilePos' => 33688,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 758,
            'endLine' => 759,
            'startColumn' => 13,
            'endColumn' => 26,
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
 * Performs relaxNG validation on the document
 * @link https://php.net/manual/en/domdocument.relaxngvalidatesource.php
 * @param string $source <p>
 * A string containing the RNG schema.
 * </p>
 * @return bool true on success or false on failure.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 756,
        'endLine' => 762,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'registerNodeClass' => 
      array (
        'name' => 'registerNodeClass',
        'parameters' => 
        array (
          'baseClass' => 
          array (
            'name' => 'baseClass',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 781,
                      'endLine' => 781,
                      'startTokenPos' => 2652,
                      'startFilePos' => 34684,
                      'endTokenPos' => 2658,
                      'endFilePos' => 34702,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 781,
                      'endLine' => 781,
                      'startTokenPos' => 2664,
                      'startFilePos' => 34714,
                      'endTokenPos' => 2664,
                      'endFilePos' => 34715,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 781,
            'endLine' => 782,
            'startColumn' => 13,
            'endColumn' => 29,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'extendedClass' => 
          array (
            'name' => 'extendedClass',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string|null\']',
                    'attributes' => 
                    array (
                      'startLine' => 783,
                      'endLine' => 783,
                      'startTokenPos' => 2676,
                      'startFilePos' => 34816,
                      'endTokenPos' => 2682,
                      'endFilePos' => 34839,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 783,
                      'endLine' => 783,
                      'startTokenPos' => 2688,
                      'startFilePos' => 34851,
                      'endTokenPos' => 2688,
                      'endFilePos' => 34852,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 783,
            'endLine' => 784,
            'startColumn' => 13,
            'endColumn' => 38,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
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
          1 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '[\'8.4\' => \'true\']',
                'attributes' => 
                array (
                  'startLine' => 779,
                  'endLine' => 779,
                  'startTokenPos' => 2626,
                  'startFilePos' => 34538,
                  'endTokenPos' => 2632,
                  'endFilePos' => 34554,
                ),
              ),
              'default' => 
              array (
                'code' => '\'bool\'',
                'attributes' => 
                array (
                  'startLine' => 779,
                  'endLine' => 779,
                  'startTokenPos' => 2638,
                  'startFilePos' => 34566,
                  'endTokenPos' => 2638,
                  'endFilePos' => 34571,
                ),
              ),
            ),
          ),
        ),
        'docComment' => '/**
 * Register extended class used to create base node type
 * @link https://php.net/manual/en/domdocument.registernodeclass.php
 * @param string $baseClass <p>
 * The DOM class that you want to extend. You can find a list of these
 * classes in the chapter introduction.
 * </p>
 * @param string $extendedClass <p>
 * Your extended class name. If null is provided, any previously
 * registered class extending baseclass will
 * be removed.
 * </p>
 * @return bool true on success or false on failure.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 778,
        'endLine' => 787,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
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