<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Support\EventMeetingConfig.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Support\EventMeetingConfig
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-e6af4c7f675d19aeead69f4ad9eebb114ce41aa0ecc643a308ba0beefe500ec7',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Support\\EventMeetingConfig',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Support/EventMeetingConfig.php',
      ),
    ),
    'namespace' => 'App\\Support',
    'name' => 'App\\Support\\EventMeetingConfig',
    'shortName' => 'EventMeetingConfig',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Surcharge, au runtime, la configuration de visioconférence à partir des réglages
 * PROPRES à un événement (`settings.meetings`) : fournisseur par défaut, identifiants
 * LiveKit / Jitsi, et qualité vidéo. Les champs laissés VIDES retombent sur la
 * configuration globale (réglages plateforme). Même esprit - et mêmes pièges - que
 * {@see EventPaymentConfig} : à appeler AVANT de résoudre ou d\'utiliser un
 * fournisseur pour une salle rattachée à un événement.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 16,
    'endLine' => 135,
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
      'LIVEKIT_KEYS' => 
      array (
        'declaringClassName' => 'App\\Support\\EventMeetingConfig',
        'implementingClassName' => 'App\\Support\\EventMeetingConfig',
        'name' => 'LIVEKIT_KEYS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'url\', \'api_key\', \'api_secret\']',
          'attributes' => 
          array (
            'startLine' => 19,
            'endLine' => 19,
            'startTokenPos' => 35,
            'startFilePos' => 727,
            'endTokenPos' => 43,
            'endFilePos' => 758,
          ),
        ),
        'docComment' => '/** Clés d\'identifiants par fournisseur (mêmes que la config globale). */',
        'attributes' => 
        array (
        ),
        'startLine' => 19,
        'endLine' => 19,
        'startColumn' => 5,
        'endColumn' => 65,
      ),
      'JITSI_KEYS' => 
      array (
        'declaringClassName' => 'App\\Support\\EventMeetingConfig',
        'implementingClassName' => 'App\\Support\\EventMeetingConfig',
        'name' => 'JITSI_KEYS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'domain\', \'app_id\', \'app_secret\', \'room_prefix\']',
          'attributes' => 
          array (
            'startLine' => 21,
            'endLine' => 21,
            'startTokenPos' => 54,
            'startFilePos' => 792,
            'endTokenPos' => 65,
            'endFilePos' => 840,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 21,
        'endLine' => 21,
        'startColumn' => 5,
        'endColumn' => 80,
      ),
      'QUALITIES' => 
      array (
        'declaringClassName' => 'App\\Support\\EventMeetingConfig',
        'implementingClassName' => 'App\\Support\\EventMeetingConfig',
        'name' => 'QUALITIES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'sd\', \'md\', \'hd\', \'fhd\']',
          'attributes' => 
          array (
            'startLine' => 36,
            'endLine' => 36,
            'startTokenPos' => 78,
            'startFilePos' => 1602,
            'endTokenPos' => 89,
            'endFilePos' => 1626,
          ),
        ),
        'docComment' => '/**
 * Qualités de capture proposées.
 *
 * ⚠️ C\'est la résolution de CAPTURE, donc la couche simulcast la plus haute :
 * rien dans la salle ne peut être plus net qu\'elle, quelle que soit la caméra.
 * Les couches basses (180p, 360p) restent publiées pour les petites vignettes
 * et les connexions faibles - c\'est ce qui rend une couche haute abordable.
 *
 * ⚠️ Le coût retombe sur la LIAISON MONTANTE de celui qui diffuse : monter la
 * qualité d\'un colloque dont les communicants sont sur une connexion modeste
 * dégraderait leur image au lieu de l\'améliorer. D\'où un réglage par événement,
 * et non une valeur unique pour toute la plateforme.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 36,
        'endLine' => 36,
        'startColumn' => 5,
        'endColumn' => 55,
      ),
      'QUALITY_DEFAULT' => 
      array (
        'declaringClassName' => 'App\\Support\\EventMeetingConfig',
        'implementingClassName' => 'App\\Support\\EventMeetingConfig',
        'name' => 'QUALITY_DEFAULT',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'md\'',
          'attributes' => 
          array (
            'startLine' => 38,
            'endLine' => 38,
            'startTokenPos' => 100,
            'startFilePos' => 1665,
            'endTokenPos' => 100,
            'endFilePos' => 1668,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 38,
        'endLine' => 38,
        'startColumn' => 5,
        'endColumn' => 40,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'settings' => 
      array (
        'name' => 'settings',
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
            'startLine' => 45,
            'endLine' => 45,
            'startColumn' => 37,
            'endColumn' => 49,
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
                  'name' => 'array',
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
 * Réglages de visio propres à un événement, ou null.
 *
 * @return array<string, mixed>|null
 */',
        'startLine' => 45,
        'endLine' => 50,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\EventMeetingConfig',
        'implementingClassName' => 'App\\Support\\EventMeetingConfig',
        'currentClassName' => 'App\\Support\\EventMeetingConfig',
        'aliasName' => NULL,
      ),
      'configures' => 
      array (
        'name' => 'configures',
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
            'startLine' => 53,
            'endLine' => 53,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** L\'événement définit-elle au moins un réglage de visio ? */',
        'startLine' => 53,
        'endLine' => 56,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\EventMeetingConfig',
        'implementingClassName' => 'App\\Support\\EventMeetingConfig',
        'currentClassName' => 'App\\Support\\EventMeetingConfig',
        'aliasName' => NULL,
      ),
      'quality' => 
      array (
        'name' => 'quality',
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
            'startLine' => 64,
            'endLine' => 64,
            'startColumn' => 36,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Qualité vidéo retenue pour cet événement, sinon celle de la plateforme.
 *
 * ⚠️ Une valeur inconnue retombe sur le défaut : un réglage saisi hier et
 * retiré du code demain ne doit pas rendre la salle muette d\'image.
 */',
        'startLine' => 64,
        'endLine' => 76,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\EventMeetingConfig',
        'implementingClassName' => 'App\\Support\\EventMeetingConfig',
        'currentClassName' => 'App\\Support\\EventMeetingConfig',
        'aliasName' => NULL,
      ),
      'apply' => 
      array (
        'name' => 'apply',
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
            'startLine' => 78,
            'endLine' => 78,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 78,
        'endLine' => 99,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\EventMeetingConfig',
        'implementingClassName' => 'App\\Support\\EventMeetingConfig',
        'currentClassName' => 'App\\Support\\EventMeetingConfig',
        'aliasName' => NULL,
      ),
      'using' => 
      array (
        'name' => 'using',
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
            'startLine' => 115,
            'endLine' => 115,
            'startColumn' => 34,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'fn' => 
          array (
            'name' => 'fn',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'callable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 115,
            'endLine' => 115,
            'startColumn' => 49,
            'endColumn' => 60,
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
            'name' => 'mixed',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Exécute $fn avec la configuration de l\'événement appliquée, puis RESTAURE la
 * précédente.
 *
 * ⚠️ Indispensable dès qu\'une même requête touche plusieurs événements (une liste
 * de séances, un tableau de bord) : `apply()` n\'écrase que les champs remplis,
 * donc sans restauration les identifiants d\'un événement resteraient actifs pour
 * la suivante - et une salle s\'ouvrirait sur le serveur d\'un autre colloque.
 *
 * @template T
 *
 * @param  callable(): T  $fn
 * @return T
 */',
        'startLine' => 115,
        'endLine' => 134,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\EventMeetingConfig',
        'implementingClassName' => 'App\\Support\\EventMeetingConfig',
        'currentClassName' => 'App\\Support\\EventMeetingConfig',
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