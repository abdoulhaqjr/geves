<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Support\CommunicationAttestations.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Support\CommunicationAttestations
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-4947ab012fada8e2cf11ebac35319d8691010e04e3fc625e0031b536477cd563',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Support\\CommunicationAttestations',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Support/CommunicationAttestations.php',
      ),
    ),
    'namespace' => 'App\\Support',
    'name' => 'App\\Support\\CommunicationAttestations',
    'shortName' => 'CommunicationAttestations',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Ayants droit à une attestation de communication.
 *
 * Règle métier : sur une communication acceptée, l\'auteur correspondant ET chaque
 * co-auteur ont droit à une attestation à leur nom. Deux plafonds :
 *  - par communication : au plus `coauthors_max` co-auteurs reçoivent une attestation ;
 *  - par personne sur l\'événement : au plus `attestation_max` attestations de
 *    communication - quand une personne dépasse ce plafond, on privilégie d\'abord
 *    les communications où elle est auteur correspondant, puis les autres dans
 *    l\'ordre chronologique.
 *
 * Les co-auteurs ne sont éligibles que s\'ils ont **payé** leurs frais (ils ont donc
 * déjà un Participant sur l\'événement). L\'auteur correspondant est le participant
 * déposant, sans condition de paiement supplémentaire ici.
 *
 * ⚠️ **Deux chemins, deux moments.** {@see self::entitlements()} calcule le lot
 * complet d\'un événement (émission en masse par le gestionnaire).
 * {@see self::forSubmission()} répond pour UNE communication, au moment où le
 * modérateur constate son passage. Les plafonds sont les mêmes, mais le second
 * ne voit pas l\'avenir : il compte ce qui est **déjà émis** au lieu de classer
 * l\'ensemble. Conséquence assumée : en séance, le plafond par personne se
 * consomme **dans l\'ordre des passages** - et c\'est le bon ordre, puisqu\'une
 * attestation déjà envoyée ne se reprend pas.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 38,
    'endLine' => 303,
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
      'entitlements' => 
      array (
        'name' => 'entitlements',
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 43,
            'endLine' => 43,
            'startColumn' => 41,
            'endColumn' => 52,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'avecPresence' => 
          array (
            'name' => 'avecPresence',
            'default' => 
            array (
              'code' => 'true',
              'attributes' => 
              array (
                'startLine' => 43,
                'endLine' => 43,
                'startTokenPos' => 76,
                'startFilePos' => 1926,
                'endTokenPos' => 76,
                'endFilePos' => 1929,
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 43,
            'endLine' => 43,
            'startColumn' => 55,
            'endColumn' => 79,
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
            'name' => 'Illuminate\\Support\\Collection',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return Collection<int, array{participant: Participant, submission: Submission, corresponding: bool}>
 */',
        'startLine' => 43,
        'endLine' => 94,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\CommunicationAttestations',
        'implementingClassName' => 'App\\Support\\CommunicationAttestations',
        'currentClassName' => 'App\\Support\\CommunicationAttestations',
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
            'startLine' => 105,
            'endLine' => 105,
            'startColumn' => 42,
            'endColumn' => 63,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'session' => 
          array (
            'name' => 'session',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 105,
                'endLine' => 105,
                'startTokenPos' => 536,
                'startFilePos' => 4664,
                'endTokenPos' => 536,
                'endFilePos' => 4667,
              ),
            ),
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
                      'name' => 'App\\Models\\EventSession',
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
            'startLine' => 105,
            'endLine' => 105,
            'startColumn' => 66,
            'endColumn' => 94,
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
            'name' => 'Illuminate\\Support\\Collection',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Ayants droit d\'UNE communication dont le passage vient d\'être constaté.
 *
 * ⚠️ Le plafond par personne se compte sur les attestations **déjà émises** :
 * en séance, on ne peut pas classer les communications à venir, et ce qui est
 * parti est parti. Premier passé, premier servi.
 *
 * @return Collection<int, array{participant: Participant, submission: Submission, corresponding: bool}>
 */',
        'startLine' => 105,
        'endLine' => 139,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\CommunicationAttestations',
        'implementingClassName' => 'App\\Support\\CommunicationAttestations',
        'currentClassName' => 'App\\Support\\CommunicationAttestations',
        'aliasName' => NULL,
      ),
      'count' => 
      array (
        'name' => 'count',
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 142,
            'endLine' => 142,
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
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Nombre d\'attestations de communication à émettre pour l\'événement (aperçu). */',
        'startLine' => 142,
        'endLine' => 145,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\CommunicationAttestations',
        'implementingClassName' => 'App\\Support\\CommunicationAttestations',
        'currentClassName' => 'App\\Support\\CommunicationAttestations',
        'aliasName' => NULL,
      ),
      'preview' => 
      array (
        'name' => 'preview',
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 157,
            'endLine' => 157,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Ce qu\'une émission en masse ferait : combien partent, combien sont
 * écartés, et pourquoi.
 *
 * ⚠️ Le bouton « Émettre » engageait un lot sans jamais dire ce qu\'il
 * contenait. Avec la réserve aux présents, un nombre qui baisse sans
 * explication se lit comme une panne - et on réimprime pour rien.
 *
 * @return array{retenus: int, ecartes: int}
 */',
        'startLine' => 157,
        'endLine' => 173,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\CommunicationAttestations',
        'implementingClassName' => 'App\\Support\\CommunicationAttestations',
        'currentClassName' => 'App\\Support\\CommunicationAttestations',
        'aliasName' => NULL,
      ),
      'authorsOf' => 
      array (
        'name' => 'authorsOf',
        'parameters' => 
        array (
          'sub' => 
          array (
            'name' => 'sub',
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
            'startLine' => 182,
            'endLine' => 182,
            'startColumn' => 39,
            'endColumn' => 53,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'paidByEmail' => 
          array (
            'name' => 'paidByEmail',
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
            'startLine' => 182,
            'endLine' => 182,
            'startColumn' => 56,
            'endColumn' => 78,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'coauthorsMax' => 
          array (
            'name' => 'coauthorsMax',
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
            'startLine' => 182,
            'endLine' => 182,
            'startColumn' => 81,
            'endColumn' => 97,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Auteurs d\'une communication ayant droit à une attestation : le correspondant,
 * puis les co-auteurs payés dans l\'ordre de la liste, plafonnés.
 *
 * @param  Collection<string, Participant>  $paidByEmail
 * @return list<array{participant: Participant, submission: Submission, corresponding: bool}>
 */',
        'startLine' => 182,
        'endLine' => 214,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 20,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\CommunicationAttestations',
        'implementingClassName' => 'App\\Support\\CommunicationAttestations',
        'currentClassName' => 'App\\Support\\CommunicationAttestations',
        'aliasName' => NULL,
      ),
      'paidByEmail' => 
      array (
        'name' => 'paidByEmail',
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 217,
            'endLine' => 217,
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
            'name' => 'Illuminate\\Support\\Collection',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Participants payés de l\'événement, indexés par e-mail (pour matcher les co-auteurs). */',
        'startLine' => 217,
        'endLine' => 223,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 20,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\CommunicationAttestations',
        'implementingClassName' => 'App\\Support\\CommunicationAttestations',
        'currentClassName' => 'App\\Support\\CommunicationAttestations',
        'aliasName' => NULL,
      ),
      'sessionsOf' => 
      array (
        'name' => 'sessionsOf',
        'parameters' => 
        array (
          'submissionIds' => 
          array (
            'name' => 'submissionIds',
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
            'startLine' => 231,
            'endLine' => 231,
            'startColumn' => 40,
            'endColumn' => 59,
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
 * Séance où chaque communication est programmée (première trouvée).
 *
 * @param  list<int>  $submissionIds
 * @return array<int, int> submission_id => session_id
 */',
        'startLine' => 231,
        'endLine' => 248,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 20,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\CommunicationAttestations',
        'implementingClassName' => 'App\\Support\\CommunicationAttestations',
        'currentClassName' => 'App\\Support\\CommunicationAttestations',
        'aliasName' => NULL,
      ),
      'presentsOf' => 
      array (
        'name' => 'presentsOf',
        'parameters' => 
        array (
          'seances' => 
          array (
            'name' => 'seances',
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
            'startLine' => 256,
            'endLine' => 256,
            'startColumn' => 40,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Présents pointés, par séance.
 *
 * @param  array<int, int>  $seances
 * @return array<int, list<int>> session_id => [participant_id]
 */',
        'startLine' => 256,
        'endLine' => 272,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 20,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\CommunicationAttestations',
        'implementingClassName' => 'App\\Support\\CommunicationAttestations',
        'currentClassName' => 'App\\Support\\CommunicationAttestations',
        'aliasName' => NULL,
      ),
      'estPresent' => 
      array (
        'name' => 'estPresent',
        'parameters' => 
        array (
          'item' => 
          array (
            'name' => 'item',
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
            'startLine' => 283,
            'endLine' => 283,
            'startColumn' => 40,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'seances' => 
          array (
            'name' => 'seances',
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
            'startLine' => 283,
            'endLine' => 283,
            'startColumn' => 53,
            'endColumn' => 66,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'presents' => 
          array (
            'name' => 'presents',
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
            'startLine' => 283,
            'endLine' => 283,
            'startColumn' => 69,
            'endColumn' => 83,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * ⚠️ Une communication **non programmée** n\'a pas de séance où se pointer :
 * la réserve aux présents l\'écarterait pour de bon. On la laisse passer -
 * c\'est au programme qu\'il manque quelque chose, pas à l\'auteur.
 *
 * @param  array{participant: Participant, submission: Submission, corresponding: bool}  $item
 * @param  array<int, int>  $seances
 * @param  array<int, list<int>>  $presents
 */',
        'startLine' => 283,
        'endLine' => 292,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 20,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\CommunicationAttestations',
        'implementingClassName' => 'App\\Support\\CommunicationAttestations',
        'currentClassName' => 'App\\Support\\CommunicationAttestations',
        'aliasName' => NULL,
      ),
      'attestationMax' => 
      array (
        'name' => 'attestationMax',
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 294,
            'endLine' => 294,
            'startColumn' => 44,
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
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 294,
        'endLine' => 297,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 20,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\CommunicationAttestations',
        'implementingClassName' => 'App\\Support\\CommunicationAttestations',
        'currentClassName' => 'App\\Support\\CommunicationAttestations',
        'aliasName' => NULL,
      ),
      'coauthorsMax' => 
      array (
        'name' => 'coauthorsMax',
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 299,
            'endLine' => 299,
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
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 299,
        'endLine' => 302,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 20,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\CommunicationAttestations',
        'implementingClassName' => 'App\\Support\\CommunicationAttestations',
        'currentClassName' => 'App\\Support\\CommunicationAttestations',
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