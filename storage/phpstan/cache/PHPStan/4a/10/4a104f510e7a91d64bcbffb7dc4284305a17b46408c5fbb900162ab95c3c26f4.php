<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Support\AccountActivity.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Support\AccountActivity
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-59b906cba47af0e3179b2bf7ee55f93ac38af5652597bfa7f3af260c8c07eb70',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Support\\AccountActivity',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Support/AccountActivity.php',
      ),
    ),
    'namespace' => 'App\\Support',
    'name' => 'App\\Support\\AccountActivity',
    'shortName' => 'AccountActivity',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Rôles et activité d\'un compte **dérivés** (jamais stockés) à travers toutes
 * les événements, par rapprochement d\'e-mail (insensible à la casse) :
 * communications (auteur/co-auteur), sessions modérées/rapportées, comités.
 * Le rôle affiché n\'est donc pas figé par participation mais reflète l\'ensemble
 * de l\'activité de la personne.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 19,
    'endLine' => 148,
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
      'account' => 
      array (
        'declaringClassName' => 'App\\Support\\AccountActivity',
        'implementingClassName' => 'App\\Support\\AccountActivity',
        'name' => 'account',
        'modifiers' => 1,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'App\\Models\\User',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 21,
        'endLine' => 21,
        'startColumn' => 33,
        'endColumn' => 52,
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
          'account' => 
          array (
            'name' => 'account',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\User',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 21,
            'endLine' => 21,
            'startColumn' => 33,
            'endColumn' => 52,
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
        'startLine' => 21,
        'endLine' => 21,
        'startColumn' => 5,
        'endColumn' => 56,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\AccountActivity',
        'implementingClassName' => 'App\\Support\\AccountActivity',
        'currentClassName' => 'App\\Support\\AccountActivity',
        'aliasName' => NULL,
      ),
      'email' => 
      array (
        'name' => 'email',
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
        'startLine' => 23,
        'endLine' => 26,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\AccountActivity',
        'implementingClassName' => 'App\\Support\\AccountActivity',
        'currentClassName' => 'App\\Support\\AccountActivity',
        'aliasName' => NULL,
      ),
      'participantIds' => 
      array (
        'name' => 'participantIds',
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
        'docComment' => '/** Identifiants des participations rattachées au compte. */',
        'startLine' => 29,
        'endLine' => 32,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\AccountActivity',
        'implementingClassName' => 'App\\Support\\AccountActivity',
        'currentClassName' => 'App\\Support\\AccountActivity',
        'aliasName' => NULL,
      ),
      'communications' => 
      array (
        'name' => 'communications',
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
 * Communications où le compte est auteur correspondant ou co-auteur.
 *
 * @return Collection<int, array{submission:Submission, corresponding:bool}>
 */',
        'startLine' => 39,
        'endLine' => 60,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\AccountActivity',
        'implementingClassName' => 'App\\Support\\AccountActivity',
        'currentClassName' => 'App\\Support\\AccountActivity',
        'aliasName' => NULL,
      ),
      'emailInAuthors' => 
      array (
        'name' => 'emailInAuthors',
        'parameters' => 
        array (
          'submission' => 
          array (
            'name' => 'submission',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Submission',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 62,
            'endLine' => 62,
            'startColumn' => 39,
            'endColumn' => 60,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'email' => 
          array (
            'name' => 'email',
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
            'startLine' => 62,
            'endLine' => 62,
            'startColumn' => 63,
            'endColumn' => 75,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 62,
        'endLine' => 71,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\AccountActivity',
        'implementingClassName' => 'App\\Support\\AccountActivity',
        'currentClassName' => 'App\\Support\\AccountActivity',
        'aliasName' => NULL,
      ),
      'moderatedSessions' => 
      array (
        'name' => 'moderatedSessions',
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
        'docComment' => '/** @return Collection<int, EventSession> */',
        'startLine' => 74,
        'endLine' => 78,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\AccountActivity',
        'implementingClassName' => 'App\\Support\\AccountActivity',
        'currentClassName' => 'App\\Support\\AccountActivity',
        'aliasName' => NULL,
      ),
      'reportedSessions' => 
      array (
        'name' => 'reportedSessions',
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
        'docComment' => '/** @return Collection<int, EventSession> */',
        'startLine' => 81,
        'endLine' => 85,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\AccountActivity',
        'implementingClassName' => 'App\\Support\\AccountActivity',
        'currentClassName' => 'App\\Support\\AccountActivity',
        'aliasName' => NULL,
      ),
      'committees' => 
      array (
        'name' => 'committees',
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
        'docComment' => '/** @return Collection<int, CommitteeMember> */',
        'startLine' => 88,
        'endLine' => 102,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\AccountActivity',
        'implementingClassName' => 'App\\Support\\AccountActivity',
        'currentClassName' => 'App\\Support\\AccountActivity',
        'aliasName' => NULL,
      ),
      'isReviewer' => 
      array (
        'name' => 'isReviewer',
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
        'docComment' => '/** Le compte a-t-il au moins une participation en tant que relecteur ? */',
        'startLine' => 105,
        'endLine' => 109,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\AccountActivity',
        'implementingClassName' => 'App\\Support\\AccountActivity',
        'currentClassName' => 'App\\Support\\AccountActivity',
        'aliasName' => NULL,
      ),
      'hasAnyParticipation' => 
      array (
        'name' => 'hasAnyParticipation',
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
        'docComment' => '/** Le compte a-t-il au moins une participation, quelle qu\'elle soit ? */',
        'startLine' => 112,
        'endLine' => 115,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\AccountActivity',
        'implementingClassName' => 'App\\Support\\AccountActivity',
        'currentClassName' => 'App\\Support\\AccountActivity',
        'aliasName' => NULL,
      ),
      'roles' => 
      array (
        'name' => 'roles',
        'parameters' => 
        array (
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
 * Rôles dérivés (libellés), agrégés sur tous les événements.
 *
 * @return list<string>
 */',
        'startLine' => 122,
        'endLine' => 147,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\AccountActivity',
        'implementingClassName' => 'App\\Support\\AccountActivity',
        'currentClassName' => 'App\\Support\\AccountActivity',
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