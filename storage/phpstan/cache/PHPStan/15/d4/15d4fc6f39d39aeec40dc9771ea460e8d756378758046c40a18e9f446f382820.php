<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Actions\AutoAssignReviewers.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Actions\AutoAssignReviewers
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-a6cede16c883be6520d9a1a4a12e3c106a5b9147f877b445110013c0d489657b',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Actions\\AutoAssignReviewers',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Actions/AutoAssignReviewers.php',
      ),
    ),
    'namespace' => 'App\\Actions',
    'name' => 'App\\Actions\\AutoAssignReviewers',
    'shortName' => 'AutoAssignReviewers',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Attribution automatique des relecteurs sur un événement : prépare les données
 * réelles (soumissions à couvrir, relecteurs + expertise + charge, conflits
 * d\'intérêts), délègue le choix au {@see ReviewerAssignmentPlanner}, et sait
 * appliquer un plan validé en réutilisant {@see AssignReviewer} (idempotent).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 22,
    'endLine' => 186,
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
      'OPEN_STATUSES' => 
      array (
        'declaringClassName' => 'App\\Actions\\AutoAssignReviewers',
        'implementingClassName' => 'App\\Actions\\AutoAssignReviewers',
        'name' => 'OPEN_STATUSES',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'submitted\', \'under_review\']',
          'attributes' => 
          array (
            'startLine' => 25,
            'endLine' => 25,
            'startTokenPos' => 75,
            'startFilePos' => 798,
            'endTokenPos' => 80,
            'endFilePos' => 826,
          ),
        ),
        'docComment' => '/** Statuts de soumission éligibles à l\'attribution. */',
        'attributes' => 
        array (
        ),
        'startLine' => 25,
        'endLine' => 25,
        'startColumn' => 5,
        'endColumn' => 64,
      ),
    ),
    'immediateProperties' => 
    array (
      'planner' => 
      array (
        'declaringClassName' => 'App\\Actions\\AutoAssignReviewers',
        'implementingClassName' => 'App\\Actions\\AutoAssignReviewers',
        'name' => 'planner',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'App\\Support\\ReviewerAssignmentPlanner',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 28,
        'endLine' => 28,
        'startColumn' => 9,
        'endColumn' => 59,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'assign' => 
      array (
        'declaringClassName' => 'App\\Actions\\AutoAssignReviewers',
        'implementingClassName' => 'App\\Actions\\AutoAssignReviewers',
        'name' => 'assign',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'App\\Actions\\AssignReviewer',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 29,
        'endLine' => 29,
        'startColumn' => 9,
        'endColumn' => 47,
        'isPromoted' => true,
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
          'planner' => 
          array (
            'name' => 'planner',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Support\\ReviewerAssignmentPlanner',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 28,
            'endLine' => 28,
            'startColumn' => 9,
            'endColumn' => 59,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'assign' => 
          array (
            'name' => 'assign',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Actions\\AssignReviewer',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 29,
            'endLine' => 29,
            'startColumn' => 9,
            'endColumn' => 47,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 27,
        'endLine' => 30,
        'startColumn' => 5,
        'endColumn' => 8,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Actions',
        'declaringClassName' => 'App\\Actions\\AutoAssignReviewers',
        'implementingClassName' => 'App\\Actions\\AutoAssignReviewers',
        'currentClassName' => 'App\\Actions\\AutoAssignReviewers',
        'aliasName' => NULL,
      ),
      'preview' => 
      array (
        'name' => 'preview',
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
            'startLine' => 42,
            'endLine' => 42,
            'startColumn' => 29,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'target' => 
          array (
            'name' => 'target',
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
            'startLine' => 42,
            'endLine' => 42,
            'startColumn' => 43,
            'endColumn' => 53,
            'parameterIndex' => 1,
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
            'startLine' => 42,
            'endLine' => 42,
            'startColumn' => 56,
            'endColumn' => 67,
            'parameterIndex' => 2,
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
 * Construit un aperçu (dry-run) : propositions + charge par relecteur + stats.
 *
 * @return array{
 *   proposals: list<array{submission_id:int, submission_title:string, submission_ref:string, track:string, reviewer_id:int, reviewer_name:string, reason:string, coi:string|null}>,
 *   reviewers: list<array{id:int, name:string, tracks:list<string>, current:int, proposed:int}>,
 *   needing: int,
 *   uncovered: int
 * }
 */',
        'startLine' => 42,
        'endLine' => 139,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Actions',
        'declaringClassName' => 'App\\Actions\\AutoAssignReviewers',
        'implementingClassName' => 'App\\Actions\\AutoAssignReviewers',
        'currentClassName' => 'App\\Actions\\AutoAssignReviewers',
        'aliasName' => NULL,
      ),
      'apply' => 
      array (
        'name' => 'apply',
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
            'startLine' => 148,
            'endLine' => 148,
            'startColumn' => 27,
            'endColumn' => 38,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'pairs' => 
          array (
            'name' => 'pairs',
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
            'startLine' => 148,
            'endLine' => 148,
            'startColumn' => 41,
            'endColumn' => 52,
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
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Applique un plan validé (paires soumission→relecteur), en re-vérifiant
 * l\'appartenance à l\'événement et l\'absence de conflit d\'intérêt élevé.
 *
 * @param  list<array{submission_id:int, reviewer_id:int, reason?:string|null}>  $pairs
 * @return int nombre de relectures effectivement créées
 */',
        'startLine' => 148,
        'endLine' => 185,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Actions',
        'declaringClassName' => 'App\\Actions\\AutoAssignReviewers',
        'implementingClassName' => 'App\\Actions\\AutoAssignReviewers',
        'currentClassName' => 'App\\Actions\\AutoAssignReviewers',
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