<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Models\Payment.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Models\Payment
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-9566f473f7b09ccd1058b2570f2232840af34a53a6fbe642e4be11b3c1e542ad',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Models\\Payment',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Models/Payment.php',
      ),
    ),
    'namespace' => 'App\\Models',
    'name' => 'App\\Models\\Payment',
    'shortName' => 'Payment',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Paiement des frais d\'inscription (mobile money, carte via passerelle, ou
 * encaissement manuel). Un règlement par participant et par événement.
 * Statuts : {@see PaymentStatus}.
 *
 * @property string|null $reference
 * @property Carbon|null $paid_at
 * @property array<string, mixed>|null $meta
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 23,
    'endLine' => 271,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Database\\Eloquent\\Model',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
      0 => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
    ),
    'immediateConstants' => 
    array (
      'MANUAL_METHODS' => 
      array (
        'declaringClassName' => 'App\\Models\\Payment',
        'implementingClassName' => 'App\\Models\\Payment',
        'name' => 'MANUAL_METHODS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'cash\' => \'Espèces\', \'transfer\' => \'Virement\', \'deposit\' => \'Dépôt\', \'mobile_money\' => \'Mobile money (hors ligne)\', \'check\' => \'Chèque\', \'other\' => \'Autre\']',
          'attributes' => 
          array (
            'startLine' => 30,
            'endLine' => 37,
            'startTokenPos' => 85,
            'startFilePos' => 814,
            'endTokenPos' => 129,
            'endFilePos' => 1029,
          ),
        ),
        'docComment' => '/** Moyens d\'encaissement manuel. */',
        'attributes' => 
        array (
        ),
        'startLine' => 30,
        'endLine' => 37,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
      'ONLINE_METHODS' => 
      array (
        'declaringClassName' => 'App\\Models\\Payment',
        'implementingClassName' => 'App\\Models\\Payment',
        'name' => 'ONLINE_METHODS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'fedapay\' => \'En ligne · Mobile money / carte\', \'cashpay\' => \'En ligne · Mobile money\', \'manual\' => \'Sur place / virement (à valider)\']',
          'attributes' => 
          array (
            'startLine' => 45,
            'endLine' => 49,
            'startTokenPos' => 142,
            'startFilePos' => 1415,
            'endTokenPos' => 165,
            'endFilePos' => 1584,
          ),
        ),
        'docComment' => '/**
 * Moyens issus d\'un parcours de paiement choisi par le payeur (clés des
 * passerelles). ⚠️ On ne nomme jamais l\'agrégateur : `fedapay`/`cashpay` sont
 * des clés techniques, leur libellé reste générique. `manual` = la passerelle
 * « sur place / virement » (le payeur choisit, l\'admin valide ensuite).
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 45,
        'endLine' => 49,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
      'FLOWS' => 
      array (
        'declaringClassName' => 'App\\Models\\Payment',
        'implementingClassName' => 'App\\Models\\Payment',
        'name' => 'FLOWS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'participation\' => \'Participation\', \'formation\' => \'Formation\']',
          'attributes' => 
          array (
            'startLine' => 58,
            'endLine' => 61,
            'startTokenPos' => 178,
            'startFilePos' => 2023,
            'endTokenPos' => 194,
            'endFilePos' => 2109,
          ),
        ),
        'docComment' => '/**
 * Natures **affichées** en back-office. ⚠️ Un paiement de co-auteur est un
 * paiement de participation (le co-auteur règle pour participer) : il est donc
 * présenté et filtré comme tel, la nuance n\'apparaissant qu\'en détail. La
 * valeur technique `meta.flow = \'coauthor\'` reste distincte en base : elle
 * pilote la garde anti-double de {@see ConfirmPayment}.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 58,
        'endLine' => 61,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
    ),
    'immediateProperties' => 
    array (
      'guarded' => 
      array (
        'declaringClassName' => 'App\\Models\\Payment',
        'implementingClassName' => 'App\\Models\\Payment',
        'name' => 'guarded',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'id\']',
          'attributes' => 
          array (
            'startLine' => 27,
            'endLine' => 27,
            'startTokenPos' => 70,
            'startFilePos' => 730,
            'endTokenPos' => 72,
            'endFilePos' => 735,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 27,
        'endLine' => 27,
        'startColumn' => 5,
        'endColumn' => 32,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
    ),
    'immediateMethods' => 
    array (
      'booted' => 
      array (
        'name' => 'booted',
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
 * Attribue une référence unique dès la création (tous flux : manuel, en
 * ligne, co-auteur…), même avant confirmation.
 */',
        'startLine' => 67,
        'endLine' => 74,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 18,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Payment',
        'implementingClassName' => 'App\\Models\\Payment',
        'currentClassName' => 'App\\Models\\Payment',
        'aliasName' => NULL,
      ),
      'generateReference' => 
      array (
        'name' => 'generateReference',
        'parameters' => 
        array (
          'payment' => 
          array (
            'name' => 'payment',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Payment',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 77,
            'endLine' => 77,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Référence unique de la forme PAY-<année>-<6 car.>. */',
        'startLine' => 77,
        'endLine' => 86,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Payment',
        'implementingClassName' => 'App\\Models\\Payment',
        'currentClassName' => 'App\\Models\\Payment',
        'aliasName' => NULL,
      ),
      'methodLabels' => 
      array (
        'name' => 'methodLabels',
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
        'docComment' => '/** Libellés combinés (filtre admin) : moyens manuels + en ligne. */',
        'startLine' => 89,
        'endLine' => 92,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Payment',
        'implementingClassName' => 'App\\Models\\Payment',
        'currentClassName' => 'App\\Models\\Payment',
        'aliasName' => NULL,
      ),
      'statuses' => 
      array (
        'name' => 'statuses',
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
        'docComment' => '/** @return array<string, string> valeur => libellé. */',
        'startLine' => 95,
        'endLine' => 98,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Payment',
        'implementingClassName' => 'App\\Models\\Payment',
        'currentClassName' => 'App\\Models\\Payment',
        'aliasName' => NULL,
      ),
      'statusPills' => 
      array (
        'name' => 'statusPills',
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
        'docComment' => '/** @return array<string, string> valeur => variante de pastille. */',
        'startLine' => 101,
        'endLine' => 104,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Payment',
        'implementingClassName' => 'App\\Models\\Payment',
        'currentClassName' => 'App\\Models\\Payment',
        'aliasName' => NULL,
      ),
      'casts' => 
      array (
        'name' => 'casts',
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
        'docComment' => NULL,
        'startLine' => 106,
        'endLine' => 113,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Payment',
        'implementingClassName' => 'App\\Models\\Payment',
        'currentClassName' => 'App\\Models\\Payment',
        'aliasName' => NULL,
      ),
      'flow' => 
      array (
        'name' => 'flow',
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
 * Nature du paiement (`meta.flow`) : « participation » par défaut, sinon
 * « coauthor » (co-auteur) ou « formation ». Détermine notamment la garde
 * anti-double-paiement (limitée à la participation).
 */',
        'startLine' => 120,
        'endLine' => 125,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Payment',
        'implementingClassName' => 'App\\Models\\Payment',
        'currentClassName' => 'App\\Models\\Payment',
        'aliasName' => NULL,
      ),
      'statusEnum' => 
      array (
        'name' => 'statusEnum',
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
                  'name' => 'App\\Enums\\PaymentStatus',
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
        'attributes' => 
        array (
        ),
        'docComment' => '/** Statut typé (ou null si valeur inconnue). */',
        'startLine' => 128,
        'endLine' => 131,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Payment',
        'implementingClassName' => 'App\\Models\\Payment',
        'currentClassName' => 'App\\Models\\Payment',
        'aliasName' => NULL,
      ),
      'statusLabel' => 
      array (
        'name' => 'statusLabel',
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
        'startLine' => 133,
        'endLine' => 136,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Payment',
        'implementingClassName' => 'App\\Models\\Payment',
        'currentClassName' => 'App\\Models\\Payment',
        'aliasName' => NULL,
      ),
      'statusPill' => 
      array (
        'name' => 'statusPill',
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
        'startLine' => 138,
        'endLine' => 141,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Payment',
        'implementingClassName' => 'App\\Models\\Payment',
        'currentClassName' => 'App\\Models\\Payment',
        'aliasName' => NULL,
      ),
      'methodLabel' => 
      array (
        'name' => 'methodLabel',
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
        'startLine' => 143,
        'endLine' => 148,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Payment',
        'implementingClassName' => 'App\\Models\\Payment',
        'currentClassName' => 'App\\Models\\Payment',
        'aliasName' => NULL,
      ),
      'scopeForParticipation' => 
      array (
        'name' => 'scopeForParticipation',
        'parameters' => 
        array (
          'query' => 
          array (
            'name' => 'query',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Database\\Eloquent\\Builder',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 161,
            'endLine' => 161,
            'startColumn' => 43,
            'endColumn' => 56,
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
            'name' => 'Illuminate\\Database\\Eloquent\\Builder',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Restreint aux paiements qui ouvrent les **droits de participation** à
 * l\'événement (badge, attestations, accès) : tout sauf les formations.
 *
 * ⚠️ Un paiement de formation est rattaché au même `Participant` : sans ce
 * filtre, régler une formation débloquerait à tort la participation.
 * Le co-auteur est inclus : il règle bien pour participer.
 *
 * @param  Builder<Payment>  $query
 * @return Builder<Payment>
 */',
        'startLine' => 161,
        'endLine' => 166,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Payment',
        'implementingClassName' => 'App\\Models\\Payment',
        'currentClassName' => 'App\\Models\\Payment',
        'aliasName' => NULL,
      ),
      'displayFlow' => 
      array (
        'name' => 'displayFlow',
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
        'docComment' => '/** Nature affichée : « formation », sinon « participation » (co-auteur inclus). */',
        'startLine' => 169,
        'endLine' => 172,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Payment',
        'implementingClassName' => 'App\\Models\\Payment',
        'currentClassName' => 'App\\Models\\Payment',
        'aliasName' => NULL,
      ),
      'flowLabel' => 
      array (
        'name' => 'flowLabel',
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
        'docComment' => '/** Libellé de la nature affichée (Participation / Formation). */',
        'startLine' => 175,
        'endLine' => 178,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Payment',
        'implementingClassName' => 'App\\Models\\Payment',
        'currentClassName' => 'App\\Models\\Payment',
        'aliasName' => NULL,
      ),
      'flowDetail' => 
      array (
        'name' => 'flowDetail',
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
        'docComment' => '/**
 * Précision sur l\'objet : le titre de la formation réglée, ou la mention
 * « Co-auteur » qui distingue ce cas particulier de participation.
 */',
        'startLine' => 184,
        'endLine' => 197,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Payment',
        'implementingClassName' => 'App\\Models\\Payment',
        'currentClassName' => 'App\\Models\\Payment',
        'aliasName' => NULL,
      ),
      'methodGroups' => 
      array (
        'name' => 'methodGroups',
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
 * Moyens de paiement groupés pour les filtres admin (évite une liste à plat
 * où « Manuel » et « Mobile money » se confondent).
 *
 * @return array<string, array<string, string>>
 */',
        'startLine' => 205,
        'endLine' => 211,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Payment',
        'implementingClassName' => 'App\\Models\\Payment',
        'currentClassName' => 'App\\Models\\Payment',
        'aliasName' => NULL,
      ),
      'event' => 
      array (
        'name' => 'event',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** @return BelongsTo<Event, $this> */',
        'startLine' => 214,
        'endLine' => 217,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Payment',
        'implementingClassName' => 'App\\Models\\Payment',
        'currentClassName' => 'App\\Models\\Payment',
        'aliasName' => NULL,
      ),
      'participant' => 
      array (
        'name' => 'participant',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** @return BelongsTo<Participant, $this> */',
        'startLine' => 220,
        'endLine' => 223,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Payment',
        'implementingClassName' => 'App\\Models\\Payment',
        'currentClassName' => 'App\\Models\\Payment',
        'aliasName' => NULL,
      ),
      'isPaid' => 
      array (
        'name' => 'isPaid',
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
        'docComment' => NULL,
        'startLine' => 225,
        'endLine' => 228,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Payment',
        'implementingClassName' => 'App\\Models\\Payment',
        'currentClassName' => 'App\\Models\\Payment',
        'aliasName' => NULL,
      ),
      'setOperator' => 
      array (
        'name' => 'setOperator',
        'parameters' => 
        array (
          'operator' => 
          array (
            'name' => 'operator',
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
            'startLine' => 235,
            'endLine' => 235,
            'startColumn' => 33,
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
        'docComment' => '/**
 * Mémorise l\'opérateur mobile money choisi pour **cette tentative** (un
 * paiement en attente peut être repris avec un autre opérateur). Vide = laisser
 * le payeur choisir sur la page du prestataire.
 */',
        'startLine' => 235,
        'endLine' => 247,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Payment',
        'implementingClassName' => 'App\\Models\\Payment',
        'currentClassName' => 'App\\Models\\Payment',
        'aliasName' => NULL,
      ),
      'payerContact' => 
      array (
        'name' => 'payerContact',
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
 * Coordonnées du payeur transmises à la passerelle pour **pré-remplir sa page**
 * (mobile money / carte). On lit le participant de l\'événement, avec repli sur le
 * **compte unifié** lié : le participant peut avoir été créé sans téléphone
 * alors que le compte le porte (profil).
 *
 * @return array{firstname: ?string, lastname: ?string, email: ?string, phone: ?string}
 */',
        'startLine' => 257,
        'endLine' => 270,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Models',
        'declaringClassName' => 'App\\Models\\Payment',
        'implementingClassName' => 'App\\Models\\Payment',
        'currentClassName' => 'App\\Models\\Payment',
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