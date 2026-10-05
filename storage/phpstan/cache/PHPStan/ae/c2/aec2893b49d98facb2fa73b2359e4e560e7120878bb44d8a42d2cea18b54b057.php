<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Http\Controllers\Account\AuthorLookupController.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Http\Controllers\Account\AuthorLookupController
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-9400f1f3ea7b81efb3d316c014959a75bc7202537bcd524b081cfde41e9ecbd7',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Http\\Controllers\\Account\\AuthorLookupController',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Http/Controllers/Account/AuthorLookupController.php',
      ),
    ),
    'namespace' => 'App\\Http\\Controllers\\Account',
    'name' => 'App\\Http\\Controllers\\Account\\AuthorLookupController',
    'shortName' => 'AuthorLookupController',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Vérification discrète, pendant la saisie d\'un auteur/co-auteur, de l\'existence
 * d\'un compte correspondant - POUR GUIDER le déposant sans révéler l\'identité
 * d\'autrui :
 * - e-mail / téléphone (fournis par le déposant) → on confirme s\'ils
 *   correspondent à un membre (il sera associé & notifié) ou non (il sera invité) ;
 * - nom seul → aucune divulgation d\'existence (respect de la vie privée) ;
 *   on propose seulement une mise en forme du nom.
 *
 * Le rapprochement réel (y compris par nom + affiliation + IA) est fait au dépôt
 * par {@see LinkSubmissionAuthors} ; ici on ne fait qu\'informer.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 23,
    'endLine' => 77,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'App\\Http\\Controllers\\Controller',
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
      'check' => 
      array (
        'name' => 'check',
        'parameters' => 
        array (
          'request' => 
          array (
            'name' => 'request',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Http\\Request',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 25,
            'endLine' => 25,
            'startColumn' => 27,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'matcher' => 
          array (
            'name' => 'matcher',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Support\\AuthorMatcher',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 25,
            'endLine' => 25,
            'startColumn' => 45,
            'endColumn' => 66,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Http\\JsonResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 25,
        'endLine' => 76,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Controllers\\Account',
        'declaringClassName' => 'App\\Http\\Controllers\\Account\\AuthorLookupController',
        'implementingClassName' => 'App\\Http\\Controllers\\Account\\AuthorLookupController',
        'currentClassName' => 'App\\Http\\Controllers\\Account\\AuthorLookupController',
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