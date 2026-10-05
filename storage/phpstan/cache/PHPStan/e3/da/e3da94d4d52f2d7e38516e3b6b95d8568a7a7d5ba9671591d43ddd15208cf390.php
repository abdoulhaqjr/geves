<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Models\Participant.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Models\Participant
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-c33b2c401f77ea37eef8828c434c937e35bb97be68839ac974d84aa92141e9ae',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Models\\Participant',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Models/Participant.php',
      ),
    ),
    'namespace' => 'App\\Models',
    'name' => 'App\\Models\\Participant',
    'shortName' => 'Participant',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Participant : présence d\'une personne sur un événement (auteur, auditeur,
 * relecteur, membre de comité, co-auteur…). Reliée au compte global quand
 * connue.
 *
 * @property int|null $account_id
 * @property int|null $user_id
 * @property Carbon|null $checked_in_at
 * @property array<string, mixed>|null $meta
 * @property Event|null $event
 * @property User|null $user
 */',
    'attributes' => 
    array (
      0 => 
      array (
        'name' => 'Illuminate\\Database\\Eloquent\\Attributes\\ObservedBy',
        'isRepeated' => false,
        'arguments' => 
        array (
          0 => 
          array (
            'code' => '[\\App\\Observers\\ParticipantObserver::class]',
            'attributes' => 
            array (
              'startLine' => 25,
              'endLine' => 25,
              'startTokenPos' => 47,
              'startFilePos' => 762,
              'endTokenPos' => 51,
              'endFilePos' => 789,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 25,
    'endLine' => 157,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Database\\Eloquent\\Model',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
      0 => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
    ),
    'immediateConstants' => 
    array (
      'ROLES' => 
      array (
        'declaringClassName' => 'App\\Models\\Participant',
        'implementingClassName' => 'App\\Models\\Participant',
        'name' => 'ROLES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'author\' => \'Communicant\', \'coauthor\' => \'Co-auteur\', \'reviewer\' => \'Relecteur\', \'committee\' => \'Comité scientifique\', \'moderator\' => \'Modérateur\', \'rapporteur\' => \'Rapporteur\', \'attendee\' => \'Auditeur\']',
          'attributes' => 
          array (
            'startLine' => 33,
            'endLine' => 41,
            'startTokenPos' => 91,
            'startFilePos' => 940,
            'endTokenPos' => 142,
            'endFilePos' => 1208,
          ),
        ),
        'docComment' => '/** Libellés des rôles. */',
        'attributes' => 
        array (
        ),
        'startLine' => 33,
        'endLine' => 41,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
    ),
    'immediateProperties' => 
    array (
      'guarded' => 
      array (
        'declaringClassName' => 'App\\Models\\Participant',
        'implementingClassName' => 'App\\Models\\Participant',
        'name' => 'guarded',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'id\']',
          'attributes' => 
          array (
            'startLine' => 30,
            'endLine' => 30,
            'startTokenPos' => 76,
            'startFilePos' => 873,
            'endTokenPos' => 78,
            'endFilePos' => 878,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 30,
        'endLine' => 30,
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
        'startLine' => 43,
        'endLine' => 46,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Participant',
        'implementingClassName' => 'App\\Models\\Participant',
        'currentClassName' => 'App\\Models\\Participant',
        'aliasName' => NULL,
      ),
      'isCheckedIn' => 
      array (
        'name' => 'isCheckedIn',
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
        'docComment' => '/** Le participant a-t-il été pointé (présent à l\'accueil) ? */',
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
        'declaringClassName' => 'App\\Models\\Participant',
        'implementingClassName' => 'App\\Models\\Participant',
        'currentClassName' => 'App\\Models\\Participant',
        'aliasName' => NULL,
      ),
      'wasPresent' => 
      array (
        'name' => 'wasPresent',
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
 * Présence réelle avérée : pointé à l\'accueil, **ou** porté présent à au
 * moins une séance par son modérateur / rapporteur. Sert à conditionner
 * l\'attestation de participation.
 *
 * ⚠️ Rien n\'écrit `checked_in_at` depuis le retrait du scan de badge : la
 * présence en séance est devenue la seule source réelle. Sans elle, l\'option
 * « présence requise » des attestations ne laissait passer personne.
 */',
        'startLine' => 63,
        'endLine' => 66,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Participant',
        'implementingClassName' => 'App\\Models\\Participant',
        'currentClassName' => 'App\\Models\\Participant',
        'aliasName' => NULL,
      ),
      'attendedASession' => 
      array (
        'name' => 'attendedASession',
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
        'docComment' => '/** Porté présent à au moins une séance. */',
        'startLine' => 69,
        'endLine' => 72,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Participant',
        'implementingClassName' => 'App\\Models\\Participant',
        'currentClassName' => 'App\\Models\\Participant',
        'aliasName' => NULL,
      ),
      'roleLabel' => 
      array (
        'name' => 'roleLabel',
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
        'startLine' => 74,
        'endLine' => 77,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Participant',
        'implementingClassName' => 'App\\Models\\Participant',
        'currentClassName' => 'App\\Models\\Participant',
        'aliasName' => NULL,
      ),
      'isInvited' => 
      array (
        'name' => 'isInvited',
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
        'startLine' => 79,
        'endLine' => 82,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Participant',
        'implementingClassName' => 'App\\Models\\Participant',
        'currentClassName' => 'App\\Models\\Participant',
        'aliasName' => NULL,
      ),
      'markInvited' => 
      array (
        'name' => 'markInvited',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 84,
        'endLine' => 89,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Participant',
        'implementingClassName' => 'App\\Models\\Participant',
        'currentClassName' => 'App\\Models\\Participant',
        'aliasName' => NULL,
      ),
      'event' => 
      array (
        'name' => 'event',
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
        'docComment' => '/** @return BelongsTo<Event, $this> */',
        'startLine' => 92,
        'endLine' => 95,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Participant',
        'implementingClassName' => 'App\\Models\\Participant',
        'currentClassName' => 'App\\Models\\Participant',
        'aliasName' => NULL,
      ),
      'user' => 
      array (
        'name' => 'user',
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
        'docComment' => '/**
 * Utilisateur rattaché à cette inscription (identité unifiée - option B).
 *
 * @return BelongsTo<User, $this>
 */',
        'startLine' => 102,
        'endLine' => 105,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Participant',
        'implementingClassName' => 'App\\Models\\Participant',
        'currentClassName' => 'App\\Models\\Participant',
        'aliasName' => NULL,
      ),
      'submissions' => 
      array (
        'name' => 'submissions',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Database\\Eloquent\\Relations\\HasMany',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** @return HasMany<Submission, $this> */',
        'startLine' => 108,
        'endLine' => 111,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Participant',
        'implementingClassName' => 'App\\Models\\Participant',
        'currentClassName' => 'App\\Models\\Participant',
        'aliasName' => NULL,
      ),
      'payments' => 
      array (
        'name' => 'payments',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Database\\Eloquent\\Relations\\HasMany',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** @return HasMany<Payment, $this> */',
        'startLine' => 114,
        'endLine' => 117,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Participant',
        'implementingClassName' => 'App\\Models\\Participant',
        'currentClassName' => 'App\\Models\\Participant',
        'aliasName' => NULL,
      ),
      'sessionAttendances' => 
      array (
        'name' => 'sessionAttendances',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Database\\Eloquent\\Relations\\HasMany',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** @return HasMany<SessionAttendance, $this> */',
        'startLine' => 120,
        'endLine' => 123,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Participant',
        'implementingClassName' => 'App\\Models\\Participant',
        'currentClassName' => 'App\\Models\\Participant',
        'aliasName' => NULL,
      ),
      'participationPayments' => 
      array (
        'name' => 'participationPayments',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Database\\Eloquent\\Relations\\HasMany',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Paiements qui ouvrent les **droits de participation** (badge, attestations,
 * accès) : tout sauf les formations, qui sont réglées à part et rattachées au
 * même participant. C\'est cette relation qu\'il faut interroger pour savoir si
 * quelqu\'un « a payé », jamais `payments()`.
 *
 * @return HasMany<Payment, $this>
 */',
        'startLine' => 133,
        'endLine' => 136,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Participant',
        'implementingClassName' => 'App\\Models\\Participant',
        'currentClassName' => 'App\\Models\\Participant',
        'aliasName' => NULL,
      ),
      'getFullNameAttribute' => 
      array (
        'name' => 'getFullNameAttribute',
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
        'startLine' => 138,
        'endLine' => 141,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Participant',
        'implementingClassName' => 'App\\Models\\Participant',
        'currentClassName' => 'App\\Models\\Participant',
        'aliasName' => NULL,
      ),
      'hasAcceptedSubmission' => 
      array (
        'name' => 'hasAcceptedSubmission',
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
        'docComment' => '/** Au moins une communication acceptée (canonique) sur l\'événement. */',
        'startLine' => 144,
        'endLine' => 150,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Participant',
        'implementingClassName' => 'App\\Models\\Participant',
        'currentClassName' => 'App\\Models\\Participant',
        'aliasName' => NULL,
      ),
      'hasPaid' => 
      array (
        'name' => 'hasPaid',
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
        'docComment' => '/** Le participant a déjà un paiement réglé (garde anti-double-paiement). */',
        'startLine' => 153,
        'endLine' => 156,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Participant',
        'implementingClassName' => 'App\\Models\\Participant',
        'currentClassName' => 'App\\Models\\Participant',
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