<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Documents\DocumentFonts.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Documents\DocumentFonts
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-0a6f88ad277feff658633428d697c9aeae0e795f0076fb7d1c53bc37c3c63edf',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Documents\\DocumentFonts',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Documents/DocumentFonts.php',
      ),
    ),
    'namespace' => 'App\\Documents',
    'name' => 'App\\Documents\\DocumentFonts',
    'shortName' => 'DocumentFonts',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Polices des documents, hébergées par le site (`public/fonts`).
 *
 * Source unique des fontes déclarées : le partial `documents/_fonts` et le
 * rendu (`LayoutRenderer`) y puisent tous les deux, sinon le document délivré
 * demanderait une graisse que la feuille de styles n\'a pas déclarée.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 12,
    'endLine' => 96,
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
      'FACES' => 
      array (
        'declaringClassName' => 'App\\Documents\\DocumentFonts',
        'implementingClassName' => 'App\\Documents\\DocumentFonts',
        'name' => 'FACES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[[\'jost-400\', \'Jost\', 400, \'normal\'], [\'jost-500\', \'Jost\', 500, \'normal\'], [\'jost-600\', \'Jost\', 600, \'normal\'], [\'jost-700\', \'Jost\', 700, \'normal\'], [\'jost-800\', \'Jost\', 800, \'normal\'], [\'jost-900\', \'Jost\', 900, \'normal\'], [\'jost-400-italic\', \'Jost\', 400, \'italic\'], [\'jost-700-italic\', \'Jost\', 700, \'italic\'], [\'playfair-700\', \'Playfair Display\', 700, \'normal\'], [\'playfair-900\', \'Playfair Display\', 900, \'normal\'], [\'playfair-700-italic\', \'Playfair Display\', 700, \'italic\']]',
          'attributes' => 
          array (
            'startLine' => 22,
            'endLine' => 34,
            'startTokenPos' => 25,
            'startFilePos' => 705,
            'endTokenPos' => 181,
            'endFilePos' => 1275,
          ),
        ),
        'docComment' => '/**
 * Fontes livrées : [fichier, famille, graisse, style].
 *
 * ⚠️ Les graisses reprennent exactement celles du concepteur : une graisse
 * absente serait synthétisée par le moteur, ce qui change le rendu.
 *
 * @var list<array{0: string, 1: string, 2: int, 3: string}>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 22,
        'endLine' => 34,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
      'FAMILIES' => 
      array (
        'declaringClassName' => 'App\\Documents\\DocumentFonts',
        'implementingClassName' => 'App\\Documents\\DocumentFonts',
        'name' => 'FAMILIES',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'jost\' => \'Jost\', \'playfair\' => \'Playfair Display\']',
          'attributes' => 
          array (
            'startLine' => 37,
            'endLine' => 40,
            'startTokenPos' => 194,
            'startFilePos' => 1370,
            'endTokenPos' => 210,
            'endFilePos' => 1444,
          ),
        ),
        'docComment' => '/** Famille réelle, par clé de police du concepteur. */',
        'attributes' => 
        array (
        ),
        'startLine' => 37,
        'endLine' => 40,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'pdfFamily' => 
      array (
        'name' => 'pdfFamily',
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
            'startLine' => 51,
            'endLine' => 51,
            'startColumn' => 38,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'weight' => 
          array (
            'name' => 'weight',
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
            'startLine' => 51,
            'endLine' => 51,
            'startColumn' => 51,
            'endColumn' => 61,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'italic' => 
          array (
            'name' => 'italic',
            'default' => NULL,
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
            'startLine' => 51,
            'endLine' => 51,
            'startColumn' => 64,
            'endColumn' => 75,
            'parameterIndex' => 2,
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
 * Nom de famille **par graisse**, pour le PDF.
 *
 * ⚠️ **dompdf ne garde que quatre variantes par famille** (normale, grasse,
 * italique, grasse italique) : nos six graisses de Jost s\'y écrasaient deux
 * à deux, et un texte dessiné en 500 ressortait en 400, un titre en 700 en
 * 900. On déclare donc une famille par graisse (« Jost 500 »), que dompdf
 * n\'a plus à départager.
 */',
        'startLine' => 51,
        'endLine' => 72,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\DocumentFonts',
        'implementingClassName' => 'App\\Documents\\DocumentFonts',
        'currentClassName' => 'App\\Documents\\DocumentFonts',
        'aliasName' => NULL,
      ),
      'pdfFamilyName' => 
      array (
        'name' => 'pdfFamilyName',
        'parameters' => 
        array (
          'family' => 
          array (
            'name' => 'family',
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
            'startLine' => 75,
            'endLine' => 75,
            'startColumn' => 42,
            'endColumn' => 55,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'weight' => 
          array (
            'name' => 'weight',
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
            'startColumn' => 58,
            'endColumn' => 68,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'style' => 
          array (
            'name' => 'style',
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
            'startLine' => 75,
            'endLine' => 75,
            'startColumn' => 71,
            'endColumn' => 83,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Nom de la famille dédiée à une fonte, côté PDF. */',
        'startLine' => 75,
        'endLine' => 78,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\DocumentFonts',
        'implementingClassName' => 'App\\Documents\\DocumentFonts',
        'currentClassName' => 'App\\Documents\\DocumentFonts',
        'aliasName' => NULL,
      ),
      'metricsFile' => 
      array (
        'name' => 'metricsFile',
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
            'startLine' => 81,
            'endLine' => 81,
            'startColumn' => 40,
            'endColumn' => 50,
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
        'docComment' => '/** Fichier dont on lit les métriques pour une clé du concepteur. */',
        'startLine' => 81,
        'endLine' => 95,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\DocumentFonts',
        'implementingClassName' => 'App\\Documents\\DocumentFonts',
        'currentClassName' => 'App\\Documents\\DocumentFonts',
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