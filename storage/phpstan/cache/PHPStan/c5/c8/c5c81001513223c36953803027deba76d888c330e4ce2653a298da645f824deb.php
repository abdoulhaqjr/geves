<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Actions\RevokeAttestationsOnRefund.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Actions\RevokeAttestationsOnRefund
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-41a13c10cc057b60f05b1361bc34f6a9604f00b9b972411adc20a7eb4c8c15b0',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Actions\\RevokeAttestationsOnRefund',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Actions/RevokeAttestationsOnRefund.php',
      ),
    ),
    'namespace' => 'App\\Actions',
    'name' => 'App\\Actions\\RevokeAttestationsOnRefund',
    'shortName' => 'RevokeAttestationsOnRefund',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Remboursement → retrait des attestations que ce paiement justifiait.
 *
 * Une attestation atteste d\'un droit acquis par un paiement. Si ce paiement est
 * remboursé et qu\'aucun autre ne le remplace, l\'attestation n\'a plus de
 * fondement : elle est **révoquée** (jamais supprimée - une attestation est
 * vérifiable publiquement, elle doit rester traçable, la page de vérification
 * la signalant alors comme révoquée).
 *
 * ⚠️ On ne révoque que s\'il ne reste **aucun paiement réglé** couvrant le même
 * droit : un participant peut avoir réglé deux fois (tentative + encaissement
 * manuel), ou s\'être inscrit à plusieurs formations.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 21,
    'endLine' => 90,
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
          'payment' => 
          array (
            'name' => 'payment',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Payment',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 24,
            'endLine' => 24,
            'startColumn' => 28,
            'endColumn' => 43,
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
        'docComment' => '/** @return int Nombre d\'attestations révoquées. */',
        'startLine' => 24,
        'endLine' => 34,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Actions',
        'declaringClassName' => 'App\\Actions\\RevokeAttestationsOnRefund',
        'implementingClassName' => 'App\\Actions\\RevokeAttestationsOnRefund',
        'currentClassName' => 'App\\Actions\\RevokeAttestationsOnRefund',
        'aliasName' => NULL,
      ),
      'forFormation' => 
      array (
        'name' => 'forFormation',
        'parameters' => 
        array (
          'payment' => 
          array (
            'name' => 'payment',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Payment',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 43,
            'endLine' => 43,
            'startColumn' => 35,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'participantId' => 
          array (
            'name' => 'participantId',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 43,
            'endLine' => 43,
            'startColumn' => 53,
            'endColumn' => 70,
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
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Frais de formation : l\'attestation de formation tombe si plus aucun
 * paiement réglé ne couvre CETTE formation pour cette personne.
 *
 * L\'attestation d\'animation (`formation_animation`) n\'est pas concernée :
 * un formateur ne paie pas, son attestation ne dépend d\'aucun paiement.
 */',
        'startLine' => 43,
        'endLine' => 66,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Actions',
        'declaringClassName' => 'App\\Actions\\RevokeAttestationsOnRefund',
        'implementingClassName' => 'App\\Actions\\RevokeAttestationsOnRefund',
        'currentClassName' => 'App\\Actions\\RevokeAttestationsOnRefund',
        'aliasName' => NULL,
      ),
      'forParticipation' => 
      array (
        'name' => 'forParticipation',
        'parameters' => 
        array (
          'payment' => 
          array (
            'name' => 'payment',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Payment',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 73,
            'endLine' => 73,
            'startColumn' => 39,
            'endColumn' => 54,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'participantId' => 
          array (
            'name' => 'participantId',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 73,
            'endLine' => 73,
            'startColumn' => 57,
            'endColumn' => 74,
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
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Frais de participation (co-auteur inclus : il règle pour participer) :
 * l\'attestation de participation ET les attestations de communication
 * tombent ensemble, faute d\'inscription réglée.
 */',
        'startLine' => 73,
        'endLine' => 89,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Actions',
        'declaringClassName' => 'App\\Actions\\RevokeAttestationsOnRefund',
        'implementingClassName' => 'App\\Actions\\RevokeAttestationsOnRefund',
        'currentClassName' => 'App\\Actions\\RevokeAttestationsOnRefund',
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