<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Http\Controllers\Account\ReviewWorkspaceController.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Http\Controllers\Account\ReviewWorkspaceController
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-f36ebee185c431764dee20a5316f8e4e0ed78e7808683306700ce82f8c7c416f',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Http\\Controllers\\Account\\ReviewWorkspaceController',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Http/Controllers/Account/ReviewWorkspaceController.php',
      ),
    ),
    'namespace' => 'App\\Http\\Controllers\\Account',
    'name' => 'App\\Http\\Controllers\\Account\\ReviewWorkspaceController',
    'shortName' => 'ReviewWorkspaceController',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Espace relecteur (côté participant) : « Mes relectures ». Regroupe les
 * relectures confiées à l\'utilisateur au titre de ses inscriptions « relecteur »
 * (par événement) et l\'évaluation se fait EN PLACE (panneau latéral, sans changer
 * de page), en aveugle. Un appel à candidatures (préférences + conflits) est
 * proposé pour les communications encore ouvertes. Remplace l\'ancien portail à
 * jeton public.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 29,
    'endLine' => 320,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'App\\Http\\Controllers\\Controller',
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
      'index' => 
      array (
        'name' => 'index',
        'parameters' => 
        array (
          'request' => 
          array (
            'name' => 'request',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Http\\Request',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 31,
            'endLine' => 31,
            'startColumn' => 27,
            'endColumn' => 42,
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
            'name' => 'Illuminate\\View\\View',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 31,
        'endLine' => 152,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Controllers\\Account',
        'declaringClassName' => 'App\\Http\\Controllers\\Account\\ReviewWorkspaceController',
        'implementingClassName' => 'App\\Http\\Controllers\\Account\\ReviewWorkspaceController',
        'currentClassName' => 'App\\Http\\Controllers\\Account\\ReviewWorkspaceController',
        'aliasName' => NULL,
      ),
      'respond' => 
      array (
        'name' => 'respond',
        'parameters' => 
        array (
          'request' => 
          array (
            'name' => 'request',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Http\\Request',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 160,
            'endLine' => 160,
            'startColumn' => 29,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'review' => 
          array (
            'name' => 'review',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Review',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 160,
            'endLine' => 160,
            'startColumn' => 47,
            'endColumn' => 60,
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
            'name' => 'Illuminate\\Http\\RedirectResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Réponse du relecteur à une invitation : accepter ou refuser (avec motif).
 * Un refus libère la place - la soumission réapparaît dans l\'aperçu
 * d\'attribution côté admin, sans remplacement automatique : c\'est le
 * comité qui décide qui solliciter ensuite.
 */',
        'startLine' => 160,
        'endLine' => 202,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Controllers\\Account',
        'declaringClassName' => 'App\\Http\\Controllers\\Account\\ReviewWorkspaceController',
        'implementingClassName' => 'App\\Http\\Controllers\\Account\\ReviewWorkspaceController',
        'currentClassName' => 'App\\Http\\Controllers\\Account\\ReviewWorkspaceController',
        'aliasName' => NULL,
      ),
      'submit' => 
      array (
        'name' => 'submit',
        'parameters' => 
        array (
          'request' => 
          array (
            'name' => 'request',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Http\\Request',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 205,
            'endLine' => 205,
            'startColumn' => 28,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'review' => 
          array (
            'name' => 'review',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Review',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 205,
            'endLine' => 205,
            'startColumn' => 46,
            'endColumn' => 59,
            'parameterIndex' => 1,
            'isOptional' => false,
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
                  'name' => 'Illuminate\\Http\\JsonResponse',
                  'isIdentifier' => false,
                ),
              ),
              1 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'Illuminate\\Http\\RedirectResponse',
                  'isIdentifier' => false,
                ),
              ),
            ),
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Enregistre l\'évaluation en place (AJAX ou repli formulaire classique). */',
        'startLine' => 205,
        'endLine' => 254,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Controllers\\Account',
        'declaringClassName' => 'App\\Http\\Controllers\\Account\\ReviewWorkspaceController',
        'implementingClassName' => 'App\\Http\\Controllers\\Account\\ReviewWorkspaceController',
        'currentClassName' => 'App\\Http\\Controllers\\Account\\ReviewWorkspaceController',
        'aliasName' => NULL,
      ),
      'saveBids' => 
      array (
        'name' => 'saveBids',
        'parameters' => 
        array (
          'request' => 
          array (
            'name' => 'request',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Http\\Request',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 257,
            'endLine' => 257,
            'startColumn' => 30,
            'endColumn' => 45,
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
            'name' => 'Illuminate\\Http\\RedirectResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Enregistre les candidatures (préférences + conflits) du relecteur. */',
        'startLine' => 257,
        'endLine' => 295,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Controllers\\Account',
        'declaringClassName' => 'App\\Http\\Controllers\\Account\\ReviewWorkspaceController',
        'implementingClassName' => 'App\\Http\\Controllers\\Account\\ReviewWorkspaceController',
        'currentClassName' => 'App\\Http\\Controllers\\Account\\ReviewWorkspaceController',
        'aliasName' => NULL,
      ),
      'reviewerParticipants' => 
      array (
        'name' => 'reviewerParticipants',
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
 * Inscriptions « relecteur » de l\'utilisateur connecté (une par événement).
 *
 * @return Collection<int, Participant>
 */',
        'startLine' => 302,
        'endLine' => 310,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Http\\Controllers\\Account',
        'declaringClassName' => 'App\\Http\\Controllers\\Account\\ReviewWorkspaceController',
        'implementingClassName' => 'App\\Http\\Controllers\\Account\\ReviewWorkspaceController',
        'currentClassName' => 'App\\Http\\Controllers\\Account\\ReviewWorkspaceController',
        'aliasName' => NULL,
      ),
      'authorizeReview' => 
      array (
        'name' => 'authorizeReview',
        'parameters' => 
        array (
          'review' => 
          array (
            'name' => 'review',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Review',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 313,
            'endLine' => 313,
            'startColumn' => 38,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** La relecture appartient-elle bien à une inscription relecteur du compte ? */',
        'startLine' => 313,
        'endLine' => 319,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Http\\Controllers\\Account',
        'declaringClassName' => 'App\\Http\\Controllers\\Account\\ReviewWorkspaceController',
        'implementingClassName' => 'App\\Http\\Controllers\\Account\\ReviewWorkspaceController',
        'currentClassName' => 'App\\Http\\Controllers\\Account\\ReviewWorkspaceController',
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