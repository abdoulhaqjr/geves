<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Http\Controllers\Admin\SettingAgentController.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Http\Controllers\Admin\SettingAgentController
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-720115e490fab8bfe9be5701b9f49344fe9e044fd45bdb1d9ec1c5b2a2a81a66',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Http\\Controllers\\Admin\\SettingAgentController',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Http/Controllers/Admin/SettingAgentController.php',
      ),
    ),
    'namespace' => 'App\\Http\\Controllers\\Admin',
    'name' => 'App\\Http\\Controllers\\Admin\\SettingAgentController',
    'shortName' => 'SettingAgentController',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Réglages « Agents IA » : persona et garde-fous
 * (instructions POSITIVES / NÉGATIVES) de chaque agent, extensible. Les agents,
 * onglets et avatars sont gérés côté client (Alpine) ; les avatars sont
 * téléversés à l\'enregistrement. Persisté via {@see AiAgents} (réglage
 * `ai.agents`).
 *
 * ⚠️ Plus de page dédiée : l\'éditeur est l\'onglet `?tab=agents-ia` des réglages
 * (vue `admin/settings/_agents.blade.php`). Seul l\'enregistrement vit ici.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 22,
    'endLine' => 72,
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
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
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
            'startLine' => 24,
            'endLine' => 24,
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
        'startLine' => 24,
        'endLine' => 64,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Controllers\\Admin',
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\SettingAgentController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\SettingAgentController',
        'currentClassName' => 'App\\Http\\Controllers\\Admin\\SettingAgentController',
        'aliasName' => NULL,
      ),
      'deleteAvatar' => 
      array (
        'name' => 'deleteAvatar',
        'parameters' => 
        array (
          'path' => 
          array (
            'name' => 'path',
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
            'startLine' => 66,
            'endLine' => 66,
            'startColumn' => 35,
            'endColumn' => 46,
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
        'startLine' => 66,
        'endLine' => 71,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Http\\Controllers\\Admin',
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\SettingAgentController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\SettingAgentController',
        'currentClassName' => 'App\\Http\\Controllers\\Admin\\SettingAgentController',
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