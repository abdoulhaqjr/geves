<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Sms\Contracts\SmsProvider.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Sms\Contracts\SmsProvider
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-ab5f0a8dc95f1b31ea16d1c46992e1506fa37098d632ff2950890c74594a6598',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Sms\\Contracts\\SmsProvider',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Sms/Contracts/SmsProvider.php',
      ),
    ),
    'namespace' => 'App\\Sms\\Contracts',
    'name' => 'App\\Sms\\Contracts\\SmsProvider',
    'shortName' => 'SmsProvider',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Passerelle d\'envoi de SMS (abstraction, calquée sur les passerelles de
 * paiement) : le fournisseur réel est configurable et ses identifiants ne sont
 * jamais commités. Un fournisseur « log » sert de défaut sûr en développement.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 10,
    'endLine' => 27,
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
      'key' => 
      array (
        'name' => 'key',
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
        'docComment' => '/** Identifiant technique (ex. « log », « http »). */',
        'startLine' => 13,
        'endLine' => 13,
        'startColumn' => 5,
        'endColumn' => 34,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Sms\\Contracts',
        'declaringClassName' => 'App\\Sms\\Contracts\\SmsProvider',
        'implementingClassName' => 'App\\Sms\\Contracts\\SmsProvider',
        'currentClassName' => 'App\\Sms\\Contracts\\SmsProvider',
        'aliasName' => NULL,
      ),
      'label' => 
      array (
        'name' => 'label',
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
        'docComment' => '/** Libellé lisible. */',
        'startLine' => 16,
        'endLine' => 16,
        'startColumn' => 5,
        'endColumn' => 36,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Sms\\Contracts',
        'declaringClassName' => 'App\\Sms\\Contracts\\SmsProvider',
        'implementingClassName' => 'App\\Sms\\Contracts\\SmsProvider',
        'currentClassName' => 'App\\Sms\\Contracts\\SmsProvider',
        'aliasName' => NULL,
      ),
      'isAvailable' => 
      array (
        'name' => 'isAvailable',
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
        'docComment' => '/** Le fournisseur est-il configuré et utilisable ? */',
        'startLine' => 19,
        'endLine' => 19,
        'startColumn' => 5,
        'endColumn' => 40,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Sms\\Contracts',
        'declaringClassName' => 'App\\Sms\\Contracts\\SmsProvider',
        'implementingClassName' => 'App\\Sms\\Contracts\\SmsProvider',
        'currentClassName' => 'App\\Sms\\Contracts\\SmsProvider',
        'aliasName' => NULL,
      ),
      'send' => 
      array (
        'name' => 'send',
        'parameters' => 
        array (
          'to' => 
          array (
            'name' => 'to',
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
            'startLine' => 26,
            'endLine' => 26,
            'startColumn' => 26,
            'endColumn' => 35,
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
            'startLine' => 26,
            'endLine' => 26,
            'startColumn' => 38,
            'endColumn' => 52,
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
 * Envoie un message court à un numéro (format international recommandé).
 *
 * @throws \\RuntimeException en cas d\'échec d\'envoi
 */',
        'startLine' => 26,
        'endLine' => 26,
        'startColumn' => 5,
        'endColumn' => 60,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Sms\\Contracts',
        'declaringClassName' => 'App\\Sms\\Contracts\\SmsProvider',
        'implementingClassName' => 'App\\Sms\\Contracts\\SmsProvider',
        'currentClassName' => 'App\\Sms\\Contracts\\SmsProvider',
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