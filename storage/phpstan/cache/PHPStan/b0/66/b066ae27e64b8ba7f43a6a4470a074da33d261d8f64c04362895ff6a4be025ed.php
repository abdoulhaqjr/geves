<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Models\Attestation.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Models\Attestation
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-f32d235b6cce535627563b9b94cddb0df21d82aab83e32d9d6447bea9a05e96d',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Models\\Attestation',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Models/Attestation.php',
      ),
    ),
    'namespace' => 'App\\Models',
    'name' => 'App\\Models\\Attestation',
    'shortName' => 'Attestation',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Attestation émise (participation, communication, modération, rapporteur) avec
 * UUID de vérification publique.
 *
 * @property array<string, mixed>|null $meta
 * @property Carbon|null $issued_at
 * @property Carbon|null $delivered_at
 * @property string|null $credential
 * @property string|null $key_id
 * @property Carbon|null $signed_at
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
            'code' => '[\\App\\Observers\\AttestationObserver::class]',
            'attributes' => 
            array (
              'startLine' => 22,
              'endLine' => 22,
              'startTokenPos' => 37,
              'startFilePos' => 619,
              'endTokenPos' => 41,
              'endFilePos' => 646,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 22,
    'endLine' => 116,
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
      'TYPES' => 
      array (
        'declaringClassName' => 'App\\Models\\Attestation',
        'implementingClassName' => 'App\\Models\\Attestation',
        'name' => 'TYPES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'soumission\' => \'Soumission\', \'acceptation\' => \'Acceptation\', \'participation\' => \'Participation\', \'communication\' => \'Communication\', \'moderation\' => \'Modération\', \'rapporteur\' => \'Rapporteur\', \'formation\' => \'Formation\', \'formation_animation\' => \'Animation de formation\']',
          'attributes' => 
          array (
            'startLine' => 28,
            'endLine' => 37,
            'startTokenPos' => 76,
            'startFilePos' => 775,
            'endTokenPos' => 134,
            'endFilePos' => 1119,
          ),
        ),
        'docComment' => '/** Types d\'attestation. */',
        'attributes' => 
        array (
        ),
        'startLine' => 28,
        'endLine' => 37,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
    ),
    'immediateProperties' => 
    array (
      'guarded' => 
      array (
        'declaringClassName' => 'App\\Models\\Attestation',
        'implementingClassName' => 'App\\Models\\Attestation',
        'name' => 'guarded',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'id\']',
          'attributes' => 
          array (
            'startLine' => 25,
            'endLine' => 25,
            'startTokenPos' => 61,
            'startFilePos' => 709,
            'endTokenPos' => 63,
            'endFilePos' => 714,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 25,
        'endLine' => 25,
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
      'typeLabel' => 
      array (
        'name' => 'typeLabel',
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
        'declaringClassName' => 'App\\Models\\Attestation',
        'implementingClassName' => 'App\\Models\\Attestation',
        'currentClassName' => 'App\\Models\\Attestation',
        'aliasName' => NULL,
      ),
      'isValid' => 
      array (
        'name' => 'isValid',
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
        'startLine' => 44,
        'endLine' => 47,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Attestation',
        'implementingClassName' => 'App\\Models\\Attestation',
        'currentClassName' => 'App\\Models\\Attestation',
        'aliasName' => NULL,
      ),
      'isDelivered' => 
      array (
        'name' => 'isDelivered',
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
 * Remise en main propre ? (délivrance papier)
 *
 * ⚠️ **Émettre n\'est pas remettre.** Une attestation peut être émise,
 * imprimée, et attendre des mois dans une chemise parce que personne ne
 * sait ce qui reste à distribuer.
 */',
        'startLine' => 56,
        'endLine' => 59,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Attestation',
        'implementingClassName' => 'App\\Models\\Attestation',
        'currentClassName' => 'App\\Models\\Attestation',
        'aliasName' => NULL,
      ),
      'formationTitle' => 
      array (
        'name' => 'formationTitle',
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
 * Titre de la formation que cette attestation porte (vide sinon).
 *
 * ⚠️ `meta` d\'abord : il est **figé à l\'émission**, alors que la formation
 * peut avoir été renommée depuis. Une attestation doit dire ce qu\'elle disait
 * le jour où elle a été délivrée, pas ce que le catalogue dit aujourd\'hui.
 */',
        'startLine' => 68,
        'endLine' => 72,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Attestation',
        'implementingClassName' => 'App\\Models\\Attestation',
        'currentClassName' => 'App\\Models\\Attestation',
        'aliasName' => NULL,
      ),
      'isSigned' => 
      array (
        'name' => 'isSigned',
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
        'docComment' => '/** L\'attestation porte-t-elle une signature cryptographique ? */',
        'startLine' => 75,
        'endLine' => 78,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Attestation',
        'implementingClassName' => 'App\\Models\\Attestation',
        'currentClassName' => 'App\\Models\\Attestation',
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
        'startLine' => 80,
        'endLine' => 89,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Attestation',
        'implementingClassName' => 'App\\Models\\Attestation',
        'currentClassName' => 'App\\Models\\Attestation',
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
        'declaringClassName' => 'App\\Models\\Attestation',
        'implementingClassName' => 'App\\Models\\Attestation',
        'currentClassName' => 'App\\Models\\Attestation',
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
        'startLine' => 98,
        'endLine' => 101,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Attestation',
        'implementingClassName' => 'App\\Models\\Attestation',
        'currentClassName' => 'App\\Models\\Attestation',
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
        'startLine' => 105,
        'endLine' => 108,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Attestation',
        'implementingClassName' => 'App\\Models\\Attestation',
        'currentClassName' => 'App\\Models\\Attestation',
        'aliasName' => NULL,
      ),
      'formation' => 
      array (
        'name' => 'formation',
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
        'docComment' => '/** @return BelongsTo<Formation, $this> */',
        'startLine' => 112,
        'endLine' => 115,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Attestation',
        'implementingClassName' => 'App\\Models\\Attestation',
        'currentClassName' => 'App\\Models\\Attestation',
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