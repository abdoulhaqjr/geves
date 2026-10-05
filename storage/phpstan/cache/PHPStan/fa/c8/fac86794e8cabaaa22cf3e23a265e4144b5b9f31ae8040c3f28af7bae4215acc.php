<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Notifications\NotificationKinds.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Notifications\NotificationKinds
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-05be89c882462d260514060e5ba6df7eadfde60b1b1fc897d74850ac4d2b4e1f',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Notifications\\NotificationKinds',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Notifications/NotificationKinds.php',
      ),
    ),
    'namespace' => 'App\\Notifications',
    'name' => 'App\\Notifications\\NotificationKinds',
    'shortName' => 'NotificationKinds',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Registre des types de notifications (source unique). Pour chaque `kind` :
 * libellé, audience (account | staff | both), canaux par défaut (in-app /
 * e-mail), et `hasMail` = un Mailable dédié (riche) couvre déjà l\'e-mail côté
 * participant. Quand `hasMail` est vrai, la notification n\'envoie PAS son e-mail
 * générique (pour éviter le doublon) : c\'est le Mailable dédié qui part, mais il
 * est désormais soumis à la préférence e-mail du destinataire. Les Mailables
 * dédiés étant tous côté participant, le staff (User) reçoit l\'e-mail générique.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 14,
    'endLine' => 73,
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
      'all' => 
      array (
        'name' => 'all',
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
 * @return array<string, array{label: string, audience: string, inapp: bool, email: bool, hasMail: bool}>
 */',
        'startLine' => 19,
        'endLine' => 36,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Notifications',
        'declaringClassName' => 'App\\Notifications\\NotificationKinds',
        'implementingClassName' => 'App\\Notifications\\NotificationKinds',
        'currentClassName' => 'App\\Notifications\\NotificationKinds',
        'aliasName' => NULL,
      ),
      'get' => 
      array (
        'name' => 'get',
        'parameters' => 
        array (
          'kind' => 
          array (
            'name' => 'kind',
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
            'startLine' => 39,
            'endLine' => 39,
            'startColumn' => 32,
            'endColumn' => 43,
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
        'docComment' => '/** Définition d\'un type (ou null si inconnu). */',
        'startLine' => 39,
        'endLine' => 42,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Notifications',
        'declaringClassName' => 'App\\Notifications\\NotificationKinds',
        'implementingClassName' => 'App\\Notifications\\NotificationKinds',
        'currentClassName' => 'App\\Notifications\\NotificationKinds',
        'aliasName' => NULL,
      ),
      'forAudience' => 
      array (
        'name' => 'forAudience',
        'parameters' => 
        array (
          'audience' => 
          array (
            'name' => 'audience',
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
            'startLine' => 49,
            'endLine' => 49,
            'startColumn' => 40,
            'endColumn' => 55,
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
        'docComment' => '/**
 * Types pertinents pour une audience donnée (\'account\' ou \'staff\').
 *
 * @return array<string, array{label: string, audience: string, inapp: bool, email: bool, hasMail: bool}>
 */',
        'startLine' => 49,
        'endLine' => 55,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Notifications',
        'declaringClassName' => 'App\\Notifications\\NotificationKinds',
        'implementingClassName' => 'App\\Notifications\\NotificationKinds',
        'currentClassName' => 'App\\Notifications\\NotificationKinds',
        'aliasName' => NULL,
      ),
      'defaults' => 
      array (
        'name' => 'defaults',
        'parameters' => 
        array (
          'kind' => 
          array (
            'name' => 'kind',
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
            'startLine' => 58,
            'endLine' => 58,
            'startColumn' => 37,
            'endColumn' => 48,
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
        'docComment' => '/** Canaux activés par défaut pour un type, sous forme { inapp, email }. */',
        'startLine' => 58,
        'endLine' => 66,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Notifications',
        'declaringClassName' => 'App\\Notifications\\NotificationKinds',
        'implementingClassName' => 'App\\Notifications\\NotificationKinds',
        'currentClassName' => 'App\\Notifications\\NotificationKinds',
        'aliasName' => NULL,
      ),
      'hasMail' => 
      array (
        'name' => 'hasMail',
        'parameters' => 
        array (
          'kind' => 
          array (
            'name' => 'kind',
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
            'startLine' => 69,
            'endLine' => 69,
            'startColumn' => 36,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Un Mailable dédié couvre déjà l\'e-mail (côté participant) pour ce type. */',
        'startLine' => 69,
        'endLine' => 72,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Notifications',
        'declaringClassName' => 'App\\Notifications\\NotificationKinds',
        'implementingClassName' => 'App\\Notifications\\NotificationKinds',
        'currentClassName' => 'App\\Notifications\\NotificationKinds',
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