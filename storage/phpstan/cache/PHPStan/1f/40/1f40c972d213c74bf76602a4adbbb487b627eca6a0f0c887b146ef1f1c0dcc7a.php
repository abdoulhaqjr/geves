<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Observers\SubmissionObserver.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Observers\SubmissionObserver
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-5dc53f27f9387c6559c2464e00444dbffcd6ff726ece0125e186806d4fb050bf',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Observers\\SubmissionObserver',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Observers/SubmissionObserver.php',
      ),
    ),
    'namespace' => 'App\\Observers',
    'name' => 'App\\Observers\\SubmissionObserver',
    'shortName' => 'SubmissionObserver',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Invariants d\'une communication garantis à la création, quel que soit
 * l\'appelant : référence unique (`ref_code`) et jeton d\'accès (`access_token`).
 * Ne remplace jamais une valeur déjà fournie (tests, imports…).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 14,
    'endLine' => 45,
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
          'submission' => 
          array (
            'name' => 'submission',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Submission',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 16,
            'endLine' => 16,
            'startColumn' => 30,
            'endColumn' => 51,
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
        'startLine' => 16,
        'endLine' => 25,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Observers',
        'declaringClassName' => 'App\\Observers\\SubmissionObserver',
        'implementingClassName' => 'App\\Observers\\SubmissionObserver',
        'currentClassName' => 'App\\Observers\\SubmissionObserver',
        'aliasName' => NULL,
      ),
      'generateRefCode' => 
      array (
        'name' => 'generateRefCode',
        'parameters' => 
        array (
          'submission' => 
          array (
            'name' => 'submission',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Submission',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 28,
            'endLine' => 28,
            'startColumn' => 40,
            'endColumn' => 61,
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
        'docComment' => '/** Référence lisible `AA-NNN` (préfixe = 2 derniers chiffres de l\'année). */',
        'startLine' => 28,
        'endLine' => 44,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Observers',
        'declaringClassName' => 'App\\Observers\\SubmissionObserver',
        'implementingClassName' => 'App\\Observers\\SubmissionObserver',
        'currentClassName' => 'App\\Observers\\SubmissionObserver',
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