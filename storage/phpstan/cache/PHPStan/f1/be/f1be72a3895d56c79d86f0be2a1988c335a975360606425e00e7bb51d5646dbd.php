<?php declare(strict_types = 1);

// osfsl-C:/Users/TOURE/Herd/gesco/vendor/composer/../laravel/framework/src/Illuminate/Contracts/Notifications/Factory.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Contracts\Notifications\Factory
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-e604a1cb7c1a97c0c56bab8a09b6a921e1f4f7ff029f923a1789cc7d0d2f1fd3-8.4.24-6.70.0.3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Contracts\\Notifications\\Factory',
        'filename' => 'C:/Users/TOURE/Herd/gesco/vendor/composer/../laravel/framework/src/Illuminate/Contracts/Notifications/Factory.php',
      ),
    ),
    'namespace' => 'Illuminate\\Contracts\\Notifications',
    'name' => 'Illuminate\\Contracts\\Notifications\\Factory',
    'shortName' => 'Factory',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 5,
    'endLine' => 32,
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
      'channel' => 
      array (
        'name' => 'channel',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 13,
                'endLine' => 13,
                'startTokenPos' => 25,
                'startFilePos' => 227,
                'endTokenPos' => 25,
                'endFilePos' => 230,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 13,
            'endLine' => 13,
            'startColumn' => 29,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get a channel instance by name.
 *
 * @param  string|null  $name
 * @return mixed
 */',
        'startLine' => 13,
        'endLine' => 13,
        'startColumn' => 5,
        'endColumn' => 42,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Contracts\\Notifications',
        'declaringClassName' => 'Illuminate\\Contracts\\Notifications\\Factory',
        'implementingClassName' => 'Illuminate\\Contracts\\Notifications\\Factory',
        'currentClassName' => 'Illuminate\\Contracts\\Notifications\\Factory',
        'aliasName' => NULL,
      ),
      'send' => 
      array (
        'name' => 'send',
        'parameters' => 
        array (
          'notifiables' => 
          array (
            'name' => 'notifiables',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 22,
            'endLine' => 22,
            'startColumn' => 26,
            'endColumn' => 37,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'notification' => 
          array (
            'name' => 'notification',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 22,
            'endLine' => 22,
            'startColumn' => 40,
            'endColumn' => 52,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Send the given notification to the given notifiable entities.
 *
 * @param  \\Illuminate\\Support\\Collection|mixed  $notifiables
 * @param  mixed  $notification
 * @return void
 */',
        'startLine' => 22,
        'endLine' => 22,
        'startColumn' => 5,
        'endColumn' => 54,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Contracts\\Notifications',
        'declaringClassName' => 'Illuminate\\Contracts\\Notifications\\Factory',
        'implementingClassName' => 'Illuminate\\Contracts\\Notifications\\Factory',
        'currentClassName' => 'Illuminate\\Contracts\\Notifications\\Factory',
        'aliasName' => NULL,
      ),
      'sendNow' => 
      array (
        'name' => 'sendNow',
        'parameters' => 
        array (
          'notifiables' => 
          array (
            'name' => 'notifiables',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 31,
            'endLine' => 31,
            'startColumn' => 29,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'notification' => 
          array (
            'name' => 'notification',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 31,
            'endLine' => 31,
            'startColumn' => 43,
            'endColumn' => 55,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Send the given notification immediately.
 *
 * @param  \\Illuminate\\Support\\Collection|mixed  $notifiables
 * @param  mixed  $notification
 * @return void
 */',
        'startLine' => 31,
        'endLine' => 31,
        'startColumn' => 5,
        'endColumn' => 57,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Contracts\\Notifications',
        'declaringClassName' => 'Illuminate\\Contracts\\Notifications\\Factory',
        'implementingClassName' => 'Illuminate\\Contracts\\Notifications\\Factory',
        'currentClassName' => 'Illuminate\\Contracts\\Notifications\\Factory',
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