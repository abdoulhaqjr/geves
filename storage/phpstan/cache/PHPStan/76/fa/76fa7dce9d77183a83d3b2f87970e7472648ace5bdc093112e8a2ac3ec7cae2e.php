<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Models\Account.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Models\Account
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-34fff40ecc972fd62a42508c6f85447023d8ecce9f8329db1f22f2c84d8c5e7f',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Models\\Account',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Models/Account.php',
      ),
    ),
    'namespace' => 'App\\Models',
    'name' => 'App\\Models\\Account',
    'shortName' => 'Account',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Compte participant : authentification propre à la plateforme (guard
 * « account »), distincte du back-office (guard « web » / {@see User}). Un
 * compte global par personne, valable pour tous les événements. Peut être
 * impersonifié par le staff (assistance).
 *
 * @property string|null $two_factor_secret secret TOTP (chiffré)
 * @property array<int, string>|null $two_factor_recovery_codes codes de secours (chiffrés)
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $two_factor_method « totp » ou « sms »
 * @property string|null $two_factor_otp code OTP SMS haché
 * @property Carbon|null $two_factor_otp_expires_at
 * @property string|null $phone
 * @property string|null $locale langue préférée (i18n)
 * @property array<string, array{inapp: bool, email: bool}>|null $notification_preferences
 * @property array<string, mixed>|null $meta
 * @property string|null $orcid identifiant ORCID canonique (0000-0002-1825-0097)
 * @property Carbon|null $orcid_verified_at appartenance ORCID confirmée via OAuth
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 33,
    'endLine' => 55,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Foundation\\Auth\\User',
    'implementsClassNames' => 
    array (
      0 => 'Illuminate\\Contracts\\Auth\\MustVerifyEmail',
    ),
    'traitClassNames' => 
    array (
      0 => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
      1 => 'App\\Models\\Concerns\\HasNotificationPreferences',
      2 => 'Lab404\\Impersonate\\Models\\Impersonate',
      3 => 'Illuminate\\Notifications\\Notifiable',
      4 => 'App\\Models\\Concerns\\ParticipantIdentity',
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'guarded' => 
      array (
        'declaringClassName' => 'App\\Models\\Account',
        'implementingClassName' => 'App\\Models\\Account',
        'name' => 'guarded',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'id\']',
          'attributes' => 
          array (
            'startLine' => 37,
            'endLine' => 37,
            'startTokenPos' => 90,
            'startFilePos' => 1657,
            'endTokenPos' => 92,
            'endFilePos' => 1662,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 37,
        'endLine' => 37,
        'startColumn' => 5,
        'endColumn' => 32,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'hidden' => 
      array (
        'declaringClassName' => 'App\\Models\\Account',
        'implementingClassName' => 'App\\Models\\Account',
        'name' => 'hidden',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'password\', \'remember_token\']',
          'attributes' => 
          array (
            'startLine' => 39,
            'endLine' => 39,
            'startTokenPos' => 101,
            'startFilePos' => 1690,
            'endTokenPos' => 106,
            'endFilePos' => 1719,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 39,
        'endLine' => 39,
        'startColumn' => 5,
        'endColumn' => 55,
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
      'casts' => 
      array (
        'name' => 'casts',
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
        'docComment' => NULL,
        'startLine' => 41,
        'endLine' => 54,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Account',
        'implementingClassName' => 'App\\Models\\Account',
        'currentClassName' => 'App\\Models\\Account',
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