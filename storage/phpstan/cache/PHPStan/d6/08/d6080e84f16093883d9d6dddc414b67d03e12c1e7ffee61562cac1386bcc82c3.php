<?php declare(strict_types = 1);

// osfsl-C:/Users/TOURE/Herd/gesco/vendor/composer/../laravel/framework/src/Illuminate/Notifications/ChannelManager.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Notifications\ChannelManager
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-86da51a1c88f881e76f5c93622ffe57008d94d1db7658da3dc588c7d81978bef-8.4.24-6.70.0.3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Notifications\\ChannelManager',
        'filename' => 'C:/Users/TOURE/Herd/gesco/vendor/composer/../laravel/framework/src/Illuminate/Notifications/ChannelManager.php',
      ),
    ),
    'namespace' => 'Illuminate\\Notifications',
    'name' => 'Illuminate\\Notifications\\ChannelManager',
    'shortName' => 'ChannelManager',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 14,
    'endLine' => 192,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Support\\Manager',
    'implementsClassNames' => 
    array (
      0 => 'Illuminate\\Contracts\\Notifications\\Dispatcher',
      1 => 'Illuminate\\Contracts\\Notifications\\Factory',
    ),
    'traitClassNames' => 
    array (
      0 => 'Illuminate\\Support\\Traits\\Macroable',
      1 => 'Illuminate\\Support\\Queue\\Concerns\\ResolvesQueueRoutes',
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'notificationSender' => 
      array (
        'declaringClassName' => 'Illuminate\\Notifications\\ChannelManager',
        'implementingClassName' => 'Illuminate\\Notifications\\ChannelManager',
        'name' => 'notificationSender',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The resolved notification sender instance.
 *
 * @var \\Illuminate\\Notifications\\NotificationSender|null
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 23,
        'endLine' => 23,
        'startColumn' => 5,
        'endColumn' => 34,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'defaultChannel' => 
      array (
        'declaringClassName' => 'Illuminate\\Notifications\\ChannelManager',
        'implementingClassName' => 'Illuminate\\Notifications\\ChannelManager',
        'name' => 'defaultChannel',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'mail\'',
          'attributes' => 
          array (
            'startLine' => 30,
            'endLine' => 30,
            'startTokenPos' => 99,
            'startFilePos' => 864,
            'endTokenPos' => 99,
            'endFilePos' => 869,
          ),
        ),
        'docComment' => '/**
 * The default channel used to deliver messages.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 30,
        'endLine' => 30,
        'startColumn' => 5,
        'endColumn' => 39,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'locale' => 
      array (
        'declaringClassName' => 'Illuminate\\Notifications\\ChannelManager',
        'implementingClassName' => 'Illuminate\\Notifications\\ChannelManager',
        'name' => 'locale',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The locale used when sending notifications.
 *
 * @var string|null
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 37,
        'endLine' => 37,
        'startColumn' => 5,
        'endColumn' => 22,
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
            'startLine' => 46,
            'endLine' => 46,
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
            'startLine' => 46,
            'endLine' => 46,
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
        'startLine' => 46,
        'endLine' => 49,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Notifications',
        'declaringClassName' => 'Illuminate\\Notifications\\ChannelManager',
        'implementingClassName' => 'Illuminate\\Notifications\\ChannelManager',
        'currentClassName' => 'Illuminate\\Notifications\\ChannelManager',
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
            'startLine' => 59,
            'endLine' => 59,
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
            'startLine' => 59,
            'endLine' => 59,
            'startColumn' => 43,
            'endColumn' => 55,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'channels' => 
          array (
            'name' => 'channels',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 59,
                'endLine' => 59,
                'startTokenPos' => 163,
                'startFilePos' => 1661,
                'endTokenPos' => 163,
                'endFilePos' => 1664,
              ),
            ),
            'type' => 
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 59,
            'endLine' => 59,
            'startColumn' => 58,
            'endColumn' => 80,
            'parameterIndex' => 2,
            'isOptional' => true,
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
 * @param  array|null  $channels
 * @return void
 */',
        'startLine' => 59,
        'endLine' => 62,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Notifications',
        'declaringClassName' => 'Illuminate\\Notifications\\ChannelManager',
        'implementingClassName' => 'Illuminate\\Notifications\\ChannelManager',
        'currentClassName' => 'Illuminate\\Notifications\\ChannelManager',
        'aliasName' => NULL,
      ),
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
                'startLine' => 70,
                'endLine' => 70,
                'startTokenPos' => 200,
                'startFilePos' => 1928,
                'endTokenPos' => 200,
                'endFilePos' => 1931,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 70,
            'endLine' => 70,
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
 * Get a channel instance.
 *
 * @param  \\UnitEnum|string|null  $name
 * @return mixed
 */',
        'startLine' => 70,
        'endLine' => 73,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Notifications',
        'declaringClassName' => 'Illuminate\\Notifications\\ChannelManager',
        'implementingClassName' => 'Illuminate\\Notifications\\ChannelManager',
        'currentClassName' => 'Illuminate\\Notifications\\ChannelManager',
        'aliasName' => NULL,
      ),
      'driver' => 
      array (
        'name' => 'driver',
        'parameters' => 
        array (
          'driver' => 
          array (
            'name' => 'driver',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 81,
                'endLine' => 81,
                'startTokenPos' => 229,
                'startFilePos' => 2141,
                'endTokenPos' => 229,
                'endFilePos' => 2144,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 81,
            'endLine' => 81,
            'startColumn' => 28,
            'endColumn' => 41,
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
 * Get a driver instance.
 *
 * @param  \\UnitEnum|string|null  $driver
 * @return mixed
 */',
        'startLine' => 81,
        'endLine' => 84,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Notifications',
        'declaringClassName' => 'Illuminate\\Notifications\\ChannelManager',
        'implementingClassName' => 'Illuminate\\Notifications\\ChannelManager',
        'currentClassName' => 'Illuminate\\Notifications\\ChannelManager',
        'aliasName' => NULL,
      ),
      'createDatabaseDriver' => 
      array (
        'name' => 'createDatabaseDriver',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Create an instance of the database driver.
 *
 * @return \\Illuminate\\Notifications\\Channels\\DatabaseChannel
 */',
        'startLine' => 91,
        'endLine' => 94,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Notifications',
        'declaringClassName' => 'Illuminate\\Notifications\\ChannelManager',
        'implementingClassName' => 'Illuminate\\Notifications\\ChannelManager',
        'currentClassName' => 'Illuminate\\Notifications\\ChannelManager',
        'aliasName' => NULL,
      ),
      'createBroadcastDriver' => 
      array (
        'name' => 'createBroadcastDriver',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Create an instance of the broadcast driver.
 *
 * @return \\Illuminate\\Notifications\\Channels\\BroadcastChannel
 */',
        'startLine' => 101,
        'endLine' => 104,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Notifications',
        'declaringClassName' => 'Illuminate\\Notifications\\ChannelManager',
        'implementingClassName' => 'Illuminate\\Notifications\\ChannelManager',
        'currentClassName' => 'Illuminate\\Notifications\\ChannelManager',
        'aliasName' => NULL,
      ),
      'createMailDriver' => 
      array (
        'name' => 'createMailDriver',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Create an instance of the mail driver.
 *
 * @return \\Illuminate\\Notifications\\Channels\\MailChannel
 */',
        'startLine' => 111,
        'endLine' => 114,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Notifications',
        'declaringClassName' => 'Illuminate\\Notifications\\ChannelManager',
        'implementingClassName' => 'Illuminate\\Notifications\\ChannelManager',
        'currentClassName' => 'Illuminate\\Notifications\\ChannelManager',
        'aliasName' => NULL,
      ),
      'createDriver' => 
      array (
        'name' => 'createDriver',
        'parameters' => 
        array (
          'driver' => 
          array (
            'name' => 'driver',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 124,
            'endLine' => 124,
            'startColumn' => 37,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Create a new driver instance.
 *
 * @param  string  $driver
 * @return mixed
 *
 * @throws \\InvalidArgumentException
 */',
        'startLine' => 124,
        'endLine' => 135,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Notifications',
        'declaringClassName' => 'Illuminate\\Notifications\\ChannelManager',
        'implementingClassName' => 'Illuminate\\Notifications\\ChannelManager',
        'currentClassName' => 'Illuminate\\Notifications\\ChannelManager',
        'aliasName' => NULL,
      ),
      'resolveNotificationSender' => 
      array (
        'name' => 'resolveNotificationSender',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Resolve the NotificationSender instance.
 *
 * @return \\Illuminate\\Notifications\\NotificationSender
 */',
        'startLine' => 142,
        'endLine' => 147,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Notifications',
        'declaringClassName' => 'Illuminate\\Notifications\\ChannelManager',
        'implementingClassName' => 'Illuminate\\Notifications\\ChannelManager',
        'currentClassName' => 'Illuminate\\Notifications\\ChannelManager',
        'aliasName' => NULL,
      ),
      'getDefaultDriver' => 
      array (
        'name' => 'getDefaultDriver',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the default channel driver name.
 *
 * @return string
 */',
        'startLine' => 154,
        'endLine' => 157,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Notifications',
        'declaringClassName' => 'Illuminate\\Notifications\\ChannelManager',
        'implementingClassName' => 'Illuminate\\Notifications\\ChannelManager',
        'currentClassName' => 'Illuminate\\Notifications\\ChannelManager',
        'aliasName' => NULL,
      ),
      'deliversVia' => 
      array (
        'name' => 'deliversVia',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the default channel driver name.
 *
 * @return string
 */',
        'startLine' => 164,
        'endLine' => 167,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Notifications',
        'declaringClassName' => 'Illuminate\\Notifications\\ChannelManager',
        'implementingClassName' => 'Illuminate\\Notifications\\ChannelManager',
        'currentClassName' => 'Illuminate\\Notifications\\ChannelManager',
        'aliasName' => NULL,
      ),
      'deliverVia' => 
      array (
        'name' => 'deliverVia',
        'parameters' => 
        array (
          'channel' => 
          array (
            'name' => 'channel',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 175,
            'endLine' => 175,
            'startColumn' => 32,
            'endColumn' => 39,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set the default channel driver name.
 *
 * @param  string  $channel
 * @return void
 */',
        'startLine' => 175,
        'endLine' => 178,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Notifications',
        'declaringClassName' => 'Illuminate\\Notifications\\ChannelManager',
        'implementingClassName' => 'Illuminate\\Notifications\\ChannelManager',
        'currentClassName' => 'Illuminate\\Notifications\\ChannelManager',
        'aliasName' => NULL,
      ),
      'locale' => 
      array (
        'name' => 'locale',
        'parameters' => 
        array (
          'locale' => 
          array (
            'name' => 'locale',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 186,
            'endLine' => 186,
            'startColumn' => 28,
            'endColumn' => 34,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set the locale of notifications.
 *
 * @param  string  $locale
 * @return $this
 */',
        'startLine' => 186,
        'endLine' => 191,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Notifications',
        'declaringClassName' => 'Illuminate\\Notifications\\ChannelManager',
        'implementingClassName' => 'Illuminate\\Notifications\\ChannelManager',
        'currentClassName' => 'Illuminate\\Notifications\\ChannelManager',
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