<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Console\Commands\ReconcilePaymentsCommand.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Console\Commands\ReconcilePaymentsCommand
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-850e41127155732fdbbd45d22737182770725c5b5bf658d4899ebca58ac273c6',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Console\\Commands\\ReconcilePaymentsCommand',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Console/Commands/ReconcilePaymentsCommand.php',
      ),
    ),
    'namespace' => 'App\\Console\\Commands',
    'name' => 'App\\Console\\Commands\\ReconcilePaymentsCommand',
    'shortName' => 'ReconcilePaymentsCommand',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Revérifie auprès des passerelles les paiements en ligne restés « en attente »
 * et confirme ceux qui sont réellement réglés (filet de sécurité si un webhook
 * n\'est jamais arrivé). Planifiée toutes les 10 minutes dans routes/console.php ;
 * à la main : `php artisan payments:reconcile [--days=7] [--payment=12]`.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 14,
    'endLine' => 40,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Console\\Command',
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
      'signature' => 
      array (
        'declaringClassName' => 'App\\Console\\Commands\\ReconcilePaymentsCommand',
        'implementingClassName' => 'App\\Console\\Commands\\ReconcilePaymentsCommand',
        'name' => 'signature',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'payments:reconcile
        {--days=7 : Ancienneté maximale (en jours) des paiements réexaminés}
        {--payment= : Ne revérifier qu’un paiement précis (son id)}\'',
          'attributes' => 
          array (
            'startLine' => 16,
            'endLine' => 18,
            'startTokenPos' => 35,
            'startFilePos' => 524,
            'endTokenPos' => 35,
            'endFilePos' => 695,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 16,
        'endLine' => 18,
        'startColumn' => 5,
        'endColumn' => 73,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'description' => 
      array (
        'declaringClassName' => 'App\\Console\\Commands\\ReconcilePaymentsCommand',
        'implementingClassName' => 'App\\Console\\Commands\\ReconcilePaymentsCommand',
        'name' => 'description',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'Revérifie les paiements en ligne en attente auprès des passerelles\'',
          'attributes' => 
          array (
            'startLine' => 20,
            'endLine' => 20,
            'startTokenPos' => 44,
            'startFilePos' => 728,
            'endTokenPos' => 44,
            'endFilePos' => 797,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 20,
        'endLine' => 20,
        'startColumn' => 5,
        'endColumn' => 100,
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
      'handle' => 
      array (
        'name' => 'handle',
        'parameters' => 
        array (
          'action' => 
          array (
            'name' => 'action',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Actions\\ReconcilePayments',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 22,
            'endLine' => 22,
            'startColumn' => 28,
            'endColumn' => 52,
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
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 22,
        'endLine' => 39,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Console\\Commands',
        'declaringClassName' => 'App\\Console\\Commands\\ReconcilePaymentsCommand',
        'implementingClassName' => 'App\\Console\\Commands\\ReconcilePaymentsCommand',
        'currentClassName' => 'App\\Console\\Commands\\ReconcilePaymentsCommand',
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