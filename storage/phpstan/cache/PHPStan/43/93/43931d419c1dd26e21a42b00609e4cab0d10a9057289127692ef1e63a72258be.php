<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Support\ParticipantTypes.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Support\ParticipantTypes
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-37c44a9083388977999e8fc5bf25c894fbfecde1599a021a6ea6fd78352301fe',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Support\\ParticipantTypes',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Support/ParticipantTypes.php',
      ),
    ),
    'namespace' => 'App\\Support',
    'name' => 'App\\Support\\ParticipantTypes',
    'shortName' => 'ParticipantTypes',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Liste de référence des types/grades de participant (profil, auteur
 * correspondant, co-auteurs). **Source unique** des sélecteurs de grade, pour
 * éviter les valeurs libres divergentes : un grade absent de cette liste ne peut
 * être rattaché à aucune catégorie tarifaire, et le montant reste « à préciser »
 * au moment de payer.
 *
 * La liste est **éditable** dans Réglages (clé `participant_types`, un grade par
 * ligne) : chaque institution a ses propres intitulés. `DEFAULTS` ne sert que de
 * point de départ.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 16,
    'endLine' => 54,
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
      'DEFAULTS' => 
      array (
        'declaringClassName' => 'App\\Support\\ParticipantTypes',
        'implementingClassName' => 'App\\Support\\ParticipantTypes',
        'name' => 'DEFAULTS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'Professeur\', \'Maître de conférences\', \'Maître-assistant\', \'Enseignant / Chercheur\', \'Enseignant\', \'Doctorant\', \'Étudiant en master\', \'Autre participant\']',
          'attributes' => 
          array (
            'startLine' => 23,
            'endLine' => 32,
            'startTokenPos' => 25,
            'startFilePos' => 754,
            'endTokenPos' => 51,
            'endFilePos' => 983,
          ),
        ),
        'docComment' => '/**
 * Grades proposés par défaut, du plus fréquent au plus général.
 *
 * @var list<string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 23,
        'endLine' => 32,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'all' => 
      array (
        'name' => 'all',
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
 * Liste effective : celle des réglages si elle est renseignée, sinon les
 * valeurs par défaut. Toujours non vide (un sélecteur vide bloquerait
 * l\'inscription).
 *
 * @return list<string>
 */',
        'startLine' => 41,
        'endLine' => 53,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Support',
        'declaringClassName' => 'App\\Support\\ParticipantTypes',
        'implementingClassName' => 'App\\Support\\ParticipantTypes',
        'currentClassName' => 'App\\Support\\ParticipantTypes',
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