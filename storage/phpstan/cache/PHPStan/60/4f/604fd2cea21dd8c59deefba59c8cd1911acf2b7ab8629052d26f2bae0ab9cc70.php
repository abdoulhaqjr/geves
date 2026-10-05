<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Http\Controllers\Account\LoginController.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Http\Controllers\Account\LoginController
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-7c15ce74f30a6fc440f2b3afd6b51a310aeafcf26d1f521dedd0ea85749fc50d',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Http\\Controllers\\Account\\LoginController',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Http/Controllers/Account/LoginController.php',
      ),
    ),
    'namespace' => 'App\\Http\\Controllers\\Account',
    'name' => 'App\\Http\\Controllers\\Account\\LoginController',
    'shortName' => 'LoginController',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Connexion d\'un compte participant (guard « account »), contrôleur + Blade
 * (sans Livewire), avec limitation du nombre de tentatives. Si le compte a
 * activé la double authentification (TOTP ou OTP SMS), la connexion n\'est
 * finalisée qu\'après un second facteur ; l\'identifiant reste en session en
 * attendant (le challenge est un second POST).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 26,
    'endLine' => 259,
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
      'SESSION_LOGIN_ID' => 
      array (
        'declaringClassName' => 'App\\Http\\Controllers\\Account\\LoginController',
        'implementingClassName' => 'App\\Http\\Controllers\\Account\\LoginController',
        'name' => 'SESSION_LOGIN_ID',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'account.2fa.login_id\'',
          'attributes' => 
          array (
            'startLine' => 28,
            'endLine' => 28,
            'startTokenPos' => 92,
            'startFilePos' => 921,
            'endTokenPos' => 92,
            'endFilePos' => 942,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 28,
        'endLine' => 28,
        'startColumn' => 5,
        'endColumn' => 60,
      ),
      'SESSION_LOGIN_REMEMBER' => 
      array (
        'declaringClassName' => 'App\\Http\\Controllers\\Account\\LoginController',
        'implementingClassName' => 'App\\Http\\Controllers\\Account\\LoginController',
        'name' => 'SESSION_LOGIN_REMEMBER',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'account.2fa.login_remember\'',
          'attributes' => 
          array (
            'startLine' => 30,
            'endLine' => 30,
            'startTokenPos' => 103,
            'startFilePos' => 989,
            'endTokenPos' => 103,
            'endFilePos' => 1016,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 30,
        'endLine' => 30,
        'startColumn' => 5,
        'endColumn' => 72,
      ),
      'SESSION_METHOD' => 
      array (
        'declaringClassName' => 'App\\Http\\Controllers\\Account\\LoginController',
        'implementingClassName' => 'App\\Http\\Controllers\\Account\\LoginController',
        'name' => 'SESSION_METHOD',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'account.2fa.method\'',
          'attributes' => 
          array (
            'startLine' => 32,
            'endLine' => 32,
            'startTokenPos' => 114,
            'startFilePos' => 1055,
            'endTokenPos' => 114,
            'endFilePos' => 1074,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 32,
        'endLine' => 32,
        'startColumn' => 5,
        'endColumn' => 56,
      ),
      'SESSION_PHONE_HINT' => 
      array (
        'declaringClassName' => 'App\\Http\\Controllers\\Account\\LoginController',
        'implementingClassName' => 'App\\Http\\Controllers\\Account\\LoginController',
        'name' => 'SESSION_PHONE_HINT',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'account.2fa.phone_hint\'',
          'attributes' => 
          array (
            'startLine' => 34,
            'endLine' => 34,
            'startTokenPos' => 125,
            'startFilePos' => 1117,
            'endTokenPos' => 125,
            'endFilePos' => 1140,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 34,
        'endLine' => 34,
        'startColumn' => 5,
        'endColumn' => 64,
      ),
      'SESSION_IS_STAFF' => 
      array (
        'declaringClassName' => 'App\\Http\\Controllers\\Account\\LoginController',
        'implementingClassName' => 'App\\Http\\Controllers\\Account\\LoginController',
        'name' => 'SESSION_IS_STAFF',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'account.2fa.is_staff\'',
          'attributes' => 
          array (
            'startLine' => 37,
            'endLine' => 37,
            'startTokenPos' => 138,
            'startFilePos' => 1267,
            'endTokenPos' => 138,
            'endFilePos' => 1288,
          ),
        ),
        'docComment' => '/** Le login en attente de 2FA doit-il finaliser vers le back-office (staff) ? */',
        'attributes' => 
        array (
        ),
        'startLine' => 37,
        'endLine' => 37,
        'startColumn' => 5,
        'endColumn' => 60,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'create' => 
      array (
        'name' => 'create',
        'parameters' => 
        array (
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
                  'name' => 'Illuminate\\View\\View',
                  'isIdentifier' => false,
                ),
              ),
              1 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'Illuminate\\Http\\RedirectResponse',
                  'isIdentifier' => false,
                ),
              ),
            ),
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Formulaire de connexion (ou écran de second facteur si un challenge est en cours). */',
        'startLine' => 40,
        'endLine' => 53,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Controllers\\Account',
        'declaringClassName' => 'App\\Http\\Controllers\\Account\\LoginController',
        'implementingClassName' => 'App\\Http\\Controllers\\Account\\LoginController',
        'currentClassName' => 'App\\Http\\Controllers\\Account\\LoginController',
        'aliasName' => NULL,
      ),
      'store' => 
      array (
        'name' => 'store',
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
            'startLine' => 56,
            'endLine' => 56,
            'startColumn' => 27,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'sms' => 
          array (
            'name' => 'sms',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Sms\\SmsManager',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 56,
            'endLine' => 56,
            'startColumn' => 45,
            'endColumn' => 59,
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
            'name' => 'Illuminate\\Http\\RedirectResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Vérifie les identifiants ; déclenche le second facteur si nécessaire. */',
        'startLine' => 56,
        'endLine' => 110,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Controllers\\Account',
        'declaringClassName' => 'App\\Http\\Controllers\\Account\\LoginController',
        'implementingClassName' => 'App\\Http\\Controllers\\Account\\LoginController',
        'currentClassName' => 'App\\Http\\Controllers\\Account\\LoginController',
        'aliasName' => NULL,
      ),
      'confirm' => 
      array (
        'name' => 'confirm',
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
            'startLine' => 113,
            'endLine' => 113,
            'startColumn' => 29,
            'endColumn' => 44,
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
        'docComment' => '/** Vérifie le second facteur puis finalise la connexion. */',
        'startLine' => 113,
        'endLine' => 151,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Controllers\\Account',
        'declaringClassName' => 'App\\Http\\Controllers\\Account\\LoginController',
        'implementingClassName' => 'App\\Http\\Controllers\\Account\\LoginController',
        'currentClassName' => 'App\\Http\\Controllers\\Account\\LoginController',
        'aliasName' => NULL,
      ),
      'resendOtp' => 
      array (
        'name' => 'resendOtp',
        'parameters' => 
        array (
          'sms' => 
          array (
            'name' => 'sms',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Sms\\SmsManager',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 154,
            'endLine' => 154,
            'startColumn' => 31,
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
            'name' => 'Illuminate\\Http\\RedirectResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Renvoie un nouveau code OTP par SMS pendant le challenge. */',
        'startLine' => 154,
        'endLine' => 165,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Controllers\\Account',
        'declaringClassName' => 'App\\Http\\Controllers\\Account\\LoginController',
        'implementingClassName' => 'App\\Http\\Controllers\\Account\\LoginController',
        'currentClassName' => 'App\\Http\\Controllers\\Account\\LoginController',
        'aliasName' => NULL,
      ),
      'backToLogin' => 
      array (
        'name' => 'backToLogin',
        'parameters' => 
        array (
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
        'docComment' => '/** Abandonne le challenge et repart de l\'écran d\'identifiants. */',
        'startLine' => 168,
        'endLine' => 173,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Controllers\\Account',
        'declaringClassName' => 'App\\Http\\Controllers\\Account\\LoginController',
        'implementingClassName' => 'App\\Http\\Controllers\\Account\\LoginController',
        'currentClassName' => 'App\\Http\\Controllers\\Account\\LoginController',
        'aliasName' => NULL,
      ),
      'sendOtp' => 
      array (
        'name' => 'sendOtp',
        'parameters' => 
        array (
          'account' => 
          array (
            'name' => 'account',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\User',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 175,
            'endLine' => 175,
            'startColumn' => 30,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'sms' => 
          array (
            'name' => 'sms',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Sms\\SmsManager',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 175,
            'endLine' => 175,
            'startColumn' => 45,
            'endColumn' => 59,
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
        'docComment' => NULL,
        'startLine' => 175,
        'endLine' => 186,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Http\\Controllers\\Account',
        'declaringClassName' => 'App\\Http\\Controllers\\Account\\LoginController',
        'implementingClassName' => 'App\\Http\\Controllers\\Account\\LoginController',
        'currentClassName' => 'App\\Http\\Controllers\\Account\\LoginController',
        'aliasName' => NULL,
      ),
      'maskPhone' => 
      array (
        'name' => 'maskPhone',
        'parameters' => 
        array (
          'phone' => 
          array (
            'name' => 'phone',
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
            'startLine' => 188,
            'endLine' => 188,
            'startColumn' => 32,
            'endColumn' => 44,
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
        'startLine' => 188,
        'endLine' => 193,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Http\\Controllers\\Account',
        'declaringClassName' => 'App\\Http\\Controllers\\Account\\LoginController',
        'implementingClassName' => 'App\\Http\\Controllers\\Account\\LoginController',
        'currentClassName' => 'App\\Http\\Controllers\\Account\\LoginController',
        'aliasName' => NULL,
      ),
      'resetChallenge' => 
      array (
        'name' => 'resetChallenge',
        'parameters' => 
        array (
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
        'docComment' => NULL,
        'startLine' => 195,
        'endLine' => 204,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Http\\Controllers\\Account',
        'declaringClassName' => 'App\\Http\\Controllers\\Account\\LoginController',
        'implementingClassName' => 'App\\Http\\Controllers\\Account\\LoginController',
        'currentClassName' => 'App\\Http\\Controllers\\Account\\LoginController',
        'aliasName' => NULL,
      ),
      'hasBackOfficeAccess' => 
      array (
        'name' => 'hasBackOfficeAccess',
        'parameters' => 
        array (
          'user' => 
          array (
            'name' => 'user',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\User',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 211,
            'endLine' => 211,
            'startColumn' => 42,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * L\'utilisateur a-t-il un accès back-office (super-admin, admin de colloque
 * ou une fonction d\'organisation sur au moins un événement) ? Détermine s\'il
 * est orienté vers le back-office plutôt que l\'espace participant.
 */',
        'startLine' => 211,
        'endLine' => 214,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Http\\Controllers\\Account',
        'declaringClassName' => 'App\\Http\\Controllers\\Account\\LoginController',
        'implementingClassName' => 'App\\Http\\Controllers\\Account\\LoginController',
        'currentClassName' => 'App\\Http\\Controllers\\Account\\LoginController',
        'aliasName' => NULL,
      ),
      'completeStaffLogin' => 
      array (
        'name' => 'completeStaffLogin',
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
            'startLine' => 217,
            'endLine' => 217,
            'startColumn' => 41,
            'endColumn' => 56,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'user' => 
          array (
            'name' => 'user',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\User',
                'isIdentifier' => false,
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
            'startColumn' => 59,
            'endColumn' => 68,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'remember' => 
          array (
            'name' => 'remember',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 217,
                'endLine' => 217,
                'startTokenPos' => 1439,
                'startFilePos' => 8359,
                'endTokenPos' => 1439,
                'endFilePos' => 8363,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'bool',
                'isIdentifier' => true,
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
            'startColumn' => 71,
            'endColumn' => 92,
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
            'name' => 'Illuminate\\Http\\RedirectResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Connexion du personnel de gestion : guard « web » + orientation back-office. */',
        'startLine' => 217,
        'endLine' => 229,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Http\\Controllers\\Account',
        'declaringClassName' => 'App\\Http\\Controllers\\Account\\LoginController',
        'implementingClassName' => 'App\\Http\\Controllers\\Account\\LoginController',
        'currentClassName' => 'App\\Http\\Controllers\\Account\\LoginController',
        'aliasName' => NULL,
      ),
      'completeLogin' => 
      array (
        'name' => 'completeLogin',
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
            'startLine' => 231,
            'endLine' => 231,
            'startColumn' => 36,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'account' => 
          array (
            'name' => 'account',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\User',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 231,
            'endLine' => 231,
            'startColumn' => 54,
            'endColumn' => 66,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'remember' => 
          array (
            'name' => 'remember',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'bool',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 231,
            'endLine' => 231,
            'startColumn' => 69,
            'endColumn' => 82,
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
            'name' => 'Illuminate\\Http\\RedirectResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 231,
        'endLine' => 238,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Http\\Controllers\\Account',
        'declaringClassName' => 'App\\Http\\Controllers\\Account\\LoginController',
        'implementingClassName' => 'App\\Http\\Controllers\\Account\\LoginController',
        'currentClassName' => 'App\\Http\\Controllers\\Account\\LoginController',
        'aliasName' => NULL,
      ),
      'ensureIsNotRateLimited' => 
      array (
        'name' => 'ensureIsNotRateLimited',
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
            'startLine' => 240,
            'endLine' => 240,
            'startColumn' => 47,
            'endColumn' => 62,
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
        'docComment' => NULL,
        'startLine' => 240,
        'endLine' => 253,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Http\\Controllers\\Account',
        'declaringClassName' => 'App\\Http\\Controllers\\Account\\LoginController',
        'implementingClassName' => 'App\\Http\\Controllers\\Account\\LoginController',
        'currentClassName' => 'App\\Http\\Controllers\\Account\\LoginController',
        'aliasName' => NULL,
      ),
      'throttleKey' => 
      array (
        'name' => 'throttleKey',
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
            'startLine' => 255,
            'endLine' => 255,
            'startColumn' => 36,
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
        'startLine' => 255,
        'endLine' => 258,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Http\\Controllers\\Account',
        'declaringClassName' => 'App\\Http\\Controllers\\Account\\LoginController',
        'implementingClassName' => 'App\\Http\\Controllers\\Account\\LoginController',
        'currentClassName' => 'App\\Http\\Controllers\\Account\\LoginController',
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