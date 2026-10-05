<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Models\MeetingMessage.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Models\MeetingMessage
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-4075acfbe9b3214d8cbf44db051f5c854c24128487c7597283955c2e6425b767',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Models\\MeetingMessage',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Models/MeetingMessage.php',
      ),
    ),
    'namespace' => 'App\\Models',
    'name' => 'App\\Models\\MeetingMessage',
    'shortName' => 'MeetingMessage',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Un message du fil d\'une salle de visio.
 *
 * ⚠️ Le fil est archivé pour être **rejoué à l\'arrivée** : sans lui, qui rejoint
 * en cours de séance ne voit rien de ce qui s\'est dit. Le canal de données de
 * LiveKit ne sert plus qu\'à l\'instantané (les personnes déjà connectées).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 17,
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
      'EMOJIS' => 
      array (
        'declaringClassName' => 'App\\Models\\MeetingMessage',
        'implementingClassName' => 'App\\Models\\MeetingMessage',
        'name' => 'EMOJIS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'👍\', \'❤️\', \'😄\', \'👏\', \'❓\']',
          'attributes' => 
          array (
            'startLine' => 25,
            'endLine' => 25,
            'startTokenPos' => 49,
            'startFilePos' => 848,
            'endTokenPos' => 63,
            'endFilePos' => 888,
          ),
        ),
        'docComment' => '/**
 * Réactions proposées, et liste FERMÉE de ce qui peut être enregistré.
 *
 * ⚠️ Jamais l\'emoji envoyé par le client tel quel : une colonne libre
 * laisserait écrire n\'importe quoi dans le fil de la salle.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 25,
        'endLine' => 25,
        'startColumn' => 5,
        'endColumn' => 68,
      ),
    ),
    'immediateProperties' => 
    array (
      'fillable' => 
      array (
        'declaringClassName' => 'App\\Models\\MeetingMessage',
        'implementingClassName' => 'App\\Models\\MeetingMessage',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'uid\', \'user_id\', \'author_name\', \'body\', \'reply_uid\', \'reply_author\', \'reply_excerpt\']',
          'attributes' => 
          array (
            'startLine' => 27,
            'endLine' => 30,
            'startTokenPos' => 72,
            'startFilePos' => 918,
            'endTokenPos' => 95,
            'endFilePos' => 1027,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 27,
        'endLine' => 30,
        'startColumn' => 5,
        'endColumn' => 6,
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
      'meetable' => 
      array (
        'name' => 'meetable',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Database\\Eloquent\\Relations\\MorphTo',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** @return MorphTo<Model, $this> */',
        'startLine' => 33,
        'endLine' => 36,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\MeetingMessage',
        'implementingClassName' => 'App\\Models\\MeetingMessage',
        'currentClassName' => 'App\\Models\\MeetingMessage',
        'aliasName' => NULL,
      ),
      'author' => 
      array (
        'name' => 'author',
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
        'docComment' => '/** @return BelongsTo<User, $this> */',
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
        'declaringClassName' => 'App\\Models\\MeetingMessage',
        'implementingClassName' => 'App\\Models\\MeetingMessage',
        'currentClassName' => 'App\\Models\\MeetingMessage',
        'aliasName' => NULL,
      ),
      'reactions' => 
      array (
        'name' => 'reactions',
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
        'docComment' => '/** @return HasMany<MeetingMessageReaction, $this> */',
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
        'declaringClassName' => 'App\\Models\\MeetingMessage',
        'implementingClassName' => 'App\\Models\\MeetingMessage',
        'currentClassName' => 'App\\Models\\MeetingMessage',
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