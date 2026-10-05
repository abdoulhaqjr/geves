<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Http\Controllers\Account\AgendaController.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Http\Controllers\Account\AgendaController
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-e2067ec3e5d00eec0c7c6e6472c3652efef1ebd929a445616ceefb7f9d88ddc9',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Http\\Controllers\\Account\\AgendaController',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Http/Controllers/Account/AgendaController.php',
      ),
    ),
    'namespace' => 'App\\Http\\Controllers\\Account',
    'name' => 'App\\Http\\Controllers\\Account\\AgendaController',
    'shortName' => 'AgendaController',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Agenda personnel du participant : ses créneaux de passage (colloque courant)
 * exportés en calendrier ICS (avec rappel), importable partout.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 20,
    'endLine' => 79,
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
      'ics' => 
      array (
        'name' => 'ics',
        'parameters' => 
        array (
          'calendar' => 
          array (
            'name' => 'calendar',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Support\\CalendarExport',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 22,
            'endLine' => 22,
            'startColumn' => 25,
            'endColumn' => 48,
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
            'name' => 'Symfony\\Component\\HttpFoundation\\StreamedResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 22,
        'endLine' => 30,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Controllers\\Account',
        'declaringClassName' => 'App\\Http\\Controllers\\Account\\AgendaController',
        'implementingClassName' => 'App\\Http\\Controllers\\Account\\AgendaController',
        'currentClassName' => 'App\\Http\\Controllers\\Account\\AgendaController',
        'aliasName' => NULL,
      ),
      'events' => 
      array (
        'name' => 'events',
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
        'docComment' => '/**
 * Événements calendrier des passages du participant sur le colloque courant.
 *
 * @return list<array{uid:string, summary:string, start:Carbon, end:Carbon|null, location:string, description:string}>
 */',
        'startLine' => 37,
        'endLine' => 59,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Http\\Controllers\\Account',
        'declaringClassName' => 'App\\Http\\Controllers\\Account\\AgendaController',
        'implementingClassName' => 'App\\Http\\Controllers\\Account\\AgendaController',
        'currentClassName' => 'App\\Http\\Controllers\\Account\\AgendaController',
        'aliasName' => NULL,
      ),
      'slotEvent' => 
      array (
        'name' => 'slotEvent',
        'parameters' => 
        array (
          'slot' => 
          array (
            'name' => 'slot',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Slot',
                'isIdentifier' => false,
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
            'startColumn' => 38,
            'endColumn' => 47,
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
 * Construit l\'événement calendrier d\'un créneau (réutilisable).
 *
 * @return array{uid:string, summary:string, start:Carbon, end:Carbon|null, location:string, description:string}
 */',
        'startLine' => 66,
        'endLine' => 78,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Http\\Controllers\\Account',
        'declaringClassName' => 'App\\Http\\Controllers\\Account\\AgendaController',
        'implementingClassName' => 'App\\Http\\Controllers\\Account\\AgendaController',
        'currentClassName' => 'App\\Http\\Controllers\\Account\\AgendaController',
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