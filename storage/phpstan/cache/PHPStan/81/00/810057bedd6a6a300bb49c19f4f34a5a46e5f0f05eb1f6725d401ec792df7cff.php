<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Support\SessionDocLayout.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Support\SessionDocLayout
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-15bc4472cd9445af8d1ca7cefaafdcf088758f8faadc43a34e2c56fb9b113ebc',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Support\\SessionDocLayout',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Support/SessionDocLayout.php',
      ),
    ),
    'namespace' => 'App\\Support',
    'name' => 'App\\Support\\SessionDocLayout',
    'shortName' => 'SessionDocLayout',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Mise en page (entêtes + colonnes) des documents de séance - fiches de session
 * et PV (procès-verbal / rapport de séance) - configurable PAR ÉDITION via
 * {@see EventSettings}. Catalogue de champs/colonnes disponibles + résolution
 * de la sélection (avec libellés personnalisables et repli sur les valeurs par
 * défaut). Éditable par l\'admin ou le secrétariat.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 16,
    'endLine' => 153,
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
      'COLUMNS' => 
      array (
        'declaringClassName' => 'App\\Support\\SessionDocLayout',
        'implementingClassName' => 'App\\Support\\SessionDocLayout',
        'name' => 'COLUMNS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'horaire\' => \'Horaire\', \'titre\' => \'Titre\', \'auteurs\' => \'Auteur(s)\', \'reference\' => \'Référence\', \'type\' => \'Type\', \'duree\' => \'Durée\', \'emargement\' => \'Émargement\']',
          'attributes' => 
          array (
            'startLine' => 19,
            'endLine' => 27,
            'startTokenPos' => 40,
            'startFilePos' => 611,
            'endTokenPos' => 91,
            'endFilePos' => 843,
          ),
        ),
        'docComment' => '/** Colonnes disponibles pour le tableau des communications. */',
        'attributes' => 
        array (
        ),
        'startLine' => 19,
        'endLine' => 27,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
      'HEADER_FIELDS' => 
      array (
        'declaringClassName' => 'App\\Support\\SessionDocLayout',
        'implementingClassName' => 'App\\Support\\SessionDocLayout',
        'name' => 'HEADER_FIELDS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'date\' => \'Date\', \'horaire\' => \'Horaire\', \'salle\' => \'Salle\', \'atelier\' => \'Atelier\', \'moderator\' => \'Modérateur\', \'rapporteur\' => \'Rapporteur\']',
          'attributes' => 
          array (
            'startLine' => 30,
            'endLine' => 37,
            'startTokenPos' => 104,
            'startFilePos' => 940,
            'endTokenPos' => 148,
            'endFilePos' => 1140,
          ),
        ),
        'docComment' => '/** Champs disponibles pour l\'entête d\'une séance. */',
        'attributes' => 
        array (
        ),
        'startLine' => 30,
        'endLine' => 37,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
      'PV_SECTIONS' => 
      array (
        'declaringClassName' => 'App\\Support\\SessionDocLayout',
        'implementingClassName' => 'App\\Support\\SessionDocLayout',
        'name' => 'PV_SECTIONS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'synthese\' => \'Synthèse\', \'points_saillants\' => \'Points saillants & recommandations\', \'presents\' => \'Affluence estimée\']',
          'attributes' => 
          array (
            'startLine' => 40,
            'endLine' => 44,
            'startTokenPos' => 161,
            'startFilePos' => 1253,
            'endTokenPos' => 184,
            'endFilePos' => 1406,
          ),
        ),
        'docComment' => '/** Sections disponibles pour le PV (issues du rapport du rapporteur). */',
        'attributes' => 
        array (
        ),
        'startLine' => 40,
        'endLine' => 44,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
      'SHEET_DEFAULT_COLUMNS' => 
      array (
        'declaringClassName' => 'App\\Support\\SessionDocLayout',
        'implementingClassName' => 'App\\Support\\SessionDocLayout',
        'name' => 'SHEET_DEFAULT_COLUMNS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'horaire\', \'titre\', \'auteurs\', \'reference\']',
          'attributes' => 
          array (
            'startLine' => 47,
            'endLine' => 47,
            'startTokenPos' => 197,
            'startFilePos' => 1480,
            'endTokenPos' => 208,
            'endFilePos' => 1523,
          ),
        ),
        'docComment' => '/** @var list<string> */',
        'attributes' => 
        array (
        ),
        'startLine' => 47,
        'endLine' => 47,
        'startColumn' => 5,
        'endColumn' => 86,
      ),
      'SHEET_DEFAULT_HEADER' => 
      array (
        'declaringClassName' => 'App\\Support\\SessionDocLayout',
        'implementingClassName' => 'App\\Support\\SessionDocLayout',
        'name' => 'SHEET_DEFAULT_HEADER',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'date\', \'horaire\', \'salle\', \'moderator\', \'rapporteur\']',
          'attributes' => 
          array (
            'startLine' => 50,
            'endLine' => 50,
            'startTokenPos' => 221,
            'startFilePos' => 1596,
            'endTokenPos' => 235,
            'endFilePos' => 1650,
          ),
        ),
        'docComment' => '/** @var list<string> */',
        'attributes' => 
        array (
        ),
        'startLine' => 50,
        'endLine' => 50,
        'startColumn' => 5,
        'endColumn' => 96,
      ),
      'PV_DEFAULT_COLUMNS' => 
      array (
        'declaringClassName' => 'App\\Support\\SessionDocLayout',
        'implementingClassName' => 'App\\Support\\SessionDocLayout',
        'name' => 'PV_DEFAULT_COLUMNS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'titre\', \'auteurs\']',
          'attributes' => 
          array (
            'startLine' => 53,
            'endLine' => 53,
            'startTokenPos' => 248,
            'startFilePos' => 1721,
            'endTokenPos' => 253,
            'endFilePos' => 1740,
          ),
        ),
        'docComment' => '/** @var list<string> */',
        'attributes' => 
        array (
        ),
        'startLine' => 53,
        'endLine' => 53,
        'startColumn' => 5,
        'endColumn' => 59,
      ),
      'PV_DEFAULT_HEADER' => 
      array (
        'declaringClassName' => 'App\\Support\\SessionDocLayout',
        'implementingClassName' => 'App\\Support\\SessionDocLayout',
        'name' => 'PV_DEFAULT_HEADER',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'date\', \'salle\', \'moderator\', \'rapporteur\']',
          'attributes' => 
          array (
            'startLine' => 56,
            'endLine' => 56,
            'startTokenPos' => 266,
            'startFilePos' => 1810,
            'endTokenPos' => 277,
            'endFilePos' => 1853,
          ),
        ),
        'docComment' => '/** @var list<string> */',
        'attributes' => 
        array (
        ),
        'startLine' => 56,
        'endLine' => 56,
        'startColumn' => 5,
        'endColumn' => 82,
      ),
      'PV_DEFAULT_SECTIONS' => 
      array (
        'declaringClassName' => 'App\\Support\\SessionDocLayout',
        'implementingClassName' => 'App\\Support\\SessionDocLayout',
        'name' => 'PV_DEFAULT_SECTIONS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'synthese\', \'points_saillants\', \'presents\']',
          'attributes' => 
          array (
            'startLine' => 59,
            'endLine' => 59,
            'startTokenPos' => 290,
            'startFilePos' => 1925,
            'endTokenPos' => 298,
            'endFilePos' => 1968,
          ),
        ),
        'docComment' => '/** @var list<string> */',
        'attributes' => 
        array (
        ),
        'startLine' => 59,
        'endLine' => 59,
        'startColumn' => 5,
        'endColumn' => 84,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'sheetColumns' => 
      array (
        'name' => 'sheetColumns',
        'parameters' => 
        array (
          'event' => 
          array (
            'name' => 'event',
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
                      'name' => 'App\\Models\\Event',
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 62,
            'endLine' => 62,
            'startColumn' => 41,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** @return list<array{key: string, label: string}> */',
        'startLine' => 62,
        'endLine' => 65,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\SessionDocLayout',
        'implementingClassName' => 'App\\Support\\SessionDocLayout',
        'currentClassName' => 'App\\Support\\SessionDocLayout',
        'aliasName' => NULL,
      ),
      'sheetHeader' => 
      array (
        'name' => 'sheetHeader',
        'parameters' => 
        array (
          'event' => 
          array (
            'name' => 'event',
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
                      'name' => 'App\\Models\\Event',
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 68,
            'endLine' => 68,
            'startColumn' => 40,
            'endColumn' => 52,
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
        'docComment' => '/** @return list<array{key: string, label: string}> */',
        'startLine' => 68,
        'endLine' => 71,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\SessionDocLayout',
        'implementingClassName' => 'App\\Support\\SessionDocLayout',
        'currentClassName' => 'App\\Support\\SessionDocLayout',
        'aliasName' => NULL,
      ),
      'pvColumns' => 
      array (
        'name' => 'pvColumns',
        'parameters' => 
        array (
          'event' => 
          array (
            'name' => 'event',
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
                      'name' => 'App\\Models\\Event',
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 74,
            'endLine' => 74,
            'startColumn' => 38,
            'endColumn' => 50,
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
        'docComment' => '/** @return list<array{key: string, label: string}> */',
        'startLine' => 74,
        'endLine' => 77,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\SessionDocLayout',
        'implementingClassName' => 'App\\Support\\SessionDocLayout',
        'currentClassName' => 'App\\Support\\SessionDocLayout',
        'aliasName' => NULL,
      ),
      'pvHeader' => 
      array (
        'name' => 'pvHeader',
        'parameters' => 
        array (
          'event' => 
          array (
            'name' => 'event',
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
                      'name' => 'App\\Models\\Event',
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 80,
            'endLine' => 80,
            'startColumn' => 37,
            'endColumn' => 49,
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
        'docComment' => '/** @return list<array{key: string, label: string}> */',
        'startLine' => 80,
        'endLine' => 83,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\SessionDocLayout',
        'implementingClassName' => 'App\\Support\\SessionDocLayout',
        'currentClassName' => 'App\\Support\\SessionDocLayout',
        'aliasName' => NULL,
      ),
      'pvSections' => 
      array (
        'name' => 'pvSections',
        'parameters' => 
        array (
          'event' => 
          array (
            'name' => 'event',
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
                      'name' => 'App\\Models\\Event',
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 86,
            'endLine' => 86,
            'startColumn' => 39,
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
        'docComment' => '/** @return list<array{key: string, label: string}> */',
        'startLine' => 86,
        'endLine' => 89,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\SessionDocLayout',
        'implementingClassName' => 'App\\Support\\SessionDocLayout',
        'currentClassName' => 'App\\Support\\SessionDocLayout',
        'aliasName' => NULL,
      ),
      'resolve' => 
      array (
        'name' => 'resolve',
        'parameters' => 
        array (
          'event' => 
          array (
            'name' => 'event',
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
                      'name' => 'App\\Models\\Event',
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 99,
            'endLine' => 99,
            'startColumn' => 37,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'key' => 
          array (
            'name' => 'key',
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
            'startLine' => 99,
            'endLine' => 99,
            'startColumn' => 52,
            'endColumn' => 62,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'catalog' => 
          array (
            'name' => 'catalog',
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
            'startLine' => 99,
            'endLine' => 99,
            'startColumn' => 65,
            'endColumn' => 78,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'default' => 
          array (
            'name' => 'default',
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
            'startLine' => 99,
            'endLine' => 99,
            'startColumn' => 81,
            'endColumn' => 94,
            'parameterIndex' => 3,
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
 * Résout une sélection ordonnée de clés (+ libellés personnalisés) depuis les
 * réglages de l\'événement, avec repli sur le catalogue et la sélection par défaut.
 *
 * @param  array<string, string>  $catalog
 * @param  list<string>  $default
 * @return list<array{key: string, label: string}>
 */',
        'startLine' => 99,
        'endLine' => 117,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 20,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\SessionDocLayout',
        'implementingClassName' => 'App\\Support\\SessionDocLayout',
        'currentClassName' => 'App\\Support\\SessionDocLayout',
        'aliasName' => NULL,
      ),
      'cell' => 
      array (
        'name' => 'cell',
        'parameters' => 
        array (
          'key' => 
          array (
            'name' => 'key',
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
            'startLine' => 120,
            'endLine' => 120,
            'startColumn' => 33,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'slot' => 
          array (
            'name' => 'slot',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Slot',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 120,
            'endLine' => 120,
            'startColumn' => 46,
            'endColumn' => 55,
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
        'docComment' => '/** Valeur (texte) d\'une colonne pour un créneau/communication donné. */',
        'startLine' => 120,
        'endLine' => 136,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\SessionDocLayout',
        'implementingClassName' => 'App\\Support\\SessionDocLayout',
        'currentClassName' => 'App\\Support\\SessionDocLayout',
        'aliasName' => NULL,
      ),
      'headerValue' => 
      array (
        'name' => 'headerValue',
        'parameters' => 
        array (
          'key' => 
          array (
            'name' => 'key',
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
            'startLine' => 139,
            'endLine' => 139,
            'startColumn' => 40,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'session' => 
          array (
            'name' => 'session',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\EventSession',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 139,
            'endLine' => 139,
            'startColumn' => 53,
            'endColumn' => 73,
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
        'docComment' => '/** Valeur (texte) d\'un champ d\'entête pour une séance donnée. */',
        'startLine' => 139,
        'endLine' => 152,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\SessionDocLayout',
        'implementingClassName' => 'App\\Support\\SessionDocLayout',
        'currentClassName' => 'App\\Support\\SessionDocLayout',
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