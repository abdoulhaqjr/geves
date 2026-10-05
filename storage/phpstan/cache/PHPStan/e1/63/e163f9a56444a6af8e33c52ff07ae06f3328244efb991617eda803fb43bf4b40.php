<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Documents\AttestationCertifier.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Documents\AttestationCertifier
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-caa273d7270964b0c6f4805a1e4f4d80eb7bb1868c3ebd168e7ae0fcf628ef57',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Documents\\AttestationCertifier',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Documents/AttestationCertifier.php',
      ),
    ),
    'namespace' => 'App\\Documents',
    'name' => 'App\\Documents\\AttestationCertifier',
    'shortName' => 'AttestationCertifier',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Certification cryptographique des attestations (identité vérifiable).
 *
 * Chaque attestation est scellée par une **signature EdDSA (Ed25519)** de
 * l\'émetteur, sous la forme d\'un **Verifiable Credential** W3C sécurisé en JWS
 * compact (« VC secured using JOSE », alias VC-JWT). On s\'appuie sur
 * firebase/php-jwt (crypto vérifiée, libsodium) - jamais de crypto maison.
 *
 * La clé PRIVÉE de l\'émetteur est une graine Ed25519 hors dépôt
 * (config `attestations.signing_key`, via l\'environnement). La clé PUBLIQUE est
 * publiée via un document `did:web` et un JWKS pour permettre la vérification
 * par un tiers, hors-ligne.
 *
 * Modèle de confiance :
 *  - la SIGNATURE prouve l\'authenticité et l\'intégrité (émetteur + non-altération) ;
 *  - la RÉVOCATION reste un état en base (une signature ne peut pas être « défaite »),
 *    donc la validité courante = signature valide ET attestation non révoquée.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 29,
    'endLine' => 347,
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
      'ALG' => 
      array (
        'declaringClassName' => 'App\\Documents\\AttestationCertifier',
        'implementingClassName' => 'App\\Documents\\AttestationCertifier',
        'name' => 'ALG',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'EdDSA\'',
          'attributes' => 
          array (
            'startLine' => 32,
            'endLine' => 32,
            'startTokenPos' => 50,
            'startFilePos' => 1227,
            'endTokenPos' => 50,
            'endFilePos' => 1233,
          ),
        ),
        'docComment' => '/** Algorithme JOSE (EdDSA sur courbe Ed25519). */',
        'attributes' => 
        array (
        ),
        'startLine' => 32,
        'endLine' => 32,
        'startColumn' => 5,
        'endColumn' => 31,
      ),
      'HEADER_TYP' => 
      array (
        'declaringClassName' => 'App\\Documents\\AttestationCertifier',
        'implementingClassName' => 'App\\Documents\\AttestationCertifier',
        'name' => 'HEADER_TYP',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'vc+jwt\'',
          'attributes' => 
          array (
            'startLine' => 35,
            'endLine' => 35,
            'startTokenPos' => 63,
            'startFilePos' => 1349,
            'endTokenPos' => 63,
            'endFilePos' => 1356,
          ),
        ),
        'docComment' => '/** Type d\'en-tête JOSE pour un Verifiable Credential porté par un JWS. */',
        'attributes' => 
        array (
        ),
        'startLine' => 35,
        'endLine' => 35,
        'startColumn' => 5,
        'endColumn' => 40,
      ),
      'DEFAULT_ISSUER' => 
      array (
        'declaringClassName' => 'App\\Documents\\AttestationCertifier',
        'implementingClassName' => 'App\\Documents\\AttestationCertifier',
        'name' => 'DEFAULT_ISSUER',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'did:web:univkara.tg:colloques\'',
          'attributes' => 
          array (
            'startLine' => 38,
            'endLine' => 38,
            'startTokenPos' => 76,
            'startFilePos' => 1468,
            'endTokenPos' => 76,
            'endFilePos' => 1498,
          ),
        ),
        'docComment' => '/** Émetteur DID par défaut (repli des réglages d\'événement). */',
        'attributes' => 
        array (
        ),
        'startLine' => 38,
        'endLine' => 38,
        'startColumn' => 5,
        'endColumn' => 66,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'enabled' => 
      array (
        'name' => 'enabled',
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
        'docComment' => '/** La certification est-elle activée (clé de signature configurée et valide) ? */',
        'startLine' => 41,
        'endLine' => 44,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\AttestationCertifier',
        'implementingClassName' => 'App\\Documents\\AttestationCertifier',
        'currentClassName' => 'App\\Documents\\AttestationCertifier',
        'aliasName' => NULL,
      ),
      'sign' => 
      array (
        'name' => 'sign',
        'parameters' => 
        array (
          'attestation' => 
          array (
            'name' => 'attestation',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Attestation',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 52,
            'endLine' => 52,
            'startColumn' => 26,
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
 * Signe une attestation et renvoie le JWS compact + le kid utilisé, ou null
 * si la certification est désactivée (aucune clé configurée).
 *
 * @return array{jws: string, kid: string}|null
 */',
        'startLine' => 52,
        'endLine' => 74,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\AttestationCertifier',
        'implementingClassName' => 'App\\Documents\\AttestationCertifier',
        'currentClassName' => 'App\\Documents\\AttestationCertifier',
        'aliasName' => NULL,
      ),
      'verify' => 
      array (
        'name' => 'verify',
        'parameters' => 
        array (
          'attestation' => 
          array (
            'name' => 'attestation',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Attestation',
                'isIdentifier' => false,
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
            'startColumn' => 28,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Vérifie la signature d\'une attestation déjà émise.
 *
 * @return array{
 *     signed: bool,
 *     valid: bool,
 *     reason: string,
 *     payload: array<string, mixed>|null
 * }
 */',
        'startLine' => 86,
        'endLine' => 113,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\AttestationCertifier',
        'implementingClassName' => 'App\\Documents\\AttestationCertifier',
        'currentClassName' => 'App\\Documents\\AttestationCertifier',
        'aliasName' => NULL,
      ),
      'buildCredential' => 
      array (
        'name' => 'buildCredential',
        'parameters' => 
        array (
          'attestation' => 
          array (
            'name' => 'attestation',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Attestation',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 122,
            'endLine' => 122,
            'startColumn' => 37,
            'endColumn' => 60,
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
 * Construit le Verifiable Credential (modèle de données W3C VC 2.0) qui sera
 * signé. Claims JOSE enregistrées (iss/jti/iat) alignées sur les champs VC
 * pour rester lisible par un vérificateur générique.
 *
 * @return array<string, mixed>
 */',
        'startLine' => 122,
        'endLine' => 170,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\AttestationCertifier',
        'implementingClassName' => 'App\\Documents\\AttestationCertifier',
        'currentClassName' => 'App\\Documents\\AttestationCertifier',
        'aliasName' => NULL,
      ),
      'didDocument' => 
      array (
        'name' => 'didDocument',
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
 * Document DID (did:web) publiant la clé publique de l\'émetteur.
 *
 * @return array<string, mixed>|null
 */',
        'startLine' => 177,
        'endLine' => 201,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\AttestationCertifier',
        'implementingClassName' => 'App\\Documents\\AttestationCertifier',
        'currentClassName' => 'App\\Documents\\AttestationCertifier',
        'aliasName' => NULL,
      ),
      'jwks' => 
      array (
        'name' => 'jwks',
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
 * JWKS (JSON Web Key Set) de la clé publique active + clés retirées.
 *
 * @return array{keys: array<int, array<string, mixed>>}|null
 */',
        'startLine' => 208,
        'endLine' => 228,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\AttestationCertifier',
        'implementingClassName' => 'App\\Documents\\AttestationCertifier',
        'currentClassName' => 'App\\Documents\\AttestationCertifier',
        'aliasName' => NULL,
      ),
      'publicJwk' => 
      array (
        'name' => 'publicJwk',
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
 * JWK de la clé publique active, ou null si la certification est désactivée.
 *
 * @return array<string, mixed>|null
 */',
        'startLine' => 235,
        'endLine' => 250,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\AttestationCertifier',
        'implementingClassName' => 'App\\Documents\\AttestationCertifier',
        'currentClassName' => 'App\\Documents\\AttestationCertifier',
        'aliasName' => NULL,
      ),
      'seed' => 
      array (
        'name' => 'seed',
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
        'docComment' => '/** Graine privée Ed25519 (32 octets) décodée, ou null si absente/invalide. */',
        'startLine' => 253,
        'endLine' => 267,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\AttestationCertifier',
        'implementingClassName' => 'App\\Documents\\AttestationCertifier',
        'currentClassName' => 'App\\Documents\\AttestationCertifier',
        'aliasName' => NULL,
      ),
      'activePublicKey' => 
      array (
        'name' => 'activePublicKey',
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
        'docComment' => '/** Clé publique active (32 octets) dérivée de la graine, ou null. */',
        'startLine' => 270,
        'endLine' => 278,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\AttestationCertifier',
        'implementingClassName' => 'App\\Documents\\AttestationCertifier',
        'currentClassName' => 'App\\Documents\\AttestationCertifier',
        'aliasName' => NULL,
      ),
      'publicKeyForJws' => 
      array (
        'name' => 'publicKeyForJws',
        'parameters' => 
        array (
          'jws' => 
          array (
            'name' => 'jws',
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
            'startLine' => 284,
            'endLine' => 284,
            'startColumn' => 38,
            'endColumn' => 48,
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
 * Sélectionne la clé publique (32 octets) permettant de vérifier un JWS,
 * d\'après le fragment kid de son en-tête (support de la rotation).
 */',
        'startLine' => 284,
        'endLine' => 314,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\AttestationCertifier',
        'implementingClassName' => 'App\\Documents\\AttestationCertifier',
        'currentClassName' => 'App\\Documents\\AttestationCertifier',
        'aliasName' => NULL,
      ),
      'issuerFor' => 
      array (
        'name' => 'issuerFor',
        'parameters' => 
        array (
          'attestation' => 
          array (
            'name' => 'attestation',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Attestation',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 317,
            'endLine' => 317,
            'startColumn' => 32,
            'endColumn' => 55,
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
        'docComment' => '/** Émetteur DID d\'une attestation (réglage d\'événement, repli global). */',
        'startLine' => 317,
        'endLine' => 320,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\AttestationCertifier',
        'implementingClassName' => 'App\\Documents\\AttestationCertifier',
        'currentClassName' => 'App\\Documents\\AttestationCertifier',
        'aliasName' => NULL,
      ),
      'defaultIssuer' => 
      array (
        'name' => 'defaultIssuer',
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
        'docComment' => '/** Émetteur DID par défaut (réglage global, puis constante). */',
        'startLine' => 323,
        'endLine' => 326,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\AttestationCertifier',
        'implementingClassName' => 'App\\Documents\\AttestationCertifier',
        'currentClassName' => 'App\\Documents\\AttestationCertifier',
        'aliasName' => NULL,
      ),
      'kidFor' => 
      array (
        'name' => 'kidFor',
        'parameters' => 
        array (
          'attestation' => 
          array (
            'name' => 'attestation',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Attestation',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 329,
            'endLine' => 329,
            'startColumn' => 29,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Identifiant de méthode de vérification (kid) complet pour une attestation. */',
        'startLine' => 329,
        'endLine' => 332,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\AttestationCertifier',
        'implementingClassName' => 'App\\Documents\\AttestationCertifier',
        'currentClassName' => 'App\\Documents\\AttestationCertifier',
        'aliasName' => NULL,
      ),
      'activeKid' => 
      array (
        'name' => 'activeKid',
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
        'docComment' => '/** Fragment de clé actif (config). */',
        'startLine' => 335,
        'endLine' => 338,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\AttestationCertifier',
        'implementingClassName' => 'App\\Documents\\AttestationCertifier',
        'currentClassName' => 'App\\Documents\\AttestationCertifier',
        'aliasName' => NULL,
      ),
      'fragment' => 
      array (
        'name' => 'fragment',
        'parameters' => 
        array (
          'kid' => 
          array (
            'name' => 'kid',
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
            'startLine' => 341,
            'endLine' => 341,
            'startColumn' => 31,
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
        'docComment' => '/** Fragment (après « # ») d\'un identifiant de méthode de vérification. */',
        'startLine' => 341,
        'endLine' => 346,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\AttestationCertifier',
        'implementingClassName' => 'App\\Documents\\AttestationCertifier',
        'currentClassName' => 'App\\Documents\\AttestationCertifier',
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