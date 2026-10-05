<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Support\Branding.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Support\Branding
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-1a83cede866c0aa8e1d483a29fbff3e38f3e01181f60ce3f3435f2da53b57449',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Support\\Branding',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Support/Branding.php',
      ),
    ),
    'namespace' => 'App\\Support',
    'name' => 'App\\Support\\Branding',
    'shortName' => 'Branding',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Logos de la plateforme, remplaçables depuis **Réglages › Général**.
 *
 * **Deux formes**, parce qu\'un logo ne tient pas dans toutes les largeurs :
 *  - **large** (lockup complet, marque + nom de l\'établissement) : pied de page,
 *    pages de connexion et de création de compte, bandeau des pages publiques
 *    d\'événement - partout où l\'on dispose de la largeur ;
 *  - **compacte** (monogramme carré) : en-tête public et menus latéraux, où le
 *    logo côtoie un texte ou se réduit à une colonne d\'icônes.
 *
 * Chaque forme accepte **une version pour fonds sombres, facultative**.
 *
 * ⚠️ **Sans elle, on blanchit en CSS** (`.logo-auto-white`,
 * `brightness(0) invert(1)`) : `whitensOnDark()` dit quand poser la classe. Ce
 * secours rend le logo lisible sur le bleu, mais il met **toute** la matière
 * opaque en blanc - les évidements (lettres détourées dans un écusson) sont
 * perdus. Une vraie version claire est un **dessin**, aucun filtre ne la déduit
 * du logo couleur : c\'est pourquoi la case existe toujours.
 *
 * ⚠️ **Repli** sur les fichiers livrés dans `public/img` si rien n\'est téléversé
 * ou si le fichier réglé a disparu - jamais d\'image cassée. Le repli sombre du
 * logo large est `logo-uk-white.png`, **déjà dessiné en blanc** : on ne le
 * blanchit pas (et blanchir le monogramme livré en ferait un aplat).
 *
 * ⚠️ Les clés stockées gardent leurs noms d\'origine : les renommer invaliderait
 * les logos déjà téléversés.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 34,
    'endLine' => 177,
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
      'WIDE' => 
      array (
        'declaringClassName' => 'App\\Support\\Branding',
        'implementingClassName' => 'App\\Support\\Branding',
        'name' => 'WIDE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'app_logo\'',
          'attributes' => 
          array (
            'startLine' => 37,
            'endLine' => 37,
            'startTokenPos' => 30,
            'startFilePos' => 1688,
            'endTokenPos' => 30,
            'endFilePos' => 1697,
          ),
        ),
        'docComment' => '/** Logo large (lockup complet), fonds clairs. */',
        'attributes' => 
        array (
        ),
        'startLine' => 37,
        'endLine' => 37,
        'startColumn' => 5,
        'endColumn' => 35,
      ),
      'WIDE_WHITE' => 
      array (
        'declaringClassName' => 'App\\Support\\Branding',
        'implementingClassName' => 'App\\Support\\Branding',
        'name' => 'WIDE_WHITE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'app_logo_white\'',
          'attributes' => 
          array (
            'startLine' => 40,
            'endLine' => 40,
            'startTokenPos' => 43,
            'startFilePos' => 1782,
            'endTokenPos' => 43,
            'endFilePos' => 1797,
          ),
        ),
        'docComment' => '/** Logo large, fonds sombres - facultatif. */',
        'attributes' => 
        array (
        ),
        'startLine' => 40,
        'endLine' => 40,
        'startColumn' => 5,
        'endColumn' => 47,
      ),
      'MARK' => 
      array (
        'declaringClassName' => 'App\\Support\\Branding',
        'implementingClassName' => 'App\\Support\\Branding',
        'name' => 'MARK',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'app_logo_mark\'',
          'attributes' => 
          array (
            'startLine' => 43,
            'endLine' => 43,
            'startTokenPos' => 56,
            'startFilePos' => 1884,
            'endTokenPos' => 56,
            'endFilePos' => 1898,
          ),
        ),
        'docComment' => '/** Logo compact (monogramme carré), fonds clairs. */',
        'attributes' => 
        array (
        ),
        'startLine' => 43,
        'endLine' => 43,
        'startColumn' => 5,
        'endColumn' => 40,
      ),
      'MARK_WHITE' => 
      array (
        'declaringClassName' => 'App\\Support\\Branding',
        'implementingClassName' => 'App\\Support\\Branding',
        'name' => 'MARK_WHITE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'app_logo_mark_white\'',
          'attributes' => 
          array (
            'startLine' => 46,
            'endLine' => 46,
            'startTokenPos' => 69,
            'startFilePos' => 1985,
            'endTokenPos' => 69,
            'endFilePos' => 2005,
          ),
        ),
        'docComment' => '/** Logo compact, fonds sombres - facultatif. */',
        'attributes' => 
        array (
        ),
        'startLine' => 46,
        'endLine' => 46,
        'startColumn' => 5,
        'endColumn' => 52,
      ),
      'SHIPPED_WIDE' => 
      array (
        'declaringClassName' => 'App\\Support\\Branding',
        'implementingClassName' => 'App\\Support\\Branding',
        'name' => 'SHIPPED_WIDE',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'img/logo-uk.png\'',
          'attributes' => 
          array (
            'startLine' => 49,
            'endLine' => 49,
            'startTokenPos' => 82,
            'startFilePos' => 2090,
            'endTokenPos' => 82,
            'endFilePos' => 2106,
          ),
        ),
        'docComment' => '/** Fichiers livrés avec l\'application. */',
        'attributes' => 
        array (
        ),
        'startLine' => 49,
        'endLine' => 49,
        'startColumn' => 5,
        'endColumn' => 51,
      ),
      'SHIPPED_WIDE_WHITE' => 
      array (
        'declaringClassName' => 'App\\Support\\Branding',
        'implementingClassName' => 'App\\Support\\Branding',
        'name' => 'SHIPPED_WIDE_WHITE',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'img/logo-uk-white.png\'',
          'attributes' => 
          array (
            'startLine' => 51,
            'endLine' => 51,
            'startTokenPos' => 93,
            'startFilePos' => 2149,
            'endTokenPos' => 93,
            'endFilePos' => 2171,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 51,
        'endLine' => 51,
        'startColumn' => 5,
        'endColumn' => 63,
      ),
      'SHIPPED_MARK' => 
      array (
        'declaringClassName' => 'App\\Support\\Branding',
        'implementingClassName' => 'App\\Support\\Branding',
        'name' => 'SHIPPED_MARK',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'img/apple-touch-icon.png\'',
          'attributes' => 
          array (
            'startLine' => 53,
            'endLine' => 53,
            'startTokenPos' => 104,
            'startFilePos' => 2208,
            'endTokenPos' => 104,
            'endFilePos' => 2233,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 53,
        'endLine' => 53,
        'startColumn' => 5,
        'endColumn' => 60,
      ),
      'FORMS' => 
      array (
        'declaringClassName' => 'App\\Support\\Branding',
        'implementingClassName' => 'App\\Support\\Branding',
        'name' => 'FORMS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[self::WIDE => self::WIDE_WHITE, self::MARK => self::MARK_WHITE]',
          'attributes' => 
          array (
            'startLine' => 56,
            'endLine' => 59,
            'startTokenPos' => 117,
            'startFilePos' => 2350,
            'endTokenPos' => 141,
            'endFilePos' => 2436,
          ),
        ),
        'docComment' => '/** Les deux formes, clé claire => clé sombre. Ordre de l\'écran de réglages. */',
        'attributes' => 
        array (
        ),
        'startLine' => 56,
        'endLine' => 59,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
      'KEYS' => 
      array (
        'declaringClassName' => 'App\\Support\\Branding',
        'implementingClassName' => 'App\\Support\\Branding',
        'name' => 'KEYS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[self::WIDE, self::WIDE_WHITE, self::MARK, self::MARK_WHITE]',
          'attributes' => 
          array (
            'startLine' => 62,
            'endLine' => 62,
            'startTokenPos' => 154,
            'startFilePos' => 2546,
            'endTokenPos' => 173,
            'endFilePos' => 2605,
          ),
        ),
        'docComment' => '/** Toutes les clés téléversables (la clé sert aussi de nom de champ). */',
        'attributes' => 
        array (
        ),
        'startLine' => 62,
        'endLine' => 62,
        'startColumn' => 5,
        'endColumn' => 85,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'wide' => 
      array (
        'name' => 'wide',
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
        'docComment' => '/** Logo large sur fond clair. */',
        'startLine' => 65,
        'endLine' => 68,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\Branding',
        'implementingClassName' => 'App\\Support\\Branding',
        'currentClassName' => 'App\\Support\\Branding',
        'aliasName' => NULL,
      ),
      'wideWhite' => 
      array (
        'name' => 'wideWhite',
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
        'docComment' => '/**
 * Logo large sur fond sombre.
 *
 * ⚠️ À accompagner de `.logo-auto-white` quand `whitensOnDark(WIDE)` : sans
 * elle, le logo à texte sombre servi en secours disparaît sur le bleu.
 */',
        'startLine' => 76,
        'endLine' => 79,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\Branding',
        'implementingClassName' => 'App\\Support\\Branding',
        'currentClassName' => 'App\\Support\\Branding',
        'aliasName' => NULL,
      ),
      'mark' => 
      array (
        'name' => 'mark',
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
        'docComment' => '/** Monogramme sur fond clair. */',
        'startLine' => 82,
        'endLine' => 85,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\Branding',
        'implementingClassName' => 'App\\Support\\Branding',
        'currentClassName' => 'App\\Support\\Branding',
        'aliasName' => NULL,
      ),
      'markWhite' => 
      array (
        'name' => 'markWhite',
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
        'docComment' => '/** Monogramme sur fond sombre (voir `whitensOnDark(MARK)`). */',
        'startLine' => 88,
        'endLine' => 91,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\Branding',
        'implementingClassName' => 'App\\Support\\Branding',
        'currentClassName' => 'App\\Support\\Branding',
        'aliasName' => NULL,
      ),
      'whitensOnDark' => 
      array (
        'name' => 'whitensOnDark',
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
            'startLine' => 104,
            'endLine' => 104,
            'startColumn' => 42,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Faut-il **blanchir en CSS** cette forme posée sur un fond sombre ?
 *
 * Vrai dans le seul cas du secours : pas de version claire téléversée, mais
 * un fichier clair qui, lui, l\'a été. Faux quand une version claire existe
 * (on respecte le dessin de l\'admin) et faux sur les fichiers livrés, dont
 * la version sombre est déjà dessinée (logo large) ou deviendrait un aplat
 * (monogramme).
 *
 * @param  string  $key  la clé **claire** de la forme (WIDE ou MARK)
 */',
        'startLine' => 104,
        'endLine' => 111,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\Branding',
        'implementingClassName' => 'App\\Support\\Branding',
        'currentClassName' => 'App\\Support\\Branding',
        'aliasName' => NULL,
      ),
      'isCustom' => 
      array (
        'name' => 'isCustom',
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
        'docComment' => '/** Un logo a-t-il été téléversé, quelle que soit la variante ? */',
        'startLine' => 114,
        'endLine' => 123,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\Branding',
        'implementingClassName' => 'App\\Support\\Branding',
        'currentClassName' => 'App\\Support\\Branding',
        'aliasName' => NULL,
      ),
      'shipped' => 
      array (
        'name' => 'shipped',
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
            'startLine' => 131,
            'endLine' => 131,
            'startColumn' => 36,
            'endColumn' => 46,
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
 * Fichier **livré** correspondant à une variante.
 *
 * Sert les aperçus de « Supprimer » dans les réglages : c\'est ce que le
 * bouton rétablit, le logo livré reprenant la main.
 */',
        'startLine' => 131,
        'endLine' => 138,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\Branding',
        'implementingClassName' => 'App\\Support\\Branding',
        'currentClassName' => 'App\\Support\\Branding',
        'aliasName' => NULL,
      ),
      'url' => 
      array (
        'name' => 'url',
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
            'startLine' => 141,
            'endLine' => 141,
            'startColumn' => 32,
            'endColumn' => 42,
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
        'docComment' => '/** URL du fichier téléversé pour cette variante, ou null. */',
        'startLine' => 141,
        'endLine' => 146,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\Branding',
        'implementingClassName' => 'App\\Support\\Branding',
        'currentClassName' => 'App\\Support\\Branding',
        'aliasName' => NULL,
      ),
      'first' => 
      array (
        'name' => 'first',
        'parameters' => 
        array (
          'keys' => 
          array (
            'name' => 'keys',
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
            'startLine' => 153,
            'endLine' => 153,
            'startColumn' => 35,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'shipped' => 
          array (
            'name' => 'shipped',
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
            'startLine' => 153,
            'endLine' => 153,
            'startColumn' => 48,
            'endColumn' => 62,
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
        'docComment' => '/**
 * Première variante disponible parmi `$keys`, sinon le fichier livré.
 *
 * @param  list<string>  $keys
 */',
        'startLine' => 153,
        'endLine' => 163,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 20,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\Branding',
        'implementingClassName' => 'App\\Support\\Branding',
        'currentClassName' => 'App\\Support\\Branding',
        'aliasName' => NULL,
      ),
      'stored' => 
      array (
        'name' => 'stored',
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
            'startLine' => 169,
            'endLine' => 169,
            'startColumn' => 36,
            'endColumn' => 46,
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
 * Chemin stocké d\'une variante, seulement si le fichier existe encore : un
 * réglage pointant vers un fichier supprimé ne doit pas casser l\'affichage.
 */',
        'startLine' => 169,
        'endLine' => 176,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 20,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\Branding',
        'implementingClassName' => 'App\\Support\\Branding',
        'currentClassName' => 'App\\Support\\Branding',
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