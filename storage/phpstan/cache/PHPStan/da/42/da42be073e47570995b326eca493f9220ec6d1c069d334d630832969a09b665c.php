<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\AI\ScoreTestimonials.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\AI\ScoreTestimonials
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-7c2b89b30bc0e32832c2dbbf27bd2463310dca5d888ff4ba3d1f3cfcb8405fd0',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\AI\\ScoreTestimonials',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/AI/ScoreTestimonials.php',
      ),
    ),
    'namespace' => 'App\\AI',
    'name' => 'App\\AI\\ScoreTestimonials',
    'shortName' => 'ScoreTestimonials',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Évalue l\'« impact » des témoignages approuvés et propose lesquels mettre en
 * avant sur la page d\'accueil, dans un ordre suggéré. L\'IA ne fait que
 * PROPOSER : elle renseigne un score + une justification et pré-remplit la
 * sélection « à la une » ; l\'admin garde la main (peut ajuster la mise en avant
 * et l\'ordre ensuite).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 15,
    'endLine' => 111,
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
      'FEATURED_TARGET' => 
      array (
        'declaringClassName' => 'App\\AI\\ScoreTestimonials',
        'implementingClassName' => 'App\\AI\\ScoreTestimonials',
        'name' => 'FEATURED_TARGET',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '6',
          'attributes' => 
          array (
            'startLine' => 18,
            'endLine' => 18,
            'startTokenPos' => 35,
            'startFilePos' => 573,
            'endTokenPos' => 35,
            'endFilePos' => 573,
          ),
        ),
        'docComment' => '/** Nombre de témoignages mis en avant proposés par défaut. */',
        'attributes' => 
        array (
        ),
        'startLine' => 18,
        'endLine' => 18,
        'startColumn' => 5,
        'endColumn' => 37,
      ),
    ),
    'immediateProperties' => 
    array (
      'ai' => 
      array (
        'declaringClassName' => 'App\\AI\\ScoreTestimonials',
        'implementingClassName' => 'App\\AI\\ScoreTestimonials',
        'name' => 'ai',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'App\\AI\\AiManager',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 20,
        'endLine' => 20,
        'startColumn' => 33,
        'endColumn' => 53,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
    ),
    'immediateMethods' => 
    array (
      '__construct' => 
      array (
        'name' => '__construct',
        'parameters' => 
        array (
          'ai' => 
          array (
            'name' => 'ai',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\AI\\AiManager',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 20,
            'endLine' => 20,
            'startColumn' => 33,
            'endColumn' => 53,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 20,
        'endLine' => 20,
        'startColumn' => 5,
        'endColumn' => 57,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\AI',
        'declaringClassName' => 'App\\AI\\ScoreTestimonials',
        'implementingClassName' => 'App\\AI\\ScoreTestimonials',
        'currentClassName' => 'App\\AI\\ScoreTestimonials',
        'aliasName' => NULL,
      ),
      'handle' => 
      array (
        'name' => 'handle',
        'parameters' => 
        array (
          'target' => 
          array (
            'name' => 'target',
            'default' => 
            array (
              'code' => 'self::FEATURED_TARGET',
              'attributes' => 
              array (
                'startLine' => 27,
                'endLine' => 27,
                'startTokenPos' => 68,
                'startFilePos' => 826,
                'endTokenPos' => 70,
                'endFilePos' => 846,
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
            'startLine' => 27,
            'endLine' => 27,
            'startColumn' => 28,
            'endColumn' => 62,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Note les témoignages approuvés et applique la sélection proposée.
 *
 * @return array{scored: int, featured: int}
 */',
        'startLine' => 27,
        'endLine' => 39,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\AI',
        'declaringClassName' => 'App\\AI\\ScoreTestimonials',
        'implementingClassName' => 'App\\AI\\ScoreTestimonials',
        'currentClassName' => 'App\\AI\\ScoreTestimonials',
        'aliasName' => NULL,
      ),
      'instruction' => 
      array (
        'name' => 'instruction',
        'parameters' => 
        array (
          'testimonials' => 
          array (
            'name' => 'testimonials',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Support\\Collection',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 44,
            'endLine' => 44,
            'startColumn' => 34,
            'endColumn' => 57,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'target' => 
          array (
            'name' => 'target',
            'default' => NULL,
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
            'startLine' => 44,
            'endLine' => 44,
            'startColumn' => 60,
            'endColumn' => 70,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param  Collection<int, Testimonial>  $testimonials
 */',
        'startLine' => 44,
        'endLine' => 66,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\AI',
        'declaringClassName' => 'App\\AI\\ScoreTestimonials',
        'implementingClassName' => 'App\\AI\\ScoreTestimonials',
        'currentClassName' => 'App\\AI\\ScoreTestimonials',
        'aliasName' => NULL,
      ),
      'apply' => 
      array (
        'name' => 'apply',
        'parameters' => 
        array (
          'testimonials' => 
          array (
            'name' => 'testimonials',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Support\\Collection',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 75,
            'endLine' => 75,
            'startColumn' => 28,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'result' => 
          array (
            'name' => 'result',
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
            'startLine' => 75,
            'endLine' => 75,
            'startColumn' => 54,
            'endColumn' => 66,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'target' => 
          array (
            'name' => 'target',
            'default' => NULL,
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
            'startLine' => 75,
            'endLine' => 75,
            'startColumn' => 69,
            'endColumn' => 79,
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
 * Applique scores + sélection proposée. Robuste à une réponse partielle.
 *
 * @param  Collection<int, Testimonial>  $testimonials
 * @param  array<string, mixed>  $result
 * @return array{scored: int, featured: int}
 */',
        'startLine' => 75,
        'endLine' => 110,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\AI',
        'declaringClassName' => 'App\\AI\\ScoreTestimonials',
        'implementingClassName' => 'App\\AI\\ScoreTestimonials',
        'currentClassName' => 'App\\AI\\ScoreTestimonials',
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