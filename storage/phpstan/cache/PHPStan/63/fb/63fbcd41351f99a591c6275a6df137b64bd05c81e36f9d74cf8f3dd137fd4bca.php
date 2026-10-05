<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Support\Plagiarism\PrepostseoProvider.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Support\Plagiarism\PrepostseoProvider
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-2a04bd95a46bfee6b9810210869ff8aaa1c6a0c738faad589163900004062f2b',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Support\\Plagiarism\\PrepostseoProvider',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Support/Plagiarism/PrepostseoProvider.php',
      ),
    ),
    'namespace' => 'App\\Support\\Plagiarism',
    'name' => 'App\\Support\\Plagiarism\\PrepostseoProvider',
    'shortName' => 'PrepostseoProvider',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Fournisseur anti-plagiat Prepostseo (freemium). Contrat de leur API :
 *   POST https://www.prepostseo.com/apis/checkPlag  (form) : key, data (≤ 2 500 mots)
 *   Réponse JSON : plagPercent, uniquePercent, paraphrasePercent,
 *                  sources[] { link, count, percent }, totalQueries, isQueriesFinished
 * Le parsing est défensif (champs tolérés absents) ; la clé vit dans les
 * réglages (DB), jamais en dur ni dans .env.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 16,
    'endLine' => 72,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'App\\Support\\Plagiarism\\WebPlagiarismProvider',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'ENDPOINT' => 
      array (
        'declaringClassName' => 'App\\Support\\Plagiarism\\PrepostseoProvider',
        'implementingClassName' => 'App\\Support\\Plagiarism\\PrepostseoProvider',
        'name' => 'ENDPOINT',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'https://www.prepostseo.com/apis/checkPlag\'',
          'attributes' => 
          array (
            'startLine' => 18,
            'endLine' => 18,
            'startTokenPos' => 37,
            'startFilePos' => 646,
            'endTokenPos' => 37,
            'endFilePos' => 688,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 18,
        'endLine' => 18,
        'startColumn' => 5,
        'endColumn' => 73,
      ),
      'MAX_WORDS' => 
      array (
        'declaringClassName' => 'App\\Support\\Plagiarism\\PrepostseoProvider',
        'implementingClassName' => 'App\\Support\\Plagiarism\\PrepostseoProvider',
        'name' => 'MAX_WORDS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '2500',
          'attributes' => 
          array (
            'startLine' => 20,
            'endLine' => 20,
            'startTokenPos' => 48,
            'startFilePos' => 722,
            'endTokenPos' => 48,
            'endFilePos' => 725,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 20,
        'endLine' => 20,
        'startColumn' => 5,
        'endColumn' => 35,
      ),
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
        'docComment' => NULL,
        'startLine' => 22,
        'endLine' => 25,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support\\Plagiarism',
        'declaringClassName' => 'App\\Support\\Plagiarism\\PrepostseoProvider',
        'implementingClassName' => 'App\\Support\\Plagiarism\\PrepostseoProvider',
        'currentClassName' => 'App\\Support\\Plagiarism\\PrepostseoProvider',
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
        'docComment' => NULL,
        'startLine' => 27,
        'endLine' => 30,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support\\Plagiarism',
        'declaringClassName' => 'App\\Support\\Plagiarism\\PrepostseoProvider',
        'implementingClassName' => 'App\\Support\\Plagiarism\\PrepostseoProvider',
        'currentClassName' => 'App\\Support\\Plagiarism\\PrepostseoProvider',
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
        'docComment' => NULL,
        'startLine' => 32,
        'endLine' => 35,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support\\Plagiarism',
        'declaringClassName' => 'App\\Support\\Plagiarism\\PrepostseoProvider',
        'implementingClassName' => 'App\\Support\\Plagiarism\\PrepostseoProvider',
        'currentClassName' => 'App\\Support\\Plagiarism\\PrepostseoProvider',
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
            'startLine' => 37,
            'endLine' => 37,
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
        'docComment' => NULL,
        'startLine' => 37,
        'endLine' => 71,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support\\Plagiarism',
        'declaringClassName' => 'App\\Support\\Plagiarism\\PrepostseoProvider',
        'implementingClassName' => 'App\\Support\\Plagiarism\\PrepostseoProvider',
        'currentClassName' => 'App\\Support\\Plagiarism\\PrepostseoProvider',
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