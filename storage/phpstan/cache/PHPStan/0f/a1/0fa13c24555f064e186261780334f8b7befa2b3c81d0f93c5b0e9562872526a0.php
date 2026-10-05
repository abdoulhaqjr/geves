<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\AI\Providers\OpenAiCompatibleProvider.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\AI\Providers\OpenAiCompatibleProvider
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-4025dee32645b487fcb0bd0e215b05d7fd9a1c202a31055bfe755586954ea3d3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\AI\\Providers\\OpenAiCompatibleProvider',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/AI/Providers/OpenAiCompatibleProvider.php',
      ),
    ),
    'namespace' => 'App\\AI\\Providers',
    'name' => 'App\\AI\\Providers\\OpenAiCompatibleProvider',
    'shortName' => 'OpenAiCompatibleProvider',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 64,
    'docComment' => '/**
 * Socle des fournisseurs à API compatible OpenAI (chat/completions) : OpenAI et
 * Grok (xAI). Multimodal images uniquement (pas de PDF).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 15,
    'endLine' => 101,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'App\\AI\\Contracts\\AiProvider',
    ),
    'traitClassNames' => 
    array (
      0 => 'App\\AI\\Concerns\\ParsesJsonResponse',
      1 => 'App\\AI\\Concerns\\RetriesTransientFailures',
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
        'docComment' => NULL,
        'startLine' => 20,
        'endLine' => 20,
        'startColumn' => 5,
        'endColumn' => 43,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 65,
        'namespace' => 'App\\AI\\Providers',
        'declaringClassName' => 'App\\AI\\Providers\\OpenAiCompatibleProvider',
        'implementingClassName' => 'App\\AI\\Providers\\OpenAiCompatibleProvider',
        'currentClassName' => 'App\\AI\\Providers\\OpenAiCompatibleProvider',
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
        'startLine' => 22,
        'endLine' => 22,
        'startColumn' => 5,
        'endColumn' => 45,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 65,
        'namespace' => 'App\\AI\\Providers',
        'declaringClassName' => 'App\\AI\\Providers\\OpenAiCompatibleProvider',
        'implementingClassName' => 'App\\AI\\Providers\\OpenAiCompatibleProvider',
        'currentClassName' => 'App\\AI\\Providers\\OpenAiCompatibleProvider',
        'aliasName' => NULL,
      ),
      'service' => 
      array (
        'name' => 'service',
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
        'docComment' => '/** Nom du service dans config/services.php (openai | grok). */',
        'startLine' => 25,
        'endLine' => 25,
        'startColumn' => 5,
        'endColumn' => 50,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 66,
        'namespace' => 'App\\AI\\Providers',
        'declaringClassName' => 'App\\AI\\Providers\\OpenAiCompatibleProvider',
        'implementingClassName' => 'App\\AI\\Providers\\OpenAiCompatibleProvider',
        'currentClassName' => 'App\\AI\\Providers\\OpenAiCompatibleProvider',
        'aliasName' => NULL,
      ),
      'endpoint' => 
      array (
        'name' => 'endpoint',
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
        'endLine' => 27,
        'startColumn' => 5,
        'endColumn' => 51,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 66,
        'namespace' => 'App\\AI\\Providers',
        'declaringClassName' => 'App\\AI\\Providers\\OpenAiCompatibleProvider',
        'implementingClassName' => 'App\\AI\\Providers\\OpenAiCompatibleProvider',
        'currentClassName' => 'App\\AI\\Providers\\OpenAiCompatibleProvider',
        'aliasName' => NULL,
      ),
      'fallbackModel' => 
      array (
        'name' => 'fallbackModel',
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
        'startLine' => 29,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 56,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 66,
        'namespace' => 'App\\AI\\Providers',
        'declaringClassName' => 'App\\AI\\Providers\\OpenAiCompatibleProvider',
        'implementingClassName' => 'App\\AI\\Providers\\OpenAiCompatibleProvider',
        'currentClassName' => 'App\\AI\\Providers\\OpenAiCompatibleProvider',
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
        'docComment' => NULL,
        'startLine' => 31,
        'endLine' => 34,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\AI\\Providers',
        'declaringClassName' => 'App\\AI\\Providers\\OpenAiCompatibleProvider',
        'implementingClassName' => 'App\\AI\\Providers\\OpenAiCompatibleProvider',
        'currentClassName' => 'App\\AI\\Providers\\OpenAiCompatibleProvider',
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
        'docComment' => NULL,
        'startLine' => 36,
        'endLine' => 39,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\AI\\Providers',
        'declaringClassName' => 'App\\AI\\Providers\\OpenAiCompatibleProvider',
        'implementingClassName' => 'App\\AI\\Providers\\OpenAiCompatibleProvider',
        'currentClassName' => 'App\\AI\\Providers\\OpenAiCompatibleProvider',
        'aliasName' => NULL,
      ),
      'model' => 
      array (
        'name' => 'model',
        'parameters' => 
        array (
          'tier' => 
          array (
            'name' => 'tier',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 42,
                'endLine' => 42,
                'startTokenPos' => 199,
                'startFilePos' => 1172,
                'endTokenPos' => 199,
                'endFilePos' => 1175,
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
            'startLine' => 42,
            'endLine' => 42,
            'startColumn' => 30,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
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
        'docComment' => '/** Modèle concret pour le palier demandé (économie / qualité), repli sur le défaut. */',
        'startLine' => 42,
        'endLine' => 53,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\AI\\Providers',
        'declaringClassName' => 'App\\AI\\Providers\\OpenAiCompatibleProvider',
        'implementingClassName' => 'App\\AI\\Providers\\OpenAiCompatibleProvider',
        'currentClassName' => 'App\\AI\\Providers\\OpenAiCompatibleProvider',
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
            'startLine' => 55,
            'endLine' => 55,
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
                'startLine' => 55,
                'endLine' => 55,
                'startTokenPos' => 312,
                'startFilePos' => 1698,
                'endTokenPos' => 313,
                'endFilePos' => 1699,
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
            'startLine' => 55,
            'endLine' => 55,
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
                'startLine' => 55,
                'endLine' => 55,
                'startTokenPos' => 323,
                'startFilePos' => 1718,
                'endTokenPos' => 323,
                'endFilePos' => 1721,
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
            'startLine' => 55,
            'endLine' => 55,
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
        'docComment' => NULL,
        'startLine' => 55,
        'endLine' => 67,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\AI\\Providers',
        'declaringClassName' => 'App\\AI\\Providers\\OpenAiCompatibleProvider',
        'implementingClassName' => 'App\\AI\\Providers\\OpenAiCompatibleProvider',
        'currentClassName' => 'App\\AI\\Providers\\OpenAiCompatibleProvider',
        'aliasName' => NULL,
      ),
      'send' => 
      array (
        'name' => 'send',
        'parameters' => 
        array (
          'model' => 
          array (
            'name' => 'model',
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
            'startLine' => 73,
            'endLine' => 73,
            'startColumn' => 29,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 73,
            'endLine' => 73,
            'startColumn' => 44,
            'endColumn' => 62,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'files' => 
          array (
            'name' => 'files',
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
            'startLine' => 73,
            'endLine' => 73,
            'startColumn' => 65,
            'endColumn' => 76,
            'parameterIndex' => 2,
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
 * @param  array<int, array{mime: string, data: string}>  $files
 * @return array<string, mixed>
 */',
        'startLine' => 73,
        'endLine' => 100,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\AI\\Providers',
        'declaringClassName' => 'App\\AI\\Providers\\OpenAiCompatibleProvider',
        'implementingClassName' => 'App\\AI\\Providers\\OpenAiCompatibleProvider',
        'currentClassName' => 'App\\AI\\Providers\\OpenAiCompatibleProvider',
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