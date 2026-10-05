<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Http\Controllers\Admin\TemplateDesignerController.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Http\Controllers\Admin\TemplateDesignerController
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-e3b65b79a7153e2292b50b7befca68ca4319f94ad5ebb88cd638d8a132d180bb',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Http\\Controllers\\Admin\\TemplateDesignerController',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Http/Controllers/Admin/TemplateDesignerController.php',
      ),
    ),
    'namespace' => 'App\\Http\\Controllers\\Admin',
    'name' => 'App\\Http\\Controllers\\Admin\\TemplateDesignerController',
    'shortName' => 'TemplateDesignerController',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Éditeur visuel de modèles (badges / attestations), contrôleur + Blade. Le
 * canvas drag-drop est piloté par l\'Alpine `docEditor` (autoritatif) ; la
 * génération IA et l\'enregistrement passent par des endpoints JSON, la mise en
 * page traversant toujours le sanitizer déterministe avant persistance.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 26,
    'endLine' => 339,
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
      'ATTESTATION_TYPES' => 
      array (
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\TemplateDesignerController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\TemplateDesignerController',
        'name' => 'ATTESTATION_TYPES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\\App\\Models\\Attestation::TYPES',
          'attributes' => 
          array (
            'startLine' => 29,
            'endLine' => 29,
            'startTokenPos' => 99,
            'startFilePos' => 996,
            'endTokenPos' => 101,
            'endFilePos' => 1013,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 29,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 56,
      ),
      'VARIABLES' => 
      array (
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\TemplateDesignerController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\TemplateDesignerController',
        'name' => 'VARIABLES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'{name}\' => \'Nom du bénéficiaire\', \'{role}\' => \'Rôle\', \'{institution}\' => \'Institution\', \'{type}\' => \'Type d’attestation\', \'{org}\' => \'Organisateur\', \'{event}\' => \'Événement\', \'{year}\' => \'Année\', \'{location}\' => \'Lieu\', \'{date}\' => \'Date d’émission\', \'{ref}\' => \'Référence\', \'{verify_url}\' => \'URL de vérification\', \'{communication}\' => \'Titre de la communication\', \'{communication_ref}\' => \'Réf. communication\', \'{formation}\' => \'Titre de la formation\', \'{formation_theme}\' => \'Thème de la formation\', \'{formateurs}\' => \'Formateur(s) de la formation\']',
          'attributes' => 
          array (
            'startLine' => 32,
            'endLine' => 49,
            'startTokenPos' => 114,
            'startFilePos' => 1117,
            'endTokenPos' => 228,
            'endFilePos' => 1821,
          ),
        ),
        'docComment' => '/** Variables insérables dans les champs texte, avec libellé. */',
        'attributes' => 
        array (
        ),
        'startLine' => 32,
        'endLine' => 49,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
      'SCOPES' => 
      array (
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\TemplateDesignerController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\TemplateDesignerController',
        'name' => 'SCOPES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'soumission\' => \'Par communication\', \'acceptation\' => \'Par communication\', \'communication\' => \'Par communication\', \'participation\' => \'Participant\', \'moderation\' => \'Par session\', \'rapporteur\' => \'Par session\', \'formation\' => \'Par formation\', \'formation_animation\' => \'Par formation\']',
          'attributes' => 
          array (
            'startLine' => 117,
            'endLine' => 126,
            'startTokenPos' => 782,
            'startFilePos' => 4881,
            'endTokenPos' => 840,
            'endFilePos' => 5236,
          ),
        ),
        'docComment' => '/**
 * Portée d\'un modèle d\'attestation : déduite du type (elle pilote l\'émission,
 * elle ne se choisit donc pas librement). Affichée à la création pour que
 * l\'admin sache ce qu\'il crée.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 117,
        'endLine' => 126,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
      'BADGE_FORMATS' => 
      array (
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\TemplateDesignerController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\TemplateDesignerController',
        'name' => 'BADGE_FORMATS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'a6\' => \'A6 (105 × 148 mm)\', \'a7\' => \'A7 (74 × 105 mm)\', \'cr80\' => \'CR80 (85,6 × 54 mm)\']',
          'attributes' => 
          array (
            'startLine' => 129,
            'endLine' => 133,
            'startTokenPos' => 853,
            'startFilePos' => 5328,
            'endTokenPos' => 876,
            'endFilePos' => 5451,
          ),
        ),
        'docComment' => '/** Formats de badge proposés à la création. */',
        'attributes' => 
        array (
        ),
        'startLine' => 129,
        'endLine' => 133,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
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
            'startLine' => 51,
            'endLine' => 51,
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
        'startLine' => 51,
        'endLine' => 54,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Controllers\\Admin',
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\TemplateDesignerController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\TemplateDesignerController',
        'currentClassName' => 'App\\Http\\Controllers\\Admin\\TemplateDesignerController',
        'aliasName' => NULL,
      ),
      'preview' => 
      array (
        'name' => 'preview',
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
            'startLine' => 62,
            'endLine' => 62,
            'startColumn' => 29,
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
            'name' => 'Illuminate\\View\\View',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Aperçu d\'un modèle (attestation / badge) : ouvre le concepteur en mode
 * APERÇU VERROUILLÉ. On réutilise le canevas exact du concepteur (polices,
 * logos, mise en forme) et on remplace les jetons par des valeurs d\'exemple,
 * pour voir EXACTEMENT le design du modèle - pas une reconstitution.
 */',
        'startLine' => 62,
        'endLine' => 65,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Controllers\\Admin',
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\TemplateDesignerController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\TemplateDesignerController',
        'currentClassName' => 'App\\Http\\Controllers\\Admin\\TemplateDesignerController',
        'aliasName' => NULL,
      ),
      'renderDesigner' => 
      array (
        'name' => 'renderDesigner',
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
            'startLine' => 71,
            'endLine' => 71,
            'startColumn' => 37,
            'endColumn' => 52,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'preview' => 
          array (
            'name' => 'preview',
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
            'startLine' => 71,
            'endLine' => 71,
            'startColumn' => 55,
            'endColumn' => 67,
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
            'name' => 'Illuminate\\View\\View',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Rend le concepteur de documents (attestation/badge). En mode `$preview`, la
 * page s\'ouvre verrouillée sur l\'aperçu plein écran (chrome d\'événement masqué).
 */',
        'startLine' => 71,
        'endLine' => 110,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Http\\Controllers\\Admin',
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\TemplateDesignerController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\TemplateDesignerController',
        'currentClassName' => 'App\\Http\\Controllers\\Admin\\TemplateDesignerController',
        'aliasName' => NULL,
      ),
      'create' => 
      array (
        'name' => 'create',
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
            'startLine' => 140,
            'endLine' => 140,
            'startColumn' => 28,
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
            'name' => 'Illuminate\\Http\\RedirectResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Création d\'un modèle : on renseigne d\'abord le libellé, le type et (pour un
 * badge) le format, PUIS on ouvre le concepteur. Sans cette étape, le modèle
 * était créé implicitement au premier enregistrement, sans identité.
 */',
        'startLine' => 140,
        'endLine' => 168,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Controllers\\Admin',
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\TemplateDesignerController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\TemplateDesignerController',
        'currentClassName' => 'App\\Http\\Controllers\\Admin\\TemplateDesignerController',
        'aliasName' => NULL,
      ),
      'deleteCode' => 
      array (
        'name' => 'deleteCode',
        'parameters' => 
        array (
          'template' => 
          array (
            'name' => 'template',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\DocumentTemplate',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 175,
            'endLine' => 175,
            'startColumn' => 39,
            'endColumn' => 64,
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
 * Code à retaper pour confirmer la suppression d\'un modèle. Volontairement
 * dérivé du type (et non aléatoire) : il reste vérifiable côté serveur sans
 * état de session, et oblige à lire ce qu\'on supprime.
 */',
        'startLine' => 175,
        'endLine' => 178,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Http\\Controllers\\Admin',
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\TemplateDesignerController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\TemplateDesignerController',
        'currentClassName' => 'App\\Http\\Controllers\\Admin\\TemplateDesignerController',
        'aliasName' => NULL,
      ),
      'destroy' => 
      array (
        'name' => 'destroy',
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
            'startLine' => 185,
            'endLine' => 185,
            'startColumn' => 29,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'template' => 
          array (
            'name' => 'template',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\DocumentTemplate',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 185,
            'endLine' => 185,
            'startColumn' => 47,
            'endColumn' => 72,
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
 * Supprime un modèle après confirmation explicite (code retapé). La
 * suppression ne retire que la **mise en page** : les documents déjà émis ne
 * sont pas touchés, et le modèle par défaut à la charte reprend la main.
 */',
        'startLine' => 185,
        'endLine' => 204,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Controllers\\Admin',
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\TemplateDesignerController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\TemplateDesignerController',
        'currentClassName' => 'App\\Http\\Controllers\\Admin\\TemplateDesignerController',
        'aliasName' => NULL,
      ),
      'generate' => 
      array (
        'name' => 'generate',
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
            'startLine' => 207,
            'endLine' => 207,
            'startColumn' => 30,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'action' => 
          array (
            'name' => 'action',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Actions\\GenerateDocumentLayout',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 207,
            'endLine' => 207,
            'startColumn' => 48,
            'endColumn' => 77,
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
            'name' => 'Illuminate\\Http\\JsonResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Génère une mise en page via IA (consigne + image d\'inspiration). */',
        'startLine' => 207,
        'endLine' => 235,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Controllers\\Admin',
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\TemplateDesignerController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\TemplateDesignerController',
        'currentClassName' => 'App\\Http\\Controllers\\Admin\\TemplateDesignerController',
        'aliasName' => NULL,
      ),
      'save' => 
      array (
        'name' => 'save',
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
            'startLine' => 238,
            'endLine' => 238,
            'startColumn' => 26,
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
            'name' => 'Illuminate\\Http\\JsonResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Persiste la mise en page produite par l\'éditeur (sanitizée). */',
        'startLine' => 238,
        'endLine' => 269,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Controllers\\Admin',
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\TemplateDesignerController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\TemplateDesignerController',
        'currentClassName' => 'App\\Http\\Controllers\\Admin\\TemplateDesignerController',
        'aliasName' => NULL,
      ),
      'upload' => 
      array (
        'name' => 'upload',
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
            'startLine' => 281,
            'endLine' => 281,
            'startColumn' => 28,
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
            'name' => 'Illuminate\\Http\\JsonResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Téléversement d\'une image du concepteur (logo, illustration, fond).
 *
 * ⚠️ Le concepteur embarquait l\'image en `data:` base64 dans le DOM. Elle
 * survivait à l\'instantané de l\'éditeur - donc elle restait visible dans le
 * concepteur - mais `LayoutSanitizer::src()` refuse `data:`, donc l\'élément
 * était **jeté de la mise en page enregistrée** : les logos disparaissaient
 * du document délivré, en silence. Le fichier est donc déposé ici, et la
 * mise en page ne retient que son adresse (légère, lisible par dompdf).
 */',
        'startLine' => 281,
        'endLine' => 290,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Controllers\\Admin',
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\TemplateDesignerController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\TemplateDesignerController',
        'currentClassName' => 'App\\Http\\Controllers\\Admin\\TemplateDesignerController',
        'aliasName' => NULL,
      ),
      'kindType' => 
      array (
        'name' => 'kindType',
        'parameters' => 
        array (
          'kind' => 
          array (
            'name' => 'kind',
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
            'startLine' => 293,
            'endLine' => 293,
            'startColumn' => 31,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'type' => 
          array (
            'name' => 'type',
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
            'startLine' => 293,
            'endLine' => 293,
            'startColumn' => 46,
            'endColumn' => 58,
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
        'docComment' => '/** @return array{0: string, 1: string} */',
        'startLine' => 293,
        'endLine' => 301,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Http\\Controllers\\Admin',
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\TemplateDesignerController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\TemplateDesignerController',
        'currentClassName' => 'App\\Http\\Controllers\\Admin\\TemplateDesignerController',
        'aliasName' => NULL,
      ),
      'sampleVars' => 
      array (
        'name' => 'sampleVars',
        'parameters' => 
        array (
          'editionId' => 
          array (
            'name' => 'editionId',
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
            'startLine' => 304,
            'endLine' => 304,
            'startColumn' => 33,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 304,
            'endLine' => 304,
            'startColumn' => 52,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** @return array<string, string|list<array{name?: string, src?: string}>> */',
        'startLine' => 304,
        'endLine' => 338,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Http\\Controllers\\Admin',
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\TemplateDesignerController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\TemplateDesignerController',
        'currentClassName' => 'App\\Http\\Controllers\\Admin\\TemplateDesignerController',
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