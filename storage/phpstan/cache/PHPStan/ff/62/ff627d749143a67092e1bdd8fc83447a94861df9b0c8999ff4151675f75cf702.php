<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Meetings\LiveKitEgress.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Meetings\LiveKitEgress
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-b0f83a9de45bb841913cec584009c79d26841b28bb275e28f52aa651b3da82c6',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Meetings\\LiveKitEgress',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Meetings/LiveKitEgress.php',
      ),
    ),
    'namespace' => 'App\\Meetings',
    'name' => 'App\\Meetings\\LiveKitEgress',
    'shortName' => 'LiveKitEgress',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Client LiveKit Egress (enregistrement RoomComposite → fichier MP4). Démarre et
 * arrête l\'enregistrement d\'une salle via l\'API Egress (Twirp/JSON) du serveur
 * LiveKit, avec un jeton portant le droit `roomRecord`. Le fichier est déposé
 * dans un stockage objet compatible S3 configuré dans les réglages admin.
 *
 * L\'identifiant d\'egress renvoyé par « start » est mémorisé (cache) pour pouvoir
 * arrêter l\'enregistrement plus tard.
 *
 * @phpstan-type EgressResult array{egressId?: string, message?: string}
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 22,
    'endLine' => 176,
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
    ),
    'immediateMethods' => 
    array (
      'isConfigured' => 
      array (
        'name' => 'isConfigured',
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
        'docComment' => '/** L\'enregistrement est-il exploitable ? (stockage S3 complet). */',
        'startLine' => 25,
        'endLine' => 34,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Meetings',
        'declaringClassName' => 'App\\Meetings\\LiveKitEgress',
        'implementingClassName' => 'App\\Meetings\\LiveKitEgress',
        'currentClassName' => 'App\\Meetings\\LiveKitEgress',
        'aliasName' => NULL,
      ),
      'start' => 
      array (
        'name' => 'start',
        'parameters' => 
        array (
          'session' => 
          array (
            'name' => 'session',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\EventSession',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 37,
            'endLine' => 37,
            'startColumn' => 27,
            'endColumn' => 47,
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
        'docComment' => '/** @return EgressResult */',
        'startLine' => 37,
        'endLine' => 61,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Meetings',
        'declaringClassName' => 'App\\Meetings\\LiveKitEgress',
        'implementingClassName' => 'App\\Meetings\\LiveKitEgress',
        'currentClassName' => 'App\\Meetings\\LiveKitEgress',
        'aliasName' => NULL,
      ),
      'stop' => 
      array (
        'name' => 'stop',
        'parameters' => 
        array (
          'session' => 
          array (
            'name' => 'session',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\EventSession',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 64,
            'endLine' => 64,
            'startColumn' => 26,
            'endColumn' => 46,
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
        'docComment' => '/** @return EgressResult */',
        'startLine' => 64,
        'endLine' => 75,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Meetings',
        'declaringClassName' => 'App\\Meetings\\LiveKitEgress',
        'implementingClassName' => 'App\\Meetings\\LiveKitEgress',
        'currentClassName' => 'App\\Meetings\\LiveKitEgress',
        'aliasName' => NULL,
      ),
      'call' => 
      array (
        'name' => 'call',
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
            'startLine' => 83,
            'endLine' => 83,
            'startColumn' => 27,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'body' => 
          array (
            'name' => 'body',
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
            'startLine' => 83,
            'endLine' => 83,
            'startColumn' => 43,
            'endColumn' => 53,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Appelle un endpoint Twirp de l\'API Egress avec un jeton `roomRecord`.
 *
 * @param  array<string, mixed>  $body
 * @return array<string, mixed>
 */',
        'startLine' => 83,
        'endLine' => 104,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Meetings',
        'declaringClassName' => 'App\\Meetings\\LiveKitEgress',
        'implementingClassName' => 'App\\Meetings\\LiveKitEgress',
        'currentClassName' => 'App\\Meetings\\LiveKitEgress',
        'aliasName' => NULL,
      ),
      'token' => 
      array (
        'name' => 'token',
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
        'docComment' => '/** Jeton d\'accès (JWT HS256) portant le droit d\'enregistrer (`roomRecord`). */',
        'startLine' => 107,
        'endLine' => 125,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Meetings',
        'declaringClassName' => 'App\\Meetings\\LiveKitEgress',
        'implementingClassName' => 'App\\Meetings\\LiveKitEgress',
        'currentClassName' => 'App\\Meetings\\LiveKitEgress',
        'aliasName' => NULL,
      ),
      's3Config' => 
      array (
        'name' => 's3Config',
        'parameters' => 
        array (
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
 * Destination de stockage S3 (validée). Lève une exception si incomplète.
 *
 * @return array<string, string>
 */',
        'startLine' => 132,
        'endLine' => 154,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Meetings',
        'declaringClassName' => 'App\\Meetings\\LiveKitEgress',
        'implementingClassName' => 'App\\Meetings\\LiveKitEgress',
        'currentClassName' => 'App\\Meetings\\LiveKitEgress',
        'aliasName' => NULL,
      ),
      'apiBase' => 
      array (
        'name' => 'apiBase',
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
        'docComment' => '/** Base HTTP de l\'API LiveKit, dérivée de l\'URL WebSocket (wss→https). */',
        'startLine' => 157,
        'endLine' => 165,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Meetings',
        'declaringClassName' => 'App\\Meetings\\LiveKitEgress',
        'implementingClassName' => 'App\\Meetings\\LiveKitEgress',
        'currentClassName' => 'App\\Meetings\\LiveKitEgress',
        'aliasName' => NULL,
      ),
      'filepath' => 
      array (
        'name' => 'filepath',
        'parameters' => 
        array (
          'session' => 
          array (
            'name' => 'session',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\EventSession',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 167,
            'endLine' => 167,
            'startColumn' => 31,
            'endColumn' => 51,
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
        'docComment' => NULL,
        'startLine' => 167,
        'endLine' => 170,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Meetings',
        'declaringClassName' => 'App\\Meetings\\LiveKitEgress',
        'implementingClassName' => 'App\\Meetings\\LiveKitEgress',
        'currentClassName' => 'App\\Meetings\\LiveKitEgress',
        'aliasName' => NULL,
      ),
      'cacheKey' => 
      array (
        'name' => 'cacheKey',
        'parameters' => 
        array (
          'session' => 
          array (
            'name' => 'session',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\EventSession',
                'isIdentifier' => false,
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
            'startColumn' => 31,
            'endColumn' => 51,
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
        'docComment' => NULL,
        'startLine' => 172,
        'endLine' => 175,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Meetings',
        'declaringClassName' => 'App\\Meetings\\LiveKitEgress',
        'implementingClassName' => 'App\\Meetings\\LiveKitEgress',
        'currentClassName' => 'App\\Meetings\\LiveKitEgress',
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