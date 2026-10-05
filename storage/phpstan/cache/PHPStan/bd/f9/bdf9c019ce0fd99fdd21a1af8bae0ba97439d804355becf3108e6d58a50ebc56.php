<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Support\RorClient.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Support\RorClient
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-81a7d1aa6534658f0843879a2d159c588cff281a390774adc43d65c87c49aaba',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Support\\RorClient',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Support/RorClient.php',
      ),
    ),
    'namespace' => 'App\\Support',
    'name' => 'App\\Support\\RorClient',
    'shortName' => 'RorClient',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Autocomplétion des affiliations via **ROR** (Research Organization Registry,
 * https://ror.org) - API publique, gratuite, ouverte, sans clé. Couvre les
 * universités, laboratoires et instituts de recherche du monde entier.
 *
 * Résilient : cache 1 jour, timeout court, **fail-closed** (liste vide en cas
 * d\'erreur/timeout) - la saisie libre reste toujours possible.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 16,
    'endLine' => 53,
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
      'ENDPOINT' => 
      array (
        'declaringClassName' => 'App\\Support\\RorClient',
        'implementingClassName' => 'App\\Support\\RorClient',
        'name' => 'ENDPOINT',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'https://api.ror.org/organizations\'',
          'attributes' => 
          array (
            'startLine' => 18,
            'endLine' => 18,
            'startTokenPos' => 33,
            'startFilePos' => 540,
            'endTokenPos' => 33,
            'endFilePos' => 574,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 18,
        'endLine' => 18,
        'startColumn' => 5,
        'endColumn' => 65,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'suggest' => 
      array (
        'name' => 'suggest',
        'parameters' => 
        array (
          'query' => 
          array (
            'name' => 'query',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 23,
            'endLine' => 23,
            'startColumn' => 36,
            'endColumn' => 48,
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
 * @return list<string> noms d\'organisations (avec pays entre parenthèses)
 */',
        'startLine' => 23,
        'endLine' => 52,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\RorClient',
        'implementingClassName' => 'App\\Support\\RorClient',
        'currentClassName' => 'App\\Support\\RorClient',
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