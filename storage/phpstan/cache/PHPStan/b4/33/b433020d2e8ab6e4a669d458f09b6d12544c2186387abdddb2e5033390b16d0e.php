<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Meetings\MeetingProviderRegistry.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Meetings\MeetingProviderRegistry
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-ff1ecaca8e050f372ce49d2fca9132f0220f1ec8aaa9aa366027e97ec76803cb',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Meetings\\MeetingProviderRegistry',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Meetings/MeetingProviderRegistry.php',
      ),
    ),
    'namespace' => 'App\\Meetings',
    'name' => 'App\\Meetings\\MeetingProviderRegistry',
    'shortName' => 'MeetingProviderRegistry',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Registre des fournisseurs de visio. Résout le fournisseur par clé et celui par
 * défaut (réglage `meetings.default`), avec repli sur Jitsi. Même esprit que
 * {@see GatewayRegistry}.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 17,
    'endLine' => 71,
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
      'providers' => 
      array (
        'declaringClassName' => 'App\\Meetings\\MeetingProviderRegistry',
        'implementingClassName' => 'App\\Meetings\\MeetingProviderRegistry',
        'name' => 'providers',
        'modifiers' => 2,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => '/** @var array<int, MeetingProvider> */',
        'attributes' => 
        array (
        ),
        'startLine' => 20,
        'endLine' => 20,
        'startColumn' => 5,
        'endColumn' => 31,
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
      '__construct' => 
      array (
        'name' => '__construct',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 22,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Meetings',
        'declaringClassName' => 'App\\Meetings\\MeetingProviderRegistry',
        'implementingClassName' => 'App\\Meetings\\MeetingProviderRegistry',
        'currentClassName' => 'App\\Meetings\\MeetingProviderRegistry',
        'aliasName' => NULL,
      ),
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
        'docComment' => '/** @return array<int, MeetingProvider> */',
        'startLine' => 32,
        'endLine' => 35,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Meetings',
        'declaringClassName' => 'App\\Meetings\\MeetingProviderRegistry',
        'implementingClassName' => 'App\\Meetings\\MeetingProviderRegistry',
        'currentClassName' => 'App\\Meetings\\MeetingProviderRegistry',
        'aliasName' => NULL,
      ),
      'get' => 
      array (
        'name' => 'get',
        'parameters' => 
        array (
          'key' => 
          array (
            'name' => 'key',
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
            'startLine' => 37,
            'endLine' => 37,
            'startColumn' => 25,
            'endColumn' => 35,
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
                  'name' => 'App\\Meetings\\Contracts\\MeetingProvider',
                  'isIdentifier' => false,
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
        'docComment' => NULL,
        'startLine' => 37,
        'endLine' => 46,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Meetings',
        'declaringClassName' => 'App\\Meetings\\MeetingProviderRegistry',
        'implementingClassName' => 'App\\Meetings\\MeetingProviderRegistry',
        'currentClassName' => 'App\\Meetings\\MeetingProviderRegistry',
        'aliasName' => NULL,
      ),
      'default' => 
      array (
        'name' => 'default',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'App\\Meetings\\Contracts\\MeetingProvider',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Fournisseur par défaut (réglage), repli sur Jitsi (toujours disponible). */',
        'startLine' => 49,
        'endLine' => 56,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Meetings',
        'declaringClassName' => 'App\\Meetings\\MeetingProviderRegistry',
        'implementingClassName' => 'App\\Meetings\\MeetingProviderRegistry',
        'currentClassName' => 'App\\Meetings\\MeetingProviderRegistry',
        'aliasName' => NULL,
      ),
      'for' => 
      array (
        'name' => 'for',
        'parameters' => 
        array (
          'session' => 
          array (
            'name' => 'session',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Meetings\\Contracts\\Meetable',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 63,
            'endLine' => 63,
            'startColumn' => 25,
            'endColumn' => 41,
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
            'name' => 'App\\Meetings\\Contracts\\MeetingProvider',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Fournisseur d\'une session (son `meeting_provider`, sinon le défaut). Si le
 * fournisseur choisi n\'est pas configuré (ex. LiveKit sans identifiants), on
 * retombe sur le défaut disponible - pas de salle cassée.
 */',
        'startLine' => 63,
        'endLine' => 70,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Meetings',
        'declaringClassName' => 'App\\Meetings\\MeetingProviderRegistry',
        'implementingClassName' => 'App\\Meetings\\MeetingProviderRegistry',
        'currentClassName' => 'App\\Meetings\\MeetingProviderRegistry',
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