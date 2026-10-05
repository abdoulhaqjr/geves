<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Documents\LayoutSanitizer.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Documents\LayoutSanitizer
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-3cf0efbc3c6da22f3b72a73e04e2227f1b1b81883827a28a4b8c8f35081426aa',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Documents\\LayoutSanitizer',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Documents/LayoutSanitizer.php',
      ),
    ),
    'namespace' => 'App\\Documents',
    'name' => 'App\\Documents\\LayoutSanitizer',
    'shortName' => 'LayoutSanitizer',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Validation déterministe d\'une mise en page (badge / attestation). Quelle que
 * soit la sortie de l\'IA, on borne les coordonnées, on liste blanche les types
 * d\'éléments et leurs propriétés, et on retombe sur une mise en page par défaut
 * si le résultat est vide/invalide. Le rendu (LayoutRenderer) reste ainsi sûr.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 11,
    'endLine' => 196,
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
      'KINDS' => 
      array (
        'declaringClassName' => 'App\\Documents\\LayoutSanitizer',
        'implementingClassName' => 'App\\Documents\\LayoutSanitizer',
        'name' => 'KINDS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'badge\', \'attestation\']',
          'attributes' => 
          array (
            'startLine' => 13,
            'endLine' => 13,
            'startTokenPos' => 23,
            'startFilePos' => 420,
            'endTokenPos' => 28,
            'endFilePos' => 443,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 13,
        'endLine' => 13,
        'startColumn' => 5,
        'endColumn' => 50,
      ),
      'ELEMENT_TYPES' => 
      array (
        'declaringClassName' => 'App\\Documents\\LayoutSanitizer',
        'implementingClassName' => 'App\\Documents\\LayoutSanitizer',
        'name' => 'ELEMENT_TYPES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'text\', \'image\', \'qr\', \'rect\', \'signatures\']',
          'attributes' => 
          array (
            'startLine' => 16,
            'endLine' => 16,
            'startTokenPos' => 41,
            'startFilePos' => 570,
            'endTokenPos' => 55,
            'endFilePos' => 614,
          ),
        ),
        'docComment' => '/** `signatures` = bloc des signatures des formateurs (attestations de formation). */',
        'attributes' => 
        array (
        ),
        'startLine' => 16,
        'endLine' => 16,
        'startColumn' => 5,
        'endColumn' => 79,
      ),
      'FONTS' => 
      array (
        'declaringClassName' => 'App\\Documents\\LayoutSanitizer',
        'implementingClassName' => 'App\\Documents\\LayoutSanitizer',
        'name' => 'FONTS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'jost\' => "\'Jost\', system-ui, sans-serif", \'georgia\' => "Georgia, \'Times New Roman\', serif", \'playfair\' => "\'Playfair Display\', Georgia, serif", \'arial\' => \'Arial, Helvetica, sans-serif\', \'lato\' => \'Lato, system-ui, sans-serif\']',
          'attributes' => 
          array (
            'startLine' => 23,
            'endLine' => 29,
            'startTokenPos' => 68,
            'startFilePos' => 905,
            'endTokenPos' => 105,
            'endFilePos' => 1180,
          ),
        ),
        'docComment' => '/**
 * Polices proposées par le concepteur, avec leur pile CSS. ⚠️ Liste blanche :
 * la valeur enregistrée est une CLÉ, jamais une déclaration CSS libre - un
 * `font-family` venu du navigateur finirait dans un attribut `style`.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 23,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'sanitize' => 
      array (
        'name' => 'sanitize',
        'parameters' => 
        array (
          'raw' => 
          array (
            'name' => 'raw',
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
            'startColumn' => 37,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'kind' => 
          array (
            'name' => 'kind',
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
            'startColumn' => 49,
            'endColumn' => 60,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'type' => 
          array (
            'name' => 'type',
            'default' => 
            array (
              'code' => '\'default\'',
              'attributes' => 
              array (
                'startLine' => 32,
                'endLine' => 32,
                'startTokenPos' => 134,
                'startFilePos' => 1301,
                'endTokenPos' => 134,
                'endFilePos' => 1309,
              ),
            ),
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
            'startColumn' => 63,
            'endColumn' => 86,
            'parameterIndex' => 2,
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
        'docComment' => '/** @return array<string, mixed> */',
        'startLine' => 32,
        'endLine' => 106,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LayoutSanitizer',
        'implementingClassName' => 'App\\Documents\\LayoutSanitizer',
        'currentClassName' => 'App\\Documents\\LayoutSanitizer',
        'aliasName' => NULL,
      ),
      'clampFloat' => 
      array (
        'name' => 'clampFloat',
        'parameters' => 
        array (
          'v' => 
          array (
            'name' => 'v',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 109,
            'endLine' => 109,
            'startColumn' => 42,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'min' => 
          array (
            'name' => 'min',
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
            'startLine' => 109,
            'endLine' => 109,
            'startColumn' => 52,
            'endColumn' => 61,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'max' => 
          array (
            'name' => 'max',
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
            'startLine' => 109,
            'endLine' => 109,
            'startColumn' => 64,
            'endColumn' => 73,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'def' => 
          array (
            'name' => 'def',
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
            'startLine' => 109,
            'endLine' => 109,
            'startColumn' => 76,
            'endColumn' => 85,
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
            'name' => 'float',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Borne un nombre décimal (interlignage, interlettrage). */',
        'startLine' => 109,
        'endLine' => 116,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 18,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LayoutSanitizer',
        'implementingClassName' => 'App\\Documents\\LayoutSanitizer',
        'currentClassName' => 'App\\Documents\\LayoutSanitizer',
        'aliasName' => NULL,
      ),
      'src' => 
      array (
        'name' => 'src',
        'parameters' => 
        array (
          'raw' => 
          array (
            'name' => 'raw',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 127,
            'endLine' => 127,
            'startColumn' => 35,
            'endColumn' => 44,
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
        'docComment' => '/**
 * Source d\'image acceptée : URL absolue **ou** chemin servi par le site.
 *
 * ⚠️ Seules les URL absolues passaient : un logo téléversé, référencé en
 * `/storage/…`, était rejeté à l\'enregistrement et disparaissait du document.
 * `data:` reste refusé : le concepteur téléverse désormais ses images
 * (`templates.upload`) et n\'enregistre que leur adresse - un document n\'a
 * pas à embarquer ses octets, et dompdf lit le fichier sur le disque.
 */',
        'startLine' => 127,
        'endLine' => 140,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 18,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LayoutSanitizer',
        'implementingClassName' => 'App\\Documents\\LayoutSanitizer',
        'currentClassName' => 'App\\Documents\\LayoutSanitizer',
        'aliasName' => NULL,
      ),
      'default' => 
      array (
        'name' => 'default',
        'parameters' => 
        array (
          'kind' => 
          array (
            'name' => 'kind',
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
            'startLine' => 143,
            'endLine' => 143,
            'startColumn' => 36,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'type' => 
          array (
            'name' => 'type',
            'default' => 
            array (
              'code' => '\'default\'',
              'attributes' => 
              array (
                'startLine' => 143,
                'endLine' => 143,
                'startTokenPos' => 1474,
                'startFilePos' => 6267,
                'endTokenPos' => 1474,
                'endFilePos' => 6275,
              ),
            ),
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
            'startLine' => 143,
            'endLine' => 143,
            'startColumn' => 50,
            'endColumn' => 73,
            'parameterIndex' => 1,
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
        'docComment' => '/** Mise en page par défaut fonctionnelle (utilisée si aucun modèle enregistré). */',
        'startLine' => 143,
        'endLine' => 146,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LayoutSanitizer',
        'implementingClassName' => 'App\\Documents\\LayoutSanitizer',
        'currentClassName' => 'App\\Documents\\LayoutSanitizer',
        'aliasName' => NULL,
      ),
      'defaultBadge' => 
      array (
        'name' => 'defaultBadge',
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
        'docComment' => NULL,
        'startLine' => 148,
        'endLine' => 162,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 18,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LayoutSanitizer',
        'implementingClassName' => 'App\\Documents\\LayoutSanitizer',
        'currentClassName' => 'App\\Documents\\LayoutSanitizer',
        'aliasName' => NULL,
      ),
      'defaultAttestation' => 
      array (
        'name' => 'defaultAttestation',
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
        'docComment' => NULL,
        'startLine' => 164,
        'endLine' => 179,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 18,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LayoutSanitizer',
        'implementingClassName' => 'App\\Documents\\LayoutSanitizer',
        'currentClassName' => 'App\\Documents\\LayoutSanitizer',
        'aliasName' => NULL,
      ),
      'clampInt' => 
      array (
        'name' => 'clampInt',
        'parameters' => 
        array (
          'v' => 
          array (
            'name' => 'v',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 181,
            'endLine' => 181,
            'startColumn' => 40,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'min' => 
          array (
            'name' => 'min',
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
            'startLine' => 181,
            'endLine' => 181,
            'startColumn' => 50,
            'endColumn' => 57,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'max' => 
          array (
            'name' => 'max',
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
            'startLine' => 181,
            'endLine' => 181,
            'startColumn' => 60,
            'endColumn' => 67,
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
            'startLine' => 181,
            'endLine' => 181,
            'startColumn' => 70,
            'endColumn' => 81,
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
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 181,
        'endLine' => 188,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 18,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LayoutSanitizer',
        'implementingClassName' => 'App\\Documents\\LayoutSanitizer',
        'currentClassName' => 'App\\Documents\\LayoutSanitizer',
        'aliasName' => NULL,
      ),
      'color' => 
      array (
        'name' => 'color',
        'parameters' => 
        array (
          'v' => 
          array (
            'name' => 'v',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 190,
            'endLine' => 190,
            'startColumn' => 37,
            'endColumn' => 44,
            'parameterIndex' => 0,
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
            'startLine' => 190,
            'endLine' => 190,
            'startColumn' => 47,
            'endColumn' => 61,
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
        'docComment' => NULL,
        'startLine' => 190,
        'endLine' => 195,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 18,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LayoutSanitizer',
        'implementingClassName' => 'App\\Documents\\LayoutSanitizer',
        'currentClassName' => 'App\\Documents\\LayoutSanitizer',
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