<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Meetings\LiveKitRoom.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Meetings\LiveKitRoom
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-84809e5db16a6f39c16938c83ea1dd517863eafef41431cfce0c936f81939296',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Meetings\\LiveKitRoom',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Meetings/LiveKitRoom.php',
      ),
    ),
    'namespace' => 'App\\Meetings',
    'name' => 'App\\Meetings\\LiveKitRoom',
    'shortName' => 'LiveKitRoom',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Client LiveKit RoomService : **qui est réellement connecté** à la salle d\'une
 * séance, maintenant.
 *
 * ⚠️ C\'est la seule source d\'occupation en temps réel dont dispose la plateforme.
 * Jitsi public n\'expose aucune API de liste de participants : sans LiveKit
 * configuré, on ne sait que qui a **cliqué sur « Rejoindre »** depuis GEVES
 * ({@see SessionPresence}). Ne pas confondre les deux - l\'un dit
 * « est dans la salle », l\'autre « est passé par la porte ».
 *
 * L\'identité LiveKit est forgée au moment du jeton ({@see Providers\\LiveKitProvider::token()})
 * et porte l\'identifiant de l\'inscription, ce qui permet de rapprocher un
 * connecté d\'un participant sans se fier à son nom d\'affichage, que l\'intéressé
 * peut changer.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 27,
    'endLine' => 138,
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
      'IDENTITY_PREFIX' => 
      array (
        'declaringClassName' => 'App\\Meetings\\LiveKitRoom',
        'implementingClassName' => 'App\\Meetings\\LiveKitRoom',
        'name' => 'IDENTITY_PREFIX',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'ukp\'',
          'attributes' => 
          array (
            'startLine' => 30,
            'endLine' => 30,
            'startTokenPos' => 55,
            'startFilePos' => 1117,
            'endTokenPos' => 55,
            'endFilePos' => 1121,
          ),
        ),
        'docComment' => '/** Préfixe d\'identité qui porte l\'id de l\'inscription : `ukp-{id}-…`. */',
        'attributes' => 
        array (
        ),
        'startLine' => 30,
        'endLine' => 30,
        'startColumn' => 5,
        'endColumn' => 41,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'isConfigured' => 
      array (
        'name' => 'isConfigured',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 32,
        'endLine' => 37,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Meetings',
        'declaringClassName' => 'App\\Meetings\\LiveKitRoom',
        'implementingClassName' => 'App\\Meetings\\LiveKitRoom',
        'currentClassName' => 'App\\Meetings\\LiveKitRoom',
        'aliasName' => NULL,
      ),
      'connectedParticipantIds' => 
      array (
        'name' => 'connectedParticipantIds',
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
            'startLine' => 49,
            'endLine' => 49,
            'startColumn' => 45,
            'endColumn' => 61,
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
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Identifiants d\'inscription (`participants.id`) actuellement connectés.
 *
 * ⚠️ Ne lève jamais : la salle peut être injoignable, et une panne de LiveKit
 * ne doit pas emporter la page de présence ni la salle elle-même. Dans ce cas
 * on ne sait rien, et on le dit en renvoyant null - ce qui n\'est pas la même
 * chose qu\'une salle vide.
 *
 * @return list<int>|null null = information indisponible
 */',
        'startLine' => 49,
        'endLine' => 85,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Meetings',
        'declaringClassName' => 'App\\Meetings\\LiveKitRoom',
        'implementingClassName' => 'App\\Meetings\\LiveKitRoom',
        'currentClassName' => 'App\\Meetings\\LiveKitRoom',
        'aliasName' => NULL,
      ),
      'identityFor' => 
      array (
        'name' => 'identityFor',
        'parameters' => 
        array (
          'participantId' => 
          array (
            'name' => 'participantId',
            'default' => NULL,
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
                      'name' => 'int',
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
            'startLine' => 93,
            'endLine' => 93,
            'startColumn' => 40,
            'endColumn' => 58,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'displayName' => 
          array (
            'name' => 'displayName',
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
            'startColumn' => 61,
            'endColumn' => 79,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Identité LiveKit d\'une inscription : `ukp-{id}-{aléa}`.
 *
 * L\'aléa est indispensable - LiveKit refuse deux connexions de même identité,
 * et une même personne peut ouvrir la salle sur deux appareils.
 */',
        'startLine' => 93,
        'endLine' => 100,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Meetings',
        'declaringClassName' => 'App\\Meetings\\LiveKitRoom',
        'implementingClassName' => 'App\\Meetings\\LiveKitRoom',
        'currentClassName' => 'App\\Meetings\\LiveKitRoom',
        'aliasName' => NULL,
      ),
      'participantIdFromIdentity' => 
      array (
        'name' => 'participantIdFromIdentity',
        'parameters' => 
        array (
          'identity' => 
          array (
            'name' => 'identity',
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
            'startLine' => 103,
            'endLine' => 103,
            'startColumn' => 54,
            'endColumn' => 69,
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
                  'name' => 'int',
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
        'attributes' => 
        array (
        ),
        'docComment' => '/** Identifiant d\'inscription porté par une identité, ou null (invité). */',
        'startLine' => 103,
        'endLine' => 110,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Meetings',
        'declaringClassName' => 'App\\Meetings\\LiveKitRoom',
        'implementingClassName' => 'App\\Meetings\\LiveKitRoom',
        'currentClassName' => 'App\\Meetings\\LiveKitRoom',
        'aliasName' => NULL,
      ),
      'token' => 
      array (
        'name' => 'token',
        'parameters' => 
        array (
          'salle' => 
          array (
            'name' => 'salle',
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
            'startLine' => 119,
            'endLine' => 119,
            'startColumn' => 28,
            'endColumn' => 40,
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
 * Jeton d\'administration de salle (JWT HS256), courte durée.
 *
 * ⚠️ `room` doit nommer la salle EXACTE : LiveKit refuse le joker `*` sur
 * `roomAdmin` (401 « permissions denied »). Le jeton ne vaut donc que pour la
 * salle qu\'on interroge - ce qui est aussi la bonne portée.
 */',
        'startLine' => 119,
        'endLine' => 131,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Meetings',
        'declaringClassName' => 'App\\Meetings\\LiveKitRoom',
        'implementingClassName' => 'App\\Meetings\\LiveKitRoom',
        'currentClassName' => 'App\\Meetings\\LiveKitRoom',
        'aliasName' => NULL,
      ),
      'apiBase' => 
      array (
        'name' => 'apiBase',
        'parameters' => 
        array (
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
        'docComment' => '/** Base HTTP de l\'API LiveKit, dérivée de l\'URL WebSocket (wss→https). */',
        'startLine' => 134,
        'endLine' => 137,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Meetings',
        'declaringClassName' => 'App\\Meetings\\LiveKitRoom',
        'implementingClassName' => 'App\\Meetings\\LiveKitRoom',
        'currentClassName' => 'App\\Meetings\\LiveKitRoom',
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