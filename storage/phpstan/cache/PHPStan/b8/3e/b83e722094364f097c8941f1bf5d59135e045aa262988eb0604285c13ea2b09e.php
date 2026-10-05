<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Support\EmailTemplates.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Support\EmailTemplates
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-2eeb663419de5485ebc08be24f0d0924fde5bbc33af108689ff2f880217c1095',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Support\\EmailTemplates',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Support/EmailTemplates.php',
      ),
    ),
    'namespace' => 'App\\Support',
    'name' => 'App\\Support\\EmailTemplates',
    'shortName' => 'EmailTemplates',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Modèles d\'e-mails éditables : sujet, titre et texte d\'introduction
 * personnalisables par l\'admin (stockés dans le réglage `email_templates`),
 * avec repli sur des défauts. Les détails structurés (références, boutons) et le
 * gabarit de marque restent fixes. Placeholders remplacés au rendu.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 14,
    'endLine' => 247,
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
      'PLACEHOLDERS' => 
      array (
        'declaringClassName' => 'App\\Support\\EmailTemplates',
        'implementingClassName' => 'App\\Support\\EmailTemplates',
        'name' => 'PLACEHOLDERS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'{prenom}\', \'{nom_complet}\', \'{ref}\', \'{titre}\', \'{event}\']',
          'attributes' => 
          array (
            'startLine' => 17,
            'endLine' => 17,
            'startTokenPos' => 35,
            'startFilePos' => 526,
            'endTokenPos' => 49,
            'endFilePos' => 585,
          ),
        ),
        'docComment' => '/** Placeholders disponibles (selon le contexte du modèle). */',
        'attributes' => 
        array (
        ),
        'startLine' => 17,
        'endLine' => 17,
        'startColumn' => 5,
        'endColumn' => 93,
      ),
      'TEMPLATES' => 
      array (
        'declaringClassName' => 'App\\Support\\EmailTemplates',
        'implementingClassName' => 'App\\Support\\EmailTemplates',
        'name' => 'TEMPLATES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'submission_submitted\' => [\'label\' => \'Soumission - accusé de réception\', \'subject\' => \'Accusé de réception de votre communication\', \'heading\' => \'Communication bien reçue\', \'intro\' => \'Nous accusons réception de votre communication. Elle sera examinée par le comité scientifique.\'], \'submission_under_review\' => [\'label\' => \'Soumission - mise en relecture\', \'subject\' => \'Votre communication est en relecture\', \'heading\' => \'Communication en relecture\', \'intro\' => \'Votre communication a été transmise à la relecture. Vous serez informé(e) de la décision.\'], \'submission_revision\' => [\'label\' => \'Soumission - correction demandée\', \'subject\' => \'Correction demandée pour votre communication\', \'heading\' => \'Correction demandée\', \'intro\' => \'Le comité vous demande d’apporter des corrections à votre communication avant décision.\'], \'submission_accepted\' => [\'label\' => \'Soumission - acceptée\', \'subject\' => \'Votre communication est acceptée\', \'heading\' => \'Communication acceptée\', \'intro\' => \'Nous avons le plaisir de vous informer que votre communication est acceptée.\'], \'submission_rejected\' => [\'label\' => \'Soumission - refusée\', \'subject\' => \'Décision concernant votre communication\', \'heading\' => \'Communication non retenue\', \'intro\' => \'Après examen, votre communication n’a pas été retenue pour cet événement.\'], \'participant_invitation\' => [\'label\' => \'Invitation participant\', \'subject\' => \'Invitation\', \'heading\' => \'Vous êtes invité(e) 🎓\', \'intro\' => \'Nous avons le plaisir de vous convier à cet événement.\']]',
          'attributes' => 
          array (
            'startLine' => 24,
            'endLine' => 61,
            'startTokenPos' => 62,
            'startFilePos' => 780,
            'endTokenPos' => 286,
            'endFilePos' => 2754,
          ),
        ),
        'docComment' => '/**
 * Modèles et leurs valeurs par défaut.
 *
 * @var array<string, array{label:string, subject:string, heading:string, intro:string}>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 24,
        'endLine' => 61,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
      'MERGE_TAGS' => 
      array (
        'declaringClassName' => 'App\\Support\\EmailTemplates',
        'implementingClassName' => 'App\\Support\\EmailTemplates',
        'name' => 'MERGE_TAGS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'{{participant_name}}\', \'{{participant_email}}\', \'{{edition_name}}\', \'{{edition_dates}}\', \'{{communication_title}}\', \'{{amount}}\', \'{{payment_ref}}\', \'{{attestation_url}}\', \'{{badge_url}}\', \'{{login_url}}\', \'{{session_date}}\', \'{{session_room}}\']',
          'attributes' => 
          array (
            'startLine' => 64,
            'endLine' => 68,
            'startTokenPos' => 299,
            'startFilePos' => 2875,
            'endTokenPos' => 337,
            'endFilePos' => 3152,
          ),
        ),
        'docComment' => '/** Balises de fusion proposées dans l\'éditeur de modèle (puces cliquables). */',
        'attributes' => 
        array (
        ),
        'startLine' => 64,
        'endLine' => 68,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
      'TRIGGER_OPTIONS' => 
      array (
        'declaringClassName' => 'App\\Support\\EmailTemplates',
        'implementingClassName' => 'App\\Support\\EmailTemplates',
        'name' => 'TRIGGER_OPTIONS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'Manuel / programmé\', \'Paiement reçu\', \'Soumission reçue\', \'Correction demandée\', \'Résumé accepté\', \'Résumé refusé\', \'Envoi attestation\', \'Badge disponible\', \'Rappel (programmé)\']',
          'attributes' => 
          array (
            'startLine' => 71,
            'endLine' => 74,
            'startTokenPos' => 350,
            'startFilePos' => 3279,
            'endTokenPos' => 379,
            'endFilePos' => 3492,
          ),
        ),
        'docComment' => '/** Options de déclencheur proposées à la création/événement d\'un modèle. */',
        'attributes' => 
        array (
        ),
        'startLine' => 71,
        'endLine' => 74,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
      'TRIGGERS' => 
      array (
        'declaringClassName' => 'App\\Support\\EmailTemplates',
        'implementingClassName' => 'App\\Support\\EmailTemplates',
        'name' => 'TRIGGERS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'submission_submitted\' => \'Soumission reçue\', \'submission_under_review\' => \'Mise en relecture\', \'submission_revision\' => \'Correction demandée\', \'submission_accepted\' => \'Résumé accepté\', \'submission_rejected\' => \'Résumé refusé\', \'participant_invitation\' => \'Invitation (manuel)\']',
          'attributes' => 
          array (
            'startLine' => 77,
            'endLine' => 84,
            'startTokenPos' => 392,
            'startFilePos' => 3605,
            'endTokenPos' => 436,
            'endFilePos' => 3947,
          ),
        ),
        'docComment' => '/** Déclencheur (événement) qui envoie automatiquement chaque modèle. */',
        'attributes' => 
        array (
        ),
        'startLine' => 77,
        'endLine' => 84,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'customKeys' => 
      array (
        'name' => 'customKeys',
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
        'docComment' => '/** Modèles personnalisés créés depuis le back-office (hors modèles intégrés). */',
        'startLine' => 87,
        'endLine' => 92,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\EmailTemplates',
        'implementingClassName' => 'App\\Support\\EmailTemplates',
        'currentClassName' => 'App\\Support\\EmailTemplates',
        'aliasName' => NULL,
      ),
      'keys' => 
      array (
        'name' => 'keys',
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
        'docComment' => '/** Toutes les clés de modèles : intégrés puis personnalisés. */',
        'startLine' => 95,
        'endLine' => 98,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\EmailTemplates',
        'implementingClassName' => 'App\\Support\\EmailTemplates',
        'currentClassName' => 'App\\Support\\EmailTemplates',
        'aliasName' => NULL,
      ),
      'isValid' => 
      array (
        'name' => 'isValid',
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
            'startLine' => 100,
            'endLine' => 100,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 100,
        'endLine' => 104,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\EmailTemplates',
        'implementingClassName' => 'App\\Support\\EmailTemplates',
        'currentClassName' => 'App\\Support\\EmailTemplates',
        'aliasName' => NULL,
      ),
      'isCustom' => 
      array (
        'name' => 'isCustom',
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
            'startLine' => 107,
            'endLine' => 107,
            'startColumn' => 37,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Un modèle personnalisé (créé par l\'admin) vs. un modèle intégré. */',
        'startLine' => 107,
        'endLine' => 111,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\EmailTemplates',
        'implementingClassName' => 'App\\Support\\EmailTemplates',
        'currentClassName' => 'App\\Support\\EmailTemplates',
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
            'startLine' => 114,
            'endLine' => 114,
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
        'docComment' => '/** Libellé lisible d\'un modèle (repli sur « Campagne » / la clé). */',
        'startLine' => 114,
        'endLine' => 125,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\EmailTemplates',
        'implementingClassName' => 'App\\Support\\EmailTemplates',
        'currentClassName' => 'App\\Support\\EmailTemplates',
        'aliasName' => NULL,
      ),
      'trigger' => 
      array (
        'name' => 'trigger',
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
            'startLine' => 128,
            'endLine' => 128,
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
        'docComment' => '/** Déclencheur lisible d\'un modèle. */',
        'startLine' => 128,
        'endLine' => 136,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\EmailTemplates',
        'implementingClassName' => 'App\\Support\\EmailTemplates',
        'currentClassName' => 'App\\Support\\EmailTemplates',
        'aliasName' => NULL,
      ),
      'body' => 
      array (
        'name' => 'body',
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
            'startColumn' => 33,
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
        'docComment' => '/** Corps du message (override admin, sinon dérivé du titre + intro par défaut). */',
        'startLine' => 139,
        'endLine' => 149,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\EmailTemplates',
        'implementingClassName' => 'App\\Support\\EmailTemplates',
        'currentClassName' => 'App\\Support\\EmailTemplates',
        'aliasName' => NULL,
      ),
      'active' => 
      array (
        'name' => 'active',
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
            'startLine' => 152,
            'endLine' => 152,
            'startColumn' => 35,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Modèle actif (override admin, sinon actif par défaut). */',
        'startLine' => 152,
        'endLine' => 157,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\EmailTemplates',
        'implementingClassName' => 'App\\Support\\EmailTemplates',
        'currentClassName' => 'App\\Support\\EmailTemplates',
        'aliasName' => NULL,
      ),
      'raw' => 
      array (
        'name' => 'raw',
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
            'startLine' => 164,
            'endLine' => 164,
            'startColumn' => 32,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Valeurs (brutes, non rendues) d\'un modèle : override admin ou défaut.
 *
 * @return array{subject:string, heading:string, intro:string}
 */',
        'startLine' => 164,
        'endLine' => 179,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\EmailTemplates',
        'implementingClassName' => 'App\\Support\\EmailTemplates',
        'currentClassName' => 'App\\Support\\EmailTemplates',
        'aliasName' => NULL,
      ),
      'render' => 
      array (
        'name' => 'render',
        'parameters' => 
        array (
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
            'startLine' => 182,
            'endLine' => 182,
            'startColumn' => 35,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'context' => 
          array (
            'name' => 'context',
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
            'startLine' => 182,
            'endLine' => 182,
            'startColumn' => 49,
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
        'docComment' => '/** Remplace les placeholders `{cle}` par les valeurs du contexte. */',
        'startLine' => 182,
        'endLine' => 190,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\EmailTemplates',
        'implementingClassName' => 'App\\Support\\EmailTemplates',
        'currentClassName' => 'App\\Support\\EmailTemplates',
        'aliasName' => NULL,
      ),
      'forSubmission' => 
      array (
        'name' => 'forSubmission',
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
            'startLine' => 197,
            'endLine' => 197,
            'startColumn' => 42,
            'endColumn' => 63,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'statusKey' => 
          array (
            'name' => 'statusKey',
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
            'startLine' => 197,
            'endLine' => 197,
            'startColumn' => 66,
            'endColumn' => 82,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Modèle rendu pour une soumission (statut du workflow).
 *
 * @return array{subject:string, heading:string, intro:string}
 */',
        'startLine' => 197,
        'endLine' => 210,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\EmailTemplates',
        'implementingClassName' => 'App\\Support\\EmailTemplates',
        'currentClassName' => 'App\\Support\\EmailTemplates',
        'aliasName' => NULL,
      ),
      'forParticipant' => 
      array (
        'name' => 'forParticipant',
        'parameters' => 
        array (
          'participant' => 
          array (
            'name' => 'participant',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Participant',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 217,
            'endLine' => 217,
            'startColumn' => 43,
            'endColumn' => 66,
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
 * Modèle rendu pour l\'invitation d\'un participant.
 *
 * @return array{subject:string, heading:string, intro:string}
 */',
        'startLine' => 217,
        'endLine' => 228,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\EmailTemplates',
        'implementingClassName' => 'App\\Support\\EmailTemplates',
        'currentClassName' => 'App\\Support\\EmailTemplates',
        'aliasName' => NULL,
      ),
      'renderAll' => 
      array (
        'name' => 'renderAll',
        'parameters' => 
        array (
          'tpl' => 
          array (
            'name' => 'tpl',
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
            'startLine' => 234,
            'endLine' => 234,
            'startColumn' => 41,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'context' => 
          array (
            'name' => 'context',
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
            'startLine' => 234,
            'endLine' => 234,
            'startColumn' => 53,
            'endColumn' => 66,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param  array{subject:string, heading:string, intro:string}  $tpl
 * @return array{subject:string, heading:string, intro:string}
 */',
        'startLine' => 234,
        'endLine' => 241,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 18,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\EmailTemplates',
        'implementingClassName' => 'App\\Support\\EmailTemplates',
        'currentClassName' => 'App\\Support\\EmailTemplates',
        'aliasName' => NULL,
      ),
      'firstName' => 
      array (
        'name' => 'firstName',
        'parameters' => 
        array (
          'fullName' => 
          array (
            'name' => 'fullName',
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
            'startLine' => 243,
            'endLine' => 243,
            'startColumn' => 41,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 243,
        'endLine' => 246,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 18,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\EmailTemplates',
        'implementingClassName' => 'App\\Support\\EmailTemplates',
        'currentClassName' => 'App\\Support\\EmailTemplates',
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