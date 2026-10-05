<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Models\SessionAttendance.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Models\SessionAttendance
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-bf4897e4d961bdef80d61723ef4c45faea524de24808f4d4c22bac1a90fe54cd',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Models\\SessionAttendance',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Models/SessionAttendance.php',
      ),
    ),
    'namespace' => 'App\\Models',
    'name' => 'App\\Models\\SessionAttendance',
    'shortName' => 'SessionAttendance',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Présence d\'un participant à une séance, pointée par le modérateur ou le
 * rapporteur pendant la séance.
 *
 * ⚠️ **L\'absence de ligne n\'est pas une absence** : c\'est « non pointé ». Un
 * absent constaté porte `status = absent`. Confondre les deux ferait passer pour
 * absents tous ceux qu\'on n\'a pas eu le temps d\'appeler.
 *
 * @property Carbon|null $marked_at
 * @property EventSession|null $session
 * @property Participant|null $participant
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 21,
    'endLine' => 75,
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
      'STATUSES' => 
      array (
        'declaringClassName' => 'App\\Models\\SessionAttendance',
        'implementingClassName' => 'App\\Models\\SessionAttendance',
        'name' => 'STATUSES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'present\' => \'Présent\', \'absent\' => \'Absent\']',
          'attributes' => 
          array (
            'startLine' => 38,
            'endLine' => 41,
            'startTokenPos' => 55,
            'startFilePos' => 1387,
            'endTokenPos' => 71,
            'endFilePos' => 1456,
          ),
        ),
        'docComment' => '/**
 * Verdicts possibles, et leurs libellés.
 *
 * ⚠️ **Deux verdicts, pas trois.** « Excusé » a existé et a été retiré : il
 * ne changeait rien en aval - seul `present` compte dans le PV et dans
 * l\'attestation - mais il ajoutait un troisième bouton à trancher pendant
 * qu\'on appelle des gens à la voix. Un motif d\'absence se dit dans le compte
 * rendu, pas dans une colonne qui ne sert à aucun calcul.
 *
 * ⚠️ Source unique : les deux contrôleurs valident sur `array_keys()` de
 * cette liste. Y remettre une valeur l\'ouvre PARTOUT, y compris dans la
 * salle de visio.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 38,
        'endLine' => 41,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
      'SOURCE_MANUAL' => 
      array (
        'declaringClassName' => 'App\\Models\\SessionAttendance',
        'implementingClassName' => 'App\\Models\\SessionAttendance',
        'name' => 'SOURCE_MANUAL',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'manual\'',
          'attributes' => 
          array (
            'startLine' => 44,
            'endLine' => 44,
            'startTokenPos' => 84,
            'startFilePos' => 1571,
            'endTokenPos' => 84,
            'endFilePos' => 1578,
          ),
        ),
        'docComment' => '/** Pointage posé à la main, ou déduit d\'une entrée dans la visio. */',
        'attributes' => 
        array (
        ),
        'startLine' => 44,
        'endLine' => 44,
        'startColumn' => 5,
        'endColumn' => 42,
      ),
      'SOURCE_MEETING' => 
      array (
        'declaringClassName' => 'App\\Models\\SessionAttendance',
        'implementingClassName' => 'App\\Models\\SessionAttendance',
        'name' => 'SOURCE_MEETING',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'meeting\'',
          'attributes' => 
          array (
            'startLine' => 46,
            'endLine' => 46,
            'startTokenPos' => 95,
            'startFilePos' => 1616,
            'endTokenPos' => 95,
            'endFilePos' => 1624,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 46,
        'endLine' => 46,
        'startColumn' => 5,
        'endColumn' => 44,
      ),
    ),
    'immediateProperties' => 
    array (
      'guarded' => 
      array (
        'declaringClassName' => 'App\\Models\\SessionAttendance',
        'implementingClassName' => 'App\\Models\\SessionAttendance',
        'name' => 'guarded',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'id\']',
          'attributes' => 
          array (
            'startLine' => 23,
            'endLine' => 23,
            'startTokenPos' => 40,
            'startFilePos' => 694,
            'endTokenPos' => 42,
            'endFilePos' => 699,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 23,
        'endLine' => 23,
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
      'isAutomatic' => 
      array (
        'name' => 'isAutomatic',
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
        'docComment' => '/** Posé automatiquement par l\'entrée en visio (et non par une personne). */',
        'startLine' => 49,
        'endLine' => 52,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\SessionAttendance',
        'implementingClassName' => 'App\\Models\\SessionAttendance',
        'currentClassName' => 'App\\Models\\SessionAttendance',
        'aliasName' => NULL,
      ),
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
        'startLine' => 54,
        'endLine' => 57,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\SessionAttendance',
        'implementingClassName' => 'App\\Models\\SessionAttendance',
        'currentClassName' => 'App\\Models\\SessionAttendance',
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
        'startLine' => 59,
        'endLine' => 62,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\SessionAttendance',
        'implementingClassName' => 'App\\Models\\SessionAttendance',
        'currentClassName' => 'App\\Models\\SessionAttendance',
        'aliasName' => NULL,
      ),
      'session' => 
      array (
        'name' => 'session',
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
        'docComment' => '/** @return BelongsTo<EventSession, $this> */',
        'startLine' => 65,
        'endLine' => 68,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\SessionAttendance',
        'implementingClassName' => 'App\\Models\\SessionAttendance',
        'currentClassName' => 'App\\Models\\SessionAttendance',
        'aliasName' => NULL,
      ),
      'participant' => 
      array (
        'name' => 'participant',
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
        'startLine' => 71,
        'endLine' => 74,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\SessionAttendance',
        'implementingClassName' => 'App\\Models\\SessionAttendance',
        'currentClassName' => 'App\\Models\\SessionAttendance',
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