<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Notifications\StaffVerifyEmail.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Notifications\StaffVerifyEmail
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-a0a88773240ef5f37adb02a29383332f280985ab22613da2c27b4dc18f773991',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Notifications\\StaffVerifyEmail',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Notifications/StaffVerifyEmail.php',
      ),
    ),
    'namespace' => 'App\\Notifications',
    'name' => 'App\\Notifications\\StaffVerifyEmail',
    'shortName' => 'StaffVerifyEmail',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Vérification d\'e-mail pour un utilisateur du back-office (guard « web »).
 * Construit un lien signé vers la route admin `admin.verification.verify`
 * (distincte de la vérification des comptes participants).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 16,
    'endLine' => 39,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Auth\\Notifications\\VerifyEmail',
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
      'verificationUrl' => 
      array (
        'name' => 'verificationUrl',
        'parameters' => 
        array (
          'notifiable' => 
          array (
            'name' => 'notifiable',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 18,
            'endLine' => 18,
            'startColumn' => 40,
            'endColumn' => 50,
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
        'startLine' => 18,
        'endLine' => 28,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Notifications',
        'declaringClassName' => 'App\\Notifications\\StaffVerifyEmail',
        'implementingClassName' => 'App\\Notifications\\StaffVerifyEmail',
        'currentClassName' => 'App\\Notifications\\StaffVerifyEmail',
        'aliasName' => NULL,
      ),
      'buildMailMessage' => 
      array (
        'name' => 'buildMailMessage',
        'parameters' => 
        array (
          'url' => 
          array (
            'name' => 'url',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 30,
            'endLine' => 30,
            'startColumn' => 41,
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
            'name' => 'Illuminate\\Notifications\\Messages\\MailMessage',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 30,
        'endLine' => 38,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Notifications',
        'declaringClassName' => 'App\\Notifications\\StaffVerifyEmail',
        'implementingClassName' => 'App\\Notifications\\StaffVerifyEmail',
        'currentClassName' => 'App\\Notifications\\StaffVerifyEmail',
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