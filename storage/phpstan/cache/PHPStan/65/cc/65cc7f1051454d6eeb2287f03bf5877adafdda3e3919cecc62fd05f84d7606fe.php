<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Models\Concerns\ParticipantIdentity.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Models\Concerns\ParticipantIdentity
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-0e12a4f384506bdca6c8dc9450fb312911c3584e5d76db2b4dbd486ea1843ad2',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Models\\Concerns\\ParticipantIdentity',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Models/Concerns/ParticipantIdentity.php',
      ),
    ),
    'namespace' => 'App\\Models\\Concerns',
    'name' => 'App\\Models\\Concerns\\ParticipantIdentity',
    'shortName' => 'ParticipantIdentity',
    'isInterface' => false,
    'isTrait' => true,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Capacités « participant » d\'une identité : profil (nom, initiales, ORCID),
 * double authentification (TOTP / OTP SMS / codes de secours) et inscriptions
 * aux événements. Partagé par {@see Account} (guard historique) et
 * {@see User} (identité unifiée - refonte comptes & rôles), pour
 * que l\'utilisateur puisse porter la connexion participant sans duplication.
 *
 * Suppose des colonnes : first_name, last_name, affiliation, orcid,
 * orcid_verified_at, two_factor_* et une relation `participants.<fk>`.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 22,
    'endLine' => 183,
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
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'orcidUrl' => 
      array (
        'name' => 'orcidUrl',
        'parameters' => 
        array (
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
        'docComment' => '/** Lien vérifiable vers la fiche ORCID, ou null si non renseigné/invalide. */',
        'startLine' => 25,
        'endLine' => 28,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models\\Concerns',
        'declaringClassName' => 'App\\Models\\Concerns\\ParticipantIdentity',
        'implementingClassName' => 'App\\Models\\Concerns\\ParticipantIdentity',
        'currentClassName' => 'App\\Models\\Concerns\\ParticipantIdentity',
        'aliasName' => NULL,
      ),
      'orcidVerified' => 
      array (
        'name' => 'orcidVerified',
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
        'docComment' => '/** L\'ORCID a-t-il été confirmé par l\'utilisateur via OAuth ? */',
        'startLine' => 31,
        'endLine' => 34,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models\\Concerns',
        'declaringClassName' => 'App\\Models\\Concerns\\ParticipantIdentity',
        'implementingClassName' => 'App\\Models\\Concerns\\ParticipantIdentity',
        'currentClassName' => 'App\\Models\\Concerns\\ParticipantIdentity',
        'aliasName' => NULL,
      ),
      'hasTotpEnabled' => 
      array (
        'name' => 'hasTotpEnabled',
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
        'docComment' => '/** La double authentification par application (TOTP) est active et confirmée. */',
        'startLine' => 37,
        'endLine' => 40,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models\\Concerns',
        'declaringClassName' => 'App\\Models\\Concerns\\ParticipantIdentity',
        'implementingClassName' => 'App\\Models\\Concerns\\ParticipantIdentity',
        'currentClassName' => 'App\\Models\\Concerns\\ParticipantIdentity',
        'aliasName' => NULL,
      ),
      'hasSmsEnabled' => 
      array (
        'name' => 'hasSmsEnabled',
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
        'docComment' => '/** La double authentification par SMS (OTP) est active et confirmée. */',
        'startLine' => 43,
        'endLine' => 46,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models\\Concerns',
        'declaringClassName' => 'App\\Models\\Concerns\\ParticipantIdentity',
        'implementingClassName' => 'App\\Models\\Concerns\\ParticipantIdentity',
        'currentClassName' => 'App\\Models\\Concerns\\ParticipantIdentity',
        'aliasName' => NULL,
      ),
      'hasTwoFactorEnabled' => 
      array (
        'name' => 'hasTwoFactorEnabled',
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
        'docComment' => '/** Une méthode de 2FA (TOTP ou SMS) est active. */',
        'startLine' => 49,
        'endLine' => 52,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models\\Concerns',
        'declaringClassName' => 'App\\Models\\Concerns\\ParticipantIdentity',
        'implementingClassName' => 'App\\Models\\Concerns\\ParticipantIdentity',
        'currentClassName' => 'App\\Models\\Concerns\\ParticipantIdentity',
        'aliasName' => NULL,
      ),
      'twoFactorMethod' => 
      array (
        'name' => 'twoFactorMethod',
        'parameters' => 
        array (
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
        'docComment' => '/** Méthode de 2FA active (« totp », « sms » ou null). */',
        'startLine' => 55,
        'endLine' => 62,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models\\Concerns',
        'declaringClassName' => 'App\\Models\\Concerns\\ParticipantIdentity',
        'implementingClassName' => 'App\\Models\\Concerns\\ParticipantIdentity',
        'currentClassName' => 'App\\Models\\Concerns\\ParticipantIdentity',
        'aliasName' => NULL,
      ),
      'generateOtp' => 
      array (
        'name' => 'generateOtp',
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
 * Génère un code OTP à 6 chiffres (haché + expiration) pour un envoi SMS et
 * retourne le code en clair (à transmettre). Ne persiste pas : appeler save().
 */',
        'startLine' => 68,
        'endLine' => 75,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models\\Concerns',
        'declaringClassName' => 'App\\Models\\Concerns\\ParticipantIdentity',
        'implementingClassName' => 'App\\Models\\Concerns\\ParticipantIdentity',
        'currentClassName' => 'App\\Models\\Concerns\\ParticipantIdentity',
        'aliasName' => NULL,
      ),
      'verifyOtp' => 
      array (
        'name' => 'verifyOtp',
        'parameters' => 
        array (
          'code' => 
          array (
            'name' => 'code',
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
            'startLine' => 78,
            'endLine' => 78,
            'startColumn' => 31,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Vérifie un code OTP (non expiré) et le consomme. Ne persiste pas. */',
        'startLine' => 78,
        'endLine' => 93,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models\\Concerns',
        'declaringClassName' => 'App\\Models\\Concerns\\ParticipantIdentity',
        'implementingClassName' => 'App\\Models\\Concerns\\ParticipantIdentity',
        'currentClassName' => 'App\\Models\\Concerns\\ParticipantIdentity',
        'aliasName' => NULL,
      ),
      'generateRecoveryCodes' => 
      array (
        'name' => 'generateRecoveryCodes',
        'parameters' => 
        array (
          'count' => 
          array (
            'name' => 'count',
            'default' => 
            array (
              'code' => '8',
              'attributes' => 
              array (
                'startLine' => 102,
                'endLine' => 102,
                'startTokenPos' => 514,
                'startFilePos' => 3483,
                'endTokenPos' => 514,
                'endFilePos' => 3483,
              ),
            ),
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
            'startLine' => 102,
            'endLine' => 102,
            'startColumn' => 43,
            'endColumn' => 56,
            'parameterIndex' => 0,
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
        'docComment' => '/**
 * (Re)génère les codes de récupération à usage unique et les affecte au
 * modèle (retourne les codes en clair pour affichage unique). Ne persiste
 * pas : appeler save() ensuite.
 *
 * @return array<int, string>
 */',
        'startLine' => 102,
        'endLine' => 113,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models\\Concerns',
        'declaringClassName' => 'App\\Models\\Concerns\\ParticipantIdentity',
        'implementingClassName' => 'App\\Models\\Concerns\\ParticipantIdentity',
        'currentClassName' => 'App\\Models\\Concerns\\ParticipantIdentity',
        'aliasName' => NULL,
      ),
      'countRecoveryCodes' => 
      array (
        'name' => 'countRecoveryCodes',
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
        'docComment' => '/** Nombre de codes de récupération restants. */',
        'startLine' => 116,
        'endLine' => 119,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models\\Concerns',
        'declaringClassName' => 'App\\Models\\Concerns\\ParticipantIdentity',
        'implementingClassName' => 'App\\Models\\Concerns\\ParticipantIdentity',
        'currentClassName' => 'App\\Models\\Concerns\\ParticipantIdentity',
        'aliasName' => NULL,
      ),
      'consumeRecoveryCode' => 
      array (
        'name' => 'consumeRecoveryCode',
        'parameters' => 
        array (
          'code' => 
          array (
            'name' => 'code',
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
            'startLine' => 125,
            'endLine' => 125,
            'startColumn' => 41,
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
 * Consomme (retire) un code de récupération s\'il correspond. Insensible à la
 * casse et aux espaces. Ne persiste pas : appeler save() ensuite.
 */',
        'startLine' => 125,
        'endLine' => 139,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models\\Concerns',
        'declaringClassName' => 'App\\Models\\Concerns\\ParticipantIdentity',
        'implementingClassName' => 'App\\Models\\Concerns\\ParticipantIdentity',
        'currentClassName' => 'App\\Models\\Concerns\\ParticipantIdentity',
        'aliasName' => NULL,
      ),
      'disableTotp' => 
      array (
        'name' => 'disableTotp',
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
        'docComment' => '/** Désactive complètement la 2FA (TOTP et SMS) et purge les secrets. Ne persiste pas. */',
        'startLine' => 142,
        'endLine' => 150,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models\\Concerns',
        'declaringClassName' => 'App\\Models\\Concerns\\ParticipantIdentity',
        'implementingClassName' => 'App\\Models\\Concerns\\ParticipantIdentity',
        'currentClassName' => 'App\\Models\\Concerns\\ParticipantIdentity',
        'aliasName' => NULL,
      ),
      'participations' => 
      array (
        'name' => 'participations',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Database\\Eloquent\\Relations\\HasMany',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Inscriptions de cette identité aux différentes événements (fiche participant).
 *
 * @return HasMany<Participant, $this>
 */',
        'startLine' => 157,
        'endLine' => 160,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models\\Concerns',
        'declaringClassName' => 'App\\Models\\Concerns\\ParticipantIdentity',
        'implementingClassName' => 'App\\Models\\Concerns\\ParticipantIdentity',
        'currentClassName' => 'App\\Models\\Concerns\\ParticipantIdentity',
        'aliasName' => NULL,
      ),
      'getFullNameAttribute' => 
      array (
        'name' => 'getFullNameAttribute',
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
        'docComment' => NULL,
        'startLine' => 162,
        'endLine' => 165,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models\\Concerns',
        'declaringClassName' => 'App\\Models\\Concerns\\ParticipantIdentity',
        'implementingClassName' => 'App\\Models\\Concerns\\ParticipantIdentity',
        'currentClassName' => 'App\\Models\\Concerns\\ParticipantIdentity',
        'aliasName' => NULL,
      ),
      'getInitialsAttribute' => 
      array (
        'name' => 'getInitialsAttribute',
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
        'docComment' => NULL,
        'startLine' => 167,
        'endLine' => 173,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models\\Concerns',
        'declaringClassName' => 'App\\Models\\Concerns\\ParticipantIdentity',
        'implementingClassName' => 'App\\Models\\Concerns\\ParticipantIdentity',
        'currentClassName' => 'App\\Models\\Concerns\\ParticipantIdentity',
        'aliasName' => NULL,
      ),
      'canSubmit' => 
      array (
        'name' => 'canSubmit',
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
        'docComment' => '/**
 * Règle de base pour soumettre : e-mail vérifié + profil complet
 * (affiliation renseignée). La photo reste optionnelle.
 */',
        'startLine' => 179,
        'endLine' => 182,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models\\Concerns',
        'declaringClassName' => 'App\\Models\\Concerns\\ParticipantIdentity',
        'implementingClassName' => 'App\\Models\\Concerns\\ParticipantIdentity',
        'currentClassName' => 'App\\Models\\Concerns\\ParticipantIdentity',
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