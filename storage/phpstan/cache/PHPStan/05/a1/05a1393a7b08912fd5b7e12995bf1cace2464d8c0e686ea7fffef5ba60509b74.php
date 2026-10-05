<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Models\Review.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Models\Review
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-9436d2673a4607824a4f915b468a7a64a003140c4a5788be613be7460c67780d',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Models\\Review',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Models/Review.php',
      ),
    ),
    'namespace' => 'App\\Models',
    'name' => 'App\\Models\\Review',
    'shortName' => 'Review',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Relecture en aveugle d\'une soumission par un relecteur (participant).
 *
 * @property array<string, int>|null $scores notes par critère de la grille
 * @property Carbon|null $assigned_at
 * @property Carbon|null $due_at échéance de rendu (cf. ReviewGrid::dueDays)
 * @property Carbon|null $responded_at
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 17,
    'endLine' => 219,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Database\\Eloquent\\Model',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'RECOMMENDATIONS' => 
      array (
        'declaringClassName' => 'App\\Models\\Review',
        'implementingClassName' => 'App\\Models\\Review',
        'name' => 'RECOMMENDATIONS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'accept\' => \'Accepter en l’état\', \'minor\' => \'Révisions mineures\', \'major\' => \'Révisions majeures\', \'reject\' => \'Rejeter\']',
          'attributes' => 
          array (
            'startLine' => 22,
            'endLine' => 27,
            'startTokenPos' => 55,
            'startFilePos' => 612,
            'endTokenPos' => 85,
            'endFilePos' => 778,
          ),
        ),
        'docComment' => '/** Recommandations possibles d\'un relecteur. */',
        'attributes' => 
        array (
        ),
        'startLine' => 22,
        'endLine' => 27,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
      'STATUSES' => 
      array (
        'declaringClassName' => 'App\\Models\\Review',
        'implementingClassName' => 'App\\Models\\Review',
        'name' => 'STATUSES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'invited\' => \'Invitée\', \'accepted\' => \'Acceptée\', \'declined\' => \'Refusée\', \'completed\' => \'Rendue\', \'cancelled\' => \'Annulée\']',
          'attributes' => 
          array (
            'startLine' => 35,
            'endLine' => 41,
            'startTokenPos' => 98,
            'startFilePos' => 1128,
            'endTokenPos' => 135,
            'endFilePos' => 1304,
          ),
        ),
        'docComment' => '/**
 * Cycle de vie d\'une relecture. `invited` : confiée, le relecteur n\'a pas
 * encore répondu. `accepted` : il s\'est engagé. `declined` : il a refusé (la
 * place est libérée, la soumission redevient à couvrir). `completed` : rendue.
 * `cancelled` : retirée par l\'organisation.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 35,
        'endLine' => 41,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
      'ACTIVE_STATUSES' => 
      array (
        'declaringClassName' => 'App\\Models\\Review',
        'implementingClassName' => 'App\\Models\\Review',
        'name' => 'ACTIVE_STATUSES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'invited\', \'accepted\', \'completed\']',
          'attributes' => 
          array (
            'startLine' => 47,
            'endLine' => 47,
            'startTokenPos' => 148,
            'startFilePos' => 1515,
            'endTokenPos' => 156,
            'endFilePos' => 1550,
          ),
        ),
        'docComment' => '/**
 * Statuts qui « occupent une place » sur la soumission. Un refus ou une
 * annulation ne comptent pas : la soumission est de nouveau à couvrir.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 47,
        'endLine' => 47,
        'startColumn' => 5,
        'endColumn' => 72,
      ),
      'PENDING_STATUSES' => 
      array (
        'declaringClassName' => 'App\\Models\\Review',
        'implementingClassName' => 'App\\Models\\Review',
        'name' => 'PENDING_STATUSES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'invited\', \'accepted\']',
          'attributes' => 
          array (
            'startLine' => 54,
            'endLine' => 54,
            'startTokenPos' => 169,
            'startFilePos' => 1853,
            'endTokenPos' => 174,
            'endFilePos' => 1875,
          ),
        ),
        'docComment' => '/**
 * Statuts « encore dus » : le relecteur a quelque chose à faire (confirmer
 * ou évaluer). ⚠️ C\'est la bonne liste pour un compteur « à traiter » -
 * un `status != completed` compterait aussi les refus et les annulations.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 54,
        'endLine' => 54,
        'startColumn' => 5,
        'endColumn' => 60,
      ),
      'DEFAULT_DUE_DAYS' => 
      array (
        'declaringClassName' => 'App\\Models\\Review',
        'implementingClassName' => 'App\\Models\\Review',
        'name' => 'DEFAULT_DUE_DAYS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '21',
          'attributes' => 
          array (
            'startLine' => 57,
            'endLine' => 57,
            'startTokenPos' => 187,
            'startFilePos' => 2009,
            'endTokenPos' => 187,
            'endFilePos' => 2010,
          ),
        ),
        'docComment' => '/** Durée d\'évaluation par défaut, à défaut de réglage de l\'événement (jours). */',
        'attributes' => 
        array (
        ),
        'startLine' => 57,
        'endLine' => 57,
        'startColumn' => 5,
        'endColumn' => 39,
      ),
    ),
    'immediateProperties' => 
    array (
      'guarded' => 
      array (
        'declaringClassName' => 'App\\Models\\Review',
        'implementingClassName' => 'App\\Models\\Review',
        'name' => 'guarded',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'id\']',
          'attributes' => 
          array (
            'startLine' => 19,
            'endLine' => 19,
            'startTokenPos' => 40,
            'startFilePos' => 515,
            'endTokenPos' => 42,
            'endFilePos' => 520,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 19,
        'endLine' => 19,
        'startColumn' => 5,
        'endColumn' => 32,
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
      'casts' => 
      array (
        'name' => 'casts',
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
        'docComment' => NULL,
        'startLine' => 59,
        'endLine' => 68,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Review',
        'implementingClassName' => 'App\\Models\\Review',
        'currentClassName' => 'App\\Models\\Review',
        'aliasName' => NULL,
      ),
      'progressOf' => 
      array (
        'name' => 'progressOf',
        'parameters' => 
        array (
          'reviews' => 
          array (
            'name' => 'reviews',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'iterable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 78,
            'endLine' => 78,
            'startColumn' => 39,
            'endColumn' => 55,
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
 * Avancement compact d\'un lot de relectures, pour une liste :
 * `[\'done\' => 1, \'total\' => 2, \'late\' => 1, \'lateDays\' => 3]`. Même règle
 * que summarize() - seules les relectures actives comptent.
 *
 * @param  iterable<int, self>  $reviews
 * @return array{done:int, total:int, late:int, lateDays:int}
 */',
        'startLine' => 78,
        'endLine' => 89,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Review',
        'implementingClassName' => 'App\\Models\\Review',
        'currentClassName' => 'App\\Models\\Review',
        'aliasName' => NULL,
      ),
      'summarize' => 
      array (
        'name' => 'summarize',
        'parameters' => 
        array (
          'reviews' => 
          array (
            'name' => 'reviews',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'iterable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 99,
            'endLine' => 99,
            'startColumn' => 38,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Phrase d\'état d\'un lot de relectures, telle que le comité la lit :
 * « 2 relectures demandées - 1 rendue, 1 en retard de 3 j ». Les relectures
 * annulées ne comptent pas ; les refus sont mentionnés à part (ils ont bien
 * eu lieu, mais la place est libre).
 *
 * @param  iterable<int, self>  $reviews
 */',
        'startLine' => 99,
        'endLine' => 137,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Review',
        'implementingClassName' => 'App\\Models\\Review',
        'currentClassName' => 'App\\Models\\Review',
        'aliasName' => NULL,
      ),
      'statusLabel' => 
      array (
        'name' => 'statusLabel',
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
        'docComment' => NULL,
        'startLine' => 139,
        'endLine' => 142,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Review',
        'implementingClassName' => 'App\\Models\\Review',
        'currentClassName' => 'App\\Models\\Review',
        'aliasName' => NULL,
      ),
      'statusPill' => 
      array (
        'name' => 'statusPill',
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
        'docComment' => '/**
 * Teinte de la pastille d\'ÉTAT. ⚠️ Le retard n\'est pas un état : il a son
 * propre marqueur rouge à côté (échéance / « N j de retard »). Teinter la
 * pastille en rouge en plus rendait les deux signaux redondants et faisait
 * lire « Invitée » en rouge, ce qui n\'a pas de sens.
 */',
        'startLine' => 151,
        'endLine' => 159,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Review',
        'implementingClassName' => 'App\\Models\\Review',
        'currentClassName' => 'App\\Models\\Review',
        'aliasName' => NULL,
      ),
      'isPending' => 
      array (
        'name' => 'isPending',
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
        'docComment' => '/** Relecture attendue : confiée ou acceptée, pas encore rendue. */',
        'startLine' => 162,
        'endLine' => 165,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Review',
        'implementingClassName' => 'App\\Models\\Review',
        'currentClassName' => 'App\\Models\\Review',
        'aliasName' => NULL,
      ),
      'isActive' => 
      array (
        'name' => 'isActive',
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
        'docComment' => '/** Occupe une place sur la soumission (compte dans la couverture). */',
        'startLine' => 168,
        'endLine' => 171,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Review',
        'implementingClassName' => 'App\\Models\\Review',
        'currentClassName' => 'App\\Models\\Review',
        'aliasName' => NULL,
      ),
      'isLate' => 
      array (
        'name' => 'isLate',
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
        'docComment' => '/**
 * En retard : attendue ET échéance dépassée. ⚠️ Ne pas confondre avec
 * « non rendue » - sans échéance, une relecture n\'est jamais en retard.
 */',
        'startLine' => 177,
        'endLine' => 180,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Review',
        'implementingClassName' => 'App\\Models\\Review',
        'currentClassName' => 'App\\Models\\Review',
        'aliasName' => NULL,
      ),
      'daysLate' => 
      array (
        'name' => 'daysLate',
        'parameters' => 
        array (
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
        'docComment' => '/** Nombre de jours de retard (0 si dans les temps). */',
        'startLine' => 183,
        'endLine' => 186,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Review',
        'implementingClassName' => 'App\\Models\\Review',
        'currentClassName' => 'App\\Models\\Review',
        'aliasName' => NULL,
      ),
      'recommendationLabel' => 
      array (
        'name' => 'recommendationLabel',
        'parameters' => 
        array (
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
        'docComment' => NULL,
        'startLine' => 188,
        'endLine' => 191,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Review',
        'implementingClassName' => 'App\\Models\\Review',
        'currentClassName' => 'App\\Models\\Review',
        'aliasName' => NULL,
      ),
      'criterionScores' => 
      array (
        'name' => 'criterionScores',
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
 * Notes par critère (grille structurée).
 *
 * @return array<string, int>
 */',
        'startLine' => 198,
        'endLine' => 201,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Review',
        'implementingClassName' => 'App\\Models\\Review',
        'currentClassName' => 'App\\Models\\Review',
        'aliasName' => NULL,
      ),
      'isCompleted' => 
      array (
        'name' => 'isCompleted',
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
        'startLine' => 203,
        'endLine' => 206,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Review',
        'implementingClassName' => 'App\\Models\\Review',
        'currentClassName' => 'App\\Models\\Review',
        'aliasName' => NULL,
      ),
      'submission' => 
      array (
        'name' => 'submission',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** @return BelongsTo<Submission, $this> */',
        'startLine' => 209,
        'endLine' => 212,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Review',
        'implementingClassName' => 'App\\Models\\Review',
        'currentClassName' => 'App\\Models\\Review',
        'aliasName' => NULL,
      ),
      'reviewer' => 
      array (
        'name' => 'reviewer',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** @return BelongsTo<Participant, $this> */',
        'startLine' => 215,
        'endLine' => 218,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Review',
        'implementingClassName' => 'App\\Models\\Review',
        'currentClassName' => 'App\\Models\\Review',
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