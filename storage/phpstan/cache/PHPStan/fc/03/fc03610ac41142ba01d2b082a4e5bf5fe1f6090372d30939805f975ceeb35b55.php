<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\AI\Contracts\AiProvider.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\AI\Contracts\AiProvider
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-8a832b29480fa0a51b6cbbfc6a48c66598acc480681979da33f5cc533bb3f9d9',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\AI\\Contracts\\AiProvider',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/AI/Contracts/AiProvider.php',
      ),
    ),
    'namespace' => 'App\\AI\\Contracts',
    'name' => 'App\\AI\\Contracts\\AiProvider',
    'shortName' => 'AiProvider',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Fournisseur d\'IA multimodal. Extrait des données structurées (JSON) à partir
 * d\'une instruction et de pièces jointes (PDF / images).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 9,
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
        'docComment' => '/** Jeton du fournisseur (aligné sur services.ai.default : claude|openai|gemini|grok). */',
        'startLine' => 12,
        'endLine' => 12,
        'startColumn' => 5,
        'endColumn' => 34,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\AI\\Contracts',
        'declaringClassName' => 'App\\AI\\Contracts\\AiProvider',
        'implementingClassName' => 'App\\AI\\Contracts\\AiProvider',
        'currentClassName' => 'App\\AI\\Contracts\\AiProvider',
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
        'startLine' => 14,
        'endLine' => 14,
        'startColumn' => 5,
        'endColumn' => 36,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\AI\\Contracts',
        'declaringClassName' => 'App\\AI\\Contracts\\AiProvider',
        'implementingClassName' => 'App\\AI\\Contracts\\AiProvider',
        'currentClassName' => 'App\\AI\\Contracts\\AiProvider',
        'aliasName' => NULL,
      ),
      'isAvailable' => 
      array (
        'name' => 'isAvailable',
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
        'docComment' => '/** Vrai si une clé API est configurée. */',
        'startLine' => 17,
        'endLine' => 17,
        'startColumn' => 5,
        'endColumn' => 40,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\AI\\Contracts',
        'declaringClassName' => 'App\\AI\\Contracts\\AiProvider',
        'implementingClassName' => 'App\\AI\\Contracts\\AiProvider',
        'currentClassName' => 'App\\AI\\Contracts\\AiProvider',
        'aliasName' => NULL,
      ),
      'supportsPdf' => 
      array (
        'name' => 'supportsPdf',
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
        'docComment' => '/** Le fournisseur sait-il lire des PDF ? */',
        'startLine' => 20,
        'endLine' => 20,
        'startColumn' => 5,
        'endColumn' => 40,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\AI\\Contracts',
        'declaringClassName' => 'App\\AI\\Contracts\\AiProvider',
        'implementingClassName' => 'App\\AI\\Contracts\\AiProvider',
        'currentClassName' => 'App\\AI\\Contracts\\AiProvider',
        'aliasName' => NULL,
      ),
      'extract' => 
      array (
        'name' => 'extract',
        'parameters' => 
        array (
          'instruction' => 
          array (
            'name' => 'instruction',
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
            'startColumn' => 29,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'files' => 
          array (
            'name' => 'files',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 29,
                'endLine' => 29,
                'startTokenPos' => 88,
                'startFilePos' => 966,
                'endTokenPos' => 89,
                'endFilePos' => 967,
              ),
            ),
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
            'startLine' => 29,
            'endLine' => 29,
            'startColumn' => 50,
            'endColumn' => 66,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'tier' => 
          array (
            'name' => 'tier',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 29,
                'endLine' => 29,
                'startTokenPos' => 99,
                'startFilePos' => 986,
                'endTokenPos' => 99,
                'endFilePos' => 989,
              ),
            ),
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
                      'name' => 'string',
                      'isIdentifier' => true,
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
            'startLine' => 29,
            'endLine' => 29,
            'startColumn' => 69,
            'endColumn' => 88,
            'parameterIndex' => 2,
            'isOptional' => true,
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
 * Envoie l\'instruction + les fichiers et renvoie la réponse JSON décodée.
 *
 * @param  array<int, array{mime: string, data: string}>  $files  data = contenu base64
 * @param  \'fast\'|\'deep\'|null  $tier  palier de modèle souhaité (économie / qualité), null = défaut
 * @return array<string, mixed>
 */',
        'startLine' => 29,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 97,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\AI\\Contracts',
        'declaringClassName' => 'App\\AI\\Contracts\\AiProvider',
        'implementingClassName' => 'App\\AI\\Contracts\\AiProvider',
        'currentClassName' => 'App\\AI\\Contracts\\AiProvider',
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