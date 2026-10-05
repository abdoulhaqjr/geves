<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Http\Controllers\Admin\SettingController.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Http\Controllers\Admin\SettingController
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-3d3a96bfe468e1ffc391e5d6bc22b471f360ae6dcbfcd60f0e3ebfefc065a6e4',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Http\\Controllers\\Admin\\SettingController',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Http/Controllers/Admin/SettingController.php',
      ),
    ),
    'namespace' => 'App\\Http\\Controllers\\Admin',
    'name' => 'App\\Http\\Controllers\\Admin\\SettingController',
    'shortName' => 'SettingController',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Réglages du back-office (contrôleur + Blade) : expéditeur, SMTP (avec e-mail
 * de test), paiement, IA, visio (LiveKit + Egress), intégrations (HAL, SMS).
 * Écrits dans la table settings et appliqués à la config au boot
 * (AppServiceProvider). Onglets pilotés par Alpine ; en cas d\'erreur de
 * validation, l\'onglet fautif est réactivé via un flash `settings_tab`.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 26,
    'endLine' => 309,
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
      'TAB_LABELS' => 
      array (
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\SettingController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\SettingController',
        'name' => 'TAB_LABELS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'general\' => \'Général\', \'payments\' => \'Paiements\', \'email\' => \'E-mail\', \'ai\' => \'Intelligence artificielle\', \'agents-ia\' => \'Agents IA\', \'meetings\' => \'Visio\', \'integrations\' => \'Intégrations\']',
          'attributes' => 
          array (
            'startLine' => 35,
            'endLine' => 43,
            'startTokenPos' => 94,
            'startFilePos' => 1295,
            'endTokenPos' => 145,
            'endFilePos' => 1554,
          ),
        ),
        'docComment' => '/**
 * Onglets de la page, dans l\'ordre d\'affichage. Source unique : la barre de
 * navigation les parcourt, et `?tab=` est validé contre cette liste.
 *
 * ⚠️ « Agents IA » est un onglet mais garde **son propre formulaire** (PUT
 * sur `admin.settings.agents.update`) : il vit hors du formulaire général.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 35,
        'endLine' => 43,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
      'TABS' => 
      array (
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\SettingController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\SettingController',
        'name' => 'TABS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'general\', \'payments\', \'email\', \'ai\', \'agents-ia\', \'meetings\', \'integrations\']',
          'attributes' => 
          array (
            'startLine' => 46,
            'endLine' => 46,
            'startTokenPos' => 158,
            'startFilePos' => 1611,
            'endTokenPos' => 178,
            'endFilePos' => 1689,
          ),
        ),
        'docComment' => '/** @var list<string> */',
        'attributes' => 
        array (
        ),
        'startLine' => 46,
        'endLine' => 46,
        'startColumn' => 5,
        'endColumn' => 104,
      ),
      'MAP' => 
      array (
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\SettingController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\SettingController',
        'name' => 'MAP',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'app_name\' => \'app.name\', \'participant_types\' => \'participant_types\', \'from_name\' => \'mail.from_name\', \'from_address\' => \'mail.from_address\', \'currency\' => \'payments.currency\', \'app_url\' => \'app.url\', \'mail_mailer\' => \'mail.mailer\', \'mail_host\' => \'mail.host\', \'mail_port\' => \'mail.port\', \'mail_encryption\' => \'mail.encryption\', \'mail_username\' => \'mail.username\', \'mail_password\' => \'mail.password\', \'payments_default\' => \'payments.default\', \'cashpay_base_url\' => \'cashpay.base_url\', \'cashpay_username\' => \'cashpay.username\', \'cashpay_password\' => \'cashpay.password\', \'cashpay_client_secret\' => \'cashpay.client_secret\', \'cashpay_api_key\' => \'cashpay.api_key\', \'fedapay_secret\' => \'fedapay.secret\', \'fedapay_mode\' => \'fedapay.mode\', \'fedapay_webhook_secret\' => \'fedapay.webhook_secret\', \'ai_default\' => \'ai.default\', \'ai_openai_key\' => \'ai.openai_key\', \'ai_openai_model\' => \'ai.openai_model\', \'ai_anthropic_key\' => \'ai.anthropic_key\', \'ai_anthropic_model\' => \'ai.anthropic_model\', \'ai_gemini_key\' => \'ai.gemini_key\', \'ai_gemini_model\' => \'ai.gemini_model\', \'ai_grok_key\' => \'ai.grok_key\', \'ai_grok_model\' => \'ai.grok_model\', \'meeting_default\' => \'meetings.default\', \'meetings_backgrounds\' => \'meetings.backgrounds\', \'livekit_url\' => \'livekit.url\', \'livekit_api_key\' => \'livekit.api_key\', \'livekit_api_secret\' => \'livekit.api_secret\', \'livekit_meet_url\' => \'livekit.meet_url\', \'livekit_egress_s3_bucket\' => \'livekit_egress.s3_bucket\', \'livekit_egress_s3_region\' => \'livekit_egress.s3_region\', \'livekit_egress_s3_access_key\' => \'livekit_egress.s3_access_key\', \'livekit_egress_s3_secret\' => \'livekit_egress.s3_secret\', \'livekit_egress_s3_endpoint\' => \'livekit_egress.s3_endpoint\', \'sms_default\' => \'sms.default\', \'sms_http_url\' => \'sms.http_url\', \'sms_http_token\' => \'sms.http_token\', \'sms_http_sender\' => \'sms.http_sender\', \'site_facebook\' => \'site.facebook\', \'site_x\' => \'site.x\', \'site_youtube\' => \'site.youtube\', \'site_linkedin\' => \'site.linkedin\', \'plagiarism_provider\' => \'plagiarism.provider\', \'plagiarism_prepostseo_key\' => \'plagiarism.prepostseo_key\', \'orcid_environment\' => \'orcid.environment\', \'orcid_client_id\' => \'orcid.client_id\', \'orcid_client_secret\' => \'orcid.client_secret\']',
          'attributes' => 
          array (
            'startLine' => 49,
            'endLine' => 104,
            'startTokenPos' => 191,
            'startFilePos' => 1785,
            'endTokenPos' => 571,
            'endFilePos' => 4413,
          ),
        ),
        'docComment' => '/** Correspondance champ de formulaire → clé de réglage. */',
        'attributes' => 
        array (
        ),
        'startLine' => 49,
        'endLine' => 104,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'edit' => 
      array (
        'name' => 'edit',
        'parameters' => 
        array (
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
        'startLine' => 106,
        'endLine' => 176,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Controllers\\Admin',
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\SettingController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\SettingController',
        'currentClassName' => 'App\\Http\\Controllers\\Admin\\SettingController',
        'aliasName' => NULL,
      ),
      'update' => 
      array (
        'name' => 'update',
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
            'startLine' => 178,
            'endLine' => 178,
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
        'docComment' => NULL,
        'startLine' => 178,
        'endLine' => 227,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Controllers\\Admin',
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\SettingController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\SettingController',
        'currentClassName' => 'App\\Http\\Controllers\\Admin\\SettingController',
        'aliasName' => NULL,
      ),
      'saveLogos' => 
      array (
        'name' => 'saveLogos',
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
            'startLine' => 235,
            'endLine' => 235,
            'startColumn' => 32,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Logos de la plateforme (deux formes x deux fonds) et remise à zéro.
 *
 * ⚠️ On remplace le fichier précédent plutôt que d\'empiler : un logo n\'a pas
 * d\'historique, et les anciens resteraient servis publiquement.
 */',
        'startLine' => 235,
        'endLine' => 257,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Http\\Controllers\\Admin',
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\SettingController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\SettingController',
        'currentClassName' => 'App\\Http\\Controllers\\Admin\\SettingController',
        'aliasName' => NULL,
      ),
      'forget' => 
      array (
        'name' => 'forget',
        'parameters' => 
        array (
          'path' => 
          array (
            'name' => 'path',
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
            'startLine' => 260,
            'endLine' => 260,
            'startColumn' => 29,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Supprime un fichier de marque devenu inutile (ignore l\'absence). */',
        'startLine' => 260,
        'endLine' => 265,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Http\\Controllers\\Admin',
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\SettingController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\SettingController',
        'currentClassName' => 'App\\Http\\Controllers\\Admin\\SettingController',
        'aliasName' => NULL,
      ),
      'sendTest' => 
      array (
        'name' => 'sendTest',
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
            'startLine' => 268,
            'endLine' => 268,
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
            'name' => 'Illuminate\\Http\\RedirectResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Envoie un e-mail de test avec les paramètres SMTP saisis. */',
        'startLine' => 268,
        'endLine' => 295,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Controllers\\Admin',
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\SettingController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\SettingController',
        'currentClassName' => 'App\\Http\\Controllers\\Admin\\SettingController',
        'aliasName' => NULL,
      ),
      'tabForField' => 
      array (
        'name' => 'tabForField',
        'parameters' => 
        array (
          'field' => 
          array (
            'name' => 'field',
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
            'startLine' => 298,
            'endLine' => 298,
            'startColumn' => 34,
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
        'docComment' => '/** Associe un champ de formulaire à l\'onglet qui le contient. */',
        'startLine' => 298,
        'endLine' => 308,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Http\\Controllers\\Admin',
        'declaringClassName' => 'App\\Http\\Controllers\\Admin\\SettingController',
        'implementingClassName' => 'App\\Http\\Controllers\\Admin\\SettingController',
        'currentClassName' => 'App\\Http\\Controllers\\Admin\\SettingController',
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