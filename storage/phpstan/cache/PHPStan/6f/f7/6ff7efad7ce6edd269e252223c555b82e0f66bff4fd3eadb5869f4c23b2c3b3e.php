<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Support\Plagiarism\PlagiarismRegistry.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Support\Plagiarism\PlagiarismRegistry
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-994591051afb84d35d2fd0fddd0e4ec2a9ff41c8a0d0b0386b713d08bea8ff80',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Support\\Plagiarism\\PlagiarismRegistry',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Support/Plagiarism/PlagiarismRegistry.php',
      ),
    ),
    'namespace' => 'App\\Support\\Plagiarism',
    'name' => 'App\\Support\\Plagiarism\\PlagiarismRegistry',
    'shortName' => 'PlagiarismRegistry',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Registre des fournisseurs anti-plagiat web (couche 2). Renvoie le fournisseur
 * actif selon les réglages, ou null si « aucun » ou non configuré (auquel cas
 * seule la similarité interne gratuite s\'applique).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 12,
    'endLine' => 29,
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
      'providers' => 
      array (
        'name' => 'providers',
        'parameters' => 
        array (
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
        'docComment' => '/** @return array<string, WebPlagiarismProvider> */',
        'startLine' => 15,
        'endLine' => 20,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support\\Plagiarism',
        'declaringClassName' => 'App\\Support\\Plagiarism\\PlagiarismRegistry',
        'implementingClassName' => 'App\\Support\\Plagiarism\\PlagiarismRegistry',
        'currentClassName' => 'App\\Support\\Plagiarism\\PlagiarismRegistry',
        'aliasName' => NULL,
      ),
      'active' => 
      array (
        'name' => 'active',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
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
                  'name' => 'App\\Support\\Plagiarism\\WebPlagiarismProvider',
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
        'attributes' => 
        array (
        ),
        'docComment' => '/** Fournisseur actif configuré, ou null. */',
        'startLine' => 23,
        'endLine' => 28,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support\\Plagiarism',
        'declaringClassName' => 'App\\Support\\Plagiarism\\PlagiarismRegistry',
        'implementingClassName' => 'App\\Support\\Plagiarism\\PlagiarismRegistry',
        'currentClassName' => 'App\\Support\\Plagiarism\\PlagiarismRegistry',
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