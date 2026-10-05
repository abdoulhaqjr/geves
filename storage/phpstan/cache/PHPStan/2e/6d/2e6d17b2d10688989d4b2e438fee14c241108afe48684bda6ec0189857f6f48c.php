<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Documents\DocumentVars.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Documents\DocumentVars
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-255dc7ef86f11c419a673a7071a4bdbd7b46e767bea47280698a3c4f29957b4a',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Documents\\DocumentVars',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Documents/DocumentVars.php',
      ),
    ),
    'namespace' => 'App\\Documents',
    'name' => 'App\\Documents\\DocumentVars',
    'shortName' => 'DocumentVars',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Jetons disponibles dans un document (badge, attestation).
 *
 * ⚠️ **Deux syntaxes cohabitent** et doivent répondre aux mêmes valeurs :
 *  - `{cle}` - schéma historique des attestations ;
 *  - `[espace.cle]` - schéma de l\'éditeur, celui que proposent la palette du
 *    concepteur et ses modèles livrés.
 *
 * Les contrôleurs ne fournissaient que la forme plate : un modèle dessiné avec
 * la palette (`[recipient.name]`, `[certificate.issued_on]`…) sortait donc avec
 * ses jetons **vides ou affichés tels quels** sur le document délivré, alors que
 * le concepteur, lui, les remplissait. D\'où cette table d\'alias, **source
 * unique**, appliquée partout où une mise en page est rendue.
 *
 * ⚠️ Un jeton ajouté à la palette du concepteur doit l\'être ici aussi, sans quoi
 * il ne vaudra que dans l\'aperçu.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 22,
    'endLine' => 73,
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
      'ALIASES' => 
      array (
        'declaringClassName' => 'App\\Documents\\DocumentVars',
        'implementingClassName' => 'App\\Documents\\DocumentVars',
        'name' => 'ALIASES',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'recipient.name\' => \'name\', \'recipient.role\' => \'role\', \'recipient.institution\' => \'institution\', \'recipient.photo\' => \'recipient.photo\', \'participant.name\' => \'name\', \'participant.role\' => \'role\', \'participant.institution\' => \'institution\', \'edition.name\' => \'event\', \'edition.theme\' => \'theme\', \'edition.dates\' => \'dates\', \'event.name\' => \'event\', \'event.year\' => \'year\', \'org.name\' => \'org\', \'certificate.uuid\' => \'ref\', \'certificate.reference\' => \'ref\', \'certificate.issued_on\' => \'date\', \'communication.title\' => \'communication\', \'communication.type\' => \'communication_ref\', \'signatory.name\' => \'signatory\', \'signatory.role\' => \'signatory_role\', \'badge.ref\' => \'ref\']',
          'attributes' => 
          array (
            'startLine' => 29,
            'endLine' => 51,
            'startTokenPos' => 25,
            'startFilePos' => 1050,
            'endTokenPos' => 174,
            'endFilePos' => 1897,
          ),
        ),
        'docComment' => '/**
 * Alias `[espace.cle]` → clé plate correspondante.
 *
 * @var array<string, string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 29,
        'endLine' => 51,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'expand' => 
      array (
        'name' => 'expand',
        'parameters' => 
        array (
          'vars' => 
          array (
            'name' => 'vars',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 63,
            'endLine' => 63,
            'startColumn' => 35,
            'endColumn' => 45,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Complète les valeurs plates par leurs alias `[espace.cle]`.
 *
 * Une valeur déjà fournie sous sa forme longue est conservée : un appelant
 * qui connaît mieux le contexte (la carte du participant, par exemple) garde
 * la main.
 *
 * @param  array<string, mixed>  $vars
 * @return array<string, mixed>
 */',
        'startLine' => 63,
        'endLine' => 72,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\DocumentVars',
        'implementingClassName' => 'App\\Documents\\DocumentVars',
        'currentClassName' => 'App\\Documents\\DocumentVars',
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