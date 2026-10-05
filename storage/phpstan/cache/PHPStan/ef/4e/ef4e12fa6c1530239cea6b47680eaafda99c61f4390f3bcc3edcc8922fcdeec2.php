<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Http\Controllers\Account\PreferencesController.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Http\Controllers\Account\PreferencesController
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-1bda830fa729b2111eb8ffef37a0781b5ef01ecd8b91dcd301aa4b4cb34033cc',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Http\\Controllers\\Account\\PreferencesController',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Http/Controllers/Account/PreferencesController.php',
      ),
    ),
    'namespace' => 'App\\Http\\Controllers\\Account',
    'name' => 'App\\Http\\Controllers\\Account\\PreferencesController',
    'shortName' => 'PreferencesController',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Préférences du compte participant (onglet « Préférences » de Mon compte, fidèle
 * au mockup compte.html) : langue, fuseau, format de date, thème et canaux de
 * communication. Stockées dans `accounts.meta[\'prefs\']`.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 18,
    'endLine' => 93,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'App\\Http\\Controllers\\Controller',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'DEFAULTS' => 
      array (
        'declaringClassName' => 'App\\Http\\Controllers\\Account\\PreferencesController',
        'implementingClassName' => 'App\\Http\\Controllers\\Account\\PreferencesController',
        'name' => 'DEFAULTS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'lang\' => \'fr\', \'tz\' => \'Africa/Lome\', \'date_format\' => \'JJ/MM/AAAA\', \'theme\' => \'Clair\', \'comm_transactional\' => true, \'comm_sms\' => true, \'comm_newsletter\' => false]',
          'attributes' => 
          array (
            'startLine' => 21,
            'endLine' => 29,
            'startTokenPos' => 64,
            'startFilePos' => 642,
            'endTokenPos' => 115,
            'endFilePos' => 872,
          ),
        ),
        'docComment' => '/** Valeurs par défaut des préférences. */',
        'attributes' => 
        array (
        ),
        'startLine' => 21,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
      'LANGS' => 
      array (
        'declaringClassName' => 'App\\Http\\Controllers\\Account\\PreferencesController',
        'implementingClassName' => 'App\\Http\\Controllers\\Account\\PreferencesController',
        'name' => 'LANGS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'fr\' => \'Français\', \'en\' => \'English\']',
          'attributes' => 
          array (
            'startLine' => 31,
            'endLine' => 31,
            'startTokenPos' => 126,
            'startFilePos' => 901,
            'endTokenPos' => 139,
            'endFilePos' => 940,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 31,
        'endLine' => 31,
        'startColumn' => 5,
        'endColumn' => 66,
      ),
      'TIMEZONES' => 
      array (
        'declaringClassName' => 'App\\Http\\Controllers\\Account\\PreferencesController',
        'implementingClassName' => 'App\\Http\\Controllers\\Account\\PreferencesController',
        'name' => 'TIMEZONES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'Africa/Lome\' => \'Afrique/Lomé (GMT)\', \'Africa/Abidjan\' => \'Afrique/Abidjan (GMT)\', \'Europe/Paris\' => \'Europe/Paris (CET)\']',
          'attributes' => 
          array (
            'startLine' => 33,
            'endLine' => 37,
            'startTokenPos' => 150,
            'startFilePos' => 973,
            'endTokenPos' => 173,
            'endFilePos' => 1128,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 33,
        'endLine' => 37,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
      'DATE_FORMATS' => 
      array (
        'declaringClassName' => 'App\\Http\\Controllers\\Account\\PreferencesController',
        'implementingClassName' => 'App\\Http\\Controllers\\Account\\PreferencesController',
        'name' => 'DATE_FORMATS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'JJ/MM/AAAA\', \'AAAA-MM-JJ\', \'MM/JJ/AAAA\']',
          'attributes' => 
          array (
            'startLine' => 39,
            'endLine' => 39,
            'startTokenPos' => 184,
            'startFilePos' => 1164,
            'endTokenPos' => 192,
            'endFilePos' => 1205,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 39,
        'endLine' => 39,
        'startColumn' => 5,
        'endColumn' => 75,
      ),
      'THEMES' => 
      array (
        'declaringClassName' => 'App\\Http\\Controllers\\Account\\PreferencesController',
        'implementingClassName' => 'App\\Http\\Controllers\\Account\\PreferencesController',
        'name' => 'THEMES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'Clair\', \'Sombre (bientôt)\', \'Système\']',
          'attributes' => 
          array (
            'startLine' => 41,
            'endLine' => 41,
            'startTokenPos' => 203,
            'startFilePos' => 1235,
            'endTokenPos' => 211,
            'endFilePos' => 1276,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 41,
        'endLine' => 41,
        'startColumn' => 5,
        'endColumn' => 69,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'edit' => 
      array (
        'name' => 'edit',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\View\\View',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 43,
        'endLine' => 66,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Controllers\\Account',
        'declaringClassName' => 'App\\Http\\Controllers\\Account\\PreferencesController',
        'implementingClassName' => 'App\\Http\\Controllers\\Account\\PreferencesController',
        'currentClassName' => 'App\\Http\\Controllers\\Account\\PreferencesController',
        'aliasName' => NULL,
      ),
      'update' => 
      array (
        'name' => 'update',
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
            'startLine' => 68,
            'endLine' => 68,
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
            'name' => 'Illuminate\\Http\\RedirectResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 68,
        'endLine' => 92,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Controllers\\Account',
        'declaringClassName' => 'App\\Http\\Controllers\\Account\\PreferencesController',
        'implementingClassName' => 'App\\Http\\Controllers\\Account\\PreferencesController',
        'currentClassName' => 'App\\Http\\Controllers\\Account\\PreferencesController',
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