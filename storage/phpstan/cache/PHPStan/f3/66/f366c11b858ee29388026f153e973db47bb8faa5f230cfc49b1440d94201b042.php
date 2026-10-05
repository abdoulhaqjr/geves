<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Models\Concerns\HasNotificationPreferences.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Models\Concerns\HasNotificationPreferences
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-be2c20c3202d445c4148690d49d5bc96fa882247d5667fc55ef92d5f703acd8c',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Models\\Concerns\\HasNotificationPreferences',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Models/Concerns/HasNotificationPreferences.php',
      ),
    ),
    'namespace' => 'App\\Models\\Concerns',
    'name' => 'App\\Models\\Concerns\\HasNotificationPreferences',
    'shortName' => 'HasNotificationPreferences',
    'isInterface' => false,
    'isTrait' => true,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Préférences de notification par type et par canal, portées par la colonne
 * JSON `notification_preferences`. Une valeur absente retombe sur le défaut du
 * registre {@see NotificationKinds}. Canaux logiques : « inapp » (base de
 * données) et « email » (mail). Le modèle qui l\'utilise doit caster
 * `notification_preferences` en `array`.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 14,
    'endLine' => 98,
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
      'wantsNotification' => 
      array (
        'name' => 'wantsNotification',
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
            'startLine' => 17,
            'endLine' => 17,
            'startColumn' => 39,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'channel' => 
          array (
            'name' => 'channel',
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
            'startLine' => 17,
            'endLine' => 17,
            'startColumn' => 53,
            'endColumn' => 67,
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
        'docComment' => '/** L\'utilisateur veut-il ce type sur ce canal logique (\'inapp\'|\'email\') ? */',
        'startLine' => 17,
        'endLine' => 23,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models\\Concerns',
        'declaringClassName' => 'App\\Models\\Concerns\\HasNotificationPreferences',
        'implementingClassName' => 'App\\Models\\Concerns\\HasNotificationPreferences',
        'currentClassName' => 'App\\Models\\Concerns\\HasNotificationPreferences',
        'aliasName' => NULL,
      ),
      'notificationChannels' => 
      array (
        'name' => 'notificationChannels',
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
            'startLine' => 33,
            'endLine' => 33,
            'startColumn' => 42,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Canaux Laravel activés pour un type (database / mail). L\'e-mail générique
 * n\'est ajouté que si aucun Mailable dédié ne couvre déjà ce type pour ce
 * destinataire - sinon c\'est le Mailable dédié (gaté par la préférence) qui
 * porte l\'e-mail, pour éviter le doublon.
 *
 * @return array<int, string>
 */',
        'startLine' => 33,
        'endLine' => 44,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models\\Concerns',
        'declaringClassName' => 'App\\Models\\Concerns\\HasNotificationPreferences',
        'implementingClassName' => 'App\\Models\\Concerns\\HasNotificationPreferences',
        'currentClassName' => 'App\\Models\\Concerns\\HasNotificationPreferences',
        'aliasName' => NULL,
      ),
      'usesDedicatedMailFor' => 
      array (
        'name' => 'usesDedicatedMailFor',
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
            'startLine' => 51,
            'endLine' => 51,
            'startColumn' => 45,
            'endColumn' => 56,
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
        'docComment' => '/**
 * Un Mailable dédié couvre-t-il déjà l\'e-mail de ce type pour CE
 * destinataire ? Vrai pour les comptes participants (Mailables riches
 * existants) ; le staff surcharge à false (aucun Mailable dédié côté staff).
 */',
        'startLine' => 51,
        'endLine' => 54,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Models\\Concerns',
        'declaringClassName' => 'App\\Models\\Concerns\\HasNotificationPreferences',
        'implementingClassName' => 'App\\Models\\Concerns\\HasNotificationPreferences',
        'currentClassName' => 'App\\Models\\Concerns\\HasNotificationPreferences',
        'aliasName' => NULL,
      ),
      'resolvedNotificationPreferences' => 
      array (
        'name' => 'resolvedNotificationPreferences',
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
            'startLine' => 62,
            'endLine' => 62,
            'startColumn' => 53,
            'endColumn' => 68,
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
 * Préférences résolues (défauts fusionnés) pour l\'audience du modèle, pour
 * l\'affichage dans l\'UI. Clé = kind, valeur = { label, inapp, email }.
 *
 * @return array<string, array{label: string, inapp: bool, email: bool}>
 */',
        'startLine' => 62,
        'endLine' => 74,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models\\Concerns',
        'declaringClassName' => 'App\\Models\\Concerns\\HasNotificationPreferences',
        'implementingClassName' => 'App\\Models\\Concerns\\HasNotificationPreferences',
        'currentClassName' => 'App\\Models\\Concerns\\HasNotificationPreferences',
        'aliasName' => NULL,
      ),
      'saveNotificationPreferences' => 
      array (
        'name' => 'saveNotificationPreferences',
        'parameters' => 
        array (
          'prefs' => 
          array (
            'name' => 'prefs',
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
            'startLine' => 82,
            'endLine' => 82,
            'startColumn' => 49,
            'endColumn' => 60,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 82,
            'endLine' => 82,
            'startColumn' => 63,
            'endColumn' => 78,
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
 * Enregistre les préférences depuis l\'UI (map kind => { inapp, email }),
 * restreinte aux types de l\'audience donnée.
 *
 * @param  array<string, array{inapp?: bool, email?: bool}>  $prefs
 */',
        'startLine' => 82,
        'endLine' => 97,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models\\Concerns',
        'declaringClassName' => 'App\\Models\\Concerns\\HasNotificationPreferences',
        'implementingClassName' => 'App\\Models\\Concerns\\HasNotificationPreferences',
        'currentClassName' => 'App\\Models\\Concerns\\HasNotificationPreferences',
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