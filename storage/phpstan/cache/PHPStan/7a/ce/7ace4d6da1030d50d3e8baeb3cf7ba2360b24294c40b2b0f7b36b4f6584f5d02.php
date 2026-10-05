<?php declare(strict_types = 1);

// osfsl-C:/Users/TOURE/Herd/gesco/vendor/composer/../laravel/framework/src/Illuminate/Contracts/Container/ContextualBindingBuilder.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Contracts\Container\ContextualBindingBuilder
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-edc295c36c194bfcd210be079efa51b74e4e26683bde88c03f32a9daaa9985a9-8.4.24-6.70.0.3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Contracts\\Container\\ContextualBindingBuilder',
        'filename' => 'C:/Users/TOURE/Herd/gesco/vendor/composer/../laravel/framework/src/Illuminate/Contracts/Container/ContextualBindingBuilder.php',
      ),
    ),
    'namespace' => 'Illuminate\\Contracts\\Container',
    'name' => 'Illuminate\\Contracts\\Container\\ContextualBindingBuilder',
    'shortName' => 'ContextualBindingBuilder',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 5,
    'endLine' => 39,
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
      'needs' => 
      array (
        'name' => 'needs',
        'parameters' => 
        array (
          'abstract' => 
          array (
            'name' => 'abstract',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 13,
            'endLine' => 13,
            'startColumn' => 27,
            'endColumn' => 35,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Define the abstract target that depends on the context.
 *
 * @param  string  $abstract
 * @return $this
 */',
        'startLine' => 13,
        'endLine' => 13,
        'startColumn' => 5,
        'endColumn' => 37,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Contracts\\Container',
        'declaringClassName' => 'Illuminate\\Contracts\\Container\\ContextualBindingBuilder',
        'implementingClassName' => 'Illuminate\\Contracts\\Container\\ContextualBindingBuilder',
        'currentClassName' => 'Illuminate\\Contracts\\Container\\ContextualBindingBuilder',
        'aliasName' => NULL,
      ),
      'give' => 
      array (
        'name' => 'give',
        'parameters' => 
        array (
          'implementation' => 
          array (
            'name' => 'implementation',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 21,
            'endLine' => 21,
            'startColumn' => 26,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Define the implementation for the contextual binding.
 *
 * @param  \\Closure|string|array  $implementation
 * @return $this
 */',
        'startLine' => 21,
        'endLine' => 21,
        'startColumn' => 5,
        'endColumn' => 42,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Contracts\\Container',
        'declaringClassName' => 'Illuminate\\Contracts\\Container\\ContextualBindingBuilder',
        'implementingClassName' => 'Illuminate\\Contracts\\Container\\ContextualBindingBuilder',
        'currentClassName' => 'Illuminate\\Contracts\\Container\\ContextualBindingBuilder',
        'aliasName' => NULL,
      ),
      'giveTagged' => 
      array (
        'name' => 'giveTagged',
        'parameters' => 
        array (
          'tag' => 
          array (
            'name' => 'tag',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 29,
            'endLine' => 29,
            'startColumn' => 32,
            'endColumn' => 35,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Define tagged services to be used as the implementation for the contextual binding.
 *
 * @param  string  $tag
 * @return $this
 */',
        'startLine' => 29,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 37,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Contracts\\Container',
        'declaringClassName' => 'Illuminate\\Contracts\\Container\\ContextualBindingBuilder',
        'implementingClassName' => 'Illuminate\\Contracts\\Container\\ContextualBindingBuilder',
        'currentClassName' => 'Illuminate\\Contracts\\Container\\ContextualBindingBuilder',
        'aliasName' => NULL,
      ),
      'giveConfig' => 
      array (
        'name' => 'giveConfig',
        'parameters' => 
        array (
          'key' => 
          array (
            'name' => 'key',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 38,
            'endLine' => 38,
            'startColumn' => 32,
            'endColumn' => 35,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'default' => 
          array (
            'name' => 'default',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 38,
                'endLine' => 38,
                'startTokenPos' => 64,
                'startFilePos' => 884,
                'endTokenPos' => 64,
                'endFilePos' => 887,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 38,
            'endLine' => 38,
            'startColumn' => 38,
            'endColumn' => 52,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Specify the configuration item to bind as a primitive.
 *
 * @param  string  $key
 * @param  mixed  $default
 * @return $this
 */',
        'startLine' => 38,
        'endLine' => 38,
        'startColumn' => 5,
        'endColumn' => 54,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Contracts\\Container',
        'declaringClassName' => 'Illuminate\\Contracts\\Container\\ContextualBindingBuilder',
        'implementingClassName' => 'Illuminate\\Contracts\\Container\\ContextualBindingBuilder',
        'currentClassName' => 'Illuminate\\Contracts\\Container\\ContextualBindingBuilder',
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