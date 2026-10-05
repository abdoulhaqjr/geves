<?php declare(strict_types = 1);

// osfsl-C:/Users/TOURE/Herd/gesco/vendor/composer/../lab404/laravel-impersonate/src/Services/ImpersonateManager.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Lab404\Impersonate\Services\ImpersonateManager
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-136e8127d20367516e8f0fff234e0d718676d983553d46bb8f1c85a1426fb718-8.4.24-6.70.0.3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'filename' => 'C:/Users/TOURE/Herd/gesco/vendor/composer/../lab404/laravel-impersonate/src/Services/ImpersonateManager.php',
      ),
    ),
    'namespace' => 'Lab404\\Impersonate\\Services',
    'name' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
    'shortName' => 'ImpersonateManager',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 15,
    'endLine' => 266,
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
      'REMEMBER_PREFIX' => 
      array (
        'declaringClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'implementingClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'name' => 'REMEMBER_PREFIX',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'remember_web\'',
          'attributes' => 
          array (
            'startLine' => 17,
            'endLine' => 17,
            'startTokenPos' => 64,
            'startFilePos' => 504,
            'endTokenPos' => 64,
            'endFilePos' => 517,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 17,
        'endLine' => 17,
        'startColumn' => 5,
        'endColumn' => 43,
      ),
    ),
    'immediateProperties' => 
    array (
      'app' => 
      array (
        'declaringClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'implementingClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'name' => 'app',
        'modifiers' => 4,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/** @var Application $app */',
        'attributes' => 
        array (
        ),
        'startLine' => 20,
        'endLine' => 20,
        'startColumn' => 5,
        'endColumn' => 17,
        'isPromoted' => false,
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
          'app' => 
          array (
            'name' => 'app',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Foundation\\Application',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 22,
            'endLine' => 22,
            'startColumn' => 33,
            'endColumn' => 48,
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
        'startLine' => 22,
        'endLine' => 25,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Lab404\\Impersonate\\Services',
        'declaringClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'implementingClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'currentClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'aliasName' => NULL,
      ),
      'findUserById' => 
      array (
        'name' => 'findUserById',
        'parameters' => 
        array (
          'id' => 
          array (
            'name' => 'id',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 34,
            'endLine' => 34,
            'startColumn' => 34,
            'endColumn' => 36,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'guardName' => 
          array (
            'name' => 'guardName',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 34,
                'endLine' => 34,
                'startTokenPos' => 113,
                'startFilePos' => 918,
                'endTokenPos' => 113,
                'endFilePos' => 921,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 34,
            'endLine' => 34,
            'startColumn' => 39,
            'endColumn' => 55,
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
 * @param int $id
 * @return \\Illuminate\\Contracts\\Auth\\Authenticatable
 * @throws MissingUserProvider
 * @throws InvalidUserProvider
 * @throws ModelNotFoundException
 */',
        'startLine' => 34,
        'endLine' => 63,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Lab404\\Impersonate\\Services',
        'declaringClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'implementingClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'currentClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'aliasName' => NULL,
      ),
      'isImpersonating' => 
      array (
        'name' => 'isImpersonating',
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
        'startLine' => 65,
        'endLine' => 68,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Lab404\\Impersonate\\Services',
        'declaringClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'implementingClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'currentClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'aliasName' => NULL,
      ),
      'getImpersonatorId' => 
      array (
        'name' => 'getImpersonatorId',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return  int|null
 */',
        'startLine' => 73,
        'endLine' => 76,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Lab404\\Impersonate\\Services',
        'declaringClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'implementingClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'currentClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'aliasName' => NULL,
      ),
      'getImpersonator' => 
      array (
        'name' => 'getImpersonator',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return \\Illuminate\\Contracts\\Auth\\Authenticatable
 */',
        'startLine' => 81,
        'endLine' => 86,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Lab404\\Impersonate\\Services',
        'declaringClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'implementingClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'currentClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'aliasName' => NULL,
      ),
      'getImpersonatorGuardName' => 
      array (
        'name' => 'getImpersonatorGuardName',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return string|null
 */',
        'startLine' => 91,
        'endLine' => 94,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Lab404\\Impersonate\\Services',
        'declaringClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'implementingClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'currentClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'aliasName' => NULL,
      ),
      'getImpersonatorGuardUsingName' => 
      array (
        'name' => 'getImpersonatorGuardUsingName',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return string|null
 */',
        'startLine' => 99,
        'endLine' => 102,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Lab404\\Impersonate\\Services',
        'declaringClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'implementingClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'currentClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'aliasName' => NULL,
      ),
      'take' => 
      array (
        'name' => 'take',
        'parameters' => 
        array (
          'from' => 
          array (
            'name' => 'from',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 110,
            'endLine' => 110,
            'startColumn' => 26,
            'endColumn' => 30,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'to' => 
          array (
            'name' => 'to',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 110,
            'endLine' => 110,
            'startColumn' => 33,
            'endColumn' => 35,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'guardName' => 
          array (
            'name' => 'guardName',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 110,
                'endLine' => 110,
                'startTokenPos' => 508,
                'startFilePos' => 2966,
                'endTokenPos' => 508,
                'endFilePos' => 2969,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 110,
            'endLine' => 110,
            'startColumn' => 38,
            'endColumn' => 54,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param \\Illuminate\\Contracts\\Auth\\Authenticatable $from
 * @param \\Illuminate\\Contracts\\Auth\\Authenticatable $to
 * @param string|null                         $guardName
 * @return bool
 */',
        'startLine' => 110,
        'endLine' => 131,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Lab404\\Impersonate\\Services',
        'declaringClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'implementingClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'currentClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'aliasName' => NULL,
      ),
      'leave' => 
      array (
        'name' => 'leave',
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
        'startLine' => 133,
        'endLine' => 154,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Lab404\\Impersonate\\Services',
        'declaringClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'implementingClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'currentClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'aliasName' => NULL,
      ),
      'clear' => 
      array (
        'name' => 'clear',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 156,
        'endLine' => 161,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Lab404\\Impersonate\\Services',
        'declaringClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'implementingClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'currentClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'aliasName' => NULL,
      ),
      'getSessionKey' => 
      array (
        'name' => 'getSessionKey',
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
        'startLine' => 163,
        'endLine' => 166,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Lab404\\Impersonate\\Services',
        'declaringClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'implementingClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'currentClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'aliasName' => NULL,
      ),
      'getSessionGuard' => 
      array (
        'name' => 'getSessionGuard',
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
        'startLine' => 168,
        'endLine' => 171,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Lab404\\Impersonate\\Services',
        'declaringClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'implementingClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'currentClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'aliasName' => NULL,
      ),
      'getSessionGuardUsing' => 
      array (
        'name' => 'getSessionGuardUsing',
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
        'startLine' => 173,
        'endLine' => 176,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Lab404\\Impersonate\\Services',
        'declaringClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'implementingClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'currentClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'aliasName' => NULL,
      ),
      'getDefaultSessionGuard' => 
      array (
        'name' => 'getDefaultSessionGuard',
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
        'startLine' => 178,
        'endLine' => 181,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Lab404\\Impersonate\\Services',
        'declaringClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'implementingClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'currentClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'aliasName' => NULL,
      ),
      'getTakeRedirectTo' => 
      array (
        'name' => 'getTakeRedirectTo',
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
        'startLine' => 183,
        'endLine' => 192,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Lab404\\Impersonate\\Services',
        'declaringClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'implementingClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'currentClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'aliasName' => NULL,
      ),
      'getLeaveRedirectTo' => 
      array (
        'name' => 'getLeaveRedirectTo',
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
        'startLine' => 194,
        'endLine' => 210,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Lab404\\Impersonate\\Services',
        'declaringClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'implementingClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'currentClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'aliasName' => NULL,
      ),
      'getCurrentAuthGuardName' => 
      array (
        'name' => 'getCurrentAuthGuardName',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return array|null
 */',
        'startLine' => 215,
        'endLine' => 226,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Lab404\\Impersonate\\Services',
        'declaringClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'implementingClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'currentClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'aliasName' => NULL,
      ),
      'saveAuthCookieInSession' => 
      array (
        'name' => 'saveAuthCookieInSession',
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
        'startLine' => 228,
        'endLine' => 242,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Lab404\\Impersonate\\Services',
        'declaringClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'implementingClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'currentClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'aliasName' => NULL,
      ),
      'extractAuthCookieFromSession' => 
      array (
        'name' => 'extractAuthCookieFromSession',
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
        'startLine' => 244,
        'endLine' => 252,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Lab404\\Impersonate\\Services',
        'declaringClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'implementingClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'currentClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'aliasName' => NULL,
      ),
      'findByKeyInArray' => 
      array (
        'name' => 'findByKeyInArray',
        'parameters' => 
        array (
          'values' => 
          array (
            'name' => 'values',
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
            'startLine' => 259,
            'endLine' => 259,
            'startColumn' => 41,
            'endColumn' => 53,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'search' => 
          array (
            'name' => 'search',
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
            'startLine' => 259,
            'endLine' => 259,
            'startColumn' => 56,
            'endColumn' => 69,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param array  $values
 * @param string $search
 * @return \\Illuminate\\Support\\Collection
 */',
        'startLine' => 259,
        'endLine' => 265,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Lab404\\Impersonate\\Services',
        'declaringClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'implementingClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
        'currentClassName' => 'Lab404\\Impersonate\\Services\\ImpersonateManager',
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