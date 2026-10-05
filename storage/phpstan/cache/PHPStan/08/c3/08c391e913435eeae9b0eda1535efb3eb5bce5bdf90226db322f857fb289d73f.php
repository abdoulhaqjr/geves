<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Meetings\MeetableRegistry.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Meetings\MeetableRegistry
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-126b6f0905be1d07098faab65321f5fe8366c23ad317194b78892552c4480069',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Meetings\\MeetableRegistry',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Meetings/MeetableRegistry.php',
      ),
    ),
    'namespace' => 'App\\Meetings',
    'name' => 'App\\Meetings\\MeetableRegistry',
    'shortName' => 'MeetableRegistry',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Objets dont la salle de visio est servie par une adresse générique
 * (`/visio/{type}/{id}/…`) : fil de discussion, renouvellement de jeton.
 *
 * ⚠️ Liste **fermée**. Le type vient de l\'URL, et on ne résout jamais une classe
 * à partir de ce que le client a écrit. C\'est aussi le seul endroit où le
 * vocabulaire (« session », « formation ») est défini : il sert dans les deux
 * sens - l\'URL vers l\'objet, et l\'objet vers l\'URL.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 19,
    'endLine' => 66,
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
      'TYPES' => 
      array (
        'declaringClassName' => 'App\\Meetings\\MeetableRegistry',
        'implementingClassName' => 'App\\Meetings\\MeetableRegistry',
        'name' => 'TYPES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'session\' => \\App\\Models\\EventSession::class, \'formation\' => \\App\\Models\\Formation::class]',
          'attributes' => 
          array (
            'startLine' => 22,
            'endLine' => 25,
            'startTokenPos' => 45,
            'startFilePos' => 737,
            'endTokenPos' => 65,
            'endFilePos' => 826,
          ),
        ),
        'docComment' => '/** @var array<string, class-string<Meetable&Model>> */',
        'attributes' => 
        array (
        ),
        'startLine' => 22,
        'endLine' => 25,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'find' => 
      array (
        'name' => 'find',
        'parameters' => 
        array (
          'type' => 
          array (
            'name' => 'type',
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
            'startLine' => 28,
            'endLine' => 28,
            'startColumn' => 33,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'id' => 
          array (
            'name' => 'id',
            'default' => NULL,
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
                      'name' => 'int',
                      'isIdentifier' => true,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'string',
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
            'startLine' => 28,
            'endLine' => 28,
            'startColumn' => 47,
            'endColumn' => 60,
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
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionIntersectionType',
                'data' => 
                array (
                  'types' => 
                  array (
                    0 => 
                    array (
                      'name' => 'App\\Meetings\\Contracts\\Meetable',
                      'isIdentifier' => false,
                    ),
                    1 => 
                    array (
                      'name' => 'Illuminate\\Database\\Eloquent\\Model',
                      'isIdentifier' => false,
                    ),
                  ),
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
        'docComment' => '/** L\'objet visé, ou null si le type est inconnu ou l\'objet absent. */',
        'startLine' => 28,
        'endLine' => 39,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Meetings',
        'declaringClassName' => 'App\\Meetings\\MeetableRegistry',
        'implementingClassName' => 'App\\Meetings\\MeetableRegistry',
        'currentClassName' => 'App\\Meetings\\MeetableRegistry',
        'aliasName' => NULL,
      ),
      'tabFor' => 
      array (
        'name' => 'tabFor',
        'parameters' => 
        array (
          'objet' => 
          array (
            'name' => 'objet',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionIntersectionType',
              'data' => 
              array (
                'types' => 
                array (
                  0 => 
                  array (
                    'name' => 'App\\Meetings\\Contracts\\Meetable',
                    'isIdentifier' => false,
                  ),
                  1 => 
                  array (
                    'name' => 'Illuminate\\Database\\Eloquent\\Model',
                    'isIdentifier' => false,
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
            'startLine' => 52,
            'endLine' => 52,
            'startColumn' => 35,
            'endColumn' => 55,
            'parameterIndex' => 0,
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
 * Nom de l\'ONGLET qui tient la salle de cet objet, ou null s\'il n\'en a pas.
 *
 * ⚠️ La salle se nomme elle-même avec cette chaîne (`window.name`), et les
 * pages qui ramènent vers elle la posent en `target` : le navigateur
 * **réutilise** alors l\'onglet déjà ouvert au lieu d\'en créer un second -
 * deux onglets sur la même salle, c\'est deux connexions, deux vignettes et
 * de l\'écho. D\'où une **définition unique** : deux chaînes construites
 * chacune de leur côté se décaleraient un jour, et l\'onglet cesserait
 * d\'être retrouvé sans que rien ne casse visiblement.
 */',
        'startLine' => 52,
        'endLine' => 57,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Meetings',
        'declaringClassName' => 'App\\Meetings\\MeetableRegistry',
        'implementingClassName' => 'App\\Meetings\\MeetableRegistry',
        'currentClassName' => 'App\\Meetings\\MeetableRegistry',
        'aliasName' => NULL,
      ),
      'keyFor' => 
      array (
        'name' => 'keyFor',
        'parameters' => 
        array (
          'objet' => 
          array (
            'name' => 'objet',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionIntersectionType',
              'data' => 
              array (
                'types' => 
                array (
                  0 => 
                  array (
                    'name' => 'App\\Meetings\\Contracts\\Meetable',
                    'isIdentifier' => false,
                  ),
                  1 => 
                  array (
                    'name' => 'Illuminate\\Database\\Eloquent\\Model',
                    'isIdentifier' => false,
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
            'startLine' => 60,
            'endLine' => 60,
            'startColumn' => 35,
            'endColumn' => 55,
            'parameterIndex' => 0,
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
        'docComment' => '/** Clé d\'URL de cet objet (« session », « formation »), ou null s\'il n\'en a pas. */',
        'startLine' => 60,
        'endLine' => 65,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Meetings',
        'declaringClassName' => 'App\\Meetings\\MeetableRegistry',
        'implementingClassName' => 'App\\Meetings\\MeetableRegistry',
        'currentClassName' => 'App\\Meetings\\MeetableRegistry',
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