<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Actions\UpsertParticipantForAccount.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Actions\UpsertParticipantForAccount
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-6320b421ff516591a19c8f372842f2d517459b0cb50c498cd59e2780f218366d',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Actions\\UpsertParticipantForAccount',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Actions/UpsertParticipantForAccount.php',
      ),
    ),
    'namespace' => 'App\\Actions',
    'name' => 'App\\Actions\\UpsertParticipantForAccount',
    'shortName' => 'UpsertParticipantForAccount',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Crée ou retrouve le participant d\'un compte sur un événement donné.
 *
 * Règles (alignées sur le plugin WordPress) :
 * - **Identité imposée par le compte** : nom/prénom proviennent du compte
 *   (source de vérité), jamais falsifiables via un formulaire.
 * - **Rôle fort non rétrogradé** : un relecteur / membre de comité / modérateur /
 *   rapporteur ne redevient pas « auteur » lors d\'une nouvelle soumission.
 * - `access_token` généré une seule fois (lien de suivi par jeton).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 19,
    'endLine' => 61,
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
      'STRONG_ROLES' => 
      array (
        'declaringClassName' => 'App\\Actions\\UpsertParticipantForAccount',
        'implementingClassName' => 'App\\Actions\\UpsertParticipantForAccount',
        'name' => 'STRONG_ROLES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'reviewer\', \'committee\', \'moderator\', \'rapporteur\']',
          'attributes' => 
          array (
            'startLine' => 22,
            'endLine' => 22,
            'startTokenPos' => 40,
            'startFilePos' => 733,
            'endTokenPos' => 51,
            'endFilePos' => 784,
          ),
        ),
        'docComment' => '/** Rôles à ne jamais rétrograder. */',
        'attributes' => 
        array (
        ),
        'startLine' => 22,
        'endLine' => 22,
        'startColumn' => 5,
        'endColumn' => 85,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'handle' => 
      array (
        'name' => 'handle',
        'parameters' => 
        array (
          'event' => 
          array (
            'name' => 'event',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Event',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 30,
            'endLine' => 30,
            'startColumn' => 28,
            'endColumn' => 39,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'account' => 
          array (
            'name' => 'account',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\User',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 30,
            'endLine' => 30,
            'startColumn' => 42,
            'endColumn' => 54,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'role' => 
          array (
            'name' => 'role',
            'default' => 
            array (
              'code' => '\'author\'',
              'attributes' => 
              array (
                'startLine' => 30,
                'endLine' => 30,
                'startTokenPos' => 78,
                'startFilePos' => 1211,
                'endTokenPos' => 78,
                'endFilePos' => 1218,
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
            'startLine' => 30,
            'endLine' => 30,
            'startColumn' => 57,
            'endColumn' => 79,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'grade' => 
          array (
            'name' => 'grade',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 30,
                'endLine' => 30,
                'startTokenPos' => 88,
                'startFilePos' => 1238,
                'endTokenPos' => 88,
                'endFilePos' => 1241,
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
                      'name' => 'string',
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
            'startLine' => 30,
            'endLine' => 30,
            'startColumn' => 82,
            'endColumn' => 102,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
          'affiliation' => 
          array (
            'name' => 'affiliation',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 30,
                'endLine' => 30,
                'startTokenPos' => 98,
                'startFilePos' => 1267,
                'endTokenPos' => 98,
                'endFilePos' => 1270,
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
                      'name' => 'string',
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
            'startLine' => 30,
            'endLine' => 30,
            'startColumn' => 105,
            'endColumn' => 131,
            'parameterIndex' => 4,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'App\\Models\\Participant',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param  string|null  $grade  grade POUR CETTE ÉDITION (repli : grade du compte).
 *                              Permet à une même personne d\'avoir des grades
 *                              différents au fil des colloques.
 * @param  string|null  $affiliation  affiliation pour cet événement (repli : compte).
 */',
        'startLine' => 30,
        'endLine' => 60,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Actions',
        'declaringClassName' => 'App\\Actions\\UpsertParticipantForAccount',
        'implementingClassName' => 'App\\Actions\\UpsertParticipantForAccount',
        'currentClassName' => 'App\\Actions\\UpsertParticipantForAccount',
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