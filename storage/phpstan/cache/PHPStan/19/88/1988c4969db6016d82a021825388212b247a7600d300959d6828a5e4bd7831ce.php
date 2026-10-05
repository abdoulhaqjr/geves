<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Models\ProjectTask.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Models\ProjectTask
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-cdd30375984c0928616a3426567fe1a3487421d89584cf0d0345b2c1a27bec1c',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Models\\ProjectTask',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Models/ProjectTask.php',
      ),
    ),
    'namespace' => 'App\\Models',
    'name' => 'App\\Models\\ProjectTask',
    'shortName' => 'ProjectTask',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Tâche du projet d\'un événement (colonne kanban : à faire / en cours / en
 * revue / terminé). Reliée à l\'événement et, éventuellement, à un responsable
 * (membre de l\'équipe) et à une phase du cycle de vie du colloque.
 *
 * @property int $id
 * @property int $event_id
 * @property string $title
 * @property string|null $description
 * @property array<int, array{text: string, done: bool}>|null $checklist
 * @property string $status
 * @property string $priority
 * @property string|null $phase
 * @property Carbon|null $start_date
 * @property string|null $link_key
 * @property int|null $assignee_id
 * @property int|null $created_by
 * @property Carbon|null $due_date
 * @property int $position
 * @property Carbon|null $completed_at
 * @property Event|null $event
 * @property User|null $assignee
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 34,
    'endLine' => 118,
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
      'STATUSES' => 
      array (
        'declaringClassName' => 'App\\Models\\ProjectTask',
        'implementingClassName' => 'App\\Models\\ProjectTask',
        'name' => 'STATUSES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'todo\' => \'À faire\', \'doing\' => \'En cours\', \'review\' => \'En revue\', \'done\' => \'Terminé\']',
          'attributes' => 
          array (
            'startLine' => 41,
            'endLine' => 46,
            'startTokenPos' => 70,
            'startFilePos' => 1268,
            'endTokenPos' => 100,
            'endFilePos' => 1397,
          ),
        ),
        'docComment' => '/** Colonnes du tableau kanban (ordre d\'affichage). */',
        'attributes' => 
        array (
        ),
        'startLine' => 41,
        'endLine' => 46,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
      'PRIORITIES' => 
      array (
        'declaringClassName' => 'App\\Models\\ProjectTask',
        'implementingClassName' => 'App\\Models\\ProjectTask',
        'name' => 'PRIORITIES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'low\' => \'Basse\', \'normal\' => \'Normale\', \'high\' => \'Haute\', \'urgent\' => \'Urgente\']',
          'attributes' => 
          array (
            'startLine' => 49,
            'endLine' => 54,
            'startTokenPos' => 113,
            'startFilePos' => 1494,
            'endTokenPos' => 143,
            'endFilePos' => 1615,
          ),
        ),
        'docComment' => '/** Niveaux de priorité (du plus faible au plus fort). */',
        'attributes' => 
        array (
        ),
        'startLine' => 49,
        'endLine' => 54,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
    ),
    'immediateProperties' => 
    array (
      'guarded' => 
      array (
        'declaringClassName' => 'App\\Models\\ProjectTask',
        'implementingClassName' => 'App\\Models\\ProjectTask',
        'name' => 'guarded',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'id\']',
          'attributes' => 
          array (
            'startLine' => 38,
            'endLine' => 38,
            'startTokenPos' => 55,
            'startFilePos' => 1172,
            'endTokenPos' => 57,
            'endFilePos' => 1177,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 38,
        'endLine' => 38,
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
        'startLine' => 56,
        'endLine' => 65,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\ProjectTask',
        'implementingClassName' => 'App\\Models\\ProjectTask',
        'currentClassName' => 'App\\Models\\ProjectTask',
        'aliasName' => NULL,
      ),
      'checklistProgress' => 
      array (
        'name' => 'checklistProgress',
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
 * Avancement de la checklist : [éléments cochés, total].
 *
 * @return array{0: int, 1: int}
 */',
        'startLine' => 72,
        'endLine' => 78,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\ProjectTask',
        'implementingClassName' => 'App\\Models\\ProjectTask',
        'currentClassName' => 'App\\Models\\ProjectTask',
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
        'startLine' => 80,
        'endLine' => 83,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\ProjectTask',
        'implementingClassName' => 'App\\Models\\ProjectTask',
        'currentClassName' => 'App\\Models\\ProjectTask',
        'aliasName' => NULL,
      ),
      'priorityLabel' => 
      array (
        'name' => 'priorityLabel',
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
        'startLine' => 85,
        'endLine' => 88,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\ProjectTask',
        'implementingClassName' => 'App\\Models\\ProjectTask',
        'currentClassName' => 'App\\Models\\ProjectTask',
        'aliasName' => NULL,
      ),
      'isDone' => 
      array (
        'name' => 'isDone',
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
        'startLine' => 90,
        'endLine' => 93,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\ProjectTask',
        'implementingClassName' => 'App\\Models\\ProjectTask',
        'currentClassName' => 'App\\Models\\ProjectTask',
        'aliasName' => NULL,
      ),
      'isOverdue' => 
      array (
        'name' => 'isOverdue',
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
        'docComment' => '/** En retard : échéance dépassée et tâche non terminée. */',
        'startLine' => 96,
        'endLine' => 99,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\ProjectTask',
        'implementingClassName' => 'App\\Models\\ProjectTask',
        'currentClassName' => 'App\\Models\\ProjectTask',
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
        'declaringClassName' => 'App\\Models\\ProjectTask',
        'implementingClassName' => 'App\\Models\\ProjectTask',
        'currentClassName' => 'App\\Models\\ProjectTask',
        'aliasName' => NULL,
      ),
      'assignee' => 
      array (
        'name' => 'assignee',
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
        'declaringClassName' => 'App\\Models\\ProjectTask',
        'implementingClassName' => 'App\\Models\\ProjectTask',
        'currentClassName' => 'App\\Models\\ProjectTask',
        'aliasName' => NULL,
      ),
      'comments' => 
      array (
        'name' => 'comments',
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
        'docComment' => '/** @return HasMany<ProjectTaskComment, $this> */',
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
        'declaringClassName' => 'App\\Models\\ProjectTask',
        'implementingClassName' => 'App\\Models\\ProjectTask',
        'currentClassName' => 'App\\Models\\ProjectTask',
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