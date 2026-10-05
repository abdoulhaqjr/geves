<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Support\AttestationPolicy.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Support\AttestationPolicy
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-d001f7111860e42e79c0af551f841436e2d565f932b1f29ed6b79a40440341f7',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Support\\AttestationPolicy',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Support/AttestationPolicy.php',
      ),
    ),
    'namespace' => 'App\\Support',
    'name' => 'App\\Support\\AttestationPolicy',
    'shortName' => 'AttestationPolicy',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * **Comment les attestations sont délivrées** sur un événement : en ligne (envoi
 * et téléchargement) ou sur papier (le gestionnaire imprime et remet).
 *
 * ⚠️ **Un seul endroit décide.** Trois écrans lisent ce choix - l\'émission en
 * masse, la validation du passage par le modérateur, et l\'envoi au participant -
 * et chacun en tirerait sa propre interprétation si le réglage était relu brut.
 *
 * ⚠️ Le mode papier **n\'empêche pas** l\'attestation d\'exister ni le participant
 * de la retrouver dans son espace : il coupe l\'**envoi automatique**, parce que
 * l\'exemplaire qui fait foi est celui qu\'on lui remet en main propre. Lui
 * fermer aussi l\'accès en ligne ne ferait que renvoyer ses questions au
 * secrétariat.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 21,
    'endLine' => 103,
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
      'DELIVERY_ONLINE' => 
      array (
        'declaringClassName' => 'App\\Support\\AttestationPolicy',
        'implementingClassName' => 'App\\Support\\AttestationPolicy',
        'name' => 'DELIVERY_ONLINE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'online\'',
          'attributes' => 
          array (
            'startLine' => 23,
            'endLine' => 23,
            'startTokenPos' => 28,
            'startFilePos' => 875,
            'endTokenPos' => 28,
            'endFilePos' => 882,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 23,
        'endLine' => 23,
        'startColumn' => 5,
        'endColumn' => 44,
      ),
      'DELIVERY_PAPER' => 
      array (
        'declaringClassName' => 'App\\Support\\AttestationPolicy',
        'implementingClassName' => 'App\\Support\\AttestationPolicy',
        'name' => 'DELIVERY_PAPER',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'paper\'',
          'attributes' => 
          array (
            'startLine' => 25,
            'endLine' => 25,
            'startTokenPos' => 39,
            'startFilePos' => 920,
            'endTokenPos' => 39,
            'endFilePos' => 926,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 25,
        'endLine' => 25,
        'startColumn' => 5,
        'endColumn' => 42,
      ),
      'DELIVERIES' => 
      array (
        'declaringClassName' => 'App\\Support\\AttestationPolicy',
        'implementingClassName' => 'App\\Support\\AttestationPolicy',
        'name' => 'DELIVERIES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[self::DELIVERY_ONLINE => \'En ligne (envoi et téléchargement)\', self::DELIVERY_PAPER => \'Physique (imprimées et remises)\']',
          'attributes' => 
          array (
            'startLine' => 28,
            'endLine' => 31,
            'startTokenPos' => 52,
            'startFilePos' => 1012,
            'endTokenPos' => 72,
            'endFilePos' => 1159,
          ),
        ),
        'docComment' => '/** Modes de délivrance et leurs libellés. */',
        'attributes' => 
        array (
        ),
        'startLine' => 28,
        'endLine' => 31,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
      'SCOPE_ALL' => 
      array (
        'declaringClassName' => 'App\\Support\\AttestationPolicy',
        'implementingClassName' => 'App\\Support\\AttestationPolicy',
        'name' => 'SCOPE_ALL',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'all\'',
          'attributes' => 
          array (
            'startLine' => 34,
            'endLine' => 34,
            'startTokenPos' => 85,
            'startFilePos' => 1249,
            'endTokenPos' => 85,
            'endFilePos' => 1253,
          ),
        ),
        'docComment' => '/** Qui reçoit une attestation de communication. */',
        'attributes' => 
        array (
        ),
        'startLine' => 34,
        'endLine' => 34,
        'startColumn' => 5,
        'endColumn' => 35,
      ),
      'SCOPE_PRESENT' => 
      array (
        'declaringClassName' => 'App\\Support\\AttestationPolicy',
        'implementingClassName' => 'App\\Support\\AttestationPolicy',
        'name' => 'SCOPE_PRESENT',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'present\'',
          'attributes' => 
          array (
            'startLine' => 36,
            'endLine' => 36,
            'startTokenPos' => 96,
            'startFilePos' => 1290,
            'endTokenPos' => 96,
            'endFilePos' => 1298,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 36,
        'endLine' => 36,
        'startColumn' => 5,
        'endColumn' => 43,
      ),
      'SCOPES' => 
      array (
        'declaringClassName' => 'App\\Support\\AttestationPolicy',
        'implementingClassName' => 'App\\Support\\AttestationPolicy',
        'name' => 'SCOPES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[self::SCOPE_ALL => \'Tous les auteurs concernés par la communication\', self::SCOPE_PRESENT => \'Seulement les auteurs pointés présents à la séance\']',
          'attributes' => 
          array (
            'startLine' => 38,
            'endLine' => 41,
            'startTokenPos' => 107,
            'startFilePos' => 1328,
            'endTokenPos' => 127,
            'endFilePos' => 1502,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 38,
        'endLine' => 41,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'delivery' => 
      array (
        'name' => 'delivery',
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
            'startLine' => 43,
            'endLine' => 43,
            'startColumn' => 37,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 43,
        'endLine' => 48,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\AttestationPolicy',
        'implementingClassName' => 'App\\Support\\AttestationPolicy',
        'currentClassName' => 'App\\Support\\AttestationPolicy',
        'aliasName' => NULL,
      ),
      'isPaper' => 
      array (
        'name' => 'isPaper',
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
            'startLine' => 50,
            'endLine' => 50,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 50,
        'endLine' => 53,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\AttestationPolicy',
        'implementingClassName' => 'App\\Support\\AttestationPolicy',
        'currentClassName' => 'App\\Support\\AttestationPolicy',
        'aliasName' => NULL,
      ),
      'issuesOnPassage' => 
      array (
        'name' => 'issuesOnPassage',
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
            'startLine' => 62,
            'endLine' => 62,
            'startColumn' => 44,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Le passage d\'une communication émet-il les attestations de ses auteurs ?
 *
 * ⚠️ Non en mode papier : là, c\'est le gestionnaire qui émet le lot, le
 * relit et l\'imprime. Une émission au fil des passages lui ferait réimprimer
 * à chaque communication.
 */',
        'startLine' => 62,
        'endLine' => 65,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\AttestationPolicy',
        'implementingClassName' => 'App\\Support\\AttestationPolicy',
        'currentClassName' => 'App\\Support\\AttestationPolicy',
        'aliasName' => NULL,
      ),
      'communicationScope' => 
      array (
        'name' => 'communicationScope',
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
            'startLine' => 67,
            'endLine' => 67,
            'startColumn' => 47,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 67,
        'endLine' => 72,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\AttestationPolicy',
        'implementingClassName' => 'App\\Support\\AttestationPolicy',
        'currentClassName' => 'App\\Support\\AttestationPolicy',
        'aliasName' => NULL,
      ),
      'requiresPresence' => 
      array (
        'name' => 'requiresPresence',
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
            'startLine' => 75,
            'endLine' => 75,
            'startColumn' => 45,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** L\'attestation de communication est-elle réservée aux présents de la séance ? */',
        'startLine' => 75,
        'endLine' => 78,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\AttestationPolicy',
        'implementingClassName' => 'App\\Support\\AttestationPolicy',
        'currentClassName' => 'App\\Support\\AttestationPolicy',
        'aliasName' => NULL,
      ),
      'notifies' => 
      array (
        'name' => 'notifies',
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
            'startLine' => 86,
            'endLine' => 86,
            'startColumn' => 37,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Prévient-on le bénéficiaire à l\'émission ?
 *
 * ⚠️ Jamais en mode papier - annoncer un document qu\'on ne peut pas encore
 * retirer ne fait qu\'amener la personne au secrétariat.
 */',
        'startLine' => 86,
        'endLine' => 90,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\AttestationPolicy',
        'implementingClassName' => 'App\\Support\\AttestationPolicy',
        'currentClassName' => 'App\\Support\\AttestationPolicy',
        'aliasName' => NULL,
      ),
      'sendsSms' => 
      array (
        'name' => 'sendsSms',
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
            'startLine' => 99,
            'endLine' => 99,
            'startColumn' => 37,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * SMS à l\'émission ?
 *
 * ⚠️ **Fermé par défaut, et volontairement.** Chaque SMS est facturé, et une
 * événement de deux cents communications en enverrait autant sans que personne
 * ne l\'ait décidé.
 */',
        'startLine' => 99,
        'endLine' => 102,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\AttestationPolicy',
        'implementingClassName' => 'App\\Support\\AttestationPolicy',
        'currentClassName' => 'App\\Support\\AttestationPolicy',
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