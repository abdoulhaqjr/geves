<?php declare(strict_types = 1);

// osfsl-C:/Users/TOURE/Herd/gesco/vendor/composer/../firebase/php-jwt/src/JWT.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Firebase\JWT\JWT
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-46f9557389cb9ee8c0ab02c705481cc854819deb260b22a28070f1dd4a811ed6-8.4.24-6.70.0.3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Firebase\\JWT\\JWT',
        'filename' => 'C:/Users/TOURE/Herd/gesco/vendor/composer/../firebase/php-jwt/src/JWT.php',
      ),
    ),
    'namespace' => 'Firebase\\JWT',
    'name' => 'Firebase\\JWT\\JWT',
    'shortName' => 'JWT',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * JSON Web Token implementation, based on this spec:
 * https://tools.ietf.org/html/rfc7519
 *
 * PHP version 5
 *
 * @category Authentication
 * @package  Authentication_JWT
 * @author   Neuman Vong <neuman@twilio.com>
 * @author   Anant Narayanan <anant@php.net>
 * @license  http://opensource.org/licenses/BSD-3-Clause 3-clause BSD
 * @link     https://github.com/firebase/php-jwt
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 28,
    'endLine' => 833,
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
      'ASN1_INTEGER' => 
      array (
        'declaringClassName' => 'Firebase\\JWT\\JWT',
        'implementingClassName' => 'Firebase\\JWT\\JWT',
        'name' => 'ASN1_INTEGER',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '0x2',
          'attributes' => 
          array (
            'startLine' => 30,
            'endLine' => 30,
            'startTokenPos' => 68,
            'startFilePos' => 662,
            'endTokenPos' => 68,
            'endFilePos' => 665,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 30,
        'endLine' => 30,
        'startColumn' => 5,
        'endColumn' => 38,
      ),
      'ASN1_SEQUENCE' => 
      array (
        'declaringClassName' => 'Firebase\\JWT\\JWT',
        'implementingClassName' => 'Firebase\\JWT\\JWT',
        'name' => 'ASN1_SEQUENCE',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '0x10',
          'attributes' => 
          array (
            'startLine' => 31,
            'endLine' => 31,
            'startTokenPos' => 79,
            'startFilePos' => 702,
            'endTokenPos' => 79,
            'endFilePos' => 705,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 31,
        'endLine' => 31,
        'startColumn' => 5,
        'endColumn' => 39,
      ),
      'ASN1_BIT_STRING' => 
      array (
        'declaringClassName' => 'Firebase\\JWT\\JWT',
        'implementingClassName' => 'Firebase\\JWT\\JWT',
        'name' => 'ASN1_BIT_STRING',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '0x3',
          'attributes' => 
          array (
            'startLine' => 32,
            'endLine' => 32,
            'startTokenPos' => 90,
            'startFilePos' => 744,
            'endTokenPos' => 90,
            'endFilePos' => 747,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 32,
        'endLine' => 32,
        'startColumn' => 5,
        'endColumn' => 41,
      ),
      'RSA_KEY_MIN_LENGTH' => 
      array (
        'declaringClassName' => 'Firebase\\JWT\\JWT',
        'implementingClassName' => 'Firebase\\JWT\\JWT',
        'name' => 'RSA_KEY_MIN_LENGTH',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '2048',
          'attributes' => 
          array (
            'startLine' => 34,
            'endLine' => 34,
            'startTokenPos' => 101,
            'startFilePos' => 790,
            'endTokenPos' => 101,
            'endFilePos' => 793,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 34,
        'endLine' => 34,
        'startColumn' => 5,
        'endColumn' => 44,
      ),
    ),
    'immediateProperties' => 
    array (
      'leeway' => 
      array (
        'declaringClassName' => 'Firebase\\JWT\\JWT',
        'implementingClassName' => 'Firebase\\JWT\\JWT',
        'name' => 'leeway',
        'modifiers' => 17,
        'type' => NULL,
        'default' => 
        array (
          'code' => '0',
          'attributes' => 
          array (
            'startLine' => 43,
            'endLine' => 43,
            'startTokenPos' => 114,
            'startFilePos' => 998,
            'endTokenPos' => 114,
            'endFilePos' => 998,
          ),
        ),
        'docComment' => '/**
 * When checking nbf, iat or expiration times,
 * we want to provide some extra leeway time to
 * account for clock skew.
 *
 * @var int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 43,
        'endLine' => 43,
        'startColumn' => 5,
        'endColumn' => 30,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'timestamp' => 
      array (
        'declaringClassName' => 'Firebase\\JWT\\JWT',
        'implementingClassName' => 'Firebase\\JWT\\JWT',
        'name' => 'timestamp',
        'modifiers' => 17,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 52,
            'endLine' => 52,
            'startTokenPos' => 127,
            'startFilePos' => 1228,
            'endTokenPos' => 127,
            'endFilePos' => 1231,
          ),
        ),
        'docComment' => '/**
 * Allow the current timestamp to be specified.
 * Useful for fixing a value within unit testing.
 * Will default to PHP time() value if null.
 *
 * @var ?int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 52,
        'endLine' => 52,
        'startColumn' => 5,
        'endColumn' => 36,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'supported_algs' => 
      array (
        'declaringClassName' => 'Firebase\\JWT\\JWT',
        'implementingClassName' => 'Firebase\\JWT\\JWT',
        'name' => 'supported_algs',
        'modifiers' => 17,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'ES384\' => [\'openssl\', \'SHA384\'], \'ES256\' => [\'openssl\', \'SHA256\'], \'ES256K\' => [\'openssl\', \'SHA256\'], \'HS256\' => [\'hash_hmac\', \'SHA256\'], \'HS384\' => [\'hash_hmac\', \'SHA384\'], \'HS512\' => [\'hash_hmac\', \'SHA512\'], \'RS256\' => [\'openssl\', \'SHA256\'], \'RS384\' => [\'openssl\', \'SHA384\'], \'RS512\' => [\'openssl\', \'SHA512\'], \'PS256\' => [\'openssl\', \'SHA256\'], \'EdDSA\' => [\'sodium_crypto\', \'EdDSA\']]',
          'attributes' => 
          array (
            'startLine' => 57,
            'endLine' => 69,
            'startTokenPos' => 140,
            'startFilePos' => 1323,
            'endTokenPos' => 273,
            'endFilePos' => 1802,
          ),
        ),
        'docComment' => '/**
 * @var array<string, string[]>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 57,
        'endLine' => 69,
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
      'decode' => 
      array (
        'name' => 'decode',
        'parameters' => 
        array (
          'jwt' => 
          array (
            'name' => 'jwt',
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
            'startLine' => 100,
            'endLine' => 100,
            'startColumn' => 9,
            'endColumn' => 19,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'keyOrKeyArray' => 
          array (
            'name' => 'keyOrKeyArray',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
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
            'startLine' => 101,
            'endLine' => 101,
            'startColumn' => 9,
            'endColumn' => 45,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'headers' => 
          array (
            'name' => 'headers',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 102,
                'endLine' => 102,
                'startTokenPos' => 307,
                'startFilePos' => 3972,
                'endTokenPos' => 307,
                'endFilePos' => 3975,
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
                      'name' => 'stdClass',
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
            'byRef' => true,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 102,
            'endLine' => 102,
            'startColumn' => 9,
            'endColumn' => 34,
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
            'name' => 'stdClass',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Decodes a JWT string into a PHP object.
 *
 * @param string                 $jwt            The JWT
 * @param Key|ArrayAccess<string,Key>|array<string,Key> $keyOrKeyArray  The Key or associative array of key IDs
 *                                                                      (kid) to Key objects.
 *                                                                      If the algorithm used is asymmetric, this is
 *                                                                      the public key.
 *                                                                      Each Key object contains an algorithm and
 *                                                                      matching key.
 *                                                                      Supported algorithms are \'ES384\',\'ES256\',
 *                                                                      \'HS256\', \'HS384\', \'HS512\', \'RS256\', \'RS384\'
 *                                                                      and \'RS512\'.
 * @param stdClass               $headers                               Optional. Populates stdClass with headers.
 *
 * @return stdClass The JWT\'s payload as a PHP object
 *
 * @throws InvalidArgumentException     Provided key/key-array was empty or malformed
 * @throws DomainException              Provided JWT is malformed
 * @throws UnexpectedValueException     Provided JWT was invalid
 * @throws SignatureInvalidException    Provided JWT was invalid because the signature verification failed
 * @throws BeforeValidException         Provided JWT is trying to be used before it\'s eligible as defined by \'nbf\'
 * @throws BeforeValidException         Provided JWT is trying to be used before it\'s been created as defined by \'iat\'
 * @throws ExpiredException             Provided JWT has since expired, as defined by the \'exp\' claim
 *
 * @uses jsonDecode
 * @uses urlsafeB64Decode
 */',
        'startLine' => 99,
        'endLine' => 196,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Firebase\\JWT',
        'declaringClassName' => 'Firebase\\JWT\\JWT',
        'implementingClassName' => 'Firebase\\JWT\\JWT',
        'currentClassName' => 'Firebase\\JWT\\JWT',
        'aliasName' => NULL,
      ),
      'encode' => 
      array (
        'name' => 'encode',
        'parameters' => 
        array (
          'payload' => 
          array (
            'name' => 'payload',
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
            'startLine' => 214,
            'endLine' => 214,
            'startColumn' => 9,
            'endColumn' => 22,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
              0 => 
              array (
                'name' => 'SensitiveParameter',
                'isRepeated' => false,
                'arguments' => 
                array (
                ),
              ),
            ),
            'startLine' => 215,
            'endLine' => 215,
            'startColumn' => 9,
            'endColumn' => 35,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'alg' => 
          array (
            'name' => 'alg',
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
            'startLine' => 216,
            'endLine' => 216,
            'startColumn' => 9,
            'endColumn' => 19,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'keyId' => 
          array (
            'name' => 'keyId',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 217,
                'endLine' => 217,
                'startTokenPos' => 1213,
                'startFilePos' => 8917,
                'endTokenPos' => 1213,
                'endFilePos' => 8920,
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
            'startLine' => 217,
            'endLine' => 217,
            'startColumn' => 9,
            'endColumn' => 29,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
          'head' => 
          array (
            'name' => 'head',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 218,
                'endLine' => 218,
                'startTokenPos' => 1223,
                'startFilePos' => 8946,
                'endTokenPos' => 1223,
                'endFilePos' => 8949,
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
                      'name' => 'array',
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
            'startLine' => 218,
            'endLine' => 218,
            'startColumn' => 9,
            'endColumn' => 27,
            'parameterIndex' => 4,
            'isOptional' => true,
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
 * Converts and signs a PHP array into a JWT string.
 *
 * @param array<mixed>          $payload PHP array
 * @param string|OpenSSLAsymmetricKey|OpenSSLCertificate $key The secret key.
 * @param string                $alg     Supported algorithms are \'ES384\',\'ES256\', \'ES256K\', \'HS256\',
 *                                       \'HS384\', \'HS512\', \'RS256\', \'RS384\', and \'RS512\'
 * @param string                $keyId
 * @param array<string, string|string[]> $head  An array with header elements to attach
 *
 * @return string A signed JWT
 *
 * @uses jsonEncode
 * @uses urlsafeB64Encode
 */',
        'startLine' => 213,
        'endLine' => 246,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Firebase\\JWT',
        'declaringClassName' => 'Firebase\\JWT\\JWT',
        'implementingClassName' => 'Firebase\\JWT\\JWT',
        'currentClassName' => 'Firebase\\JWT\\JWT',
        'aliasName' => NULL,
      ),
      'sign' => 
      array (
        'name' => 'sign',
        'parameters' => 
        array (
          'msg' => 
          array (
            'name' => 'msg',
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
            'startLine' => 261,
            'endLine' => 261,
            'startColumn' => 9,
            'endColumn' => 19,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
              0 => 
              array (
                'name' => 'SensitiveParameter',
                'isRepeated' => false,
                'arguments' => 
                array (
                ),
              ),
            ),
            'startLine' => 262,
            'endLine' => 262,
            'startColumn' => 9,
            'endColumn' => 35,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'alg' => 
          array (
            'name' => 'alg',
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
            'startLine' => 263,
            'endLine' => 263,
            'startColumn' => 9,
            'endColumn' => 19,
            'parameterIndex' => 2,
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
 * Sign a string with a given key and algorithm.
 *
 * @param string $msg  The message to sign
 * @param string|OpenSSLAsymmetricKey|OpenSSLCertificate  $key  The secret key.
 * @param string $alg  Supported algorithms are \'EdDSA\', \'ES384\', \'ES256\', \'ES256K\', \'HS256\',
 *                    \'HS384\', \'HS512\', \'RS256\', \'RS384\', \'PS256\' and \'RS512\'
 *
 * @return string An encrypted message
 *
 * @throws DomainException Unsupported algorithm or bad key was specified
 */',
        'startLine' => 260,
        'endLine' => 308,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Firebase\\JWT',
        'declaringClassName' => 'Firebase\\JWT\\JWT',
        'implementingClassName' => 'Firebase\\JWT\\JWT',
        'currentClassName' => 'Firebase\\JWT\\JWT',
        'aliasName' => NULL,
      ),
      'verify' => 
      array (
        'name' => 'verify',
        'parameters' => 
        array (
          'msg' => 
          array (
            'name' => 'msg',
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
            'startLine' => 324,
            'endLine' => 324,
            'startColumn' => 9,
            'endColumn' => 19,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'signature' => 
          array (
            'name' => 'signature',
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
            'startLine' => 325,
            'endLine' => 325,
            'startColumn' => 9,
            'endColumn' => 25,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'keyMaterial' => 
          array (
            'name' => 'keyMaterial',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
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
            'startLine' => 326,
            'endLine' => 326,
            'startColumn' => 9,
            'endColumn' => 43,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'alg' => 
          array (
            'name' => 'alg',
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
            'startLine' => 327,
            'endLine' => 327,
            'startColumn' => 9,
            'endColumn' => 19,
            'parameterIndex' => 3,
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
        ),
        'docComment' => '/**
 * Verify a signature with the message, key and method. Not all methods
 * are symmetric, so we must have a separate verify and sign method.
 *
 * @param string $msg         The original message (header and body)
 * @param string $signature   The original signature
 * @param string|OpenSSLAsymmetricKey|OpenSSLCertificate  $keyMaterial For Ed*, ES*, HS*, a string key works. for RS*, must be an instance of OpenSSLAsymmetricKey
 * @param string $alg         The algorithm
 *
 * @return bool
 *
 * @throws DomainException Invalid Algorithm, bad key, or OpenSSL failure
 */',
        'startLine' => 323,
        'endLine' => 377,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 20,
        'namespace' => 'Firebase\\JWT',
        'declaringClassName' => 'Firebase\\JWT\\JWT',
        'implementingClassName' => 'Firebase\\JWT\\JWT',
        'currentClassName' => 'Firebase\\JWT\\JWT',
        'aliasName' => NULL,
      ),
      'jsonDecode' => 
      array (
        'name' => 'jsonDecode',
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
            'startLine' => 388,
            'endLine' => 388,
            'startColumn' => 39,
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
 * Decode a JSON string into a PHP object.
 *
 * @param string $input JSON string
 *
 * @return mixed The decoded JSON string
 *
 * @throws DomainException Provided string was invalid JSON
 */',
        'startLine' => 388,
        'endLine' => 398,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Firebase\\JWT',
        'declaringClassName' => 'Firebase\\JWT\\JWT',
        'implementingClassName' => 'Firebase\\JWT\\JWT',
        'currentClassName' => 'Firebase\\JWT\\JWT',
        'aliasName' => NULL,
      ),
      'jsonEncode' => 
      array (
        'name' => 'jsonEncode',
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
            'startLine' => 409,
            'endLine' => 409,
            'startColumn' => 39,
            'endColumn' => 50,
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
 * Encode a PHP array into a JSON string.
 *
 * @param array<mixed> $input A PHP array
 *
 * @return string JSON representation of the PHP array
 *
 * @throws DomainException Provided object could not be encoded to valid JSON
 */',
        'startLine' => 409,
        'endLine' => 421,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Firebase\\JWT',
        'declaringClassName' => 'Firebase\\JWT\\JWT',
        'implementingClassName' => 'Firebase\\JWT\\JWT',
        'currentClassName' => 'Firebase\\JWT\\JWT',
        'aliasName' => NULL,
      ),
      'urlsafeB64Decode' => 
      array (
        'name' => 'urlsafeB64Decode',
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
            'startLine' => 432,
            'endLine' => 432,
            'startColumn' => 45,
            'endColumn' => 57,
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
 * Decode a string with URL-safe Base64.
 *
 * @param string $input A Base64 encoded string
 *
 * @return string A decoded string
 *
 * @throws InvalidArgumentException invalid base64 characters
 */',
        'startLine' => 432,
        'endLine' => 435,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Firebase\\JWT',
        'declaringClassName' => 'Firebase\\JWT\\JWT',
        'implementingClassName' => 'Firebase\\JWT\\JWT',
        'currentClassName' => 'Firebase\\JWT\\JWT',
        'aliasName' => NULL,
      ),
      'convertBase64UrlToBase64' => 
      array (
        'name' => 'convertBase64UrlToBase64',
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
            'startLine' => 447,
            'endLine' => 447,
            'startColumn' => 53,
            'endColumn' => 65,
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
 * Convert a string in the base64url (URL-safe Base64) encoding to standard base64.
 *
 * @param string $input A Base64 encoded string with URL-safe characters (-_ and no padding)
 *
 * @return string A Base64 encoded string with standard characters (+/) and padding (=), when
 * needed.
 *
 * @see https://www.rfc-editor.org/rfc/rfc4648
 */',
        'startLine' => 447,
        'endLine' => 455,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Firebase\\JWT',
        'declaringClassName' => 'Firebase\\JWT\\JWT',
        'implementingClassName' => 'Firebase\\JWT\\JWT',
        'currentClassName' => 'Firebase\\JWT\\JWT',
        'aliasName' => NULL,
      ),
      'urlsafeB64Encode' => 
      array (
        'name' => 'urlsafeB64Encode',
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
            'startLine' => 464,
            'endLine' => 464,
            'startColumn' => 45,
            'endColumn' => 57,
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
 * Encode a string with URL-safe Base64.
 *
 * @param string $input The string you want encoded
 *
 * @return string The base64 encode of what you passed in
 */',
        'startLine' => 464,
        'endLine' => 467,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Firebase\\JWT',
        'declaringClassName' => 'Firebase\\JWT\\JWT',
        'implementingClassName' => 'Firebase\\JWT\\JWT',
        'currentClassName' => 'Firebase\\JWT\\JWT',
        'aliasName' => NULL,
      ),
      'getKey' => 
      array (
        'name' => 'getKey',
        'parameters' => 
        array (
          'keyOrKeyArray' => 
          array (
            'name' => 'keyOrKeyArray',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
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
            'startLine' => 480,
            'endLine' => 480,
            'startColumn' => 9,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'kid' => 
          array (
            'name' => 'kid',
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
            ),
            'startLine' => 481,
            'endLine' => 481,
            'startColumn' => 9,
            'endColumn' => 20,
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
            'name' => 'Firebase\\JWT\\Key',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Determine if an algorithm has been provided for each Key
 *
 * @param Key|ArrayAccess<string,Key>|array<string,Key> $keyOrKeyArray
 * @param string|null            $kid
 *
 * @throws UnexpectedValueException
 *
 * @return Key
 */',
        'startLine' => 479,
        'endLine' => 501,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 20,
        'namespace' => 'Firebase\\JWT',
        'declaringClassName' => 'Firebase\\JWT\\JWT',
        'implementingClassName' => 'Firebase\\JWT\\JWT',
        'currentClassName' => 'Firebase\\JWT\\JWT',
        'aliasName' => NULL,
      ),
      'constantTimeEquals' => 
      array (
        'name' => 'constantTimeEquals',
        'parameters' => 
        array (
          'left' => 
          array (
            'name' => 'left',
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
            'startLine' => 508,
            'endLine' => 508,
            'startColumn' => 47,
            'endColumn' => 58,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'right' => 
          array (
            'name' => 'right',
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
            'startLine' => 508,
            'endLine' => 508,
            'startColumn' => 61,
            'endColumn' => 73,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param string $left  The string of known length to compare against
 * @param string $right The user-supplied string
 * @return bool
 */',
        'startLine' => 508,
        'endLine' => 522,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Firebase\\JWT',
        'declaringClassName' => 'Firebase\\JWT\\JWT',
        'implementingClassName' => 'Firebase\\JWT\\JWT',
        'currentClassName' => 'Firebase\\JWT\\JWT',
        'aliasName' => NULL,
      ),
      'handleJsonError' => 
      array (
        'name' => 'handleJsonError',
        'parameters' => 
        array (
          'errno' => 
          array (
            'name' => 'errno',
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
            'startLine' => 533,
            'endLine' => 533,
            'startColumn' => 45,
            'endColumn' => 54,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Helper method to create a JSON error.
 *
 * @param int $errno An error number from json_last_error()
 *
 * @throws DomainException
 *
 * @return void
 */',
        'startLine' => 533,
        'endLine' => 547,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 20,
        'namespace' => 'Firebase\\JWT',
        'declaringClassName' => 'Firebase\\JWT\\JWT',
        'implementingClassName' => 'Firebase\\JWT\\JWT',
        'currentClassName' => 'Firebase\\JWT\\JWT',
        'aliasName' => NULL,
      ),
      'safeStrlen' => 
      array (
        'name' => 'safeStrlen',
        'parameters' => 
        array (
          'str' => 
          array (
            'name' => 'str',
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
            'startLine' => 556,
            'endLine' => 556,
            'startColumn' => 40,
            'endColumn' => 50,
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
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the number of bytes in cryptographic strings.
 *
 * @param string $str
 *
 * @return int
 */',
        'startLine' => 556,
        'endLine' => 562,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 20,
        'namespace' => 'Firebase\\JWT',
        'declaringClassName' => 'Firebase\\JWT\\JWT',
        'implementingClassName' => 'Firebase\\JWT\\JWT',
        'currentClassName' => 'Firebase\\JWT\\JWT',
        'aliasName' => NULL,
      ),
      'signatureToDER' => 
      array (
        'name' => 'signatureToDER',
        'parameters' => 
        array (
          'sig' => 
          array (
            'name' => 'sig',
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
            'startColumn' => 44,
            'endColumn' => 54,
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
 * Convert an ECDSA signature to an ASN.1 DER sequence
 *
 * @param   string $sig The ECDSA signature to convert
 * @return  string The encoded DER object
 */',
        'startLine' => 570,
        'endLine' => 594,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 20,
        'namespace' => 'Firebase\\JWT',
        'declaringClassName' => 'Firebase\\JWT\\JWT',
        'implementingClassName' => 'Firebase\\JWT\\JWT',
        'currentClassName' => 'Firebase\\JWT\\JWT',
        'aliasName' => NULL,
      ),
      'encodeDER' => 
      array (
        'name' => 'encodeDER',
        'parameters' => 
        array (
          'type' => 
          array (
            'name' => 'type',
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
            'startLine' => 604,
            'endLine' => 604,
            'startColumn' => 39,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'value' => 
          array (
            'name' => 'value',
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
            'startLine' => 604,
            'endLine' => 604,
            'startColumn' => 50,
            'endColumn' => 62,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Encodes a value into a DER object.
 *
 * @param   int     $type DER tag
 * @param   string  $value the value to encode
 *
 * @return  string  the encoded object
 */',
        'startLine' => 604,
        'endLine' => 618,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 20,
        'namespace' => 'Firebase\\JWT',
        'declaringClassName' => 'Firebase\\JWT\\JWT',
        'implementingClassName' => 'Firebase\\JWT\\JWT',
        'currentClassName' => 'Firebase\\JWT\\JWT',
        'aliasName' => NULL,
      ),
      'signatureFromDER' => 
      array (
        'name' => 'signatureFromDER',
        'parameters' => 
        array (
          'der' => 
          array (
            'name' => 'der',
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
            'startLine' => 628,
            'endLine' => 628,
            'startColumn' => 46,
            'endColumn' => 56,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'keySize' => 
          array (
            'name' => 'keySize',
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
            'startLine' => 628,
            'endLine' => 628,
            'startColumn' => 59,
            'endColumn' => 70,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Encodes signature from a DER object.
 *
 * @param   string  $der binary signature in DER format
 * @param   int     $keySize the number of bits in the key
 *
 * @return  string  the signature
 */',
        'startLine' => 628,
        'endLine' => 645,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 20,
        'namespace' => 'Firebase\\JWT',
        'declaringClassName' => 'Firebase\\JWT\\JWT',
        'implementingClassName' => 'Firebase\\JWT\\JWT',
        'currentClassName' => 'Firebase\\JWT\\JWT',
        'aliasName' => NULL,
      ),
      'readDER' => 
      array (
        'name' => 'readDER',
        'parameters' => 
        array (
          'der' => 
          array (
            'name' => 'der',
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
            'startLine' => 656,
            'endLine' => 656,
            'startColumn' => 37,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'offset' => 
          array (
            'name' => 'offset',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 656,
                'endLine' => 656,
                'startTokenPos' => 3764,
                'startFilePos' => 24259,
                'endTokenPos' => 3764,
                'endFilePos' => 24259,
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
            'startLine' => 656,
            'endLine' => 656,
            'startColumn' => 50,
            'endColumn' => 64,
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
        ),
        'docComment' => '/**
 * Reads binary DER-encoded data and decodes into a single object
 *
 * @param string $der the binary data in DER format
 * @param int $offset the offset of the data stream containing the object
 * to decode
 *
 * @return array{int, string|null} the new offset and the decoded object
 */',
        'startLine' => 656,
        'endLine' => 686,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 20,
        'namespace' => 'Firebase\\JWT',
        'declaringClassName' => 'Firebase\\JWT\\JWT',
        'implementingClassName' => 'Firebase\\JWT\\JWT',
        'currentClassName' => 'Firebase\\JWT\\JWT',
        'aliasName' => NULL,
      ),
      'validateHmacKeyLength' => 
      array (
        'name' => 'validateHmacKeyLength',
        'parameters' => 
        array (
          'key' => 
          array (
            'name' => 'key',
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
            'startLine' => 696,
            'endLine' => 696,
            'startColumn' => 51,
            'endColumn' => 61,
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 696,
            'endLine' => 696,
            'startColumn' => 64,
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
        ),
        'docComment' => '/**
 * Validate HMAC key length
 *
 * @param string $key HMAC key material
 * @param string $algorithm The algorithm
 *
 * @throws DomainException Provided key is too short
 */',
        'startLine' => 696,
        'endLine' => 703,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 20,
        'namespace' => 'Firebase\\JWT',
        'declaringClassName' => 'Firebase\\JWT\\JWT',
        'implementingClassName' => 'Firebase\\JWT\\JWT',
        'currentClassName' => 'Firebase\\JWT\\JWT',
        'aliasName' => NULL,
      ),
      'validateRsaKeyLength' => 
      array (
        'name' => 'validateRsaKeyLength',
        'parameters' => 
        array (
          'key' => 
          array (
            'name' => 'key',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OpenSSLAsymmetricKey',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
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
            'startLine' => 711,
            'endLine' => 711,
            'startColumn' => 50,
            'endColumn' => 97,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Validate RSA key length
 *
 * @param OpenSSLAsymmetricKey $key RSA key material
 * @throws DomainException Provided key is too short
 */',
        'startLine' => 711,
        'endLine' => 719,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 20,
        'namespace' => 'Firebase\\JWT',
        'declaringClassName' => 'Firebase\\JWT\\JWT',
        'implementingClassName' => 'Firebase\\JWT\\JWT',
        'currentClassName' => 'Firebase\\JWT\\JWT',
        'aliasName' => NULL,
      ),
      'validateEcKeyLength' => 
      array (
        'name' => 'validateEcKeyLength',
        'parameters' => 
        array (
          'key' => 
          array (
            'name' => 'key',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OpenSSLAsymmetricKey',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
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
            'startLine' => 729,
            'endLine' => 729,
            'startColumn' => 9,
            'endColumn' => 56,
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 730,
            'endLine' => 730,
            'startColumn' => 9,
            'endColumn' => 25,
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
 * Validate RSA key length
 *
 * @param OpenSSLAsymmetricKey $key RSA key material
 * @param string $algorithm The algorithm
 * @throws DomainException Provided key is too short
 */',
        'startLine' => 728,
        'endLine' => 739,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 20,
        'namespace' => 'Firebase\\JWT',
        'declaringClassName' => 'Firebase\\JWT\\JWT',
        'implementingClassName' => 'Firebase\\JWT\\JWT',
        'currentClassName' => 'Firebase\\JWT\\JWT',
        'aliasName' => NULL,
      ),
      'validateEdDSAKey' => 
      array (
        'name' => 'validateEdDSAKey',
        'parameters' => 
        array (
          'keyMaterial' => 
          array (
            'name' => 'keyMaterial',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
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
            'startLine' => 745,
            'endLine' => 745,
            'startColumn' => 46,
            'endColumn' => 80,
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
 * @param string|OpenSSLAsymmetricKey|OpenSSLCertificate  $keyMaterial
 * @return non-empty-string
 */',
        'startLine' => 745,
        'endLine' => 760,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 20,
        'namespace' => 'Firebase\\JWT',
        'declaringClassName' => 'Firebase\\JWT\\JWT',
        'implementingClassName' => 'Firebase\\JWT\\JWT',
        'currentClassName' => 'Firebase\\JWT\\JWT',
        'aliasName' => NULL,
      ),
      'signPS256' => 
      array (
        'name' => 'signPS256',
        'parameters' => 
        array (
          'key' => 
          array (
            'name' => 'key',
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
                      'name' => 'OpenSSLAsymmetricKey',
                      'isIdentifier' => false,
                    ),
                  ),
                  2 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'OpenSSLCertificate',
                      'isIdentifier' => false,
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
                'name' => 'SensitiveParameter',
                'isRepeated' => false,
                'arguments' => 
                array (
                ),
              ),
            ),
            'startLine' => 770,
            'endLine' => 770,
            'startColumn' => 9,
            'endColumn' => 82,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
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
            'startLine' => 771,
            'endLine' => 771,
            'startColumn' => 9,
            'endColumn' => 23,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Signs a message with a PS256 algorithm
 *
 * @param string|OpenSSLAsymmetricKey|OpenSSLCertificate $key
 * @param string $message
 * @throws DomainException Provided key is invalid
 */',
        'startLine' => 769,
        'endLine' => 794,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 20,
        'namespace' => 'Firebase\\JWT',
        'declaringClassName' => 'Firebase\\JWT\\JWT',
        'implementingClassName' => 'Firebase\\JWT\\JWT',
        'currentClassName' => 'Firebase\\JWT\\JWT',
        'aliasName' => NULL,
      ),
      'verifyPS256' => 
      array (
        'name' => 'verifyPS256',
        'parameters' => 
        array (
          'key' => 
          array (
            'name' => 'key',
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
                      'name' => 'OpenSSLAsymmetricKey',
                      'isIdentifier' => false,
                    ),
                  ),
                  2 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'OpenSSLCertificate',
                      'isIdentifier' => false,
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
                'name' => 'SensitiveParameter',
                'isRepeated' => false,
                'arguments' => 
                array (
                ),
              ),
            ),
            'startLine' => 805,
            'endLine' => 805,
            'startColumn' => 9,
            'endColumn' => 82,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
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
            'startLine' => 806,
            'endLine' => 806,
            'startColumn' => 9,
            'endColumn' => 23,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'signature' => 
          array (
            'name' => 'signature',
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
            'startLine' => 807,
            'endLine' => 807,
            'startColumn' => 9,
            'endColumn' => 25,
            'parameterIndex' => 2,
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
        ),
        'docComment' => '/**
 * Validates a PS256 algorithm signature.
 *
 * @param string|OpenSSLAsymmetricKey|OpenSSLCertificate $key
 * @param string $message
 * @param string $signature
 * @throws DomainException Provided key is invalid
 */',
        'startLine' => 804,
        'endLine' => 832,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 20,
        'namespace' => 'Firebase\\JWT',
        'declaringClassName' => 'Firebase\\JWT\\JWT',
        'implementingClassName' => 'Firebase\\JWT\\JWT',
        'currentClassName' => 'Firebase\\JWT\\JWT',
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