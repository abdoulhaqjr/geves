<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Console\Commands\AttestationKeygenCommand.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Console\Commands\AttestationKeygenCommand
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-86cd75d065687e3610f8171917c55a4a2840e5f01a5cc6df0e23a344cfeaa722',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Console\\Commands\\AttestationKeygenCommand',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Console/Commands/AttestationKeygenCommand.php',
      ),
    ),
    'namespace' => 'App\\Console\\Commands',
    'name' => 'App\\Console\\Commands\\AttestationKeygenCommand',
    'shortName' => 'AttestationKeygenCommand',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Génère un couple de clés Ed25519 pour la certification des attestations.
 *
 * La GRAINE PRIVÉE est affichée pour être copiée dans `.env`
 * (`ATTESTATION_SIGNING_KEY`) - elle ne doit JAMAIS être committée ni stockée en
 * base. La clé publique est publiée automatiquement via le document `did:web` et
 * le JWKS une fois la graine en place.
 *
 * Usage : `php artisan attestations:keygen`
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 18,
    'endLine' => 49,
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
        'declaringClassName' => 'App\\Console\\Commands\\AttestationKeygenCommand',
        'implementingClassName' => 'App\\Console\\Commands\\AttestationKeygenCommand',
        'name' => 'signature',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'attestations:keygen\'',
          'attributes' => 
          array (
            'startLine' => 20,
            'endLine' => 20,
            'startTokenPos' => 35,
            'startFilePos' => 584,
            'endTokenPos' => 35,
            'endFilePos' => 604,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 20,
        'endLine' => 20,
        'startColumn' => 5,
        'endColumn' => 49,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'description' => 
      array (
        'declaringClassName' => 'App\\Console\\Commands\\AttestationKeygenCommand',
        'implementingClassName' => 'App\\Console\\Commands\\AttestationKeygenCommand',
        'name' => 'description',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'Génère un couple de clés Ed25519 pour signer les attestations (identité vérifiable)\'',
          'attributes' => 
          array (
            'startLine' => 22,
            'endLine' => 22,
            'startTokenPos' => 44,
            'startFilePos' => 637,
            'endTokenPos' => 44,
            'endFilePos' => 726,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 22,
        'endLine' => 22,
        'startColumn' => 5,
        'endColumn' => 120,
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
      'handle' => 
      array (
        'name' => 'handle',
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
        ),
        'docComment' => NULL,
        'startLine' => 24,
        'endLine' => 48,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Console\\Commands',
        'declaringClassName' => 'App\\Console\\Commands\\AttestationKeygenCommand',
        'implementingClassName' => 'App\\Console\\Commands\\AttestationKeygenCommand',
        'currentClassName' => 'App\\Console\\Commands\\AttestationKeygenCommand',
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