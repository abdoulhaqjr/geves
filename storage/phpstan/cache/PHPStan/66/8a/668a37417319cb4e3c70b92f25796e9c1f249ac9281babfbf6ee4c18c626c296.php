<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Models\ReviewerBid.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Models\ReviewerBid
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-8ff5996a59ccd4dfb34c1be173d9ce32075558f9ffe7e35aa777f196271e5926',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Models\\ReviewerBid',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Models/ReviewerBid.php',
      ),
    ),
    'namespace' => 'App\\Models',
    'name' => 'App\\Models\\ReviewerBid',
    'shortName' => 'ReviewerBid',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Candidature d\'un relecteur sur une soumission (« bid ») : préférence
 * d\'évaluation + auto-déclaration de conflit d\'intérêt. Exprimée depuis le
 * portail relecteur (en aveugle) et prise en compte par l\'attribution auto.
 *
 * @property Submission|null $submission
 * @property Participant|null $reviewer
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 16,
    'endLine' => 49,
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
      'INTERESTED' => 
      array (
        'declaringClassName' => 'App\\Models\\ReviewerBid',
        'implementingClassName' => 'App\\Models\\ReviewerBid',
        'name' => 'INTERESTED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'interested\'',
          'attributes' => 
          array (
            'startLine' => 18,
            'endLine' => 18,
            'startTokenPos' => 37,
            'startFilePos' => 515,
            'endTokenPos' => 37,
            'endFilePos' => 526,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 18,
        'endLine' => 18,
        'startColumn' => 5,
        'endColumn' => 43,
      ),
      'NEUTRAL' => 
      array (
        'declaringClassName' => 'App\\Models\\ReviewerBid',
        'implementingClassName' => 'App\\Models\\ReviewerBid',
        'name' => 'NEUTRAL',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'neutral\'',
          'attributes' => 
          array (
            'startLine' => 20,
            'endLine' => 20,
            'startTokenPos' => 48,
            'startFilePos' => 557,
            'endTokenPos' => 48,
            'endFilePos' => 565,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 20,
        'endLine' => 20,
        'startColumn' => 5,
        'endColumn' => 37,
      ),
      'UNAVAILABLE' => 
      array (
        'declaringClassName' => 'App\\Models\\ReviewerBid',
        'implementingClassName' => 'App\\Models\\ReviewerBid',
        'name' => 'UNAVAILABLE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'unavailable\'',
          'attributes' => 
          array (
            'startLine' => 22,
            'endLine' => 22,
            'startTokenPos' => 59,
            'startFilePos' => 600,
            'endTokenPos' => 59,
            'endFilePos' => 612,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 22,
        'endLine' => 22,
        'startColumn' => 5,
        'endColumn' => 45,
      ),
      'INTERESTS' => 
      array (
        'declaringClassName' => 'App\\Models\\ReviewerBid',
        'implementingClassName' => 'App\\Models\\ReviewerBid',
        'name' => 'INTERESTS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[self::INTERESTED => \'Intéressé(e)\', self::NEUTRAL => \'Neutre\', self::UNAVAILABLE => \'Indisponible\']',
          'attributes' => 
          array (
            'startLine' => 25,
            'endLine' => 29,
            'startTokenPos' => 72,
            'startFilePos' => 687,
            'endTokenPos' => 101,
            'endFilePos' => 819,
          ),
        ),
        'docComment' => '/** Libellés (portail relecteur). */',
        'attributes' => 
        array (
        ),
        'startLine' => 25,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
    ),
    'immediateProperties' => 
    array (
      'guarded' => 
      array (
        'declaringClassName' => 'App\\Models\\ReviewerBid',
        'implementingClassName' => 'App\\Models\\ReviewerBid',
        'name' => 'guarded',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'id\']',
          'attributes' => 
          array (
            'startLine' => 31,
            'endLine' => 31,
            'startTokenPos' => 110,
            'startFilePos' => 848,
            'endTokenPos' => 112,
            'endFilePos' => 853,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 31,
        'endLine' => 31,
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
        'startLine' => 33,
        'endLine' => 36,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\ReviewerBid',
        'implementingClassName' => 'App\\Models\\ReviewerBid',
        'currentClassName' => 'App\\Models\\ReviewerBid',
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
        'startLine' => 39,
        'endLine' => 42,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\ReviewerBid',
        'implementingClassName' => 'App\\Models\\ReviewerBid',
        'currentClassName' => 'App\\Models\\ReviewerBid',
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
        'startLine' => 45,
        'endLine' => 48,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\ReviewerBid',
        'implementingClassName' => 'App\\Models\\ReviewerBid',
        'currentClassName' => 'App\\Models\\ReviewerBid',
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