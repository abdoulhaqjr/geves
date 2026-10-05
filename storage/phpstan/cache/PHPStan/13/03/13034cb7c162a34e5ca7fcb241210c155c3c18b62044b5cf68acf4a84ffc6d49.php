<?php declare(strict_types = 1);

// osfsl-C:/Users/TOURE/Herd/gesco/vendor/composer/../laravel/framework/src/Illuminate/Support/Facades/Notification.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Support\Facades\Notification
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-8a24df5ba22136f1e18d934735e9908abd94b68054c246c1c760a5e1ec50bc4b-8.4.24-6.70.0.3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Support\\Facades\\Notification',
        'filename' => 'C:/Users/TOURE/Herd/gesco/vendor/composer/../laravel/framework/src/Illuminate/Support/Facades/Notification.php',
      ),
    ),
    'namespace' => 'Illuminate\\Support\\Facades',
    'name' => 'Illuminate\\Support\\Facades\\Notification',
    'shortName' => 'Notification',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * @method static void send(\\Illuminate\\Support\\Collection|mixed $notifiables, mixed $notification)
 * @method static void sendNow(\\Illuminate\\Support\\Collection|mixed $notifiables, mixed $notification, array|null $channels = null)
 * @method static mixed channel(\\UnitEnum|string|null $name = null)
 * @method static mixed driver(\\UnitEnum|string|null $driver = null)
 * @method static string getDefaultDriver()
 * @method static string deliversVia()
 * @method static void deliverVia(string $channel)
 * @method static \\Illuminate\\Notifications\\ChannelManager locale(string $locale)
 * @method static \\Illuminate\\Notifications\\ChannelManager extend(string $driver, \\Closure $callback)
 * @method static array getDrivers()
 * @method static \\Illuminate\\Contracts\\Container\\Container getContainer()
 * @method static \\Illuminate\\Notifications\\ChannelManager setContainer(\\Illuminate\\Contracts\\Container\\Container $container)
 * @method static \\Illuminate\\Notifications\\ChannelManager forgetDrivers()
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
 * @method static string|null resolveConnectionFromQueueRoute(object $queueable)
 * @method static string|null resolveQueueFromQueueRoute(object $queueable)
 * @method static void assertSentOnDemand(string|\\Closure $notification, callable|null $callback = null)
 * @method static void assertSentTo(mixed $notifiable, string|\\Closure $notification, callable|null $callback = null)
 * @method static void assertSentOnDemandTimes(string $notification, int $times = 1)
 * @method static void assertSentToTimes(mixed $notifiable, string $notification, int $times = 1)
 * @method static void assertNotSentTo(mixed $notifiable, string|\\Closure $notification, callable|null $callback = null)
 * @method static void assertNothingSent()
 * @method static void assertNothingSentTo(mixed $notifiable)
 * @method static void assertSentTimes(string $notification, int $expectedCount)
 * @method static void assertCount(int $expectedCount)
 * @method static \\Illuminate\\Support\\Collection sent(mixed $notifiable, string $notification, callable|null $callback = null)
 * @method static bool hasSent(mixed $notifiable, string $notification)
 * @method static \\Illuminate\\Support\\Testing\\Fakes\\NotificationFake serializeAndRestore(bool $serializeAndRestore = true)
 * @method static array sentNotifications()
 *
 * @see \\Illuminate\\Notifications\\ChannelManager
 * @see \\Illuminate\\Support\\Testing\\Fakes\\NotificationFake
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 46,
    'endLine' => 98,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Support\\Facades\\Facade',
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
      'fake' => 
      array (
        'name' => 'fake',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Replace the bound instance with a fake.
 *
 * @return \\Illuminate\\Support\\Testing\\Fakes\\NotificationFake
 */',
        'startLine' => 53,
        'endLine' => 58,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Illuminate\\Support\\Facades',
        'declaringClassName' => 'Illuminate\\Support\\Facades\\Notification',
        'implementingClassName' => 'Illuminate\\Support\\Facades\\Notification',
        'currentClassName' => 'Illuminate\\Support\\Facades\\Notification',
        'aliasName' => NULL,
      ),
      'routes' => 
      array (
        'name' => 'routes',
        'parameters' => 
        array (
          'channels' => 
          array (
            'name' => 'channels',
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
            'startLine' => 66,
            'endLine' => 66,
            'startColumn' => 35,
            'endColumn' => 49,
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
 * Begin sending a notification to an anonymous notifiable on the given channels.
 *
 * @param  array  $channels
 * @return \\Illuminate\\Notifications\\AnonymousNotifiable
 */',
        'startLine' => 66,
        'endLine' => 75,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Illuminate\\Support\\Facades',
        'declaringClassName' => 'Illuminate\\Support\\Facades\\Notification',
        'implementingClassName' => 'Illuminate\\Support\\Facades\\Notification',
        'currentClassName' => 'Illuminate\\Support\\Facades\\Notification',
        'aliasName' => NULL,
      ),
      'route' => 
      array (
        'name' => 'route',
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
            'startLine' => 84,
            'endLine' => 84,
            'startColumn' => 34,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'route' => 
          array (
            'name' => 'route',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 84,
            'endLine' => 84,
            'startColumn' => 44,
            'endColumn' => 49,
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
 * Begin sending a notification to an anonymous notifiable.
 *
 * @param  string  $channel
 * @param  mixed  $route
 * @return \\Illuminate\\Notifications\\AnonymousNotifiable
 */',
        'startLine' => 84,
        'endLine' => 87,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Illuminate\\Support\\Facades',
        'declaringClassName' => 'Illuminate\\Support\\Facades\\Notification',
        'implementingClassName' => 'Illuminate\\Support\\Facades\\Notification',
        'currentClassName' => 'Illuminate\\Support\\Facades\\Notification',
        'aliasName' => NULL,
      ),
      'getFacadeAccessor' => 
      array (
        'name' => 'getFacadeAccessor',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the registered name of the component.
 *
 * @return string
 */',
        'startLine' => 94,
        'endLine' => 97,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 18,
        'namespace' => 'Illuminate\\Support\\Facades',
        'declaringClassName' => 'Illuminate\\Support\\Facades\\Notification',
        'implementingClassName' => 'Illuminate\\Support\\Facades\\Notification',
        'currentClassName' => 'Illuminate\\Support\\Facades\\Notification',
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