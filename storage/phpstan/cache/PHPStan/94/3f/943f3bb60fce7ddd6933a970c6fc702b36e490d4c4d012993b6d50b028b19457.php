<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Listeners\NotifyAccountOfSubmissionStatus.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Listeners\NotifyAccountOfSubmissionStatus
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-808785fa74b509c0d4f31bdb78cd968022f952d20618874863f8c7ef00d859ca',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Listeners\\NotifyAccountOfSubmissionStatus',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Listeners/NotifyAccountOfSubmissionStatus.php',
      ),
    ),
    'namespace' => 'App\\Listeners',
    'name' => 'App\\Listeners\\NotifyAccountOfSubmissionStatus',
    'shortName' => 'NotifyAccountOfSubmissionStatus',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Crée une notification in-app pour le compte de l\'auteur correspondant quand
 * une décision est prise sur sa communication (en relecture, correction,
 * acceptée, refusée). Complète l\'e-mail (NotifyAuthorOfStatus) sans le remplacer.
 * Auto-découvert par Laravel - ne pas enregistrer manuellement.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 15,
    'endLine' => 37,
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
      'handle' => 
      array (
        'name' => 'handle',
        'parameters' => 
        array (
          'event' => 
          array (
            'name' => 'event',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Events\\SubmissionStatusChanged',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 17,
            'endLine' => 17,
            'startColumn' => 28,
            'endColumn' => 57,
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
        'startLine' => 17,
        'endLine' => 36,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Listeners',
        'declaringClassName' => 'App\\Listeners\\NotifyAccountOfSubmissionStatus',
        'implementingClassName' => 'App\\Listeners\\NotifyAccountOfSubmissionStatus',
        'currentClassName' => 'App\\Listeners\\NotifyAccountOfSubmissionStatus',
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