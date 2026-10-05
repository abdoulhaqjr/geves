<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Observers\ParticipantObserver.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Observers\ParticipantObserver
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-153b9b887089be1a67637ed82d558e3adf5ff7dba7c2cc93c49e27f0884549ca',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Observers\\ParticipantObserver',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Observers/ParticipantObserver.php',
      ),
    ),
    'namespace' => 'App\\Observers',
    'name' => 'App\\Observers\\ParticipantObserver',
    'shortName' => 'ParticipantObserver',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Invariant d\'un participant : jeton d\'accès (`access_token`) généré à la
 * création s\'il n\'est pas fourni (lien de suivi par jeton, portail relecteur).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 12,
    'endLine' => 20,
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
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'creating' => 
      array (
        'name' => 'creating',
        'parameters' => 
        array (
          'participant' => 
          array (
            'name' => 'participant',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Participant',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 14,
            'endLine' => 14,
            'startColumn' => 30,
            'endColumn' => 53,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 14,
        'endLine' => 19,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Observers',
        'declaringClassName' => 'App\\Observers\\ParticipantObserver',
        'implementingClassName' => 'App\\Observers\\ParticipantObserver',
        'currentClassName' => 'App\\Observers\\ParticipantObserver',
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