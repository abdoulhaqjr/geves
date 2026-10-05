<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Documents\AttestationVars.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Documents\AttestationVars
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-5815f1398f9651d15b3f86d828c644b5fa8915fb554e0318b5d1cd79b8697661',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Documents\\AttestationVars',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Documents/AttestationVars.php',
      ),
    ),
    'namespace' => 'App\\Documents',
    'name' => 'App\\Documents\\AttestationVars',
    'shortName' => 'AttestationVars',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Valeurs de rendu d\'une attestation délivrée (aperçu HTML comme PDF).
 *
 * Les deux chemins de rendu dressaient la même liste chacun de leur côté : un
 * jeton ajouté à l\'un manquait à l\'autre. Source unique ici, pour que le PDF
 * téléchargé dise exactement ce que montre la page.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 15,
    'endLine' => 56,
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
      'for' => 
      array (
        'name' => 'for',
        'parameters' => 
        array (
          'attestation' => 
          array (
            'name' => 'attestation',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Attestation',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 18,
            'endLine' => 18,
            'startColumn' => 32,
            'endColumn' => 55,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'verifyUrl' => 
          array (
            'name' => 'verifyUrl',
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
            'startLine' => 18,
            'endLine' => 18,
            'startColumn' => 58,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** @return array<string, mixed> */',
        'startLine' => 18,
        'endLine' => 55,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\AttestationVars',
        'implementingClassName' => 'App\\Documents\\AttestationVars',
        'currentClassName' => 'App\\Documents\\AttestationVars',
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