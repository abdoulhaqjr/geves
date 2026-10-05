<?php declare(strict_types = 1);

// osfsl-C:/Users/TOURE/Herd/gesco/vendor/composer/../laravel/boost/src/Console/AddSkillCommand.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Laravel\Boost\Console\AddSkillCommand
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6160bb1a574637420cc284aed634c73626b0408508d539198f24476d321a3b42-8.4.24-6.70.0.3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'filename' => 'C:/Users/TOURE/Herd/gesco/vendor/composer/../laravel/boost/src/Console/AddSkillCommand.php',
      ),
    ),
    'namespace' => 'Laravel\\Boost\\Console',
    'name' => 'Laravel\\Boost\\Console\\AddSkillCommand',
    'shortName' => 'AddSkillCommand',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 30,
    'endLine' => 409,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Console\\Command',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
      0 => 'Laravel\\Boost\\Concerns\\DisplayHelper',
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'signature' => 
      array (
        'declaringClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'implementingClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'name' => 'signature',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'boost:add-skill
        {repo? : GitHub repository (owner/repo or full URL)}
        {--list : List available skills}
        {--all : Install all skills}
        {--skill=* : Specific skills to install}
        {--force : Overwrite existing skills}
        {--skip-audit : Skip security audit}\'',
          'attributes' => 
          array (
            'startLine' => 35,
            'endLine' => 41,
            'startTokenPos' => 154,
            'startFilePos' => 938,
            'endTokenPos' => 154,
            'endFilePos' => 1233,
          ),
        ),
        'docComment' => '/** @var string */',
        'attributes' => 
        array (
        ),
        'startLine' => 35,
        'endLine' => 41,
        'startColumn' => 5,
        'endColumn' => 46,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'description' => 
      array (
        'declaringClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'implementingClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'name' => 'description',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'Add skills from a remote GitHub repository\'',
          'attributes' => 
          array (
            'startLine' => 44,
            'endLine' => 44,
            'startTokenPos' => 165,
            'startFilePos' => 1289,
            'endTokenPos' => 165,
            'endFilePos' => 1332,
          ),
        ),
        'docComment' => '/** @var string */',
        'attributes' => 
        array (
        ),
        'startLine' => 44,
        'endLine' => 44,
        'startColumn' => 5,
        'endColumn' => 74,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'repository' => 
      array (
        'declaringClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'implementingClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'name' => 'repository',
        'modifiers' => 2,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Laravel\\Boost\\Skills\\Remote\\GitHubRepository',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 46,
        'endLine' => 46,
        'startColumn' => 5,
        'endColumn' => 43,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'fetcher' => 
      array (
        'declaringClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'implementingClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'name' => 'fetcher',
        'modifiers' => 2,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Laravel\\Boost\\Skills\\Remote\\GitHubSkillProvider',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 48,
        'endLine' => 48,
        'startColumn' => 5,
        'endColumn' => 43,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'availableSkills' => 
      array (
        'declaringClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'implementingClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'name' => 'availableSkills',
        'modifiers' => 2,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Support\\Collection',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => '/** @var Collection<string, RemoteSkill> */',
        'attributes' => 
        array (
        ),
        'startLine' => 51,
        'endLine' => 51,
        'startColumn' => 5,
        'endColumn' => 42,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'defaultSkillsPath' => 
      array (
        'declaringClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'implementingClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'name' => 'defaultSkillsPath',
        'modifiers' => 2,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '\'.ai/skills\'',
          'attributes' => 
          array (
            'startLine' => 53,
            'endLine' => 53,
            'startTokenPos' => 199,
            'startFilePos' => 1560,
            'endTokenPos' => 199,
            'endFilePos' => 1571,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 53,
        'endLine' => 53,
        'startColumn' => 5,
        'endColumn' => 55,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'terminal' => 
      array (
        'declaringClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'implementingClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'name' => 'terminal',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Laravel\\Prompts\\Terminal',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 55,
        'endLine' => 55,
        'startColumn' => 33,
        'endColumn' => 67,
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
          'terminal' => 
          array (
            'name' => 'terminal',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Laravel\\Prompts\\Terminal',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 55,
            'endLine' => 55,
            'startColumn' => 33,
            'endColumn' => 67,
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
        'startLine' => 55,
        'endLine' => 58,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Laravel\\Boost\\Console',
        'declaringClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'implementingClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'currentClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'aliasName' => NULL,
      ),
      'handle' => 
      array (
        'name' => 'handle',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 60,
        'endLine' => 73,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Laravel\\Boost\\Console',
        'declaringClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'implementingClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'currentClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'aliasName' => NULL,
      ),
      'initializeRepository' => 
      array (
        'name' => 'initializeRepository',
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
        'startLine' => 75,
        'endLine' => 87,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Laravel\\Boost\\Console',
        'declaringClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'implementingClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'currentClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'aliasName' => NULL,
      ),
      'discoverAvailableSkills' => 
      array (
        'name' => 'discoverAvailableSkills',
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
        'startLine' => 89,
        'endLine' => 109,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Laravel\\Boost\\Console',
        'declaringClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'implementingClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'currentClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'aliasName' => NULL,
      ),
      'handleAction' => 
      array (
        'name' => 'handleAction',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 111,
        'endLine' => 118,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Laravel\\Boost\\Console',
        'declaringClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'implementingClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'currentClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'aliasName' => NULL,
      ),
      'parseRepository' => 
      array (
        'name' => 'parseRepository',
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
                  'name' => 'Laravel\\Boost\\Skills\\Remote\\GitHubRepository',
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
        'docComment' => NULL,
        'startLine' => 120,
        'endLine' => 140,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Laravel\\Boost\\Console',
        'declaringClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'implementingClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'currentClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'aliasName' => NULL,
      ),
      'displayHeader' => 
      array (
        'name' => 'displayHeader',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 142,
        'endLine' => 146,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Laravel\\Boost\\Console',
        'declaringClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'implementingClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'currentClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'aliasName' => NULL,
      ),
      'displaySkillsTable' => 
      array (
        'name' => 'displaySkillsTable',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 148,
        'endLine' => 155,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Laravel\\Boost\\Console',
        'declaringClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'implementingClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'currentClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'aliasName' => NULL,
      ),
      'installSkills' => 
      array (
        'name' => 'installSkills',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 157,
        'endLine' => 195,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Laravel\\Boost\\Console',
        'declaringClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'implementingClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'currentClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'aliasName' => NULL,
      ),
      'selectSkills' => 
      array (
        'name' => 'selectSkills',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Support\\Collection',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return Collection<string, RemoteSkill>
 */',
        'startLine' => 200,
        'endLine' => 225,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Laravel\\Boost\\Console',
        'declaringClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'implementingClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'currentClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'aliasName' => NULL,
      ),
      'skillsToInstall' => 
      array (
        'name' => 'skillsToInstall',
        'parameters' => 
        array (
          'skills' => 
          array (
            'name' => 'skills',
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
            'startLine' => 230,
            'endLine' => 230,
            'startColumn' => 40,
            'endColumn' => 57,
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
            'name' => 'Illuminate\\Support\\Collection',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param  Collection<string, RemoteSkill>  $skills
 */',
        'startLine' => 230,
        'endLine' => 241,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Laravel\\Boost\\Console',
        'declaringClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'implementingClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'currentClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'aliasName' => NULL,
      ),
      'shouldUpdateExisting' => 
      array (
        'name' => 'shouldUpdateExisting',
        'parameters' => 
        array (
          'existingSkills' => 
          array (
            'name' => 'existingSkills',
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
            'startLine' => 246,
            'endLine' => 246,
            'startColumn' => 45,
            'endColumn' => 70,
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
        'docComment' => '/**
 * @param  Collection<string, RemoteSkill>  $existingSkills
 */',
        'startLine' => 246,
        'endLine' => 259,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Laravel\\Boost\\Console',
        'declaringClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'implementingClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'currentClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'aliasName' => NULL,
      ),
      'skillExists' => 
      array (
        'name' => 'skillExists',
        'parameters' => 
        array (
          'skill' => 
          array (
            'name' => 'skill',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Laravel\\Boost\\Skills\\Remote\\RemoteSkill',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 261,
            'endLine' => 261,
            'startColumn' => 36,
            'endColumn' => 53,
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
        'startLine' => 261,
        'endLine' => 264,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Laravel\\Boost\\Console',
        'declaringClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'implementingClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'currentClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'aliasName' => NULL,
      ),
      'skillTargetPath' => 
      array (
        'name' => 'skillTargetPath',
        'parameters' => 
        array (
          'skill' => 
          array (
            'name' => 'skill',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Laravel\\Boost\\Skills\\Remote\\RemoteSkill',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 266,
            'endLine' => 266,
            'startColumn' => 40,
            'endColumn' => 57,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 266,
        'endLine' => 269,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Laravel\\Boost\\Console',
        'declaringClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'implementingClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'currentClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'aliasName' => NULL,
      ),
      'downloadSkills' => 
      array (
        'name' => 'downloadSkills',
        'parameters' => 
        array (
          'skills' => 
          array (
            'name' => 'skills',
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
            'startLine' => 275,
            'endLine' => 275,
            'startColumn' => 39,
            'endColumn' => 56,
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
 * @param  Collection<string, RemoteSkill>  $skills
 * @return array{installedNames: array<int, string>, failedDetails: array<string, string>}
 */',
        'startLine' => 275,
        'endLine' => 281,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Laravel\\Boost\\Console',
        'declaringClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'implementingClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'currentClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'aliasName' => NULL,
      ),
      'addSkills' => 
      array (
        'name' => 'addSkills',
        'parameters' => 
        array (
          'skills' => 
          array (
            'name' => 'skills',
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
            'startLine' => 287,
            'endLine' => 287,
            'startColumn' => 34,
            'endColumn' => 51,
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
 * @param  Collection<string, RemoteSkill>  $skills
 * @return array{installedNames: array<int, string>, failedDetails: array<string, string>}
 */',
        'startLine' => 287,
        'endLine' => 310,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Laravel\\Boost\\Console',
        'declaringClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'implementingClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'currentClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'aliasName' => NULL,
      ),
      'runAuditBeforeInstall' => 
      array (
        'name' => 'runAuditBeforeInstall',
        'parameters' => 
        array (
          'selectedSkills' => 
          array (
            'name' => 'selectedSkills',
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
            'startLine' => 315,
            'endLine' => 315,
            'startColumn' => 46,
            'endColumn' => 71,
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
        'docComment' => '/**
 * @param  Collection<string, RemoteSkill>  $selectedSkills
 */',
        'startLine' => 315,
        'endLine' => 343,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Laravel\\Boost\\Console',
        'declaringClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'implementingClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'currentClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'aliasName' => NULL,
      ),
      'displayAuditResults' => 
      array (
        'name' => 'displayAuditResults',
        'parameters' => 
        array (
          'auditResults' => 
          array (
            'name' => 'auditResults',
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
            'startLine' => 349,
            'endLine' => 349,
            'startColumn' => 44,
            'endColumn' => 62,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'skillNames' => 
          array (
            'name' => 'skillNames',
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
            'startLine' => 349,
            'endLine' => 349,
            'startColumn' => 65,
            'endColumn' => 81,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param  array<string, array<int, AuditResult>>  $auditResults
 * @param  array<int, string>  $skillNames
 */',
        'startLine' => 349,
        'endLine' => 378,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Laravel\\Boost\\Console',
        'declaringClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'implementingClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'currentClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'aliasName' => NULL,
      ),
      'hasRiskySkills' => 
      array (
        'name' => 'hasRiskySkills',
        'parameters' => 
        array (
          'auditResults' => 
          array (
            'name' => 'auditResults',
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
            'startLine' => 383,
            'endLine' => 383,
            'startColumn' => 39,
            'endColumn' => 57,
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
        'docComment' => '/**
 * @param  array<string, array<int, AuditResult>>  $auditResults
 */',
        'startLine' => 383,
        'endLine' => 388,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Laravel\\Boost\\Console',
        'declaringClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'implementingClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'currentClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'aliasName' => NULL,
      ),
      'colorizeRisk' => 
      array (
        'name' => 'colorizeRisk',
        'parameters' => 
        array (
          'result' => 
          array (
            'name' => 'result',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Laravel\\Boost\\Skills\\Remote\\AuditResult',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 390,
            'endLine' => 390,
            'startColumn' => 37,
            'endColumn' => 55,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 390,
        'endLine' => 398,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Laravel\\Boost\\Console',
        'declaringClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'implementingClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'currentClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'aliasName' => NULL,
      ),
      'runBoostUpdate' => 
      array (
        'name' => 'runBoostUpdate',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 400,
        'endLine' => 403,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Laravel\\Boost\\Console',
        'declaringClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'implementingClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'currentClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'aliasName' => NULL,
      ),
      'showOutro' => 
      array (
        'name' => 'showOutro',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 405,
        'endLine' => 408,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Laravel\\Boost\\Console',
        'declaringClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'implementingClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
        'currentClassName' => 'Laravel\\Boost\\Console\\AddSkillCommand',
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