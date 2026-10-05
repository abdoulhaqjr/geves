<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Listeners\InviteCoauthorsOnAcceptance.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Listeners\InviteCoauthorsOnAcceptance
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-df0723b8a0e6fd52010cf8cf85169c79e4821065091d7cc1b716e8b8dc35b198',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Listeners\\InviteCoauthorsOnAcceptance',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Listeners/InviteCoauthorsOnAcceptance.php',
      ),
    ),
    'namespace' => 'App\\Listeners',
    'name' => 'App\\Listeners\\InviteCoauthorsOnAcceptance',
    'shortName' => 'InviteCoauthorsOnAcceptance',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * À l\'acceptation d\'une communication, invite ses co-auteurs à régler leurs
 * frais (au tarif de leur grade) via le parcours co-auteur.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 12,
    'endLine' => 24,
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
      'notify' => 
      array (
        'declaringClassName' => 'App\\Listeners\\InviteCoauthorsOnAcceptance',
        'implementingClassName' => 'App\\Listeners\\InviteCoauthorsOnAcceptance',
        'name' => 'notify',
        'modifiers' => 2,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'App\\Actions\\NotifyCoauthorsForPayment',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 14,
        'endLine' => 14,
        'startColumn' => 33,
        'endColumn' => 75,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
    ),
    'immediateMethods' => 
    array (
      '__construct' => 
      array (
        'name' => '__construct',
        'parameters' => 
        array (
          'notify' => 
          array (
            'name' => 'notify',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Actions\\NotifyCoauthorsForPayment',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 14,
            'endLine' => 14,
            'startColumn' => 33,
            'endColumn' => 75,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 14,
        'endLine' => 14,
        'startColumn' => 5,
        'endColumn' => 79,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Listeners',
        'declaringClassName' => 'App\\Listeners\\InviteCoauthorsOnAcceptance',
        'implementingClassName' => 'App\\Listeners\\InviteCoauthorsOnAcceptance',
        'currentClassName' => 'App\\Listeners\\InviteCoauthorsOnAcceptance',
        'aliasName' => NULL,
      ),
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
            'startLine' => 16,
            'endLine' => 16,
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
        'startLine' => 16,
        'endLine' => 23,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Listeners',
        'declaringClassName' => 'App\\Listeners\\InviteCoauthorsOnAcceptance',
        'implementingClassName' => 'App\\Listeners\\InviteCoauthorsOnAcceptance',
        'currentClassName' => 'App\\Listeners\\InviteCoauthorsOnAcceptance',
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