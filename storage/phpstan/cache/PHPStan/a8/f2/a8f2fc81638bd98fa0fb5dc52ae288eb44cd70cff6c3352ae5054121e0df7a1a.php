<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Support\Plagiarism\WebPlagiarismProvider.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Support\Plagiarism\WebPlagiarismProvider
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-b21ed77d810c42f92ea46c283fe6203591b53811ca431e721b6fb30e0c3ec71a',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Support\\Plagiarism\\WebPlagiarismProvider',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Support/Plagiarism/WebPlagiarismProvider.php',
      ),
    ),
    'namespace' => 'App\\Support\\Plagiarism',
    'name' => 'App\\Support\\Plagiarism\\WebPlagiarismProvider',
    'shortName' => 'WebPlagiarismProvider',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Contrat d\'un fournisseur de vérification anti-plagiat web (couche 2, externe).
 * Complète la similarité interne gratuite ({@see SimilarityChecker}).
 * Implémentations branchables (Prepostseo aujourd\'hui ; Copyleaks… plus tard).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 12,
    'endLine' => 30,
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
      'key' => 
      array (
        'name' => 'key',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Clé technique (ex. « prepostseo »). */',
        'startLine' => 15,
        'endLine' => 15,
        'startColumn' => 5,
        'endColumn' => 34,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support\\Plagiarism',
        'declaringClassName' => 'App\\Support\\Plagiarism\\WebPlagiarismProvider',
        'implementingClassName' => 'App\\Support\\Plagiarism\\WebPlagiarismProvider',
        'currentClassName' => 'App\\Support\\Plagiarism\\WebPlagiarismProvider',
        'aliasName' => NULL,
      ),
      'label' => 
      array (
        'name' => 'label',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Libellé lisible. */',
        'startLine' => 18,
        'endLine' => 18,
        'startColumn' => 5,
        'endColumn' => 36,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support\\Plagiarism',
        'declaringClassName' => 'App\\Support\\Plagiarism\\WebPlagiarismProvider',
        'implementingClassName' => 'App\\Support\\Plagiarism\\WebPlagiarismProvider',
        'currentClassName' => 'App\\Support\\Plagiarism\\WebPlagiarismProvider',
        'aliasName' => NULL,
      ),
      'isConfigured' => 
      array (
        'name' => 'isConfigured',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Vrai si le fournisseur dispose des identifiants nécessaires. */',
        'startLine' => 21,
        'endLine' => 21,
        'startColumn' => 5,
        'endColumn' => 41,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support\\Plagiarism',
        'declaringClassName' => 'App\\Support\\Plagiarism\\WebPlagiarismProvider',
        'implementingClassName' => 'App\\Support\\Plagiarism\\WebPlagiarismProvider',
        'currentClassName' => 'App\\Support\\Plagiarism\\WebPlagiarismProvider',
        'aliasName' => NULL,
      ),
      'check' => 
      array (
        'name' => 'check',
        'parameters' => 
        array (
          'text' => 
          array (
            'name' => 'text',
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
            'startLine' => 29,
            'endLine' => 29,
            'startColumn' => 27,
            'endColumn' => 38,
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
 * Vérifie un texte et renvoie un résultat normalisé (lève une exception en cas
 * d\'échec réseau/API).
 *
 * @return array{provider:string, plagPercent:float, uniquePercent:float, paraphrasePercent:float, sources:list<array{link:string, percent:float}>, checked_at:string}
 */',
        'startLine' => 29,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 47,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support\\Plagiarism',
        'declaringClassName' => 'App\\Support\\Plagiarism\\WebPlagiarismProvider',
        'implementingClassName' => 'App\\Support\\Plagiarism\\WebPlagiarismProvider',
        'currentClassName' => 'App\\Support\\Plagiarism\\WebPlagiarismProvider',
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