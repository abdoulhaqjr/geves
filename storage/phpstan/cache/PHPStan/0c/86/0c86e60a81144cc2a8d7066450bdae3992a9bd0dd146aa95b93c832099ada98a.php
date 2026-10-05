<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\AI\AiRouter.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\AI\AiRouter
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-0a99f4e95fa5113c161274f967758947d2e4448e62c814879480ff3752d258ef',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\AI\\AiRouter',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/AI/AiRouter.php',
      ),
    ),
    'namespace' => 'App\\AI',
    'name' => 'App\\AI\\AiRouter',
    'shortName' => 'AiRouter',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Choix « intelligent » du palier de modèle selon la tâche. L\'idée : dépenser
 * moins de jetons sur les demandes simples (palier « fast »/économie) et
 * réserver le palier « deep »/qualité aux tâches lourdes ou complexes, sans
 * perte de pertinence. Renvoie un palier (\'fast\'|\'deep\') ou null (modèle par
 * défaut du fournisseur).
 *
 * Chaque fournisseur fait correspondre le palier à un modèle concret et se
 * replie sur son modèle par défaut si le palier n\'est pas disponible.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 17,
    'endLine' => 70,
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
      'COMPLEX_HINTS' => 
      array (
        'declaringClassName' => 'App\\AI\\AiRouter',
        'implementingClassName' => 'App\\AI\\AiRouter',
        'name' => 'COMPLEX_HINTS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'analyse\', \'analyser\', \'compare\', \'comparer\', \'rédige\', \'rédiger\', \'rédaction\', \'synthèse\', \'synthétise\', \'résume tout\', \'explique en détail\', \'pourquoi\', \'stratég\', \'recommand\', \'plan d\', \'planifie\', \'évalue\', \'justifie\', \'argument\']',
          'attributes' => 
          array (
            'startLine' => 20,
            'endLine' => 24,
            'startTokenPos' => 30,
            'startFilePos' => 709,
            'endTokenPos' => 89,
            'endFilePos' => 983,
          ),
        ),
        'docComment' => '/** Mots-clés révélateurs d\'une demande analytique / rédactionnelle exigeante. */',
        'attributes' => 
        array (
        ),
        'startLine' => 20,
        'endLine' => 24,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'tier' => 
      array (
        'name' => 'tier',
        'parameters' => 
        array (
          'useCase' => 
          array (
            'name' => 'useCase',
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
            'startLine' => 33,
            'endLine' => 33,
            'startColumn' => 26,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'prompt' => 
          array (
            'name' => 'prompt',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 33,
                'endLine' => 33,
                'startTokenPos' => 111,
                'startFilePos' => 1447,
                'endTokenPos' => 111,
                'endFilePos' => 1448,
              ),
            ),
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
            'startLine' => 33,
            'endLine' => 33,
            'startColumn' => 43,
            'endColumn' => 61,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'hasFiles' => 
          array (
            'name' => 'hasFiles',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 33,
                'endLine' => 33,
                'startTokenPos' => 120,
                'startFilePos' => 1468,
                'endTokenPos' => 120,
                'endFilePos' => 1472,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'bool',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 33,
            'endLine' => 33,
            'startColumn' => 64,
            'endColumn' => 85,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'historyTurns' => 
          array (
            'name' => 'historyTurns',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 33,
                'endLine' => 33,
                'startTokenPos' => 129,
                'startFilePos' => 1495,
                'endTokenPos' => 129,
                'endFilePos' => 1495,
              ),
            ),
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
            'startLine' => 33,
            'endLine' => 33,
            'startColumn' => 88,
            'endColumn' => 108,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
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
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param  \'chat_public\'|\'chat_admin\'|\'report\'|\'document\'|\'claim_analysis\'|\'extraction\'|string  $useCase
 * @param  string  $prompt  texte de la demande (pour jauger la complexité)
 * @param  bool  $hasFiles  des pièces jointes accompagnent la demande
 * @param  int  $historyTurns  nombre de tours de conversation déjà échangés
 * @return \'fast\'|\'deep\'|null
 */',
        'startLine' => 33,
        'endLine' => 55,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\AI',
        'declaringClassName' => 'App\\AI\\AiRouter',
        'implementingClassName' => 'App\\AI\\AiRouter',
        'currentClassName' => 'App\\AI\\AiRouter',
        'aliasName' => NULL,
      ),
      'looksComplex' => 
      array (
        'name' => 'looksComplex',
        'parameters' => 
        array (
          'prompt' => 
          array (
            'name' => 'prompt',
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
            'startLine' => 57,
            'endLine' => 57,
            'startColumn' => 35,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 57,
        'endLine' => 69,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\AI',
        'declaringClassName' => 'App\\AI\\AiRouter',
        'implementingClassName' => 'App\\AI\\AiRouter',
        'currentClassName' => 'App\\AI\\AiRouter',
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