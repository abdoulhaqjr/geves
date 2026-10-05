<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Support\EventReport.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Support\EventReport
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-a3308162d6532c65a3a18064864543109fb7e9cab94778f5037c176a06aaa287',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Support\\EventReport',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Support/EventReport.php',
      ),
    ),
    'namespace' => 'App\\Support',
    'name' => 'App\\Support\\EventReport',
    'shortName' => 'EventReport',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Agrège les données de reporting d\'un événement : synthèse chiffrée et jeux de
 * lignes (participants, communications, paiements) prêts à l\'export Excel/PDF.
 * Source unique pour le classeur .xlsx, le rapport PDF et les tuiles Livewire.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 19,
    'endLine' => 463,
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
      'event' => 
      array (
        'declaringClassName' => 'App\\Support\\EventReport',
        'implementingClassName' => 'App\\Support\\EventReport',
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
        'startLine' => 21,
        'endLine' => 21,
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
            'startLine' => 21,
            'endLine' => 21,
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
        'startLine' => 21,
        'endLine' => 21,
        'startColumn' => 5,
        'endColumn' => 55,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\EventReport',
        'implementingClassName' => 'App\\Support\\EventReport',
        'currentClassName' => 'App\\Support\\EventReport',
        'aliasName' => NULL,
      ),
      'summary' => 
      array (
        'name' => 'summary',
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
 * Indicateurs de synthèse.
 *
 * @return array{
 *   participants:int,
 *   submissions:int,
 *   submissionsByStatus:array<string,int>,
 *   accepted:int,
 *   paidCount:int,
 *   collected:float,
 *   pending:float,
 *   currency:string
 * }
 */',
        'startLine' => 37,
        'endLine' => 62,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\EventReport',
        'implementingClassName' => 'App\\Support\\EventReport',
        'currentClassName' => 'App\\Support\\EventReport',
        'aliasName' => NULL,
      ),
      'participantHeaders' => 
      array (
        'name' => 'participantHeaders',
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
        'docComment' => '/** @return list<string> */',
        'startLine' => 65,
        'endLine' => 68,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\EventReport',
        'implementingClassName' => 'App\\Support\\EventReport',
        'currentClassName' => 'App\\Support\\EventReport',
        'aliasName' => NULL,
      ),
      'participantRows' => 
      array (
        'name' => 'participantRows',
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
        'docComment' => '/** @return array<int, array<int, string|float>> */',
        'startLine' => 71,
        'endLine' => 95,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\EventReport',
        'implementingClassName' => 'App\\Support\\EventReport',
        'currentClassName' => 'App\\Support\\EventReport',
        'aliasName' => NULL,
      ),
      'submissionHeaders' => 
      array (
        'name' => 'submissionHeaders',
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
        'docComment' => '/** @return list<string> */',
        'startLine' => 98,
        'endLine' => 101,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\EventReport',
        'implementingClassName' => 'App\\Support\\EventReport',
        'currentClassName' => 'App\\Support\\EventReport',
        'aliasName' => NULL,
      ),
      'submissionRows' => 
      array (
        'name' => 'submissionRows',
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
        'docComment' => '/** @return array<int, array<int, string|float|int>> */',
        'startLine' => 104,
        'endLine' => 124,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\EventReport',
        'implementingClassName' => 'App\\Support\\EventReport',
        'currentClassName' => 'App\\Support\\EventReport',
        'aliasName' => NULL,
      ),
      'paymentHeaders' => 
      array (
        'name' => 'paymentHeaders',
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
        'docComment' => '/** @return list<string> */',
        'startLine' => 127,
        'endLine' => 130,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\EventReport',
        'implementingClassName' => 'App\\Support\\EventReport',
        'currentClassName' => 'App\\Support\\EventReport',
        'aliasName' => NULL,
      ),
      'paymentRows' => 
      array (
        'name' => 'paymentRows',
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
        'docComment' => '/** @return array<int, array<int, string|float>> */',
        'startLine' => 133,
        'endLine' => 151,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\EventReport',
        'implementingClassName' => 'App\\Support\\EventReport',
        'currentClassName' => 'App\\Support\\EventReport',
        'aliasName' => NULL,
      ),
      'attestationHeaders' => 
      array (
        'name' => 'attestationHeaders',
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
        'docComment' => '/** @return list<string> */',
        'startLine' => 154,
        'endLine' => 157,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\EventReport',
        'implementingClassName' => 'App\\Support\\EventReport',
        'currentClassName' => 'App\\Support\\EventReport',
        'aliasName' => NULL,
      ),
      'attestationRows' => 
      array (
        'name' => 'attestationRows',
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
        'docComment' => '/** @return array<int, array<int, string>> */',
        'startLine' => 160,
        'endLine' => 175,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\EventReport',
        'implementingClassName' => 'App\\Support\\EventReport',
        'currentClassName' => 'App\\Support\\EventReport',
        'aliasName' => NULL,
      ),
      'accountingHeaders' => 
      array (
        'name' => 'accountingHeaders',
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
        'docComment' => '/** @return list<string> */',
        'startLine' => 178,
        'endLine' => 181,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\EventReport',
        'implementingClassName' => 'App\\Support\\EventReport',
        'currentClassName' => 'App\\Support\\EventReport',
        'aliasName' => NULL,
      ),
      'accountingRows' => 
      array (
        'name' => 'accountingRows',
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
 * Récapitulatif comptable : recettes réglées agrégées par passerelle, avec
 * ligne de total (mockup « Export comptable »).
 *
 * @return array<int, array<int, string|float|int>>
 */',
        'startLine' => 189,
        'endLine' => 216,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\EventReport',
        'implementingClassName' => 'App\\Support\\EventReport',
        'currentClassName' => 'App\\Support\\EventReport',
        'aliasName' => NULL,
      ),
      'counts' => 
      array (
        'name' => 'counts',
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
 * Nombre de lignes par jeu de données (pour l\'affichage des cartes d\'export).
 *
 * @return array{submissions:int, participants:int, payments:int, attestations:int}
 */',
        'startLine' => 223,
        'endLine' => 233,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\EventReport',
        'implementingClassName' => 'App\\Support\\EventReport',
        'currentClassName' => 'App\\Support\\EventReport',
        'aliasName' => NULL,
      ),
      'subs' => 
      array (
        'name' => 'subs',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/** @return Builder<Submission> */',
        'startLine' => 240,
        'endLine' => 243,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\EventReport',
        'implementingClassName' => 'App\\Support\\EventReport',
        'currentClassName' => 'App\\Support\\EventReport',
        'aliasName' => NULL,
      ),
      'pay' => 
      array (
        'name' => 'pay',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/** @return Builder<Payment> */',
        'startLine' => 246,
        'endLine' => 249,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\EventReport',
        'implementingClassName' => 'App\\Support\\EventReport',
        'currentClassName' => 'App\\Support\\EventReport',
        'aliasName' => NULL,
      ),
      'submissionsByAxis' => 
      array (
        'name' => 'submissionsByAxis',
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
 * Répartition des soumissions par axe : total, en revue, acceptées,
 * refusées, taux d\'acceptation.
 *
 * @return array<int, array{axe:string, total:int, review:int, accepted:int, rejected:int, rate:int}>
 */',
        'startLine' => 257,
        'endLine' => 281,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\EventReport',
        'implementingClassName' => 'App\\Support\\EventReport',
        'currentClassName' => 'App\\Support\\EventReport',
        'aliasName' => NULL,
      ),
      'submissionsByType' => 
      array (
        'name' => 'submissionsByType',
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
 * Répartition par type de communication (oral / poster).
 *
 * @return array<int, array{type:string, total:int, accepted:int, part:int}>
 */',
        'startLine' => 288,
        'endLine' => 306,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\EventReport',
        'implementingClassName' => 'App\\Support\\EventReport',
        'currentClassName' => 'App\\Support\\EventReport',
        'aliasName' => NULL,
      ),
      'participantsByRole' => 
      array (
        'name' => 'participantsByRole',
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
 * Participants par rôle : inscrits et payés.
 *
 * @return array<int, array{role:string, count:int, paid:int}>
 */',
        'startLine' => 313,
        'endLine' => 327,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\EventReport',
        'implementingClassName' => 'App\\Support\\EventReport',
        'currentClassName' => 'App\\Support\\EventReport',
        'aliasName' => NULL,
      ),
      'participantsByCountry' => 
      array (
        'name' => 'participantsByCountry',
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
 * Participants par pays (top 6 + « Autres »), avec part en %.
 *
 * @return array<int, array{country:string, count:int, part:int}>
 */',
        'startLine' => 334,
        'endLine' => 359,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\EventReport',
        'implementingClassName' => 'App\\Support\\EventReport',
        'currentClassName' => 'App\\Support\\EventReport',
        'aliasName' => NULL,
      ),
      'paymentStats' => 
      array (
        'name' => 'paymentStats',
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
 * Statistiques de paiement : OK, en attente, échoués/annulés.
 *
 * @return array{ok:int, pending:int, failed:int}
 */',
        'startLine' => 366,
        'endLine' => 373,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\EventReport',
        'implementingClassName' => 'App\\Support\\EventReport',
        'currentClassName' => 'App\\Support\\EventReport',
        'aliasName' => NULL,
      ),
      'paymentsByTarif' => 
      array (
        'name' => 'paymentsByTarif',
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
 * Recettes réglées par tarif (montant unitaire), avec nombre et total.
 *
 * @return array<int, array{tarif:float, count:int, total:float}>
 */',
        'startLine' => 380,
        'endLine' => 391,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\EventReport',
        'implementingClassName' => 'App\\Support\\EventReport',
        'currentClassName' => 'App\\Support\\EventReport',
        'aliasName' => NULL,
      ),
      'reviewStats' => 
      array (
        'name' => 'reviewStats',
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
 * Statistiques de relecture : relecteurs actifs, relectures rendues, en retard,
 * délai moyen (jours entre assignation et complétion).
 *
 * ⚠️ « En retard » = échéance dépassée sur une relecture encore due
 * (Review::isLate()), et non « pas encore rendue » : une relecture confiée
 * hier n\'est pas en retard, et une relecture refusée ou annulée ne l\'est
 * jamais - la place a été libérée.
 *
 * @return array{reviewers:int, done:int, late:int, pending:int, declined:int, avgDays:float}
 */',
        'startLine' => 404,
        'endLine' => 421,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\EventReport',
        'implementingClassName' => 'App\\Support\\EventReport',
        'currentClassName' => 'App\\Support\\EventReport',
        'aliasName' => NULL,
      ),
      'reviewsByReviewer' => 
      array (
        'name' => 'reviewsByReviewer',
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
 * Charge par relecteur : assignées, rendues, en retard, complétion (%).
 * Seules les relectures actives comptent dans la charge (un refus ou une
 * annulation ne pèse pas sur la complétion du relecteur), et « en retard »
 * suit l\'échéance (Review::isLate()).
 *
 * @return array<int, array{name:string, assigned:int, done:int, pending:int, late:int, declined:int, completion:int}>
 */',
        'startLine' => 431,
        'endLine' => 455,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\EventReport',
        'implementingClassName' => 'App\\Support\\EventReport',
        'currentClassName' => 'App\\Support\\EventReport',
        'aliasName' => NULL,
      ),
      'filenameBase' => 
      array (
        'name' => 'filenameBase',
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
        'startLine' => 457,
        'endLine' => 462,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\EventReport',
        'implementingClassName' => 'App\\Support\\EventReport',
        'currentClassName' => 'App\\Support\\EventReport',
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