<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Support\SimilarityChecker.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Support\SimilarityChecker
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-7b7f612fb3b5c31eb694a87263fe29edda09b7b1f6c06915efacafe83eb4e6cb',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Support\\SimilarityChecker',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Support/SimilarityChecker.php',
      ),
    ),
    'namespace' => 'App\\Support',
    'name' => 'App\\Support\\SimilarityChecker',
    'shortName' => 'SimilarityChecker',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Détection de similarité interne (gratuite, sans I/O, sans base externe) :
 * compare un texte à un corpus de textes et renvoie les plus proches. Vise le
 * cas fréquent en colloque - résumés dupliqués, recyclés d\'un événement à
 * l\'autre, copiés entre soumissions - sans envoyer aucune donnée à l\'extérieur.
 *
 * Méthode : sac de mots pondéré (TF), similarité cosinus sur unigrammes +
 * bigrammes, après normalisation (minuscules, sans ponctuation, mots-outils et
 * jetons trop courts retirés). Déterministe et testable.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 15,
    'endLine' => 143,
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
      'STOPWORDS' => 
      array (
        'declaringClassName' => 'App\\Support\\SimilarityChecker',
        'implementingClassName' => 'App\\Support\\SimilarityChecker',
        'name' => 'STOPWORDS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'le\', \'la\', \'les\', \'un\', \'une\', \'des\', \'de\', \'du\', \'au\', \'aux\', \'et\', \'ou\', \'où\', \'à\', \'en\', \'dans\', \'pour\', \'par\', \'sur\', \'avec\', \'sans\', \'sous\', \'ce\', \'cet\', \'cette\', \'ces\', \'son\', \'sa\', \'ses\', \'leur\', \'leurs\', \'que\', \'qui\', \'quoi\', \'dont\', \'est\', \'sont\', \'être\', \'avoir\', \'plus\', \'moins\', \'aussi\', \'entre\', \'the\', \'a\', \'an\', \'of\', \'and\', \'or\', \'to\', \'in\', \'on\', \'for\', \'with\', \'is\', \'are\', \'be\', \'this\', \'that\', \'these\', \'those\', \'as\', \'by\', \'at\', \'from\', \'we\', \'our\', \'their\', \'its\']',
          'attributes' => 
          array (
            'startLine' => 18,
            'endLine' => 24,
            'startTokenPos' => 25,
            'startFilePos' => 721,
            'endTokenPos' => 234,
            'endFilePos' => 1258,
          ),
        ),
        'docComment' => '/** Mots-outils FR/EN écartés (bruit fréquent, non discriminant). */',
        'attributes' => 
        array (
        ),
        'startLine' => 18,
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
      'against' => 
      array (
        'name' => 'against',
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
            'startLine' => 32,
            'endLine' => 32,
            'startColumn' => 29,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'corpus' => 
          array (
            'name' => 'corpus',
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
            'startLine' => 32,
            'endLine' => 32,
            'startColumn' => 43,
            'endColumn' => 55,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'threshold' => 
          array (
            'name' => 'threshold',
            'default' => 
            array (
              'code' => '0.3',
              'attributes' => 
              array (
                'startLine' => 32,
                'endLine' => 32,
                'startTokenPos' => 261,
                'startFilePos' => 1567,
                'endTokenPos' => 261,
                'endFilePos' => 1570,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'float',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 32,
            'endLine' => 32,
            'startColumn' => 58,
            'endColumn' => 80,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'limit' => 
          array (
            'name' => 'limit',
            'default' => 
            array (
              'code' => '5',
              'attributes' => 
              array (
                'startLine' => 32,
                'endLine' => 32,
                'startTokenPos' => 270,
                'startFilePos' => 1586,
                'endTokenPos' => 270,
                'endFilePos' => 1586,
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
            'startLine' => 32,
            'endLine' => 32,
            'startColumn' => 83,
            'endColumn' => 96,
            'parameterIndex' => 3,
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
 * Textes du corpus les plus proches de `$text`, triés par score décroissant.
 *
 * @param  list<array{id:int, text:string}>  $corpus
 * @return list<array{id:int, score:float}> score dans [0,1]
 */',
        'startLine' => 32,
        'endLine' => 55,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\SimilarityChecker',
        'implementingClassName' => 'App\\Support\\SimilarityChecker',
        'currentClassName' => 'App\\Support\\SimilarityChecker',
        'aliasName' => NULL,
      ),
      'score' => 
      array (
        'name' => 'score',
        'parameters' => 
        array (
          'a' => 
          array (
            'name' => 'a',
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
            'startLine' => 60,
            'endLine' => 60,
            'startColumn' => 27,
            'endColumn' => 35,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'b' => 
          array (
            'name' => 'b',
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
            'startLine' => 60,
            'endLine' => 60,
            'startColumn' => 38,
            'endColumn' => 46,
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
            'name' => 'float',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Score de similarité entre deux textes (0 à 1).
 */',
        'startLine' => 60,
        'endLine' => 69,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\SimilarityChecker',
        'implementingClassName' => 'App\\Support\\SimilarityChecker',
        'currentClassName' => 'App\\Support\\SimilarityChecker',
        'aliasName' => NULL,
      ),
      'vector' => 
      array (
        'name' => 'vector',
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
            'startLine' => 76,
            'endLine' => 76,
            'startColumn' => 29,
            'endColumn' => 40,
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
 * Vecteur de fréquences (unigrammes + bigrammes) d\'un texte normalisé.
 *
 * @return array<string, int>
 */',
        'startLine' => 76,
        'endLine' => 89,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\SimilarityChecker',
        'implementingClassName' => 'App\\Support\\SimilarityChecker',
        'currentClassName' => 'App\\Support\\SimilarityChecker',
        'aliasName' => NULL,
      ),
      'tokens' => 
      array (
        'name' => 'tokens',
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
            'startLine' => 97,
            'endLine' => 97,
            'startColumn' => 29,
            'endColumn' => 40,
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
 * Jetons significatifs d\'un texte (minuscule, sans ponctuation, sans
 * mots-outils ni jetons < 3 caractères).
 *
 * @return list<string>
 */',
        'startLine' => 97,
        'endLine' => 112,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\SimilarityChecker',
        'implementingClassName' => 'App\\Support\\SimilarityChecker',
        'currentClassName' => 'App\\Support\\SimilarityChecker',
        'aliasName' => NULL,
      ),
      'norm' => 
      array (
        'name' => 'norm',
        'parameters' => 
        array (
          'vec' => 
          array (
            'name' => 'vec',
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
            'startLine' => 115,
            'endLine' => 115,
            'startColumn' => 27,
            'endColumn' => 36,
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
            'name' => 'float',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Norme euclidienne d\'un vecteur de fréquences. */',
        'startLine' => 115,
        'endLine' => 123,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\SimilarityChecker',
        'implementingClassName' => 'App\\Support\\SimilarityChecker',
        'currentClassName' => 'App\\Support\\SimilarityChecker',
        'aliasName' => NULL,
      ),
      'cosine' => 
      array (
        'name' => 'cosine',
        'parameters' => 
        array (
          'ref' => 
          array (
            'name' => 'ref',
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
            'startLine' => 131,
            'endLine' => 131,
            'startColumn' => 29,
            'endColumn' => 38,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'refNorm' => 
          array (
            'name' => 'refNorm',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'float',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 131,
            'endLine' => 131,
            'startColumn' => 41,
            'endColumn' => 54,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'vec' => 
          array (
            'name' => 'vec',
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
            'startLine' => 131,
            'endLine' => 131,
            'startColumn' => 57,
            'endColumn' => 66,
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
            'name' => 'float',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Cosinus entre le vecteur de référence (+ sa norme pré-calculée) et un autre.
 *
 * @param  array<string, int>  $ref
 * @param  array<string, int>  $vec
 */',
        'startLine' => 131,
        'endLine' => 142,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\SimilarityChecker',
        'implementingClassName' => 'App\\Support\\SimilarityChecker',
        'currentClassName' => 'App\\Support\\SimilarityChecker',
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