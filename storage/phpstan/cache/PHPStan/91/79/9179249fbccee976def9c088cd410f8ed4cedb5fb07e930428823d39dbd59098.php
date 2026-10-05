<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Actions\ValidateCommunicationPassage.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Actions\ValidateCommunicationPassage
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-c79229d939469909abb8f66c1c7e65ba1622ba4c917585b968ec146be24723f0',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Actions\\ValidateCommunicationPassage',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Actions/ValidateCommunicationPassage.php',
      ),
    ),
    'namespace' => 'App\\Actions',
    'name' => 'App\\Actions\\ValidateCommunicationPassage',
    'shortName' => 'ValidateCommunicationPassage',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * **Constat du passage d\'une communication**, posé par qui tient la séance.
 *
 * C\'est le geste qui ouvre les attestations de ses auteurs : en délivrance en
 * ligne, chacun reçoit la sienne dans la foulée. Le constat se fait au moment
 * où la communication vient d\'avoir lieu - pas à la fin du colloque, quand plus
 * personne ne se souvient de qui est monté à la tribune.
 *
 * ⚠️ **Irréversible côté séance, et c\'est voulu.** Une attestation partie ne se
 * reprend pas : elle est signée, elle porte un QR de vérification, et son
 * destinataire l\'a peut-être déjà transmise. Une erreur se répare au
 * back-office, en **révoquant** l\'attestation - un geste tracé, par quelqu\'un
 * dont c\'est la responsabilité.
 *
 * ⚠️ **Idempotent.** Deux modérateurs peuvent valider le même passage à une
 * seconde d\'intervalle (la salle et la page de modération sont deux portes sur
 * le même geste) : le second constat ne réémet rien.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 28,
    'endLine' => 63,
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
      'issue' => 
      array (
        'declaringClassName' => 'App\\Actions\\ValidateCommunicationPassage',
        'implementingClassName' => 'App\\Actions\\ValidateCommunicationPassage',
        'name' => 'issue',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'App\\Actions\\IssueAttestation',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 30,
        'endLine' => 30,
        'startColumn' => 33,
        'endColumn' => 63,
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
          'issue' => 
          array (
            'name' => 'issue',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Actions\\IssueAttestation',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 30,
            'endLine' => 30,
            'startColumn' => 33,
            'endColumn' => 63,
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
        'startLine' => 30,
        'endLine' => 30,
        'startColumn' => 5,
        'endColumn' => 67,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Actions',
        'declaringClassName' => 'App\\Actions\\ValidateCommunicationPassage',
        'implementingClassName' => 'App\\Actions\\ValidateCommunicationPassage',
        'currentClassName' => 'App\\Actions\\ValidateCommunicationPassage',
        'aliasName' => NULL,
      ),
      'handle' => 
      array (
        'name' => 'handle',
        'parameters' => 
        array (
          'slot' => 
          array (
            'name' => 'slot',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Slot',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 35,
            'endLine' => 35,
            'startColumn' => 28,
            'endColumn' => 37,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'par' => 
          array (
            'name' => 'par',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
              'data' => 
              array (
                'types' => 
                array (
                  0 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'App\\Models\\User',
                      'isIdentifier' => false,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'null',
                      'isIdentifier' => true,
                    ),
                  ),
                ),
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 35,
            'endLine' => 35,
            'startColumn' => 40,
            'endColumn' => 49,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return array{already: bool, issued: int, online: bool}
 */',
        'startLine' => 35,
        'endLine' => 62,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Actions',
        'declaringClassName' => 'App\\Actions\\ValidateCommunicationPassage',
        'implementingClassName' => 'App\\Actions\\ValidateCommunicationPassage',
        'currentClassName' => 'App\\Actions\\ValidateCommunicationPassage',
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