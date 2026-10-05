<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Actions\NotifyCoauthorsForPayment.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Actions\NotifyCoauthorsForPayment
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-a28c7b9be7953c94ff54e789b1b131801661b1a76ddbc701759ded3d9079aee4',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Actions\\NotifyCoauthorsForPayment',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Actions/NotifyCoauthorsForPayment.php',
      ),
    ),
    'namespace' => 'App\\Actions',
    'name' => 'App\\Actions\\NotifyCoauthorsForPayment',
    'shortName' => 'NotifyCoauthorsForPayment',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Notifie par e-mail les co-auteurs d\'une communication acceptée pour les
 * inviter à régler leurs frais. Journalise chaque envoi dans `emails`.
 *
 * ⚠️ Le lien mène à la **porte d\'entrée** co-auteur, qui oriente vers l\'espace
 * participant - elle n\'encaisse plus. C\'est le seul chemin de règlement :
 * deux parcours concurrents laissaient **deux participants** pour la même
 * personne, et l\'attestation partait sur celui qui n\'avait pas de compte.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 19,
    'endLine' => 71,
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
            'startLine' => 22,
            'endLine' => 22,
            'startColumn' => 28,
            'endColumn' => 49,
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
        'docComment' => '/** @return int Nombre de co-auteurs notifiés. */',
        'startLine' => 22,
        'endLine' => 70,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Actions',
        'declaringClassName' => 'App\\Actions\\NotifyCoauthorsForPayment',
        'implementingClassName' => 'App\\Actions\\NotifyCoauthorsForPayment',
        'currentClassName' => 'App\\Actions\\NotifyCoauthorsForPayment',
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