<?php declare(strict_types = 1);

// phpinternal-PHPStan\BetterReflection\Reflection\ReflectionFunction-sodium_crypto_sign_seed_keypair
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-dev-master@709e512-8.4.24',
   'data' => 
  array (
    'name' => 'sodium_crypto_sign_seed_keypair',
    'parameters' => 
    array (
      'seed' => 
      array (
        'name' => 'seed',
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
        'startLine' => 12,
        'endLine' => 12,
        'startColumn' => 46,
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
 * Derive an Ed25519 keypair for use with the crypto_sign API from a seed
 * @link https://www.php.net/manual/en/function.sodium-crypto-sign-seed-keypair.php
 * @param string $seed
 * @return string
 * @throws SodiumException
 * @since 7.2
 */',
    'startLine' => 12,
    'endLine' => 14,
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
        'name' => 'sodium_crypto_sign_seed_keypair',
        'filename' => 'phpstorm-stubs:sodium/sodium.stub',
        'extensionName' => 'sodium',
        'aliasName' => NULL,
      ),
    ),
  ),
));