<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Support\Phone.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Support\Phone
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-cb455f93833352e8409268089dda3e58161515cf80c854199d3a2c9b91ef0dcc',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Support\\Phone',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Support/Phone.php',
      ),
    ),
    'namespace' => 'App\\Support',
    'name' => 'App\\Support\\Phone',
    'shortName' => 'Phone',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Forme canonique d\'un numéro de téléphone sur la plateforme.
 *
 * ⚠️ Le téléphone est un **identifiant fort** : il rapproche un auteur ou un
 * formateur d\'un compte existant (cf. {@see AuthorMatcher}), et le serveur
 * impose alors l\'identité de ce compte. Deux écritures différentes du même
 * numéro (« +228 90 01 52 84 » et « +22890015284 ») créeraient deux identités
 * pour une seule personne, et rendraient l\'unicité contournable par la simple
 * mise en forme. Tout numéro est donc normalisé **à l\'écriture** (mutateur sur
 * `User::phone`) et avant toute vérification d\'unicité.
 *
 * On se limite à la mise en forme : séparateurs retirés, `+` initial conservé.
 * ⚠️ On n\'ajoute **jamais** d\'indicatif pays deviné - un numéro local resterait
 * ambigu, et inventer un préfixe rattacherait des personnes au mauvais pays.
 * C\'est le composant `<x-ukc.phone>` qui produit l\'E.164 à la saisie.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 21,
    'endLine' => 47,
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
      'MIN_DIGITS' => 
      array (
        'declaringClassName' => 'App\\Support\\Phone',
        'implementingClassName' => 'App\\Support\\Phone',
        'name' => 'MIN_DIGITS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '8',
          'attributes' => 
          array (
            'startLine' => 24,
            'endLine' => 24,
            'startTokenPos' => 25,
            'startFilePos' => 1101,
            'endTokenPos' => 25,
            'endFilePos' => 1101,
          ),
        ),
        'docComment' => '/** Nombre minimal de chiffres pour qu\'un numéro soit exploitable. */',
        'attributes' => 
        array (
        ),
        'startLine' => 24,
        'endLine' => 24,
        'startColumn' => 5,
        'endColumn' => 32,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'normalize' => 
      array (
        'name' => 'normalize',
        'parameters' => 
        array (
          'phone' => 
          array (
            'name' => 'phone',
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 27,
            'endLine' => 27,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Numéro nettoyé (espaces, points, tirets, parenthèses retirés). */',
        'startLine' => 27,
        'endLine' => 40,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\Phone',
        'implementingClassName' => 'App\\Support\\Phone',
        'currentClassName' => 'App\\Support\\Phone',
        'aliasName' => NULL,
      ),
      'usable' => 
      array (
        'name' => 'usable',
        'parameters' => 
        array (
          'phone' => 
          array (
            'name' => 'phone',
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 43,
            'endLine' => 43,
            'startColumn' => 35,
            'endColumn' => 48,
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
        'docComment' => '/** Numéro exploitable comme identifiant (assez de chiffres) ? */',
        'startLine' => 43,
        'endLine' => 46,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\Phone',
        'implementingClassName' => 'App\\Support\\Phone',
        'currentClassName' => 'App\\Support\\Phone',
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