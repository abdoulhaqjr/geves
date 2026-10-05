<?php declare(strict_types = 1);

// osfsl-C:/Users/TOURE/Herd/gesco/vendor/composer/../simplesoftwareio/simple-qrcode/src/Generator.php-PHPStan\BetterReflection\Reflection\ReflectionClass-SimpleSoftwareIO\QrCode\Generator
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-c60ad27bff58e11ab8f4a9c3e6a703abcb5eb410f2220dd58bf2d980e12639e5-8.4.24-6.70.0.3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'filename' => 'C:/Users/TOURE/Herd/gesco/vendor/composer/../simplesoftwareio/simple-qrcode/src/Generator.php',
      ),
    ),
    'namespace' => 'SimpleSoftwareIO\\QrCode',
    'name' => 'SimpleSoftwareIO\\QrCode\\Generator',
    'shortName' => 'Generator',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 34,
    'endLine' => 578,
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
      'format' => 
      array (
        'declaringClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'implementingClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'name' => 'format',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'svg\'',
          'attributes' => 
          array (
            'startLine' => 41,
            'endLine' => 41,
            'startTokenPos' => 161,
            'startFilePos' => 1394,
            'endTokenPos' => 161,
            'endFilePos' => 1398,
          ),
        ),
        'docComment' => '/**
 * Holds the selected formatter.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 41,
        'endLine' => 41,
        'startColumn' => 5,
        'endColumn' => 30,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'pixels' => 
      array (
        'declaringClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'implementingClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'name' => 'pixels',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '100',
          'attributes' => 
          array (
            'startLine' => 48,
            'endLine' => 48,
            'startTokenPos' => 172,
            'startFilePos' => 1512,
            'endTokenPos' => 172,
            'endFilePos' => 1514,
          ),
        ),
        'docComment' => '/**
 * Holds the size of the QrCode in pixels.
 *
 * @var int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 48,
        'endLine' => 48,
        'startColumn' => 5,
        'endColumn' => 28,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'margin' => 
      array (
        'declaringClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'implementingClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'name' => 'margin',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '0',
          'attributes' => 
          array (
            'startLine' => 55,
            'endLine' => 55,
            'startTokenPos' => 183,
            'startFilePos' => 1625,
            'endTokenPos' => 183,
            'endFilePos' => 1625,
          ),
        ),
        'docComment' => '/**
 * Holds the margin size of the QrCode.
 *
 * @var int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 55,
        'endLine' => 55,
        'startColumn' => 5,
        'endColumn' => 26,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'errorCorrection' => 
      array (
        'declaringClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'implementingClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'name' => 'errorCorrection',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 66,
            'endLine' => 66,
            'startTokenPos' => 194,
            'startFilePos' => 1832,
            'endTokenPos' => 194,
            'endFilePos' => 1835,
          ),
        ),
        'docComment' => '/**
 * Holds the selected error correction.
 * L: 7% loss.
 * M: 15% loss.
 * Q: 25% loss.
 * H: 30% loss.
 *
 * @var string|null
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 66,
        'endLine' => 66,
        'startColumn' => 5,
        'endColumn' => 38,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'encoding' => 
      array (
        'declaringClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'implementingClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'name' => 'encoding',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\\BaconQrCode\\Encoder\\Encoder::DEFAULT_BYTE_MODE_ECODING',
          'attributes' => 
          array (
            'startLine' => 78,
            'endLine' => 78,
            'startTokenPos' => 205,
            'startFilePos' => 2289,
            'endTokenPos' => 207,
            'endFilePos' => 2322,
          ),
        ),
        'docComment' => '/**
 * Holds the selected encoder.  Possible values are
 * ISO-8859-2, ISO-8859-3, ISO-8859-4, ISO-8859-5, ISO-8859-6,
 * ISO-8859-7, ISO-8859-8, ISO-8859-9, ISO-8859-10, ISO-8859-11,
 * ISO-8859-12, ISO-8859-13, ISO-8859-14, ISO-8859-15, ISO-8859-16,
 * SHIFT-JIS, WINDOWS-1250, WINDOWS-1251, WINDOWS-1252, WINDOWS-1256,
 * UTF-16BE, UTF-8, ASCII, GBK, EUC-KR.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 78,
        'endLine' => 78,
        'startColumn' => 5,
        'endColumn' => 61,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'style' => 
      array (
        'declaringClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'implementingClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'name' => 'style',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'square\'',
          'attributes' => 
          array (
            'startLine' => 86,
            'endLine' => 86,
            'startTokenPos' => 218,
            'startFilePos' => 2492,
            'endTokenPos' => 218,
            'endFilePos' => 2499,
          ),
        ),
        'docComment' => '/**
 * The style of the blocks within the QrCode.
 * Possible values are square, dot, and round.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 86,
        'endLine' => 86,
        'startColumn' => 5,
        'endColumn' => 32,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'styleSize' => 
      array (
        'declaringClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'implementingClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'name' => 'styleSize',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 94,
            'endLine' => 94,
            'startTokenPos' => 229,
            'startFilePos' => 2674,
            'endTokenPos' => 229,
            'endFilePos' => 2677,
          ),
        ),
        'docComment' => '/**
 * The size of the selected style between 0 and 1.
 * This only applies to dot and round.
 *
 * @var float|null
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 94,
        'endLine' => 94,
        'startColumn' => 5,
        'endColumn' => 32,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'eyeStyle' => 
      array (
        'declaringClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'implementingClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'name' => 'eyeStyle',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 102,
            'endLine' => 102,
            'startTokenPos' => 240,
            'startFilePos' => 2838,
            'endTokenPos' => 240,
            'endFilePos' => 2841,
          ),
        ),
        'docComment' => '/**
 * The style to apply to the eye.
 * Possible values are circle and square.
 *
 * @var string|null
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 102,
        'endLine' => 102,
        'startColumn' => 5,
        'endColumn' => 31,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'color' => 
      array (
        'declaringClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'implementingClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'name' => 'color',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 109,
            'endLine' => 109,
            'startTokenPos' => 251,
            'startFilePos' => 2966,
            'endTokenPos' => 251,
            'endFilePos' => 2969,
          ),
        ),
        'docComment' => '/**
 * The foreground color of the QrCode.
 *
 * @var ColorInterface|null
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 109,
        'endLine' => 109,
        'startColumn' => 5,
        'endColumn' => 28,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'backgroundColor' => 
      array (
        'declaringClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'implementingClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'name' => 'backgroundColor',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 116,
            'endLine' => 116,
            'startTokenPos' => 262,
            'startFilePos' => 3104,
            'endTokenPos' => 262,
            'endFilePos' => 3107,
          ),
        ),
        'docComment' => '/**
 * The background color of the QrCode.
 *
 * @var ColorInterface|null
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 116,
        'endLine' => 116,
        'startColumn' => 5,
        'endColumn' => 38,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'eyeColors' => 
      array (
        'declaringClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'implementingClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'name' => 'eyeColors',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 123,
            'endLine' => 123,
            'startTokenPos' => 273,
            'startFilePos' => 3241,
            'endTokenPos' => 274,
            'endFilePos' => 3242,
          ),
        ),
        'docComment' => '/**
 * An array that holds EyeFills of the color of the eyes.
 *
 * @var array
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 123,
        'endLine' => 123,
        'startColumn' => 5,
        'endColumn' => 30,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'gradient' => 
      array (
        'declaringClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'implementingClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'name' => 'gradient',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The gradient to apply to the QrCode.
 *
 * @var Gradient
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 130,
        'endLine' => 130,
        'startColumn' => 5,
        'endColumn' => 24,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'imageMerge' => 
      array (
        'declaringClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'implementingClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'name' => 'imageMerge',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 137,
            'endLine' => 137,
            'startTokenPos' => 292,
            'startFilePos' => 3501,
            'endTokenPos' => 292,
            'endFilePos' => 3504,
          ),
        ),
        'docComment' => '/**
 * Holds an image string that will be merged with the QrCode.
 *
 * @var null|string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 137,
        'endLine' => 137,
        'startColumn' => 5,
        'endColumn' => 33,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'imagePercentage' => 
      array (
        'declaringClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'implementingClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'name' => 'imagePercentage',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '0.2',
          'attributes' => 
          array (
            'startLine' => 144,
            'endLine' => 144,
            'startTokenPos' => 303,
            'startFilePos' => 3659,
            'endTokenPos' => 303,
            'endFilePos' => 3660,
          ),
        ),
        'docComment' => '/**
 * The percentage that a merged image should take over the source image.
 *
 * @var float
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 144,
        'endLine' => 144,
        'startColumn' => 5,
        'endColumn' => 36,
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
      '__call' => 
      array (
        'name' => '__call',
        'parameters' => 
        array (
          'method' => 
          array (
            'name' => 'method',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 152,
            'endLine' => 152,
            'startColumn' => 28,
            'endColumn' => 34,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'arguments' => 
          array (
            'name' => 'arguments',
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
            'startLine' => 152,
            'endLine' => 152,
            'startColumn' => 37,
            'endColumn' => 52,
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
 * Creates a new datatype object and then generates a QrCode.
 *
 * @param $method
 * @param array $arguments
 */',
        'startLine' => 152,
        'endLine' => 158,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'SimpleSoftwareIO\\QrCode',
        'declaringClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'implementingClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'currentClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'aliasName' => NULL,
      ),
      'generate' => 
      array (
        'name' => 'generate',
        'parameters' => 
        array (
          'text' => 
          array (
            'name' => 'text',
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
            'startLine' => 169,
            'endLine' => 169,
            'startColumn' => 30,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'filename' => 
          array (
            'name' => 'filename',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 169,
                'endLine' => 169,
                'startTokenPos' => 378,
                'startFilePos' => 4318,
                'endTokenPos' => 378,
                'endFilePos' => 4321,
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
            'startLine' => 169,
            'endLine' => 169,
            'startColumn' => 44,
            'endColumn' => 66,
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
 * Generates the QrCode.
 *
 * @param string $text
 * @param string|null $filename
 * @return void|Illuminate\\Support\\HtmlString|string
 * @throws WriterException
 * @throws InvalidArgumentException
 */',
        'startLine' => 169,
        'endLine' => 189,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'SimpleSoftwareIO\\QrCode',
        'declaringClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'implementingClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'currentClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'aliasName' => NULL,
      ),
      'merge' => 
      array (
        'name' => 'merge',
        'parameters' => 
        array (
          'filepath' => 
          array (
            'name' => 'filepath',
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
            'startLine' => 199,
            'endLine' => 199,
            'startColumn' => 27,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'percentage' => 
          array (
            'name' => 'percentage',
            'default' => 
            array (
              'code' => '0.2',
              'attributes' => 
              array (
                'startLine' => 199,
                'endLine' => 199,
                'startTokenPos' => 554,
                'startFilePos' => 5237,
                'endTokenPos' => 554,
                'endFilePos' => 5238,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'float',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 199,
            'endLine' => 199,
            'startColumn' => 45,
            'endColumn' => 66,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'absolute' => 
          array (
            'name' => 'absolute',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 199,
                'endLine' => 199,
                'startTokenPos' => 563,
                'startFilePos' => 5258,
                'endTokenPos' => 563,
                'endFilePos' => 5262,
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
            ),
            'startLine' => 199,
            'endLine' => 199,
            'startColumn' => 69,
            'endColumn' => 90,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Merges an image over the QrCode.
 *
 * @param string $filepath
 * @param float $percentage
 * @param SimpleSoftwareIO\\QrCode\\boolean|bool $absolute
 * @return Generator
 */',
        'startLine' => 199,
        'endLine' => 209,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'SimpleSoftwareIO\\QrCode',
        'declaringClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'implementingClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'currentClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'aliasName' => NULL,
      ),
      'mergeString' => 
      array (
        'name' => 'mergeString',
        'parameters' => 
        array (
          'content' => 
          array (
            'name' => 'content',
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
            'startLine' => 218,
            'endLine' => 218,
            'startColumn' => 33,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'percentage' => 
          array (
            'name' => 'percentage',
            'default' => 
            array (
              'code' => '0.2',
              'attributes' => 
              array (
                'startLine' => 218,
                'endLine' => 218,
                'startTokenPos' => 648,
                'startFilePos' => 5769,
                'endTokenPos' => 648,
                'endFilePos' => 5770,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'float',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 218,
            'endLine' => 218,
            'startColumn' => 50,
            'endColumn' => 71,
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
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Merges an image string with the center of the QrCode.
 *
 * @param string  $content
 * @param float $percentage
 * @return Generator
 */',
        'startLine' => 218,
        'endLine' => 224,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'SimpleSoftwareIO\\QrCode',
        'declaringClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'implementingClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'currentClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'aliasName' => NULL,
      ),
      'size' => 
      array (
        'name' => 'size',
        'parameters' => 
        array (
          'pixels' => 
          array (
            'name' => 'pixels',
            'default' => NULL,
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
            'startLine' => 232,
            'endLine' => 232,
            'startColumn' => 26,
            'endColumn' => 36,
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
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Sets the size of the QrCode.
 *
 * @param int $pixels
 * @return Generator
 */',
        'startLine' => 232,
        'endLine' => 237,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'SimpleSoftwareIO\\QrCode',
        'declaringClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'implementingClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'currentClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'aliasName' => NULL,
      ),
      'format' => 
      array (
        'name' => 'format',
        'parameters' => 
        array (
          'format' => 
          array (
            'name' => 'format',
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
            'startLine' => 246,
            'endLine' => 246,
            'startColumn' => 28,
            'endColumn' => 41,
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
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Sets the format of the QrCode.
 *
 * @param string $format
 * @return Generator
 * @throws InvalidArgumentException
 */',
        'startLine' => 246,
        'endLine' => 255,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'SimpleSoftwareIO\\QrCode',
        'declaringClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'implementingClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'currentClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'aliasName' => NULL,
      ),
      'color' => 
      array (
        'name' => 'color',
        'parameters' => 
        array (
          'red' => 
          array (
            'name' => 'red',
            'default' => NULL,
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
            'startLine' => 266,
            'endLine' => 266,
            'startColumn' => 27,
            'endColumn' => 34,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'green' => 
          array (
            'name' => 'green',
            'default' => NULL,
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
            'startLine' => 266,
            'endLine' => 266,
            'startColumn' => 37,
            'endColumn' => 46,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'blue' => 
          array (
            'name' => 'blue',
            'default' => NULL,
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
            'startLine' => 266,
            'endLine' => 266,
            'startColumn' => 49,
            'endColumn' => 57,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'alpha' => 
          array (
            'name' => 'alpha',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 266,
                'endLine' => 266,
                'startTokenPos' => 821,
                'startFilePos' => 6847,
                'endTokenPos' => 821,
                'endFilePos' => 6850,
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
            ),
            'startLine' => 266,
            'endLine' => 266,
            'startColumn' => 60,
            'endColumn' => 77,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Sets the foreground color of the QrCode.
 *
 * @param int $red
 * @param int $green
 * @param int $blue
 * @param null|int $alpha
 * @return Generator
 */',
        'startLine' => 266,
        'endLine' => 271,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'SimpleSoftwareIO\\QrCode',
        'declaringClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'implementingClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'currentClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'aliasName' => NULL,
      ),
      'backgroundColor' => 
      array (
        'name' => 'backgroundColor',
        'parameters' => 
        array (
          'red' => 
          array (
            'name' => 'red',
            'default' => NULL,
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
            'startLine' => 282,
            'endLine' => 282,
            'startColumn' => 37,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'green' => 
          array (
            'name' => 'green',
            'default' => NULL,
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
            'startLine' => 282,
            'endLine' => 282,
            'startColumn' => 47,
            'endColumn' => 56,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'blue' => 
          array (
            'name' => 'blue',
            'default' => NULL,
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
            'startLine' => 282,
            'endLine' => 282,
            'startColumn' => 59,
            'endColumn' => 67,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'alpha' => 
          array (
            'name' => 'alpha',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 282,
                'endLine' => 282,
                'startTokenPos' => 889,
                'startFilePos' => 7248,
                'endTokenPos' => 889,
                'endFilePos' => 7251,
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
            ),
            'startLine' => 282,
            'endLine' => 282,
            'startColumn' => 70,
            'endColumn' => 87,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Sets the background color of the QrCode.
 *
 * @param int $red
 * @param int $green
 * @param int $blue
 * @param null|int $alpha
 * @return Generator
 */',
        'startLine' => 282,
        'endLine' => 287,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'SimpleSoftwareIO\\QrCode',
        'declaringClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'implementingClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'currentClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'aliasName' => NULL,
      ),
      'eyeColor' => 
      array (
        'name' => 'eyeColor',
        'parameters' => 
        array (
          'eyeNumber' => 
          array (
            'name' => 'eyeNumber',
            'default' => NULL,
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
            'startLine' => 302,
            'endLine' => 302,
            'startColumn' => 30,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'innerRed' => 
          array (
            'name' => 'innerRed',
            'default' => NULL,
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
            'startLine' => 302,
            'endLine' => 302,
            'startColumn' => 46,
            'endColumn' => 58,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'innerGreen' => 
          array (
            'name' => 'innerGreen',
            'default' => NULL,
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
            'startLine' => 302,
            'endLine' => 302,
            'startColumn' => 61,
            'endColumn' => 75,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'innerBlue' => 
          array (
            'name' => 'innerBlue',
            'default' => NULL,
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
            'startLine' => 302,
            'endLine' => 302,
            'startColumn' => 78,
            'endColumn' => 91,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'outterRed' => 
          array (
            'name' => 'outterRed',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 302,
                'endLine' => 302,
                'startTokenPos' => 961,
                'startFilePos' => 7836,
                'endTokenPos' => 961,
                'endFilePos' => 7836,
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
            'startLine' => 302,
            'endLine' => 302,
            'startColumn' => 94,
            'endColumn' => 111,
            'parameterIndex' => 4,
            'isOptional' => true,
          ),
          'outterGreen' => 
          array (
            'name' => 'outterGreen',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 302,
                'endLine' => 302,
                'startTokenPos' => 970,
                'startFilePos' => 7858,
                'endTokenPos' => 970,
                'endFilePos' => 7858,
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
            'startLine' => 302,
            'endLine' => 302,
            'startColumn' => 114,
            'endColumn' => 133,
            'parameterIndex' => 5,
            'isOptional' => true,
          ),
          'outterBlue' => 
          array (
            'name' => 'outterBlue',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 302,
                'endLine' => 302,
                'startTokenPos' => 979,
                'startFilePos' => 7879,
                'endTokenPos' => 979,
                'endFilePos' => 7879,
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
            'startLine' => 302,
            'endLine' => 302,
            'startColumn' => 136,
            'endColumn' => 154,
            'parameterIndex' => 6,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Sets the eye color for the provided eye index.
 *
 * @param int $eyeNumber
 * @param int $innerRed
 * @param int $innerGreen
 * @param int $innerBlue
 * @param int $outterRed
 * @param int $outterGreen
 * @param int $outterBlue
 * @return Generator
 * @throws InvalidArgumentException
 */',
        'startLine' => 302,
        'endLine' => 314,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'SimpleSoftwareIO\\QrCode',
        'declaringClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'implementingClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'currentClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'aliasName' => NULL,
      ),
      'gradient' => 
      array (
        'name' => 'gradient',
        'parameters' => 
        array (
          'startRed' => 
          array (
            'name' => 'startRed',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 316,
            'endLine' => 316,
            'startColumn' => 30,
            'endColumn' => 38,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'startGreen' => 
          array (
            'name' => 'startGreen',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 316,
            'endLine' => 316,
            'startColumn' => 41,
            'endColumn' => 51,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'startBlue' => 
          array (
            'name' => 'startBlue',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 316,
            'endLine' => 316,
            'startColumn' => 54,
            'endColumn' => 63,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'endRed' => 
          array (
            'name' => 'endRed',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 316,
            'endLine' => 316,
            'startColumn' => 66,
            'endColumn' => 72,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'endGreen' => 
          array (
            'name' => 'endGreen',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 316,
            'endLine' => 316,
            'startColumn' => 75,
            'endColumn' => 83,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
          'endBlue' => 
          array (
            'name' => 'endBlue',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 316,
            'endLine' => 316,
            'startColumn' => 86,
            'endColumn' => 93,
            'parameterIndex' => 5,
            'isOptional' => false,
          ),
          'type' => 
          array (
            'name' => 'type',
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
            'startLine' => 316,
            'endLine' => 316,
            'startColumn' => 96,
            'endColumn' => 107,
            'parameterIndex' => 6,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 316,
        'endLine' => 326,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'SimpleSoftwareIO\\QrCode',
        'declaringClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'implementingClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'currentClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'aliasName' => NULL,
      ),
      'eye' => 
      array (
        'name' => 'eye',
        'parameters' => 
        array (
          'style' => 
          array (
            'name' => 'style',
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
            'startLine' => 335,
            'endLine' => 335,
            'startColumn' => 25,
            'endColumn' => 37,
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
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Sets the eye style.
 *
 * @param string $style
 * @return Generator
 * @throws InvalidArgumentException
 */',
        'startLine' => 335,
        'endLine' => 344,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'SimpleSoftwareIO\\QrCode',
        'declaringClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'implementingClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'currentClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'aliasName' => NULL,
      ),
      'style' => 
      array (
        'name' => 'style',
        'parameters' => 
        array (
          'style' => 
          array (
            'name' => 'style',
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
            'startLine' => 354,
            'endLine' => 354,
            'startColumn' => 27,
            'endColumn' => 39,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'size' => 
          array (
            'name' => 'size',
            'default' => 
            array (
              'code' => '0.5',
              'attributes' => 
              array (
                'startLine' => 354,
                'endLine' => 354,
                'startTokenPos' => 1267,
                'startFilePos' => 9390,
                'endTokenPos' => 1267,
                'endFilePos' => 9392,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'float',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 354,
            'endLine' => 354,
            'startColumn' => 42,
            'endColumn' => 58,
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
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Sets the style of the blocks for the QrCode.
 *
 * @param string $style
 * @param float $size
 * @return Generator
 * @throws InvalidArgumentException
 */',
        'startLine' => 354,
        'endLine' => 368,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'SimpleSoftwareIO\\QrCode',
        'declaringClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'implementingClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'currentClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'aliasName' => NULL,
      ),
      'encoding' => 
      array (
        'name' => 'encoding',
        'parameters' => 
        array (
          'encoding' => 
          array (
            'name' => 'encoding',
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
            'startLine' => 382,
            'endLine' => 382,
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
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Sets the encoding for the QrCode.
 * Possible values are
 * ISO-8859-2, ISO-8859-3, ISO-8859-4, ISO-8859-5, ISO-8859-6,
 * ISO-8859-7, ISO-8859-8, ISO-8859-9, ISO-8859-10, ISO-8859-11,
 * ISO-8859-12, ISO-8859-13, ISO-8859-14, ISO-8859-15, ISO-8859-16,
 * SHIFT-JIS, WINDOWS-1250, WINDOWS-1251, WINDOWS-1252, WINDOWS-1256,
 * UTF-16BE, UTF-8, ASCII, GBK, EUC-KR.
 *
 * @param string $encoding
 * @return Generator
 */',
        'startLine' => 382,
        'endLine' => 387,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'SimpleSoftwareIO\\QrCode',
        'declaringClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'implementingClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'currentClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'aliasName' => NULL,
      ),
      'errorCorrection' => 
      array (
        'name' => 'errorCorrection',
        'parameters' => 
        array (
          'errorCorrection' => 
          array (
            'name' => 'errorCorrection',
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
            'startLine' => 399,
            'endLine' => 399,
            'startColumn' => 37,
            'endColumn' => 59,
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
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Sets the error correction for the QrCode.
 * L: 7% loss.
 * M: 15% loss.
 * Q: 25% loss.
 * H: 30% loss.
 *
 * @param string $errorCorrection
 * @return Generator
 */',
        'startLine' => 399,
        'endLine' => 405,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'SimpleSoftwareIO\\QrCode',
        'declaringClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'implementingClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'currentClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'aliasName' => NULL,
      ),
      'margin' => 
      array (
        'name' => 'margin',
        'parameters' => 
        array (
          'margin' => 
          array (
            'name' => 'margin',
            'default' => NULL,
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
            'startLine' => 413,
            'endLine' => 413,
            'startColumn' => 28,
            'endColumn' => 38,
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
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Sets the margin of the QrCode.
 *
 * @param int $margin
 * @return Generator
 */',
        'startLine' => 413,
        'endLine' => 418,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'SimpleSoftwareIO\\QrCode',
        'declaringClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'implementingClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'currentClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'aliasName' => NULL,
      ),
      'getWriter' => 
      array (
        'name' => 'getWriter',
        'parameters' => 
        array (
          'renderer' => 
          array (
            'name' => 'renderer',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'BaconQrCode\\Renderer\\ImageRenderer',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 426,
            'endLine' => 426,
            'startColumn' => 31,
            'endColumn' => 53,
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
            'name' => 'BaconQrCode\\Writer',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Fetches the Writer.
 *
 * @param ImageRenderer $renderer
 * @return Writer
 */',
        'startLine' => 426,
        'endLine' => 429,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'SimpleSoftwareIO\\QrCode',
        'declaringClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'implementingClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'currentClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'aliasName' => NULL,
      ),
      'getRenderer' => 
      array (
        'name' => 'getRenderer',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'BaconQrCode\\Renderer\\ImageRenderer',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Fetches the Image Renderer.
 *
 * @return ImageRenderer
 */',
        'startLine' => 436,
        'endLine' => 444,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'SimpleSoftwareIO\\QrCode',
        'declaringClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'implementingClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'currentClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'aliasName' => NULL,
      ),
      'getRendererStyle' => 
      array (
        'name' => 'getRendererStyle',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'BaconQrCode\\Renderer\\RendererStyle\\RendererStyle',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Returns the Renderer Style.
 *
 * @return RendererStyle
 */',
        'startLine' => 451,
        'endLine' => 454,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'SimpleSoftwareIO\\QrCode',
        'declaringClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'implementingClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'currentClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'aliasName' => NULL,
      ),
      'getFormatter' => 
      array (
        'name' => 'getFormatter',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'BaconQrCode\\Renderer\\Image\\ImageBackEndInterface',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Fetches the formatter.
 *
 * @return ImageBackEndInterface
 */',
        'startLine' => 461,
        'endLine' => 472,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'SimpleSoftwareIO\\QrCode',
        'declaringClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'implementingClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'currentClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'aliasName' => NULL,
      ),
      'getModule' => 
      array (
        'name' => 'getModule',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'BaconQrCode\\Renderer\\Module\\ModuleInterface',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Fetches the module.
 *
 * @return ModuleInterface
 */',
        'startLine' => 479,
        'endLine' => 490,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'SimpleSoftwareIO\\QrCode',
        'declaringClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'implementingClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'currentClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'aliasName' => NULL,
      ),
      'getEye' => 
      array (
        'name' => 'getEye',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'BaconQrCode\\Renderer\\Eye\\EyeInterface',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Fetches the eye style.
 *
 * @return EyeInterface
 */',
        'startLine' => 497,
        'endLine' => 508,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'SimpleSoftwareIO\\QrCode',
        'declaringClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'implementingClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'currentClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'aliasName' => NULL,
      ),
      'getFill' => 
      array (
        'name' => 'getFill',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'BaconQrCode\\Renderer\\RendererStyle\\Fill',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Fetches the color fill.
 *
 * @return Fill
 */',
        'startLine' => 515,
        'endLine' => 528,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'SimpleSoftwareIO\\QrCode',
        'declaringClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'implementingClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'currentClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'aliasName' => NULL,
      ),
      'createColor' => 
      array (
        'name' => 'createColor',
        'parameters' => 
        array (
          'red' => 
          array (
            'name' => 'red',
            'default' => NULL,
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
            'startLine' => 538,
            'endLine' => 538,
            'startColumn' => 33,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'green' => 
          array (
            'name' => 'green',
            'default' => NULL,
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
            'startLine' => 538,
            'endLine' => 538,
            'startColumn' => 43,
            'endColumn' => 52,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'blue' => 
          array (
            'name' => 'blue',
            'default' => NULL,
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
            'startLine' => 538,
            'endLine' => 538,
            'startColumn' => 55,
            'endColumn' => 63,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'alpha' => 
          array (
            'name' => 'alpha',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 538,
                'endLine' => 538,
                'startTokenPos' => 2080,
                'startFilePos' => 14056,
                'endTokenPos' => 2080,
                'endFilePos' => 14059,
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
            ),
            'startLine' => 538,
            'endLine' => 538,
            'startColumn' => 66,
            'endColumn' => 83,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'BaconQrCode\\Renderer\\Color\\ColorInterface',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Creates a RGB or Alpha channel color.
 * @param int $red
 * @param int $green
 * @param int $blue
 * @param null|int $alpha
 * @return ColorInterface
 */',
        'startLine' => 538,
        'endLine' => 545,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'SimpleSoftwareIO\\QrCode',
        'declaringClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'implementingClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'currentClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'aliasName' => NULL,
      ),
      'createClass' => 
      array (
        'name' => 'createClass',
        'parameters' => 
        array (
          'method' => 
          array (
            'name' => 'method',
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
            'startLine' => 553,
            'endLine' => 553,
            'startColumn' => 36,
            'endColumn' => 49,
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
            'name' => 'SimpleSoftwareIO\\QrCode\\DataTypes\\DataTypeInterface',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Creates a new DataType class dynamically.
 *
 * @param string $method
 * @return DataTypeInterface
 */',
        'startLine' => 553,
        'endLine' => 562,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'SimpleSoftwareIO\\QrCode',
        'declaringClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'implementingClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'currentClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'aliasName' => NULL,
      ),
      'formatClass' => 
      array (
        'name' => 'formatClass',
        'parameters' => 
        array (
          'method' => 
          array (
            'name' => 'method',
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
            'startLine' => 570,
            'endLine' => 570,
            'startColumn' => 36,
            'endColumn' => 49,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Formats the method name correctly.
 *
 * @param $method
 * @return string
 */',
        'startLine' => 570,
        'endLine' => 577,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'SimpleSoftwareIO\\QrCode',
        'declaringClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'implementingClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
        'currentClassName' => 'SimpleSoftwareIO\\QrCode\\Generator',
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