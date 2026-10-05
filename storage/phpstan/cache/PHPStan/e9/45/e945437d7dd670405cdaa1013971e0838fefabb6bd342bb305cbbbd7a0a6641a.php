<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Support\SubmissionFormConfig.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Support\SubmissionFormConfig
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-39738616a2ef692c49082945b791ed913405eb4cb9785adaba9194bf7a979a90',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Support\\SubmissionFormConfig',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Support/SubmissionFormConfig.php',
      ),
    ),
    'namespace' => 'App\\Support',
    'name' => 'App\\Support\\SubmissionFormConfig',
    'shortName' => 'SubmissionFormConfig',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Configuration du formulaire de soumission d\'un événement (paramétrable par
 * l\'admin, stockée dans settings.submission_form). Résout les libellés, aides,
 * options requis/activé et les champs personnalisés, avec des valeurs par
 * défaut alignées sur le formulaire historique.
 *
 * Rappel : l\'identité (civilité, nom, e-mail, téléphone, pays, affiliation) est
 * portée par le compte (source de vérité), pas par ce formulaire.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 16,
    'endLine' => 186,
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
      'STANDARD_DEFAULTS' => 
      array (
        'declaringClassName' => 'App\\Support\\SubmissionFormConfig',
        'implementingClassName' => 'App\\Support\\SubmissionFormConfig',
        'name' => 'STANDARD_DEFAULTS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'title\' => [\'label\' => \'Titre\', \'help\' => \'\', \'required\' => true], \'track\' => [\'label\' => \'Axe thématique\', \'help\' => \'\', \'required\' => true], \'type\' => [\'label\' => \'Mode de présentation\', \'help\' => \'\', \'enabled\' => true, \'required\' => true], \'keywords\' => [\'label\' => \'Mots-clés\', \'help\' => \'Séparés par des virgules\', \'enabled\' => true, \'required\' => false], \'abstract\' => [\'label\' => \'Résumé\', \'help\' => \'\', \'required\' => true, \'min\' => 50, \'max\' => 8000], \'file\' => [\'label\' => \'Fichier joint\', \'help\' => \'PDF/DOC/DOCX, max 8 Mo\', \'enabled\' => true, \'required\' => false]]',
          'attributes' => 
          array (
            'startLine' => 19,
            'endLine' => 26,
            'startTokenPos' => 30,
            'startFilePos' => 635,
            'endTokenPos' => 229,
            'endFilePos' => 1272,
          ),
        ),
        'docComment' => '/** Champs standard et leurs valeurs par défaut. */',
        'attributes' => 
        array (
        ),
        'startLine' => 19,
        'endLine' => 26,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
      'CUSTOM_TYPES' => 
      array (
        'declaringClassName' => 'App\\Support\\SubmissionFormConfig',
        'implementingClassName' => 'App\\Support\\SubmissionFormConfig',
        'name' => 'CUSTOM_TYPES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'text\' => \'Texte court\', \'textarea\' => \'Texte long\', \'select\' => \'Liste déroulante\', \'email\' => \'E-mail\', \'number\' => \'Nombre\', \'date\' => \'Date\', \'checkbox\' => \'Case à cocher\']',
          'attributes' => 
          array (
            'startLine' => 29,
            'endLine' => 37,
            'startTokenPos' => 242,
            'startFilePos' => 1363,
            'endTokenPos' => 293,
            'endFilePos' => 1604,
          ),
        ),
        'docComment' => '/** Types de champs personnalisés disponibles. */',
        'attributes' => 
        array (
        ),
        'startLine' => 29,
        'endLine' => 37,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
      'RULE_DEFAULTS' => 
      array (
        'declaringClassName' => 'App\\Support\\SubmissionFormConfig',
        'implementingClassName' => 'App\\Support\\SubmissionFormConfig',
        'name' => 'RULE_DEFAULTS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[
    \'title_min\' => 0,
    // titre : minimum de CARACTÈRES (0 = pas de minimum)
    \'title_max\' => 256,
    // titre : maximum de CARACTÈRES
    \'word_min\' => 0,
    // résumé : minimum de MOTS (0 = pas de minimum)
    \'word_limit\' => 300,
    // résumé : maximum de MOTS
    \'keywords_min\' => 0,
    // mots-clés : minimum (0 = pas de minimum)
    \'keywords_max\' => 6,
    // mots-clés : maximum
    \'max_authors\' => 8,
    \'file_max_mb\' => 5,
    \'file_formats\' => \'pdf, docx\',
    \'require_account\' => true,
    \'no_duplicate\' => true,
    \'live_counter\' => true,
    \'captcha\' => false,
]',
          'attributes' => 
          array (
            'startLine' => 40,
            'endLine' => 54,
            'startTokenPos' => 306,
            'startFilePos' => 1734,
            'endTokenPos' => 411,
            'endFilePos' => 2387,
          ),
        ),
        'docComment' => '/** Règles & limites par défaut du formulaire (onglet « Règles » de l\'éditeur). */',
        'attributes' => 
        array (
        ),
        'startLine' => 40,
        'endLine' => 54,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
    ),
    'immediateProperties' => 
    array (
      'event' => 
      array (
        'declaringClassName' => 'App\\Support\\SubmissionFormConfig',
        'implementingClassName' => 'App\\Support\\SubmissionFormConfig',
        'name' => 'event',
        'modifiers' => 1,
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
        'startLine' => 56,
        'endLine' => 56,
        'startColumn' => 33,
        'endColumn' => 51,
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
            'startLine' => 56,
            'endLine' => 56,
            'startColumn' => 33,
            'endColumn' => 51,
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
        'startLine' => 56,
        'endLine' => 56,
        'startColumn' => 5,
        'endColumn' => 55,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\SubmissionFormConfig',
        'implementingClassName' => 'App\\Support\\SubmissionFormConfig',
        'currentClassName' => 'App\\Support\\SubmissionFormConfig',
        'aliasName' => NULL,
      ),
      'wordCount' => 
      array (
        'name' => 'wordCount',
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
            'startLine' => 59,
            'endLine' => 59,
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
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Compte les mots d\'un texte (balises HTML retirées). Utilisé pour les limites du résumé. */',
        'startLine' => 59,
        'endLine' => 64,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\SubmissionFormConfig',
        'implementingClassName' => 'App\\Support\\SubmissionFormConfig',
        'currentClassName' => 'App\\Support\\SubmissionFormConfig',
        'aliasName' => NULL,
      ),
      'rules' => 
      array (
        'name' => 'rules',
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
 * Règles & limites du formulaire (fusionnées avec les valeurs par défaut).
 *
 * @return array<string, mixed>
 */',
        'startLine' => 71,
        'endLine' => 74,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\SubmissionFormConfig',
        'implementingClassName' => 'App\\Support\\SubmissionFormConfig',
        'currentClassName' => 'App\\Support\\SubmissionFormConfig',
        'aliasName' => NULL,
      ),
      'raw' => 
      array (
        'name' => 'raw',
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
        'docComment' => '/** @return array<string, mixed> */',
        'startLine' => 77,
        'endLine' => 80,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\SubmissionFormConfig',
        'implementingClassName' => 'App\\Support\\SubmissionFormConfig',
        'currentClassName' => 'App\\Support\\SubmissionFormConfig',
        'aliasName' => NULL,
      ),
      'fieldConf' => 
      array (
        'name' => 'fieldConf',
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
            'startLine' => 83,
            'endLine' => 83,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** @return array<string, mixed> */',
        'startLine' => 83,
        'endLine' => 86,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\SubmissionFormConfig',
        'implementingClassName' => 'App\\Support\\SubmissionFormConfig',
        'currentClassName' => 'App\\Support\\SubmissionFormConfig',
        'aliasName' => NULL,
      ),
      'label' => 
      array (
        'name' => 'label',
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
            'startLine' => 88,
            'endLine' => 88,
            'startColumn' => 27,
            'endColumn' => 37,
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
        'docComment' => NULL,
        'startLine' => 88,
        'endLine' => 93,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\SubmissionFormConfig',
        'implementingClassName' => 'App\\Support\\SubmissionFormConfig',
        'currentClassName' => 'App\\Support\\SubmissionFormConfig',
        'aliasName' => NULL,
      ),
      'help' => 
      array (
        'name' => 'help',
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
            'startLine' => 95,
            'endLine' => 95,
            'startColumn' => 26,
            'endColumn' => 36,
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
        'docComment' => NULL,
        'startLine' => 95,
        'endLine' => 102,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\SubmissionFormConfig',
        'implementingClassName' => 'App\\Support\\SubmissionFormConfig',
        'currentClassName' => 'App\\Support\\SubmissionFormConfig',
        'aliasName' => NULL,
      ),
      'enabled' => 
      array (
        'name' => 'enabled',
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 104,
        'endLine' => 109,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\SubmissionFormConfig',
        'implementingClassName' => 'App\\Support\\SubmissionFormConfig',
        'currentClassName' => 'App\\Support\\SubmissionFormConfig',
        'aliasName' => NULL,
      ),
      'required' => 
      array (
        'name' => 'required',
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
            'startLine' => 111,
            'endLine' => 111,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 111,
        'endLine' => 116,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\SubmissionFormConfig',
        'implementingClassName' => 'App\\Support\\SubmissionFormConfig',
        'currentClassName' => 'App\\Support\\SubmissionFormConfig',
        'aliasName' => NULL,
      ),
      'titleMin' => 
      array (
        'name' => 'titleMin',
        'parameters' => 
        array (
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
        'docComment' => '/** Titre : minimum de CARACTÈRES (0 = pas de minimum). */',
        'startLine' => 119,
        'endLine' => 122,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\SubmissionFormConfig',
        'implementingClassName' => 'App\\Support\\SubmissionFormConfig',
        'currentClassName' => 'App\\Support\\SubmissionFormConfig',
        'aliasName' => NULL,
      ),
      'titleMax' => 
      array (
        'name' => 'titleMax',
        'parameters' => 
        array (
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
        'docComment' => '/** Titre : maximum de CARACTÈRES. */',
        'startLine' => 125,
        'endLine' => 128,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\SubmissionFormConfig',
        'implementingClassName' => 'App\\Support\\SubmissionFormConfig',
        'currentClassName' => 'App\\Support\\SubmissionFormConfig',
        'aliasName' => NULL,
      ),
      'abstractMin' => 
      array (
        'name' => 'abstractMin',
        'parameters' => 
        array (
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
        'docComment' => '/** Résumé : minimum de MOTS (les limites du résumé sont en mots, pas en caractères). */',
        'startLine' => 131,
        'endLine' => 134,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\SubmissionFormConfig',
        'implementingClassName' => 'App\\Support\\SubmissionFormConfig',
        'currentClassName' => 'App\\Support\\SubmissionFormConfig',
        'aliasName' => NULL,
      ),
      'abstractMax' => 
      array (
        'name' => 'abstractMax',
        'parameters' => 
        array (
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
        'docComment' => '/** Résumé : maximum de MOTS. */',
        'startLine' => 137,
        'endLine' => 140,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\SubmissionFormConfig',
        'implementingClassName' => 'App\\Support\\SubmissionFormConfig',
        'currentClassName' => 'App\\Support\\SubmissionFormConfig',
        'aliasName' => NULL,
      ),
      'keywordsMin' => 
      array (
        'name' => 'keywordsMin',
        'parameters' => 
        array (
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
        'docComment' => '/** Mots-clés : nombre minimum. */',
        'startLine' => 143,
        'endLine' => 146,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\SubmissionFormConfig',
        'implementingClassName' => 'App\\Support\\SubmissionFormConfig',
        'currentClassName' => 'App\\Support\\SubmissionFormConfig',
        'aliasName' => NULL,
      ),
      'keywordsMax' => 
      array (
        'name' => 'keywordsMax',
        'parameters' => 
        array (
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
        'docComment' => '/** Mots-clés : nombre maximum. */',
        'startLine' => 149,
        'endLine' => 152,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\SubmissionFormConfig',
        'implementingClassName' => 'App\\Support\\SubmissionFormConfig',
        'currentClassName' => 'App\\Support\\SubmissionFormConfig',
        'aliasName' => NULL,
      ),
      'customFields' => 
      array (
        'name' => 'customFields',
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
 * Champs personnalisés normalisés.
 *
 * @return array<int, array{key:string, label:string, type:string, required:bool, help:string, options:list<string>}>
 */',
        'startLine' => 159,
        'endLine' => 185,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\SubmissionFormConfig',
        'implementingClassName' => 'App\\Support\\SubmissionFormConfig',
        'currentClassName' => 'App\\Support\\SubmissionFormConfig',
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