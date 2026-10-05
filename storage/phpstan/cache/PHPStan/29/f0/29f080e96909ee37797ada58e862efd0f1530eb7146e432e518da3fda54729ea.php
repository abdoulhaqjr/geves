<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Documents\LivretDocx.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Documents\LivretDocx
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-5eda829e38df3ccc56b24a6ecccc9e0c963c120e340c86925e967840413d19d7',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Documents\\LivretDocx',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Documents/LivretDocx.php',
      ),
    ),
    'namespace' => 'App\\Documents',
    'name' => 'App\\Documents\\LivretDocx',
    'shortName' => 'LivretDocx',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Livret des résumés au format Word éditable (.docx), portant la logique du
 * projet source uk-colloques (booklet) : page de garde « LIVRE DES RÉSUMÉS »,
 * avant-propos, comités, sommaire (TOC Word), programme, puis résumés regroupés
 * par axe - chaque résumé avec sa référence, ses auteurs et affiliations
 * numérotées (exposants), l\'auteur correspondant, le libellé « RÉSUMÉ : » et les
 * mots-clés. Rendu déterministe ; l\'apparence est pilotée par les réglages
 * `livret.*` et le texte échappé (Word refuse un XML invalide).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 34,
    'endLine' => 1107,
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
      'WNS' => 
      array (
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'name' => 'WNS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'http://schemas.openxmlformats.org/wordprocessingml/2006/main\'',
          'attributes' => 
          array (
            'startLine' => 37,
            'endLine' => 37,
            'startTokenPos' => 120,
            'startFilePos' => 1369,
            'endTokenPos' => 120,
            'endFilePos' => 1430,
          ),
        ),
        'docComment' => '/** Espace de noms WordprocessingML (extraction des fragments OOXML pour les modèles). */',
        'attributes' => 
        array (
        ),
        'startLine' => 37,
        'endLine' => 37,
        'startColumn' => 5,
        'endColumn' => 86,
      ),
      'BLUE' => 
      array (
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'name' => 'BLUE',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'223EA4\'',
          'attributes' => 
          array (
            'startLine' => 40,
            'endLine' => 40,
            'startTokenPos' => 133,
            'startFilePos' => 1540,
            'endTokenPos' => 133,
            'endFilePos' => 1547,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 40,
        'endLine' => 40,
        'startColumn' => 5,
        'endColumn' => 34,
      ),
      'GREEN' => 
      array (
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'name' => 'GREEN',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'009933\'',
          'attributes' => 
          array (
            'startLine' => 42,
            'endLine' => 42,
            'startTokenPos' => 144,
            'startFilePos' => 1577,
            'endTokenPos' => 144,
            'endFilePos' => 1584,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 42,
        'endLine' => 42,
        'startColumn' => 5,
        'endColumn' => 35,
      ),
      'MUTED' => 
      array (
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'name' => 'MUTED',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'6B7280\'',
          'attributes' => 
          array (
            'startLine' => 44,
            'endLine' => 44,
            'startTokenPos' => 155,
            'startFilePos' => 1614,
            'endTokenPos' => 155,
            'endFilePos' => 1621,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 44,
        'endLine' => 44,
        'startColumn' => 5,
        'endColumn' => 35,
      ),
      'COMMITTEE_ROLES' => 
      array (
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'name' => 'COMMITTEE_ROLES',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'organisation\' => \'Comité d’organisation\', \'scientifique\' => \'Comité scientifique\', \'supervision\' => \'Comité de supervision\']',
          'attributes' => 
          array (
            'startLine' => 47,
            'endLine' => 51,
            'startTokenPos' => 168,
            'startFilePos' => 1749,
            'endTokenPos' => 191,
            'endFilePos' => 1910,
          ),
        ),
        'docComment' => '/** @var array<string, string> rôle => libellé de comité (ordre d\'affichage). */',
        'attributes' => 
        array (
        ),
        'startLine' => 47,
        'endLine' => 51,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
      'STYLES' => 
      array (
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'name' => 'STYLES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'default\' => \'Modèle par défaut\', \'fss\' => \'Modèle 2 - Journées scientifiques\']',
          'attributes' => 
          array (
            'startLine' => 63,
            'endLine' => 66,
            'startTokenPos' => 246,
            'startFilePos' => 2400,
            'endTokenPos' => 262,
            'endFilePos' => 2506,
          ),
        ),
        'docComment' => '/** Styles intégrés disponibles (modèle actif « default » ou « fss »). */',
        'attributes' => 
        array (
        ),
        'startLine' => 63,
        'endLine' => 66,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
      'COMMITTEE_ORDER' => 
      array (
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'name' => 'COMMITTEE_ORDER',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'supervision\' => \'Comité de supervision\', \'organisation\' => \'Comité d’organisation\', \'scientifique\' => \'Comité scientifique\']',
          'attributes' => 
          array (
            'startLine' => 472,
            'endLine' => 476,
            'startTokenPos' => 4137,
            'startFilePos' => 20224,
            'endTokenPos' => 4160,
            'endFilePos' => 20385,
          ),
        ),
        'docComment' => '/** @var array<string, string> comités dans l\'ordre de référence (supervision → scientifique). */',
        'attributes' => 
        array (
        ),
        'startLine' => 472,
        'endLine' => 476,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
    ),
    'immediateProperties' => 
    array (
      'nameMap' => 
      array (
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'name' => 'nameMap',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 54,
            'endLine' => 54,
            'startTokenPos' => 204,
            'startFilePos' => 2015,
            'endTokenPos' => 205,
            'endFilePos' => 2016,
          ),
        ),
        'docComment' => '/** @var array<string, string> map IA original => nom harmonisé */',
        'attributes' => 
        array (
        ),
        'startLine' => 54,
        'endLine' => 54,
        'startColumn' => 5,
        'endColumn' => 32,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'affMap' => 
      array (
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'name' => 'affMap',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 57,
            'endLine' => 57,
            'startTokenPos' => 218,
            'startFilePos' => 2129,
            'endTokenPos' => 219,
            'endFilePos' => 2130,
          ),
        ),
        'docComment' => '/** @var array<string, string> map IA original => affiliation harmonisée */',
        'attributes' => 
        array (
        ),
        'startLine' => 57,
        'endLine' => 57,
        'startColumn' => 5,
        'endColumn' => 31,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'authorIndex' => 
      array (
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'name' => 'authorIndex',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 60,
            'endLine' => 60,
            'startTokenPos' => 232,
            'startFilePos' => 2284,
            'endTokenPos' => 233,
            'endFilePos' => 2285,
          ),
        ),
        'docComment' => '/** @var array<string, list<string>> nom d\'auteur affiché => références des résumés (index des auteurs). */',
        'attributes' => 
        array (
        ),
        'startLine' => 60,
        'endLine' => 60,
        'startColumn' => 5,
        'endColumn' => 36,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'event' => 
      array (
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'name' => 'event',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'App\\Models\\Event',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 73,
        'endLine' => 73,
        'startColumn' => 9,
        'endColumn' => 37,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'groups' => 
      array (
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'name' => 'groups',
        'modifiers' => 132,
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
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 74,
        'endLine' => 74,
        'startColumn' => 9,
        'endColumn' => 43,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'harmonize' => 
      array (
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'name' => 'harmonize',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => 'false',
          'attributes' => 
          array (
            'startLine' => 75,
            'endLine' => 75,
            'startTokenPos' => 302,
            'startFilePos' => 2925,
            'endTokenPos' => 302,
            'endFilePos' => 2929,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 75,
        'endLine' => 75,
        'startColumn' => 9,
        'endColumn' => 48,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'style' => 
      array (
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'name' => 'style',
        'modifiers' => 132,
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
          'code' => '\'default\'',
          'attributes' => 
          array (
            'startLine' => 76,
            'endLine' => 76,
            'startTokenPos' => 315,
            'startFilePos' => 2973,
            'endTokenPos' => 315,
            'endFilePos' => 2981,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 76,
        'endLine' => 76,
        'startColumn' => 9,
        'endColumn' => 50,
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
          'event' => 
          array (
            'name' => 'event',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Event',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 73,
            'endLine' => 73,
            'startColumn' => 9,
            'endColumn' => 37,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'groups' => 
          array (
            'name' => 'groups',
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 74,
            'endLine' => 74,
            'startColumn' => 9,
            'endColumn' => 43,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'harmonize' => 
          array (
            'name' => 'harmonize',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 75,
                'endLine' => 75,
                'startTokenPos' => 302,
                'startFilePos' => 2925,
                'endTokenPos' => 302,
                'endFilePos' => 2929,
              ),
            ),
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 75,
            'endLine' => 75,
            'startColumn' => 9,
            'endColumn' => 48,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'style' => 
          array (
            'name' => 'style',
            'default' => 
            array (
              'code' => '\'default\'',
              'attributes' => 
              array (
                'startLine' => 76,
                'endLine' => 76,
                'startTokenPos' => 315,
                'startFilePos' => 2973,
                'endTokenPos' => 315,
                'endFilePos' => 2981,
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 76,
            'endLine' => 76,
            'startColumn' => 9,
            'endColumn' => 50,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param  Collection<int|string, \\Illuminate\\Database\\Eloquent\\Collection<int, Submission>>  $groups  soumissions groupées par axe
 * @param  string  $style  style intégré : \'default\' (UK/UP) ou \'fss\' (Journées scientifiques)
 */',
        'startLine' => 72,
        'endLine' => 77,
        'startColumn' => 5,
        'endColumn' => 8,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'currentClassName' => 'App\\Documents\\LivretDocx',
        'aliasName' => NULL,
      ),
      'isFss' => 
      array (
        'name' => 'isFss',
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
        'docComment' => '/** Le document est-il rendu dans le style « Journées scientifiques (FSS) » ? */',
        'startLine' => 80,
        'endLine' => 83,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'currentClassName' => 'App\\Documents\\LivretDocx',
        'aliasName' => NULL,
      ),
      'download' => 
      array (
        'name' => 'download',
        'parameters' => 
        array (
          'filename' => 
          array (
            'name' => 'filename',
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
            'startLine' => 86,
            'endLine' => 86,
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
            'name' => 'Symfony\\Component\\HttpFoundation\\Response',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Réponse HTTP de téléchargement du .docx. */',
        'startLine' => 86,
        'endLine' => 95,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'currentClassName' => 'App\\Documents\\LivretDocx',
        'aliasName' => NULL,
      ),
      'build' => 
      array (
        'name' => 'build',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PhpOffice\\PhpWord\\PhpWord',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Construit le document phpword complet (ordre du booklet uk-colloques). */',
        'startLine' => 98,
        'endLine' => 159,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'currentClassName' => 'App\\Documents\\LivretDocx',
        'aliasName' => NULL,
      ),
      'inlineValues' => 
      array (
        'name' => 'inlineValues',
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
 * Valeurs des balises « en ligne » d\'un modèle ([[TITRE]], [[DATES]]…).
 *
 * @return array<string, string>
 */',
        'startLine' => 168,
        'endLine' => 192,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'currentClassName' => 'App\\Documents\\LivretDocx',
        'aliasName' => NULL,
      ),
      'blockXml' => 
      array (
        'name' => 'blockXml',
        'parameters' => 
        array (
          'tag' => 
          array (
            'name' => 'tag',
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
            'startLine' => 198,
            'endLine' => 198,
            'startColumn' => 30,
            'endColumn' => 40,
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
 * Fragment OOXML (paragraphes/tableaux) d\'une balise « bloc » du modèle,
 * en formatage DIRECT (indépendant des styles du modèle).
 */',
        'startLine' => 198,
        'endLine' => 220,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'currentClassName' => 'App\\Documents\\LivretDocx',
        'aliasName' => NULL,
      ),
      'bodyInnerXml' => 
      array (
        'name' => 'bodyInnerXml',
        'parameters' => 
        array (
          'doc' => 
          array (
            'name' => 'doc',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpOffice\\PhpWord\\PhpWord',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 223,
            'endLine' => 223,
            'startColumn' => 42,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Sérialise les enfants de <w:body> (hors <w:sectPr>) d\'un document phpword. */',
        'startLine' => 223,
        'endLine' => 254,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 20,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'currentClassName' => 'App\\Documents\\LivretDocx',
        'aliasName' => NULL,
      ),
      'tplForeword' => 
      array (
        'name' => 'tplForeword',
        'parameters' => 
        array (
          'section' => 
          array (
            'name' => 'section',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpOffice\\PhpWord\\Element\\Section',
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
            'startColumn' => 34,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Bloc Avant-propos (sans titre : le modèle fournit l\'en-tête). */',
        'startLine' => 257,
        'endLine' => 268,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'currentClassName' => 'App\\Documents\\LivretDocx',
        'aliasName' => NULL,
      ),
      'tplCommittees' => 
      array (
        'name' => 'tplCommittees',
        'parameters' => 
        array (
          'section' => 
          array (
            'name' => 'section',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpOffice\\PhpWord\\Element\\Section',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 271,
            'endLine' => 271,
            'startColumn' => 36,
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
        'docComment' => '/** Bloc Comités (libellés de rôle en gras, membres en dessous). */',
        'startLine' => 271,
        'endLine' => 285,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'currentClassName' => 'App\\Documents\\LivretDocx',
        'aliasName' => NULL,
      ),
      'tplSommaire' => 
      array (
        'name' => 'tplSommaire',
        'parameters' => 
        array (
          'section' => 
          array (
            'name' => 'section',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpOffice\\PhpWord\\Element\\Section',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 288,
            'endLine' => 288,
            'startColumn' => 34,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Bloc Sommaire (liste déterministe des axes des résumés). */',
        'startLine' => 288,
        'endLine' => 295,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'currentClassName' => 'App\\Documents\\LivretDocx',
        'aliasName' => NULL,
      ),
      'tplProgramme' => 
      array (
        'name' => 'tplProgramme',
        'parameters' => 
        array (
          'section' => 
          array (
            'name' => 'section',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpOffice\\PhpWord\\Element\\Section',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 298,
            'endLine' => 298,
            'startColumn' => 35,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Bloc Programme (communications placées par jour) - sans titre (le modèle le fournit). */',
        'startLine' => 298,
        'endLine' => 303,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'currentClassName' => 'App\\Documents\\LivretDocx',
        'aliasName' => NULL,
      ),
      'tplResumes' => 
      array (
        'name' => 'tplResumes',
        'parameters' => 
        array (
          'section' => 
          array (
            'name' => 'section',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpOffice\\PhpWord\\Element\\Section',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 306,
            'endLine' => 306,
            'startColumn' => 33,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Bloc Résumés (axe en gras direct + résumés), réutilise addAbstract. */',
        'startLine' => 306,
        'endLine' => 325,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'currentClassName' => 'App\\Documents\\LivretDocx',
        'aliasName' => NULL,
      ),
      'addCover' => 
      array (
        'name' => 'addCover',
        'parameters' => 
        array (
          'section' => 
          array (
            'name' => 'section',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpOffice\\PhpWord\\Element\\Section',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 328,
            'endLine' => 328,
            'startColumn' => 31,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Page de garde : logo, bandeau, colloque, titre, thème, dates, « LIVRE DES RÉSUMÉS ». */',
        'startLine' => 328,
        'endLine' => 383,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'currentClassName' => 'App\\Documents\\LivretDocx',
        'aliasName' => NULL,
      ),
      'addForeword' => 
      array (
        'name' => 'addForeword',
        'parameters' => 
        array (
          'section' => 
          array (
            'name' => 'section',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpOffice\\PhpWord\\Element\\Section',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 386,
            'endLine' => 386,
            'startColumn' => 34,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Avant-propos signé (réglages `livret.*`). */',
        'startLine' => 386,
        'endLine' => 394,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'currentClassName' => 'App\\Documents\\LivretDocx',
        'aliasName' => NULL,
      ),
      'addMessage' => 
      array (
        'name' => 'addMessage',
        'parameters' => 
        array (
          'section' => 
          array (
            'name' => 'section',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpOffice\\PhpWord\\Element\\Section',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 401,
            'endLine' => 401,
            'startColumn' => 33,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'title' => 
          array (
            'name' => 'title',
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
            'startLine' => 401,
            'endLine' => 401,
            'startColumn' => 51,
            'endColumn' => 63,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'text' => 
          array (
            'name' => 'text',
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
            'startLine' => 401,
            'endLine' => 401,
            'startColumn' => 66,
            'endColumn' => 77,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'signature' => 
          array (
            'name' => 'signature',
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
            'startLine' => 401,
            'endLine' => 401,
            'startColumn' => 80,
            'endColumn' => 96,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Bloc de message signé GÉNÉRIQUE (avant-propos, mot de bienvenue, mot du
 * président…) : titre + paragraphes justifiés + signature. Ne rend RIEN si le
 * texte est vide (section masquée). Sert tous les « mots » du livret.
 */',
        'startLine' => 401,
        'endLine' => 422,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'currentClassName' => 'App\\Documents\\LivretDocx',
        'aliasName' => NULL,
      ),
      'addPatronage' => 
      array (
        'name' => 'addPatronage',
        'parameters' => 
        array (
          'section' => 
          array (
            'name' => 'section',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpOffice\\PhpWord\\Element\\Section',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 428,
            'endLine' => 428,
            'startColumn' => 35,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Haut patronage (réglage `livret.patronage`) : lignes centrées sur une page
 * dédiée (ex. « Sous le haut patronage de… »). Masqué si vide.
 */',
        'startLine' => 428,
        'endLine' => 445,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'currentClassName' => 'App\\Documents\\LivretDocx',
        'aliasName' => NULL,
      ),
      'addAcknowledgements' => 
      array (
        'name' => 'addAcknowledgements',
        'parameters' => 
        array (
          'section' => 
          array (
            'name' => 'section',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpOffice\\PhpWord\\Element\\Section',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 451,
            'endLine' => 451,
            'startColumn' => 42,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Remerciements (réglage `livret.acknowledgements`, une entrée par ligne) :
 * titre + liste à puces. Masqué si vide.
 */',
        'startLine' => 451,
        'endLine' => 469,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'currentClassName' => 'App\\Documents\\LivretDocx',
        'aliasName' => NULL,
      ),
      'addCommissionsProgramme' => 
      array (
        'name' => 'addCommissionsProgramme',
        'parameters' => 
        array (
          'section' => 
          array (
            'name' => 'section',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpOffice\\PhpWord\\Element\\Section',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 483,
            'endLine' => 483,
            'startColumn' => 46,
            'endColumn' => 61,
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
        'docComment' => '/**
 * « Différentes commissions et programmes du colloque » : comités numérotés
 * (1. Supervision, 2. Organisation, 3. Scientifique) puis « Programme
 * synthétique du colloque » - structure du livret de référence UK/UP.
 */',
        'startLine' => 483,
        'endLine' => 523,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'currentClassName' => 'App\\Documents\\LivretDocx',
        'aliasName' => NULL,
      ),
      'addProgrammeSynthetique' => 
      array (
        'name' => 'addProgrammeSynthetique',
        'parameters' => 
        array (
          'section' => 
          array (
            'name' => 'section',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpOffice\\PhpWord\\Element\\Section',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 526,
            'endLine' => 526,
            'startColumn' => 46,
            'endColumn' => 61,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'days' => 
          array (
            'name' => 'days',
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
            'startLine' => 526,
            'endLine' => 526,
            'startColumn' => 64,
            'endColumn' => 74,
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
        'docComment' => '/** Programme synthétique saisi (jours + lignes « horaire : libellé »), rendu texte. */',
        'startLine' => 526,
        'endLine' => 547,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'currentClassName' => 'App\\Documents\\LivretDocx',
        'aliasName' => NULL,
      ),
      'addProgramme' => 
      array (
        'name' => 'addProgramme',
        'parameters' => 
        array (
          'section' => 
          array (
            'name' => 'section',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpOffice\\PhpWord\\Element\\Section',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 550,
            'endLine' => 550,
            'startColumn' => 35,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Programme : sessions datées regroupées par jour (tableau Horaire / Session / Salle). */',
        'startLine' => 550,
        'endLine' => 564,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'currentClassName' => 'App\\Documents\\LivretDocx',
        'aliasName' => NULL,
      ),
      'addProgrammeFss' => 
      array (
        'name' => 'addProgrammeFss',
        'parameters' => 
        array (
          'section' => 
          array (
            'name' => 'section',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpOffice\\PhpWord\\Element\\Section',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 571,
            'endLine' => 571,
            'startColumn' => 38,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Programme « Journées scientifiques » (fidèle au livret FSS) : par JOUR, puis
 * par SALLE, chaque salle enchaînant ses SÉANCES (horaire, intitulé, modérateur)
 * et, sous chacune, la liste des communications « Titre, INTERVENANT ».
 */',
        'startLine' => 571,
        'endLine' => 607,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'currentClassName' => 'App\\Documents\\LivretDocx',
        'aliasName' => NULL,
      ),
      'programmeSessionRow' => 
      array (
        'name' => 'programmeSessionRow',
        'parameters' => 
        array (
          'table' => 
          array (
            'name' => 'table',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpOffice\\PhpWord\\Element\\Table',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 610,
            'endLine' => 610,
            'startColumn' => 42,
            'endColumn' => 53,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'sess' => 
          array (
            'name' => 'sess',
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
            'startLine' => 610,
            'endLine' => 610,
            'startColumn' => 56,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Une ligne de séance du tableau FSS : « Horaire » | intitulé + modérateur + communications. */',
        'startLine' => 610,
        'endLine' => 646,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'currentClassName' => 'App\\Documents\\LivretDocx',
        'aliasName' => NULL,
      ),
      'slotPresenter' => 
      array (
        'name' => 'slotPresenter',
        'parameters' => 
        array (
          'sl' => 
          array (
            'name' => 'sl',
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
            'startLine' => 649,
            'endLine' => 649,
            'startColumn' => 36,
            'endColumn' => 43,
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
        'docComment' => '/** Intervenant d\'une communication : auteur correspondant (ou 1er auteur), en MAJUSCULES. */',
        'startLine' => 649,
        'endLine' => 658,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'currentClassName' => 'App\\Documents\\LivretDocx',
        'aliasName' => NULL,
      ),
      'programmeByDay' => 
      array (
        'name' => 'programmeByDay',
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
 * Créneaux placés (communications), groupés et triés par jour. C\'est le
 * programme réel des communications, comme le livret uk-colloques.
 *
 * @return Collection<int|string, \\Illuminate\\Database\\Eloquent\\Collection<int, Slot>>
 */',
        'startLine' => 666,
        'endLine' => 673,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'currentClassName' => 'App\\Documents\\LivretDocx',
        'aliasName' => NULL,
      ),
      'programmeDayTable' => 
      array (
        'name' => 'programmeDayTable',
        'parameters' => 
        array (
          'section' => 
          array (
            'name' => 'section',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpOffice\\PhpWord\\Element\\Section',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 681,
            'endLine' => 681,
            'startColumn' => 40,
            'endColumn' => 55,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'day' => 
          array (
            'name' => 'day',
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
            'startLine' => 681,
            'endLine' => 681,
            'startColumn' => 58,
            'endColumn' => 68,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'slots' => 
          array (
            'name' => 'slots',
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
            'startLine' => 681,
            'endLine' => 681,
            'startColumn' => 71,
            'endColumn' => 87,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Tableau d\'un jour du programme : Horaire | Communication (titre + auteurs) |
 * Salle / Session.
 *
 * @param  \\Illuminate\\Database\\Eloquent\\Collection<int, Slot>  $slots
 */',
        'startLine' => 681,
        'endLine' => 717,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'currentClassName' => 'App\\Documents\\LivretDocx',
        'aliasName' => NULL,
      ),
      'slotAuthors' => 
      array (
        'name' => 'slotAuthors',
        'parameters' => 
        array (
          'sl' => 
          array (
            'name' => 'sl',
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
            'startLine' => 720,
            'endLine' => 720,
            'startColumn' => 34,
            'endColumn' => 41,
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
        'docComment' => '/** Auteurs d\'un créneau : « Nom (Affiliation); … » (harmonisation appliquée). */',
        'startLine' => 720,
        'endLine' => 732,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'currentClassName' => 'App\\Documents\\LivretDocx',
        'aliasName' => NULL,
      ),
      'contactLine' => 
      array (
        'name' => 'contactLine',
        'parameters' => 
        array (
          'corr' => 
          array (
            'name' => 'corr',
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
            'startLine' => 735,
            'endLine' => 735,
            'startColumn' => 34,
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
        'docComment' => '/** Coordonnées de l\'auteur correspondant : « Email : … Tél : … » (parties présentes). */',
        'startLine' => 735,
        'endLine' => 746,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'currentClassName' => 'App\\Documents\\LivretDocx',
        'aliasName' => NULL,
      ),
      'addBody' => 
      array (
        'name' => 'addBody',
        'parameters' => 
        array (
          'section' => 
          array (
            'name' => 'section',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpOffice\\PhpWord\\Element\\Section',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 749,
            'endLine' => 749,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Résumés des communications, regroupés par axe (titres en MAJUSCULES). */',
        'startLine' => 749,
        'endLine' => 780,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'currentClassName' => 'App\\Documents\\LivretDocx',
        'aliasName' => NULL,
      ),
      'addAuthorIndex' => 
      array (
        'name' => 'addAuthorIndex',
        'parameters' => 
        array (
          'section' => 
          array (
            'name' => 'section',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpOffice\\PhpWord\\Element\\Section',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 783,
            'endLine' => 783,
            'startColumn' => 37,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Index des auteurs : noms triés alphabétiquement, suivis des références de leurs résumés. */',
        'startLine' => 783,
        'endLine' => 805,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'currentClassName' => 'App\\Documents\\LivretDocx',
        'aliasName' => NULL,
      ),
      'addAbstract' => 
      array (
        'name' => 'addAbstract',
        'parameters' => 
        array (
          'section' => 
          array (
            'name' => 'section',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpOffice\\PhpWord\\Element\\Section',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 808,
            'endLine' => 808,
            'startColumn' => 34,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          's' => 
          array (
            'name' => 's',
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
            'startLine' => 808,
            'endLine' => 808,
            'startColumn' => 52,
            'endColumn' => 64,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'n' => 
          array (
            'name' => 'n',
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
            'startLine' => 808,
            'endLine' => 808,
            'startColumn' => 67,
            'endColumn' => 72,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'number' => 
          array (
            'name' => 'number',
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
            'startLine' => 808,
            'endLine' => 808,
            'startColumn' => 75,
            'endColumn' => 86,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'sizeAbstract' => 
          array (
            'name' => 'sizeAbstract',
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
            'startLine' => 808,
            'endLine' => 808,
            'startColumn' => 89,
            'endColumn' => 105,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
          'sizeAuthors' => 
          array (
            'name' => 'sizeAuthors',
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
            'startLine' => 808,
            'endLine' => 808,
            'startColumn' => 108,
            'endColumn' => 123,
            'parameterIndex' => 5,
            'isOptional' => false,
          ),
          'sizeBody' => 
          array (
            'name' => 'sizeBody',
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
            'startLine' => 808,
            'endLine' => 808,
            'startColumn' => 126,
            'endColumn' => 138,
            'parameterIndex' => 6,
            'isOptional' => false,
          ),
          'alignBody' => 
          array (
            'name' => 'alignBody',
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
            'startLine' => 808,
            'endLine' => 808,
            'startColumn' => 141,
            'endColumn' => 157,
            'parameterIndex' => 7,
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
        'docComment' => '/** Rendu d\'un résumé : référence, titre, auteurs+affiliations, correspondant, corps, mots-clés. */',
        'startLine' => 808,
        'endLine' => 913,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'currentClassName' => 'App\\Documents\\LivretDocx',
        'aliasName' => NULL,
      ),
      'paragraphs' => 
      array (
        'name' => 'paragraphs',
        'parameters' => 
        array (
          'content' => 
          array (
            'name' => 'content',
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
            'startLine' => 920,
            'endLine' => 920,
            'startColumn' => 33,
            'endColumn' => 47,
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
 * Découpe un contenu (HTML ou texte) en paragraphes bruts (fragments HTML inline conservés).
 *
 * @return list<string>
 */',
        'startLine' => 920,
        'endLine' => 941,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'currentClassName' => 'App\\Documents\\LivretDocx',
        'aliasName' => NULL,
      ),
      'addInlineRuns' => 
      array (
        'name' => 'addInlineRuns',
        'parameters' => 
        array (
          'run' => 
          array (
            'name' => 'run',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpOffice\\PhpWord\\Element\\TextRun',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 944,
            'endLine' => 944,
            'startColumn' => 36,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'html' => 
          array (
            'name' => 'html',
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
            'startLine' => 944,
            'endLine' => 944,
            'startColumn' => 50,
            'endColumn' => 61,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'size' => 
          array (
            'name' => 'size',
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
            'startLine' => 944,
            'endLine' => 944,
            'startColumn' => 64,
            'endColumn' => 72,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Ajoute à un TextRun le fragment (gras/italique de base conservés, autres balises retirées). */',
        'startLine' => 944,
        'endLine' => 970,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'currentClassName' => 'App\\Documents\\LivretDocx',
        'aliasName' => NULL,
      ),
      'mapName' => 
      array (
        'name' => 'mapName',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
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
            'startLine' => 973,
            'endLine' => 973,
            'startColumn' => 30,
            'endColumn' => 41,
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
        'docComment' => '/** Nom harmonisé : map IA si disponible, sinon normalisation déterministe, sinon original. */',
        'startLine' => 973,
        'endLine' => 984,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'currentClassName' => 'App\\Documents\\LivretDocx',
        'aliasName' => NULL,
      ),
      'mapAff' => 
      array (
        'name' => 'mapAff',
        'parameters' => 
        array (
          'aff' => 
          array (
            'name' => 'aff',
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
            'startLine' => 987,
            'endLine' => 987,
            'startColumn' => 29,
            'endColumn' => 39,
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
        'docComment' => '/** Affiliation harmonisée : map IA si disponible, sinon original. */',
        'startLine' => 987,
        'endLine' => 992,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'currentClassName' => 'App\\Documents\\LivretDocx',
        'aliasName' => NULL,
      ),
      'buildAiMaps' => 
      array (
        'name' => 'buildAiMaps',
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
        'docComment' => '/**
 * Harmonisation IA (logique uk-colloques) : collecte tous les noms et affiliations
 * des résumés, demande à l\'IA une correspondance normalisée (JSON), et remplit les
 * maps. En cas d\'IA indisponible ou d\'échec, les maps restent vides (repli déterministe).
 */',
        'startLine' => 999,
        'endLine' => 1052,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'currentClassName' => 'App\\Documents\\LivretDocx',
        'aliasName' => NULL,
      ),
      'harmonizeName' => 
      array (
        'name' => 'harmonizeName',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
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
            'startLine' => 1058,
            'endLine' => 1058,
            'startColumn' => 36,
            'endColumn' => 47,
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
 * Harmonisation déterministe d\'un nom d\'auteur : espaces normalisés, casse propre
 * (Prénom Nom), ponctuation resserrée. Repli sobre de l\'« harmonisation IA ».
 */',
        'startLine' => 1058,
        'endLine' => 1072,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'currentClassName' => 'App\\Documents\\LivretDocx',
        'aliasName' => NULL,
      ),
      'align' => 
      array (
        'name' => 'align',
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
            'startLine' => 1075,
            'endLine' => 1075,
            'startColumn' => 28,
            'endColumn' => 38,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'default' => 
          array (
            'name' => 'default',
            'default' => 
            array (
              'code' => '\'Centré\'',
              'attributes' => 
              array (
                'startLine' => 1075,
                'endLine' => 1075,
                'startTokenPos' => 10836,
                'startFilePos' => 47979,
                'endTokenPos' => 10836,
                'endFilePos' => 47987,
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
            'startLine' => 1075,
            'endLine' => 1075,
            'startColumn' => 41,
            'endColumn' => 67,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Alignement OOXML d\'après un réglage d\'apparence (Centré/Gauche/Droite/Justifié). */',
        'startLine' => 1075,
        'endLine' => 1083,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'currentClassName' => 'App\\Documents\\LivretDocx',
        'aliasName' => NULL,
      ),
      'partTitle' => 
      array (
        'name' => 'partTitle',
        'parameters' => 
        array (
          'section' => 
          array (
            'name' => 'section',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpOffice\\PhpWord\\Element\\Section',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1086,
            'endLine' => 1086,
            'startColumn' => 32,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'text' => 
          array (
            'name' => 'text',
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
            'startLine' => 1086,
            'endLine' => 1086,
            'startColumn' => 50,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Titre de section (paragraphe gras, aligné selon `livret.align_titles`). */',
        'startLine' => 1086,
        'endLine' => 1092,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'currentClassName' => 'App\\Documents\\LivretDocx',
        'aliasName' => NULL,
      ),
      'displayName' => 
      array (
        'name' => 'displayName',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
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
            'startLine' => 1095,
            'endLine' => 1095,
            'startColumn' => 34,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Nom d\'auteur pour l\'affichage : en MAJUSCULES (convention du livret). */',
        'startLine' => 1095,
        'endLine' => 1098,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'currentClassName' => 'App\\Documents\\LivretDocx',
        'aliasName' => NULL,
      ),
      'hex' => 
      array (
        'name' => 'hex',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
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
            'startLine' => 1101,
            'endLine' => 1101,
            'startColumn' => 33,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'fallback' => 
          array (
            'name' => 'fallback',
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
            'startLine' => 1101,
            'endLine' => 1101,
            'startColumn' => 48,
            'endColumn' => 63,
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
        'docComment' => '/** Normalise une couleur CSS (#rrggbb) en hex Word (RRGGBB), avec repli. */',
        'startLine' => 1101,
        'endLine' => 1106,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 20,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LivretDocx',
        'implementingClassName' => 'App\\Documents\\LivretDocx',
        'currentClassName' => 'App\\Documents\\LivretDocx',
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