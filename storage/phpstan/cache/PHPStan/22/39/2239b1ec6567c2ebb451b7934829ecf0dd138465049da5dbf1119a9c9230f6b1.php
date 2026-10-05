<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\AI\Concerns\RetriesTransientFailures.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\AI\Concerns\RetriesTransientFailures
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-d2296f1dda217095004e2be064a4bee74e7832d96e315c7e27cf92231cdbee5c',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\AI\\Concerns\\RetriesTransientFailures',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/AI/Concerns/RetriesTransientFailures.php',
      ),
    ),
    'namespace' => 'App\\AI\\Concerns',
    'name' => 'App\\AI\\Concerns\\RetriesTransientFailures',
    'shortName' => 'RetriesTransientFailures',
    'isInterface' => false,
    'isTrait' => true,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Relance transparente des requêtes IA en échec passager. Un modèle surchargé
 * répond fréquemment 503 (ou 429 / 502 / 504) ou coupe la connexion : on rejoue
 * alors la requête jusqu\'à 3 tentatives, sans que l\'utilisateur ne le voie.
 * Après la 3ᵉ tentative, on lève une {@see AiUnavailableException} porteuse d\'un
 * message clair destiné à l\'utilisateur.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 17,
    'endLine' => 56,
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
      'TRANSIENT_STATUSES' => 
      array (
        'declaringClassName' => 'App\\AI\\Concerns\\RetriesTransientFailures',
        'implementingClassName' => 'App\\AI\\Concerns\\RetriesTransientFailures',
        'name' => 'TRANSIENT_STATUSES',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[429, 500, 502, 503, 504]',
          'attributes' => 
          array (
            'startLine' => 20,
            'endLine' => 20,
            'startTokenPos' => 45,
            'startFilePos' => 724,
            'endTokenPos' => 59,
            'endFilePos' => 748,
          ),
        ),
        'docComment' => '/** Statuts HTTP considérés comme passagers (surcharge / passerelle). */',
        'attributes' => 
        array (
        ),
        'startLine' => 20,
        'endLine' => 20,
        'startColumn' => 5,
        'endColumn' => 65,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'retryOnOverload' => 
      array (
        'name' => 'retryOnOverload',
        'parameters' => 
        array (
          'callback' => 
          array (
            'name' => 'callback',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Closure',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 31,
            'endLine' => 31,
            'startColumn' => 40,
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
            'name' => 'mixed',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Exécute la requête et la rejoue en cas d\'échec passager (3 tentatives,
 * ~400 ms entre chacune). Les autres erreurs (4xx, etc.) remontent telles quelles.
 *
 * @template T
 *
 * @param  \\Closure(): T  $callback
 * @return T
 */',
        'startLine' => 31,
        'endLine' => 45,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\AI\\Concerns',
        'declaringClassName' => 'App\\AI\\Concerns\\RetriesTransientFailures',
        'implementingClassName' => 'App\\AI\\Concerns\\RetriesTransientFailures',
        'currentClassName' => 'App\\AI\\Concerns\\RetriesTransientFailures',
        'aliasName' => NULL,
      ),
      'isTransient' => 
      array (
        'name' => 'isTransient',
        'parameters' => 
        array (
          'e' => 
          array (
            'name' => 'e',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Throwable',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 47,
            'endLine' => 47,
            'startColumn' => 34,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 47,
        'endLine' => 55,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\AI\\Concerns',
        'declaringClassName' => 'App\\AI\\Concerns\\RetriesTransientFailures',
        'implementingClassName' => 'App\\AI\\Concerns\\RetriesTransientFailures',
        'currentClassName' => 'App\\AI\\Concerns\\RetriesTransientFailures',
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