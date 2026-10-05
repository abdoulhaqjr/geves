<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Payments\Contracts\OnlineGateway.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Payments\Contracts\OnlineGateway
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-509c45faae9cdb01dd843e29e54fd41b8cded28612e29844f34a2b9f1307906a',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Payments\\Contracts\\OnlineGateway',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Payments/Contracts/OnlineGateway.php',
      ),
    ),
    'namespace' => 'App\\Payments\\Contracts',
    'name' => 'App\\Payments\\Contracts\\OnlineGateway',
    'shortName' => 'OnlineGateway',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Passerelle de paiement en ligne : sait vérifier le statut d\'un paiement auprès
 * du prestataire et interpréter une notification (webhook) de façon sûre.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 12,
    'endLine' => 38,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'App\\Payments\\Contracts\\PaymentGateway',
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
      'mobileOperators' => 
      array (
        'name' => 'mobileOperators',
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
 * Opérateurs mobile money proposables au payeur AVANT la redirection, pour que
 * la page du prestataire arrive pré-sélectionnée. Clé = code attendu par le
 * prestataire, valeur = libellé affiché (⚠️ jamais le nom de l\'agrégateur).
 * Tableau vide si la passerelle ne sait pas cibler un opérateur.
 *
 * @return array<string, string>
 */',
        'startLine' => 22,
        'endLine' => 22,
        'startColumn' => 5,
        'endColumn' => 45,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Payments\\Contracts',
        'declaringClassName' => 'App\\Payments\\Contracts\\OnlineGateway',
        'implementingClassName' => 'App\\Payments\\Contracts\\OnlineGateway',
        'currentClassName' => 'App\\Payments\\Contracts\\OnlineGateway',
        'aliasName' => NULL,
      ),
      'verify' => 
      array (
        'name' => 'verify',
        'parameters' => 
        array (
          'payment' => 
          array (
            'name' => 'payment',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Payment',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 29,
            'endLine' => 29,
            'startColumn' => 28,
            'endColumn' => 43,
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
 * Interroge le prestataire et renvoie un statut normalisé.
 *
 * @return \'paid\'|\'pending\'|\'failed\'
 */',
        'startLine' => 29,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 53,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Payments\\Contracts',
        'declaringClassName' => 'App\\Payments\\Contracts\\OnlineGateway',
        'implementingClassName' => 'App\\Payments\\Contracts\\OnlineGateway',
        'currentClassName' => 'App\\Payments\\Contracts\\OnlineGateway',
        'aliasName' => NULL,
      ),
      'parseWebhook' => 
      array (
        'name' => 'parseWebhook',
        'parameters' => 
        array (
          'request' => 
          array (
            'name' => 'request',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Http\\Request',
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
            'startColumn' => 34,
            'endColumn' => 49,
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
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Vérifie l\'authenticité d\'une notification et en extrait la référence et le
 * statut. Retourne null si la signature est invalide (fail-closed).
 *
 * @return array{reference: string, status: \'paid\'|\'pending\'|\'failed\'}|null
 */',
        'startLine' => 37,
        'endLine' => 37,
        'startColumn' => 5,
        'endColumn' => 59,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Payments\\Contracts',
        'declaringClassName' => 'App\\Payments\\Contracts\\OnlineGateway',
        'implementingClassName' => 'App\\Payments\\Contracts\\OnlineGateway',
        'currentClassName' => 'App\\Payments\\Contracts\\OnlineGateway',
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