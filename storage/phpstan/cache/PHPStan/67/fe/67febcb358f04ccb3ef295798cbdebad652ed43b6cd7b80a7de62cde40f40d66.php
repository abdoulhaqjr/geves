<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Support\ConflictOfInterest.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Support\ConflictOfInterest
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-5244dc338126b8006647c26ffcd76a91fbcd98fc3bb0ace6abce1653c11977fc',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Support\\ConflictOfInterest',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Support/ConflictOfInterest.php',
      ),
    ),
    'namespace' => 'App\\Support',
    'name' => 'App\\Support\\ConflictOfInterest',
    'shortName' => 'ConflictOfInterest',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Détection déterministe des conflits d\'intérêts potentiels entre un relecteur
 * et les auteurs d\'une communication, à partir des données réelles (auteurs JSON,
 * institution, historique de co-publication). Règles du mockup / guide §2 :
 * même institution, co-auteur récent (< 5 ans), homonymie (lien familial possible),
 * relecteur lui-même auteur. Le résultat est indicatif : l\'admin garde la décision.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 14,
    'endLine' => 184,
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
      'HIGH' => 
      array (
        'declaringClassName' => 'App\\Support\\ConflictOfInterest',
        'implementingClassName' => 'App\\Support\\ConflictOfInterest',
        'name' => 'HIGH',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'high\'',
          'attributes' => 
          array (
            'startLine' => 16,
            'endLine' => 16,
            'startTokenPos' => 28,
            'startFilePos' => 542,
            'endTokenPos' => 28,
            'endFilePos' => 547,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 16,
        'endLine' => 16,
        'startColumn' => 5,
        'endColumn' => 31,
      ),
      'MEDIUM' => 
      array (
        'declaringClassName' => 'App\\Support\\ConflictOfInterest',
        'implementingClassName' => 'App\\Support\\ConflictOfInterest',
        'name' => 'MEDIUM',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'medium\'',
          'attributes' => 
          array (
            'startLine' => 18,
            'endLine' => 18,
            'startTokenPos' => 39,
            'startFilePos' => 577,
            'endTokenPos' => 39,
            'endFilePos' => 584,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 18,
        'endLine' => 18,
        'startColumn' => 5,
        'endColumn' => 35,
      ),
      'LOW' => 
      array (
        'declaringClassName' => 'App\\Support\\ConflictOfInterest',
        'implementingClassName' => 'App\\Support\\ConflictOfInterest',
        'name' => 'LOW',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'low\'',
          'attributes' => 
          array (
            'startLine' => 20,
            'endLine' => 20,
            'startTokenPos' => 50,
            'startFilePos' => 611,
            'endTokenPos' => 50,
            'endFilePos' => 615,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 20,
        'endLine' => 20,
        'startColumn' => 5,
        'endColumn' => 29,
      ),
      'LEVEL_ORDER' => 
      array (
        'declaringClassName' => 'App\\Support\\ConflictOfInterest',
        'implementingClassName' => 'App\\Support\\ConflictOfInterest',
        'name' => 'LEVEL_ORDER',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[self::LOW => 1, self::MEDIUM => 2, self::HIGH => 3]',
          'attributes' => 
          array (
            'startLine' => 22,
            'endLine' => 22,
            'startTokenPos' => 61,
            'startFilePos' => 651,
            'endTokenPos' => 87,
            'endFilePos' => 702,
          ),
        ),
        'docComment' => NULL,
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
      'check' => 
      array (
        'name' => 'check',
        'parameters' => 
        array (
          'submission' => 
          array (
            'name' => 'submission',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Submission',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 29,
            'endLine' => 29,
            'startColumn' => 34,
            'endColumn' => 55,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'reviewerName' => 
          array (
            'name' => 'reviewerName',
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
            'startLine' => 29,
            'endLine' => 29,
            'startColumn' => 58,
            'endColumn' => 77,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'reviewerEmail' => 
          array (
            'name' => 'reviewerEmail',
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
            'startLine' => 29,
            'endLine' => 29,
            'startColumn' => 80,
            'endColumn' => 100,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'institution' => 
          array (
            'name' => 'institution',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 29,
                'endLine' => 29,
                'startTokenPos' => 122,
                'startFilePos' => 1006,
                'endTokenPos' => 122,
                'endFilePos' => 1009,
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
            'startLine' => 29,
            'endLine' => 29,
            'startColumn' => 103,
            'endColumn' => 129,
            'parameterIndex' => 3,
            'isOptional' => true,
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
 * Conflits potentiels entre le relecteur et les auteurs de la soumission.
 *
 * @return list<array{level: string, label: string, detail: string}>
 */',
        'startLine' => 29,
        'endLine' => 74,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\ConflictOfInterest',
        'implementingClassName' => 'App\\Support\\ConflictOfInterest',
        'currentClassName' => 'App\\Support\\ConflictOfInterest',
        'aliasName' => NULL,
      ),
      'ignoresSameInstitution' => 
      array (
        'name' => 'ignoresSameInstitution',
        'parameters' => 
        array (
          'submission' => 
          array (
            'name' => 'submission',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Submission',
                'isIdentifier' => false,
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
            'startColumn' => 51,
            'endColumn' => 72,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * L\'événement de cette soumission ignore-t-elle le conflit « même institution » ?
 * Réglage `edition.settings[\'options\'][\'coi_ignore_same_institution\']`, coché
 * dans l\'onglet Options de la fiche d\'événement. ⚠️ Défaut IGNORÉ (true) :
 * la plateforme sert une université unique, où presque toutes les paires
 * partagent l\'institution - la règle n\'a de sens que si on l\'active.
 */',
        'startLine' => 83,
        'endLine' => 86,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\ConflictOfInterest',
        'implementingClassName' => 'App\\Support\\ConflictOfInterest',
        'currentClassName' => 'App\\Support\\ConflictOfInterest',
        'aliasName' => NULL,
      ),
      'worstLevel' => 
      array (
        'name' => 'worstLevel',
        'parameters' => 
        array (
          'conflicts' => 
          array (
            'name' => 'conflicts',
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
            'startLine' => 93,
            'endLine' => 93,
            'startColumn' => 39,
            'endColumn' => 54,
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
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Niveau le plus élevé parmi une liste de conflits (ou null si aucun).
 *
 * @param  list<array{level?: string}>  $conflicts
 */',
        'startLine' => 93,
        'endLine' => 107,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\ConflictOfInterest',
        'implementingClassName' => 'App\\Support\\ConflictOfInterest',
        'currentClassName' => 'App\\Support\\ConflictOfInterest',
        'aliasName' => NULL,
      ),
      'authorsOf' => 
      array (
        'name' => 'authorsOf',
        'parameters' => 
        array (
          'submission' => 
          array (
            'name' => 'submission',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Submission',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 114,
            'endLine' => 114,
            'startColumn' => 41,
            'endColumn' => 62,
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
 * Auteurs normalisés (éléments non-tableau du JSON écartés).
 *
 * @return list<array<string, mixed>>
 */',
        'startLine' => 114,
        'endLine' => 117,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 18,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\ConflictOfInterest',
        'implementingClassName' => 'App\\Support\\ConflictOfInterest',
        'currentClassName' => 'App\\Support\\ConflictOfInterest',
        'aliasName' => NULL,
      ),
      'coPublicationWith' => 
      array (
        'name' => 'coPublicationWith',
        'parameters' => 
        array (
          'submission' => 
          array (
            'name' => 'submission',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Submission',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 125,
            'endLine' => 125,
            'startColumn' => 49,
            'endColumn' => 70,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'revEmail' => 
          array (
            'name' => 'revEmail',
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
            'startLine' => 125,
            'endLine' => 125,
            'startColumn' => 73,
            'endColumn' => 88,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Noms des auteurs de la soumission avec qui le relecteur a déjà co-signé une
 * autre communication (dans les 5 dernières années).
 *
 * @return list<string>
 */',
        'startLine' => 125,
        'endLine' => 168,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 18,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\ConflictOfInterest',
        'implementingClassName' => 'App\\Support\\ConflictOfInterest',
        'currentClassName' => 'App\\Support\\ConflictOfInterest',
        'aliasName' => NULL,
      ),
      'normalize' => 
      array (
        'name' => 'normalize',
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
            'startLine' => 171,
            'endLine' => 171,
            'startColumn' => 41,
            'endColumn' => 53,
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
        'docComment' => '/** Normalise une chaîne (minuscule, espaces compactés) pour comparaison. */',
        'startLine' => 171,
        'endLine' => 174,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 18,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\ConflictOfInterest',
        'implementingClassName' => 'App\\Support\\ConflictOfInterest',
        'currentClassName' => 'App\\Support\\ConflictOfInterest',
        'aliasName' => NULL,
      ),
      'lastName' => 
      array (
        'name' => 'lastName',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
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
            'startLine' => 177,
            'endLine' => 177,
            'startColumn' => 40,
            'endColumn' => 51,
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
        'docComment' => '/** Dernier mot d\'un nom (heuristique de nom de famille). */',
        'startLine' => 177,
        'endLine' => 183,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 18,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\ConflictOfInterest',
        'implementingClassName' => 'App\\Support\\ConflictOfInterest',
        'currentClassName' => 'App\\Support\\ConflictOfInterest',
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