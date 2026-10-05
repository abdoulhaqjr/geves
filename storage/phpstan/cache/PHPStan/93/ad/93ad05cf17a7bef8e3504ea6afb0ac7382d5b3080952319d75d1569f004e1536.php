<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Support\CalendarExport.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Support\CalendarExport
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-d51389a3234d43622c69f52418d4678ff1ac00c28a0d407c82aa90e002da789b',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Support\\CalendarExport',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Support/CalendarExport.php',
      ),
    ),
    'namespace' => 'App\\Support',
    'name' => 'App\\Support\\CalendarExport',
    'shortName' => 'CalendarExport',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Export calendrier des passages d\'un participant : fichier ICS (RFC 5545, avec
 * rappel VALARM) importable dans n\'importe quel agenda, et lien « Ajouter à
 * Google Agenda ». Gratuit, sans dépendance externe.
 *
 * Chaque événement : [\'uid\'=>string, \'summary\'=>string, \'start\'=>Carbon,
 *   \'end\'=>Carbon|null, \'location\'=>string, \'description\'=>string].
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 15,
    'endLine' => 111,
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
      'REMINDER_MINUTES' => 
      array (
        'declaringClassName' => 'App\\Support\\CalendarExport',
        'implementingClassName' => 'App\\Support\\CalendarExport',
        'name' => 'REMINDER_MINUTES',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '60',
          'attributes' => 
          array (
            'startLine' => 18,
            'endLine' => 18,
            'startTokenPos' => 30,
            'startFilePos' => 554,
            'endTokenPos' => 30,
            'endFilePos' => 555,
          ),
        ),
        'docComment' => '/** Minutes de rappel avant l\'événement (VALARM). */',
        'attributes' => 
        array (
        ),
        'startLine' => 18,
        'endLine' => 18,
        'startColumn' => 5,
        'endColumn' => 40,
      ),
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
          'events' => 
          array (
            'name' => 'events',
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
            'startLine' => 25,
            'endLine' => 25,
            'startColumn' => 25,
            'endColumn' => 37,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'calendarName' => 
          array (
            'name' => 'calendarName',
            'default' => 
            array (
              'code' => '\'Mon agenda\'',
              'attributes' => 
              array (
                'startLine' => 25,
                'endLine' => 25,
                'startTokenPos' => 52,
                'startFilePos' => 841,
                'endTokenPos' => 52,
                'endFilePos' => 852,
              ),
            ),
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
            'startLine' => 25,
            'endLine' => 25,
            'startColumn' => 40,
            'endColumn' => 74,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        'docComment' => '/**
 * Document ICS (VCALENDAR) pour une liste d\'événements.
 *
 * @param  list<array{uid:string, summary:string, start:Carbon, end:Carbon|null, location?:string, description?:string}>  $events
 */',
        'startLine' => 25,
        'endLine' => 62,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\CalendarExport',
        'implementingClassName' => 'App\\Support\\CalendarExport',
        'currentClassName' => 'App\\Support\\CalendarExport',
        'aliasName' => NULL,
      ),
      'googleUrl' => 
      array (
        'name' => 'googleUrl',
        'parameters' => 
        array (
          'e' => 
          array (
            'name' => 'e',
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
            'startLine' => 69,
            'endLine' => 69,
            'startColumn' => 31,
            'endColumn' => 38,
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
        'docComment' => '/**
 * Lien « Ajouter à Google Agenda » pour un événement.
 *
 * @param  array{summary:string, start:Carbon, end:Carbon|null, location?:string, description?:string}  $e
 */',
        'startLine' => 69,
        'endLine' => 80,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\CalendarExport',
        'implementingClassName' => 'App\\Support\\CalendarExport',
        'currentClassName' => 'App\\Support\\CalendarExport',
        'aliasName' => NULL,
      ),
      'escape' => 
      array (
        'name' => 'escape',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
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
            'startLine' => 83,
            'endLine' => 83,
            'startColumn' => 29,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Échappement RFC 5545 (backslash, virgule, point-virgule, retour ligne). */',
        'startLine' => 83,
        'endLine' => 90,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\CalendarExport',
        'implementingClassName' => 'App\\Support\\CalendarExport',
        'currentClassName' => 'App\\Support\\CalendarExport',
        'aliasName' => NULL,
      ),
      'fold' => 
      array (
        'name' => 'fold',
        'parameters' => 
        array (
          'line' => 
          array (
            'name' => 'line',
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
            'startLine' => 93,
            'endLine' => 93,
            'startColumn' => 27,
            'endColumn' => 38,
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
        'docComment' => '/** Repli des lignes > 75 octets (RFC 5545 : continuation par espace). */',
        'startLine' => 93,
        'endLine' => 110,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\CalendarExport',
        'implementingClassName' => 'App\\Support\\CalendarExport',
        'currentClassName' => 'App\\Support\\CalendarExport',
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