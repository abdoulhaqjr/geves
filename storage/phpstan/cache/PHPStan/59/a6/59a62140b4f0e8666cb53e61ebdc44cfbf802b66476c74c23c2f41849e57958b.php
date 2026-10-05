<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Models\MeetingMessageReaction.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Models\MeetingMessageReaction
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-0fa38d68874ff06a1081f5578ecf55440f8a9945ca5cbff56bae54c65d30f5f5',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Models\\MeetingMessageReaction',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Models/MeetingMessageReaction.php',
      ),
    ),
    'namespace' => 'App\\Models',
    'name' => 'App\\Models\\MeetingMessageReaction',
    'shortName' => 'MeetingMessageReaction',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Réaction d\'une personne à un message du fil.
 *
 * `reactor` identifie qui réagit d\'une connexion à l\'autre : le compte quand il
 * y en a un, le membre du staff sinon. ⚠️ Pas l\'identité LiveKit, qui change à
 * chaque connexion - la même personne compterait alors deux fois.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 15,
    'endLine' => 24,
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
    ),
    'immediateProperties' => 
    array (
      'fillable' => 
      array (
        'declaringClassName' => 'App\\Models\\MeetingMessageReaction',
        'implementingClassName' => 'App\\Models\\MeetingMessageReaction',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'meeting_message_id\', \'reactor\', \'emoji\']',
          'attributes' => 
          array (
            'startLine' => 17,
            'endLine' => 17,
            'startTokenPos' => 35,
            'startFilePos' => 494,
            'endTokenPos' => 43,
            'endFilePos' => 535,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 17,
        'endLine' => 17,
        'startColumn' => 5,
        'endColumn' => 69,
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
      'message' => 
      array (
        'name' => 'message',
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
        'docComment' => '/** @return BelongsTo<MeetingMessage, $this> */',
        'startLine' => 20,
        'endLine' => 23,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\MeetingMessageReaction',
        'implementingClassName' => 'App\\Models\\MeetingMessageReaction',
        'currentClassName' => 'App\\Models\\MeetingMessageReaction',
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