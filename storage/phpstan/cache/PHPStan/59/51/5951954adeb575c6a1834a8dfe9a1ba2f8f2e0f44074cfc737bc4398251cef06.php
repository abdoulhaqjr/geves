<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Support\ReviewerAssignmentPlanner.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Support\ReviewerAssignmentPlanner
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-0d7ac62b6bd75abb5ffe28efebfc9b1a867d2c0a9f9f192a292c39caefac66f4',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Support\\ReviewerAssignmentPlanner',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Support/ReviewerAssignmentPlanner.php',
      ),
    ),
    'namespace' => 'App\\Support',
    'name' => 'App\\Support\\ReviewerAssignmentPlanner',
    'shortName' => 'ReviewerAssignmentPlanner',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Moteur d\'attribution automatique des relecteurs (déterministe, sans I/O donc
 * testable en isolation). À partir des soumissions à couvrir, des relecteurs
 * candidats (expertise + charge courante) et d\'une carte des conflits d\'intérêts,
 * il propose des affectations en :
 *   - excluant tout conflit d\'intérêt ÉLEVÉ (règle dure) ;
 *   - privilégiant l\'adéquation d\'axe (expertise du relecteur ↔ axe de la soumission) ;
 *   - équilibrant la charge (un relecteur peu chargé passe devant) sans dépasser un
 *     plafond par relecteur ;
 *   - pénalisant (sans interdire) un conflit d\'intérêt moyen/faible.
 * La décision finale reste humaine : ceci alimente un aperçu que l\'admin valide.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 19,
    'endLine' => 142,
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
      'SCORE_AXIS_MATCH' => 
      array (
        'declaringClassName' => 'App\\Support\\ReviewerAssignmentPlanner',
        'implementingClassName' => 'App\\Support\\ReviewerAssignmentPlanner',
        'name' => 'SCORE_AXIS_MATCH',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '100',
          'attributes' => 
          array (
            'startLine' => 21,
            'endLine' => 21,
            'startTokenPos' => 28,
            'startFilePos' => 855,
            'endTokenPos' => 28,
            'endFilePos' => 857,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 21,
        'endLine' => 21,
        'startColumn' => 5,
        'endColumn' => 41,
      ),
      'SCORE_GENERALIST' => 
      array (
        'declaringClassName' => 'App\\Support\\ReviewerAssignmentPlanner',
        'implementingClassName' => 'App\\Support\\ReviewerAssignmentPlanner',
        'name' => 'SCORE_GENERALIST',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '20',
          'attributes' => 
          array (
            'startLine' => 23,
            'endLine' => 23,
            'startTokenPos' => 39,
            'startFilePos' => 898,
            'endTokenPos' => 39,
            'endFilePos' => 899,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 23,
        'endLine' => 23,
        'startColumn' => 5,
        'endColumn' => 40,
      ),
      'SCORE_VOLUNTEER' => 
      array (
        'declaringClassName' => 'App\\Support\\ReviewerAssignmentPlanner',
        'implementingClassName' => 'App\\Support\\ReviewerAssignmentPlanner',
        'name' => 'SCORE_VOLUNTEER',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '60',
          'attributes' => 
          array (
            'startLine' => 25,
            'endLine' => 25,
            'startTokenPos' => 50,
            'startFilePos' => 939,
            'endTokenPos' => 50,
            'endFilePos' => 940,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 25,
        'endLine' => 25,
        'startColumn' => 5,
        'endColumn' => 39,
      ),
      'BID_INTERESTED' => 
      array (
        'declaringClassName' => 'App\\Support\\ReviewerAssignmentPlanner',
        'implementingClassName' => 'App\\Support\\ReviewerAssignmentPlanner',
        'name' => 'BID_INTERESTED',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'interested\'',
          'attributes' => 
          array (
            'startLine' => 28,
            'endLine' => 28,
            'startTokenPos' => 63,
            'startFilePos' => 1060,
            'endTokenPos' => 63,
            'endFilePos' => 1071,
          ),
        ),
        'docComment' => '/** Valeurs de candidature (miroir de {@see ReviewerBid}, sans couplage). */',
        'attributes' => 
        array (
        ),
        'startLine' => 28,
        'endLine' => 28,
        'startColumn' => 5,
        'endColumn' => 48,
      ),
      'BID_UNAVAILABLE' => 
      array (
        'declaringClassName' => 'App\\Support\\ReviewerAssignmentPlanner',
        'implementingClassName' => 'App\\Support\\ReviewerAssignmentPlanner',
        'name' => 'BID_UNAVAILABLE',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'unavailable\'',
          'attributes' => 
          array (
            'startLine' => 30,
            'endLine' => 30,
            'startTokenPos' => 74,
            'startFilePos' => 1111,
            'endTokenPos' => 74,
            'endFilePos' => 1123,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 30,
        'endLine' => 30,
        'startColumn' => 5,
        'endColumn' => 50,
      ),
      'PENALTY_COI_MEDIUM' => 
      array (
        'declaringClassName' => 'App\\Support\\ReviewerAssignmentPlanner',
        'implementingClassName' => 'App\\Support\\ReviewerAssignmentPlanner',
        'name' => 'PENALTY_COI_MEDIUM',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '40',
          'attributes' => 
          array (
            'startLine' => 32,
            'endLine' => 32,
            'startTokenPos' => 85,
            'startFilePos' => 1166,
            'endTokenPos' => 85,
            'endFilePos' => 1167,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 32,
        'endLine' => 32,
        'startColumn' => 5,
        'endColumn' => 42,
      ),
      'PENALTY_COI_LOW' => 
      array (
        'declaringClassName' => 'App\\Support\\ReviewerAssignmentPlanner',
        'implementingClassName' => 'App\\Support\\ReviewerAssignmentPlanner',
        'name' => 'PENALTY_COI_LOW',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '5',
          'attributes' => 
          array (
            'startLine' => 34,
            'endLine' => 34,
            'startTokenPos' => 96,
            'startFilePos' => 1207,
            'endTokenPos' => 96,
            'endFilePos' => 1207,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 34,
        'endLine' => 34,
        'startColumn' => 5,
        'endColumn' => 38,
      ),
      'PENALTY_PER_LOAD' => 
      array (
        'declaringClassName' => 'App\\Support\\ReviewerAssignmentPlanner',
        'implementingClassName' => 'App\\Support\\ReviewerAssignmentPlanner',
        'name' => 'PENALTY_PER_LOAD',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '10',
          'attributes' => 
          array (
            'startLine' => 36,
            'endLine' => 36,
            'startTokenPos' => 107,
            'startFilePos' => 1248,
            'endTokenPos' => 107,
            'endFilePos' => 1249,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 36,
        'endLine' => 36,
        'startColumn' => 5,
        'endColumn' => 40,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'plan' => 
      array (
        'name' => 'plan',
        'parameters' => 
        array (
          'submissions' => 
          array (
            'name' => 'submissions',
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
            'startLine' => 46,
            'endLine' => 46,
            'startColumn' => 26,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'reviewers' => 
          array (
            'name' => 'reviewers',
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
            'startLine' => 46,
            'endLine' => 46,
            'startColumn' => 46,
            'endColumn' => 61,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'coi' => 
          array (
            'name' => 'coi',
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
            'startLine' => 46,
            'endLine' => 46,
            'startColumn' => 64,
            'endColumn' => 73,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'maxLoad' => 
          array (
            'name' => 'maxLoad',
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
            'startLine' => 46,
            'endLine' => 46,
            'startColumn' => 76,
            'endColumn' => 87,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'bids' => 
          array (
            'name' => 'bids',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 46,
                'endLine' => 46,
                'startTokenPos' => 144,
                'startFilePos' => 2002,
                'endTokenPos' => 145,
                'endFilePos' => 2003,
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
            'startLine' => 46,
            'endLine' => 46,
            'startColumn' => 90,
            'endColumn' => 105,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param  list<array{id:int, track:string, needed:int, exclude?:list<int>}>  $submissions
 * @param  list<array{id:int, tracks:list<string>, load:int}>  $reviewers
 * @param  array<int, array<int, string|null>>  $coi  [submissionId][reviewerId] => niveau ConflictOfInterest ou null
 * @param  int  $maxLoad  charge maximale par relecteur (≤ 0 = illimitée)
 * @param  array<int, array<int, array{interest?:string, conflict?:bool}>>  $bids  [submissionId][reviewerId] => candidature du relecteur
 * @return list<array{submission_id:int, reviewer_id:int, reason:string, coi:string|null, axis_match:bool}>
 */',
        'startLine' => 46,
        'endLine' => 132,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\ReviewerAssignmentPlanner',
        'implementingClassName' => 'App\\Support\\ReviewerAssignmentPlanner',
        'currentClassName' => 'App\\Support\\ReviewerAssignmentPlanner',
        'aliasName' => NULL,
      ),
      'reason' => 
      array (
        'name' => 'reason',
        'parameters' => 
        array (
          'axisMatch' => 
          array (
            'name' => 'axisMatch',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'bool',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 134,
            'endLine' => 134,
            'startColumn' => 29,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'generalist' => 
          array (
            'name' => 'generalist',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'bool',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 134,
            'endLine' => 134,
            'startColumn' => 46,
            'endColumn' => 61,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'volunteer' => 
          array (
            'name' => 'volunteer',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 134,
                'endLine' => 134,
                'startTokenPos' => 967,
                'startFilePos' => 5689,
                'endTokenPos' => 967,
                'endFilePos' => 5693,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'bool',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 134,
            'endLine' => 134,
            'startColumn' => 64,
            'endColumn' => 86,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 134,
        'endLine' => 141,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\ReviewerAssignmentPlanner',
        'implementingClassName' => 'App\\Support\\ReviewerAssignmentPlanner',
        'currentClassName' => 'App\\Support\\ReviewerAssignmentPlanner',
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