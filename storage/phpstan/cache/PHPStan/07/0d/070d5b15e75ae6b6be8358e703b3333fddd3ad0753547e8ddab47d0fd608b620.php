<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Http\Controllers\MeetingController.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Http\Controllers\MeetingController
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-ac48752dbc723db25796b3dfbf065e5c87c2b6ff5053887b2f40c8749234a5af',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Http\\Controllers\\MeetingController',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Http/Controllers/MeetingController.php',
      ),
    ),
    'namespace' => 'App\\Http\\Controllers',
    'name' => 'App\\Http\\Controllers\\MeetingController',
    'shortName' => 'MeetingController',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Page « Rejoindre la visio » du module visio, commune à tous les objets
 * `Meetable` : session du programme ET formation. Elle embarque la salle du
 * fournisseur (Jitsi en iframe, LiveKit via le lecteur maison, ou lien externe)
 * selon le rôle calculé du visiteur. Aucune connexion imposée en amont : la page
 * s\'adapte (invite à se connecter, indique si la visio n\'est pas en cours,
 * propose le replay).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 32,
    'endLine' => 241,
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
      'join' => 
      array (
        'name' => 'join',
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
                'name' => 'App\\Models\\EventSession',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 35,
            'endLine' => 35,
            'startColumn' => 26,
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
            'name' => 'Illuminate\\Contracts\\View\\View',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Visio d\'une session du programme. */',
        'startLine' => 35,
        'endLine' => 114,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Controllers',
        'declaringClassName' => 'App\\Http\\Controllers\\MeetingController',
        'implementingClassName' => 'App\\Http\\Controllers\\MeetingController',
        'currentClassName' => 'App\\Http\\Controllers\\MeetingController',
        'aliasName' => NULL,
      ),
      'joinFormation' => 
      array (
        'name' => 'joinFormation',
        'parameters' => 
        array (
          'formation' => 
          array (
            'name' => 'formation',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Formation',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 121,
            'endLine' => 121,
            'startColumn' => 35,
            'endColumn' => 54,
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
            'name' => 'Illuminate\\Contracts\\View\\View',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Visio d\'une formation : même page, même lecteur, même module. L\'accès est
 * calculé par Formation::meetingRoleFor (formateurs = modérateurs, inscrits
 * éligibles = participants ; pas d\'accès public).
 */',
        'startLine' => 121,
        'endLine' => 131,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Controllers',
        'declaringClassName' => 'App\\Http\\Controllers\\MeetingController',
        'implementingClassName' => 'App\\Http\\Controllers\\MeetingController',
        'currentClassName' => 'App\\Http\\Controllers\\MeetingController',
        'aliasName' => NULL,
      ),
      'token' => 
      array (
        'name' => 'token',
        'parameters' => 
        array (
          'type' => 
          array (
            'name' => 'type',
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
            'startLine' => 142,
            'endLine' => 142,
            'startColumn' => 27,
            'endColumn' => 38,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'id' => 
          array (
            'name' => 'id',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 142,
            'endLine' => 142,
            'startColumn' => 41,
            'endColumn' => 47,
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
            'name' => 'Illuminate\\Http\\JsonResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Nouveau jeton d\'accès à la salle, pour se reconnecter sans recharger.
 *
 * ⚠️ Le jeton de connexion expire (2 h par défaut). Jusqu\'ici, une coupure
 * réseau après ce délai laissait le participant devant une salle morte, sans
 * rien d\'autre à faire que recharger la page - en pleine séance. Les droits
 * sont **recalculés** à chaque appel : un rôle retiré entre-temps ne revient
 * pas par la porte du renouvellement.
 */',
        'startLine' => 142,
        'endLine' => 159,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Controllers',
        'declaringClassName' => 'App\\Http\\Controllers\\MeetingController',
        'implementingClassName' => 'App\\Http\\Controllers\\MeetingController',
        'currentClassName' => 'App\\Http\\Controllers\\MeetingController',
        'aliasName' => NULL,
      ),
      'room' => 
      array (
        'name' => 'room',
        'parameters' => 
        array (
          'meetable' => 
          array (
            'name' => 'meetable',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionIntersectionType',
              'data' => 
              array (
                'types' => 
                array (
                  0 => 
                  array (
                    'name' => 'App\\Meetings\\Contracts\\Meetable',
                    'isIdentifier' => false,
                  ),
                  1 => 
                  array (
                    'name' => 'Illuminate\\Database\\Eloquent\\Model',
                    'isIdentifier' => false,
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
            'startLine' => 167,
            'endLine' => 167,
            'startColumn' => 27,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'ctx' => 
          array (
            'name' => 'ctx',
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
            'startLine' => 167,
            'endLine' => 167,
            'startColumn' => 53,
            'endColumn' => 62,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'liveKitExtra' => 
          array (
            'name' => 'liveKitExtra',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 167,
                'endLine' => 167,
                'startTokenPos' => 1059,
                'startFilePos' => 8358,
                'endTokenPos' => 1060,
                'endFilePos' => 8359,
              ),
            ),
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
            'startLine' => 167,
            'endLine' => 167,
            'startColumn' => 65,
            'endColumn' => 88,
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
            'name' => 'Illuminate\\Contracts\\View\\View',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Rendu commun de la salle pour un objet `Meetable`.
 *
 * @param  array{label: string, editionTitle: string, startsAt: Carbon|null, recordingUrl: string|null, backUrl: string, engagementUrl: string|null}  $ctx
 * @param  array<string, mixed>  $liveKitExtra  Enrichissements du lecteur LiveKit propres au type.
 */',
        'startLine' => 167,
        'endLine' => 224,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Http\\Controllers',
        'declaringClassName' => 'App\\Http\\Controllers\\MeetingController',
        'implementingClassName' => 'App\\Http\\Controllers\\MeetingController',
        'currentClassName' => 'App\\Http\\Controllers\\MeetingController',
        'aliasName' => NULL,
      ),
      'backgrounds' => 
      array (
        'name' => 'backgrounds',
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
 * Liste d\'URL d\'images d\'arrière-plan configurées par l\'admin (une par ligne
 * dans le réglage meetings.backgrounds). L\'utilisateur peut aussi charger la
 * sienne depuis la salle.
 *
 * @return list<string>
 */',
        'startLine' => 233,
        'endLine' => 240,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Http\\Controllers',
        'declaringClassName' => 'App\\Http\\Controllers\\MeetingController',
        'implementingClassName' => 'App\\Http\\Controllers\\MeetingController',
        'currentClassName' => 'App\\Http\\Controllers\\MeetingController',
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