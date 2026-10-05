<?php declare(strict_types = 1);

// ftm-C:\Users\TOURE\Herd\gesco\app\Http\Controllers\Admin\FormationController.php
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v5-2.3.3',
   'data' => 
  array (
    0 => 
    array (
      '22e2ab1c692210d720e24cb0687c1dd8' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Admin',
         'uses' => 
        array (
          'extractformationfromdocuments' => 'App\\Actions\\ExtractFormationFromDocuments',
          'issueattestation' => 'App\\Actions\\IssueAttestation',
          'upsertparticipantforaccount' => 'App\\Actions\\UpsertParticipantForAccount',
          'aimanager' => 'App\\AI\\AiManager',
          'scopeseditionaccess' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'campaignmail' => 'App\\Mail\\CampaignMail',
          'email' => 'App\\Models\\Email',
          'event' => 'App\\Models\\Event',
          'formation' => 'App\\Models\\Formation',
          'formationtrainer' => 'App\\Models\\FormationTrainer',
          'participant' => 'App\\Models\\Participant',
          'user' => 'App\\Models\\User',
          'authormatcher' => 'App\\Support\\AuthorMatcher',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
          'request' => 'Illuminate\\Http\\Request',
          'mail' => 'Illuminate\\Support\\Facades\\Mail',
          'storage' => 'Illuminate\\Support\\Facades\\Storage',
          'str' => 'Illuminate\\Support\\Str',
          'view' => 'Illuminate\\View\\View',
        ),
         'className' => 'App\\Http\\Controllers\\Admin\\FormationController',
         'functionName' => NULL,
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => NULL,
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      'f6ce2f7a3fb659184399afb185b4fba5' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Admin\\Concerns',
         'uses' => 
        array (
          'ensureeditionaccess' => 'App\\Http\\Middleware\\EnsureEditionAccess',
          'event' => 'App\\Models\\Event',
          'request' => 'Illuminate\\Http\\Request',
          'collection' => 'Illuminate\\Support\\Collection',
        ),
         'className' => 'App\\Http\\Controllers\\Admin\\FormationController',
         'functionName' => NULL,
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => NULL,
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
         'traitData' => 
        array (
          0 => 'C:\\Users\\TOURE\\Herd\\gesco\\app\\Http\\Controllers\\Admin\\FormationController.php',
          1 => 'App\\Http\\Controllers\\Admin\\FormationController',
          2 => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
          3 => NULL,
          4 => NULL,
        ),
      )),
      'ddc6f2952323d6d933298798347a745a' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Admin\\Concerns',
         'uses' => 
        array (
          'ensureeditionaccess' => 'App\\Http\\Middleware\\EnsureEditionAccess',
          'event' => 'App\\Models\\Event',
          'request' => 'Illuminate\\Http\\Request',
          'collection' => 'Illuminate\\Support\\Collection',
        ),
         'className' => 'App\\Http\\Controllers\\Admin\\FormationController',
         'functionName' => 'scopedEditionIds',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Http\\Controllers\\Admin\\Concerns',
           'uses' => 
          array (
            'ensureeditionaccess' => 'App\\Http\\Middleware\\EnsureEditionAccess',
            'event' => 'App\\Models\\Event',
            'request' => 'Illuminate\\Http\\Request',
            'collection' => 'Illuminate\\Support\\Collection',
          ),
           'className' => 'App\\Http\\Controllers\\Admin\\FormationController',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
         'traitData' => 
        array (
          0 => 'C:\\Users\\TOURE\\Herd\\gesco\\app\\Http\\Controllers\\Admin\\FormationController.php',
          1 => 'App\\Http\\Controllers\\Admin\\FormationController',
          2 => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
          3 => NULL,
          4 => NULL,
        ),
      )),
      '58733b7c251a2f9c95f9c5c8c83ee8d8' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Admin\\Concerns',
         'uses' => 
        array (
          'ensureeditionaccess' => 'App\\Http\\Middleware\\EnsureEditionAccess',
          'event' => 'App\\Models\\Event',
          'request' => 'Illuminate\\Http\\Request',
          'collection' => 'Illuminate\\Support\\Collection',
        ),
         'className' => 'App\\Http\\Controllers\\Admin\\FormationController',
         'functionName' => 'accessibleEditions',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Http\\Controllers\\Admin\\Concerns',
           'uses' => 
          array (
            'ensureeditionaccess' => 'App\\Http\\Middleware\\EnsureEditionAccess',
            'event' => 'App\\Models\\Event',
            'request' => 'Illuminate\\Http\\Request',
            'collection' => 'Illuminate\\Support\\Collection',
          ),
           'className' => 'App\\Http\\Controllers\\Admin\\FormationController',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
         'traitData' => 
        array (
          0 => 'C:\\Users\\TOURE\\Herd\\gesco\\app\\Http\\Controllers\\Admin\\FormationController.php',
          1 => 'App\\Http\\Controllers\\Admin\\FormationController',
          2 => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
          3 => NULL,
          4 => NULL,
        ),
      )),
      'c5f83a28b17f31db331390630cf5a863' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Admin\\Concerns',
         'uses' => 
        array (
          'ensureeditionaccess' => 'App\\Http\\Middleware\\EnsureEditionAccess',
          'event' => 'App\\Models\\Event',
          'request' => 'Illuminate\\Http\\Request',
          'collection' => 'Illuminate\\Support\\Collection',
        ),
         'className' => 'App\\Http\\Controllers\\Admin\\FormationController',
         'functionName' => 'currentEditionId',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Http\\Controllers\\Admin\\Concerns',
           'uses' => 
          array (
            'ensureeditionaccess' => 'App\\Http\\Middleware\\EnsureEditionAccess',
            'event' => 'App\\Models\\Event',
            'request' => 'Illuminate\\Http\\Request',
            'collection' => 'Illuminate\\Support\\Collection',
          ),
           'className' => 'App\\Http\\Controllers\\Admin\\FormationController',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
         'traitData' => 
        array (
          0 => 'C:\\Users\\TOURE\\Herd\\gesco\\app\\Http\\Controllers\\Admin\\FormationController.php',
          1 => 'App\\Http\\Controllers\\Admin\\FormationController',
          2 => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
          3 => NULL,
          4 => NULL,
        ),
      )),
      'ae30ea142ae111f3802052a4c56b9af1' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Admin',
         'uses' => 
        array (
          'extractformationfromdocuments' => 'App\\Actions\\ExtractFormationFromDocuments',
          'issueattestation' => 'App\\Actions\\IssueAttestation',
          'upsertparticipantforaccount' => 'App\\Actions\\UpsertParticipantForAccount',
          'aimanager' => 'App\\AI\\AiManager',
          'scopeseditionaccess' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'campaignmail' => 'App\\Mail\\CampaignMail',
          'email' => 'App\\Models\\Email',
          'event' => 'App\\Models\\Event',
          'formation' => 'App\\Models\\Formation',
          'formationtrainer' => 'App\\Models\\FormationTrainer',
          'participant' => 'App\\Models\\Participant',
          'user' => 'App\\Models\\User',
          'authormatcher' => 'App\\Support\\AuthorMatcher',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
          'request' => 'Illuminate\\Http\\Request',
          'mail' => 'Illuminate\\Support\\Facades\\Mail',
          'storage' => 'Illuminate\\Support\\Facades\\Storage',
          'str' => 'Illuminate\\Support\\Str',
          'view' => 'Illuminate\\View\\View',
        ),
         'className' => 'App\\Http\\Controllers\\Admin\\FormationController',
         'functionName' => 'index',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Http\\Controllers\\Admin',
           'uses' => 
          array (
            'extractformationfromdocuments' => 'App\\Actions\\ExtractFormationFromDocuments',
            'issueattestation' => 'App\\Actions\\IssueAttestation',
            'upsertparticipantforaccount' => 'App\\Actions\\UpsertParticipantForAccount',
            'aimanager' => 'App\\AI\\AiManager',
            'scopeseditionaccess' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
            'controller' => 'App\\Http\\Controllers\\Controller',
            'campaignmail' => 'App\\Mail\\CampaignMail',
            'email' => 'App\\Models\\Email',
            'event' => 'App\\Models\\Event',
            'formation' => 'App\\Models\\Formation',
            'formationtrainer' => 'App\\Models\\FormationTrainer',
            'participant' => 'App\\Models\\Participant',
            'user' => 'App\\Models\\User',
            'authormatcher' => 'App\\Support\\AuthorMatcher',
            'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
            'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
            'request' => 'Illuminate\\Http\\Request',
            'mail' => 'Illuminate\\Support\\Facades\\Mail',
            'storage' => 'Illuminate\\Support\\Facades\\Storage',
            'str' => 'Illuminate\\Support\\Str',
            'view' => 'Illuminate\\View\\View',
          ),
           'className' => 'App\\Http\\Controllers\\Admin\\FormationController',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '99f15aa50f3ef9fbaccdd3e48255d48b' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Admin',
         'uses' => 
        array (
          'extractformationfromdocuments' => 'App\\Actions\\ExtractFormationFromDocuments',
          'issueattestation' => 'App\\Actions\\IssueAttestation',
          'upsertparticipantforaccount' => 'App\\Actions\\UpsertParticipantForAccount',
          'aimanager' => 'App\\AI\\AiManager',
          'scopeseditionaccess' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'campaignmail' => 'App\\Mail\\CampaignMail',
          'email' => 'App\\Models\\Email',
          'event' => 'App\\Models\\Event',
          'formation' => 'App\\Models\\Formation',
          'formationtrainer' => 'App\\Models\\FormationTrainer',
          'participant' => 'App\\Models\\Participant',
          'user' => 'App\\Models\\User',
          'authormatcher' => 'App\\Support\\AuthorMatcher',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
          'request' => 'Illuminate\\Http\\Request',
          'mail' => 'Illuminate\\Support\\Facades\\Mail',
          'storage' => 'Illuminate\\Support\\Facades\\Storage',
          'str' => 'Illuminate\\Support\\Str',
          'view' => 'Illuminate\\View\\View',
        ),
         'className' => 'App\\Http\\Controllers\\Admin\\FormationController',
         'functionName' => 'show',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Http\\Controllers\\Admin',
           'uses' => 
          array (
            'extractformationfromdocuments' => 'App\\Actions\\ExtractFormationFromDocuments',
            'issueattestation' => 'App\\Actions\\IssueAttestation',
            'upsertparticipantforaccount' => 'App\\Actions\\UpsertParticipantForAccount',
            'aimanager' => 'App\\AI\\AiManager',
            'scopeseditionaccess' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
            'controller' => 'App\\Http\\Controllers\\Controller',
            'campaignmail' => 'App\\Mail\\CampaignMail',
            'email' => 'App\\Models\\Email',
            'event' => 'App\\Models\\Event',
            'formation' => 'App\\Models\\Formation',
            'formationtrainer' => 'App\\Models\\FormationTrainer',
            'participant' => 'App\\Models\\Participant',
            'user' => 'App\\Models\\User',
            'authormatcher' => 'App\\Support\\AuthorMatcher',
            'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
            'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
            'request' => 'Illuminate\\Http\\Request',
            'mail' => 'Illuminate\\Support\\Facades\\Mail',
            'storage' => 'Illuminate\\Support\\Facades\\Storage',
            'str' => 'Illuminate\\Support\\Str',
            'view' => 'Illuminate\\View\\View',
          ),
           'className' => 'App\\Http\\Controllers\\Admin\\FormationController',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '8a6cf840ac25e6c03723e04f6c27d918' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Admin',
         'uses' => 
        array (
          'extractformationfromdocuments' => 'App\\Actions\\ExtractFormationFromDocuments',
          'issueattestation' => 'App\\Actions\\IssueAttestation',
          'upsertparticipantforaccount' => 'App\\Actions\\UpsertParticipantForAccount',
          'aimanager' => 'App\\AI\\AiManager',
          'scopeseditionaccess' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'campaignmail' => 'App\\Mail\\CampaignMail',
          'email' => 'App\\Models\\Email',
          'event' => 'App\\Models\\Event',
          'formation' => 'App\\Models\\Formation',
          'formationtrainer' => 'App\\Models\\FormationTrainer',
          'participant' => 'App\\Models\\Participant',
          'user' => 'App\\Models\\User',
          'authormatcher' => 'App\\Support\\AuthorMatcher',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
          'request' => 'Illuminate\\Http\\Request',
          'mail' => 'Illuminate\\Support\\Facades\\Mail',
          'storage' => 'Illuminate\\Support\\Facades\\Storage',
          'str' => 'Illuminate\\Support\\Str',
          'view' => 'Illuminate\\View\\View',
        ),
         'className' => 'App\\Http\\Controllers\\Admin\\FormationController',
         'functionName' => 'store',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Http\\Controllers\\Admin',
           'uses' => 
          array (
            'extractformationfromdocuments' => 'App\\Actions\\ExtractFormationFromDocuments',
            'issueattestation' => 'App\\Actions\\IssueAttestation',
            'upsertparticipantforaccount' => 'App\\Actions\\UpsertParticipantForAccount',
            'aimanager' => 'App\\AI\\AiManager',
            'scopeseditionaccess' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
            'controller' => 'App\\Http\\Controllers\\Controller',
            'campaignmail' => 'App\\Mail\\CampaignMail',
            'email' => 'App\\Models\\Email',
            'event' => 'App\\Models\\Event',
            'formation' => 'App\\Models\\Formation',
            'formationtrainer' => 'App\\Models\\FormationTrainer',
            'participant' => 'App\\Models\\Participant',
            'user' => 'App\\Models\\User',
            'authormatcher' => 'App\\Support\\AuthorMatcher',
            'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
            'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
            'request' => 'Illuminate\\Http\\Request',
            'mail' => 'Illuminate\\Support\\Facades\\Mail',
            'storage' => 'Illuminate\\Support\\Facades\\Storage',
            'str' => 'Illuminate\\Support\\Str',
            'view' => 'Illuminate\\View\\View',
          ),
           'className' => 'App\\Http\\Controllers\\Admin\\FormationController',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      'f191c85015d38f0f248a1b75eb559455' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Admin',
         'uses' => 
        array (
          'extractformationfromdocuments' => 'App\\Actions\\ExtractFormationFromDocuments',
          'issueattestation' => 'App\\Actions\\IssueAttestation',
          'upsertparticipantforaccount' => 'App\\Actions\\UpsertParticipantForAccount',
          'aimanager' => 'App\\AI\\AiManager',
          'scopeseditionaccess' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'campaignmail' => 'App\\Mail\\CampaignMail',
          'email' => 'App\\Models\\Email',
          'event' => 'App\\Models\\Event',
          'formation' => 'App\\Models\\Formation',
          'formationtrainer' => 'App\\Models\\FormationTrainer',
          'participant' => 'App\\Models\\Participant',
          'user' => 'App\\Models\\User',
          'authormatcher' => 'App\\Support\\AuthorMatcher',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
          'request' => 'Illuminate\\Http\\Request',
          'mail' => 'Illuminate\\Support\\Facades\\Mail',
          'storage' => 'Illuminate\\Support\\Facades\\Storage',
          'str' => 'Illuminate\\Support\\Str',
          'view' => 'Illuminate\\View\\View',
        ),
         'className' => 'App\\Http\\Controllers\\Admin\\FormationController',
         'functionName' => 'update',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Http\\Controllers\\Admin',
           'uses' => 
          array (
            'extractformationfromdocuments' => 'App\\Actions\\ExtractFormationFromDocuments',
            'issueattestation' => 'App\\Actions\\IssueAttestation',
            'upsertparticipantforaccount' => 'App\\Actions\\UpsertParticipantForAccount',
            'aimanager' => 'App\\AI\\AiManager',
            'scopeseditionaccess' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
            'controller' => 'App\\Http\\Controllers\\Controller',
            'campaignmail' => 'App\\Mail\\CampaignMail',
            'email' => 'App\\Models\\Email',
            'event' => 'App\\Models\\Event',
            'formation' => 'App\\Models\\Formation',
            'formationtrainer' => 'App\\Models\\FormationTrainer',
            'participant' => 'App\\Models\\Participant',
            'user' => 'App\\Models\\User',
            'authormatcher' => 'App\\Support\\AuthorMatcher',
            'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
            'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
            'request' => 'Illuminate\\Http\\Request',
            'mail' => 'Illuminate\\Support\\Facades\\Mail',
            'storage' => 'Illuminate\\Support\\Facades\\Storage',
            'str' => 'Illuminate\\Support\\Str',
            'view' => 'Illuminate\\View\\View',
          ),
           'className' => 'App\\Http\\Controllers\\Admin\\FormationController',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '28548a89a30212ab5bcb7be6ce9bb969' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Admin',
         'uses' => 
        array (
          'extractformationfromdocuments' => 'App\\Actions\\ExtractFormationFromDocuments',
          'issueattestation' => 'App\\Actions\\IssueAttestation',
          'upsertparticipantforaccount' => 'App\\Actions\\UpsertParticipantForAccount',
          'aimanager' => 'App\\AI\\AiManager',
          'scopeseditionaccess' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'campaignmail' => 'App\\Mail\\CampaignMail',
          'email' => 'App\\Models\\Email',
          'event' => 'App\\Models\\Event',
          'formation' => 'App\\Models\\Formation',
          'formationtrainer' => 'App\\Models\\FormationTrainer',
          'participant' => 'App\\Models\\Participant',
          'user' => 'App\\Models\\User',
          'authormatcher' => 'App\\Support\\AuthorMatcher',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
          'request' => 'Illuminate\\Http\\Request',
          'mail' => 'Illuminate\\Support\\Facades\\Mail',
          'storage' => 'Illuminate\\Support\\Facades\\Storage',
          'str' => 'Illuminate\\Support\\Str',
          'view' => 'Illuminate\\View\\View',
        ),
         'className' => 'App\\Http\\Controllers\\Admin\\FormationController',
         'functionName' => 'handleFlyer',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Http\\Controllers\\Admin',
           'uses' => 
          array (
            'extractformationfromdocuments' => 'App\\Actions\\ExtractFormationFromDocuments',
            'issueattestation' => 'App\\Actions\\IssueAttestation',
            'upsertparticipantforaccount' => 'App\\Actions\\UpsertParticipantForAccount',
            'aimanager' => 'App\\AI\\AiManager',
            'scopeseditionaccess' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
            'controller' => 'App\\Http\\Controllers\\Controller',
            'campaignmail' => 'App\\Mail\\CampaignMail',
            'email' => 'App\\Models\\Email',
            'event' => 'App\\Models\\Event',
            'formation' => 'App\\Models\\Formation',
            'formationtrainer' => 'App\\Models\\FormationTrainer',
            'participant' => 'App\\Models\\Participant',
            'user' => 'App\\Models\\User',
            'authormatcher' => 'App\\Support\\AuthorMatcher',
            'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
            'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
            'request' => 'Illuminate\\Http\\Request',
            'mail' => 'Illuminate\\Support\\Facades\\Mail',
            'storage' => 'Illuminate\\Support\\Facades\\Storage',
            'str' => 'Illuminate\\Support\\Str',
            'view' => 'Illuminate\\View\\View',
          ),
           'className' => 'App\\Http\\Controllers\\Admin\\FormationController',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      'c89f8f5ff5d4e03376f5653b642be26e' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Admin',
         'uses' => 
        array (
          'extractformationfromdocuments' => 'App\\Actions\\ExtractFormationFromDocuments',
          'issueattestation' => 'App\\Actions\\IssueAttestation',
          'upsertparticipantforaccount' => 'App\\Actions\\UpsertParticipantForAccount',
          'aimanager' => 'App\\AI\\AiManager',
          'scopeseditionaccess' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'campaignmail' => 'App\\Mail\\CampaignMail',
          'email' => 'App\\Models\\Email',
          'event' => 'App\\Models\\Event',
          'formation' => 'App\\Models\\Formation',
          'formationtrainer' => 'App\\Models\\FormationTrainer',
          'participant' => 'App\\Models\\Participant',
          'user' => 'App\\Models\\User',
          'authormatcher' => 'App\\Support\\AuthorMatcher',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
          'request' => 'Illuminate\\Http\\Request',
          'mail' => 'Illuminate\\Support\\Facades\\Mail',
          'storage' => 'Illuminate\\Support\\Facades\\Storage',
          'str' => 'Illuminate\\Support\\Str',
          'view' => 'Illuminate\\View\\View',
        ),
         'className' => 'App\\Http\\Controllers\\Admin\\FormationController',
         'functionName' => 'destroy',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Http\\Controllers\\Admin',
           'uses' => 
          array (
            'extractformationfromdocuments' => 'App\\Actions\\ExtractFormationFromDocuments',
            'issueattestation' => 'App\\Actions\\IssueAttestation',
            'upsertparticipantforaccount' => 'App\\Actions\\UpsertParticipantForAccount',
            'aimanager' => 'App\\AI\\AiManager',
            'scopeseditionaccess' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
            'controller' => 'App\\Http\\Controllers\\Controller',
            'campaignmail' => 'App\\Mail\\CampaignMail',
            'email' => 'App\\Models\\Email',
            'event' => 'App\\Models\\Event',
            'formation' => 'App\\Models\\Formation',
            'formationtrainer' => 'App\\Models\\FormationTrainer',
            'participant' => 'App\\Models\\Participant',
            'user' => 'App\\Models\\User',
            'authormatcher' => 'App\\Support\\AuthorMatcher',
            'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
            'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
            'request' => 'Illuminate\\Http\\Request',
            'mail' => 'Illuminate\\Support\\Facades\\Mail',
            'storage' => 'Illuminate\\Support\\Facades\\Storage',
            'str' => 'Illuminate\\Support\\Str',
            'view' => 'Illuminate\\View\\View',
          ),
           'className' => 'App\\Http\\Controllers\\Admin\\FormationController',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      'af9020cd0c8cdcdd70d9977e5e6006e9' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Admin',
         'uses' => 
        array (
          'extractformationfromdocuments' => 'App\\Actions\\ExtractFormationFromDocuments',
          'issueattestation' => 'App\\Actions\\IssueAttestation',
          'upsertparticipantforaccount' => 'App\\Actions\\UpsertParticipantForAccount',
          'aimanager' => 'App\\AI\\AiManager',
          'scopeseditionaccess' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'campaignmail' => 'App\\Mail\\CampaignMail',
          'email' => 'App\\Models\\Email',
          'event' => 'App\\Models\\Event',
          'formation' => 'App\\Models\\Formation',
          'formationtrainer' => 'App\\Models\\FormationTrainer',
          'participant' => 'App\\Models\\Participant',
          'user' => 'App\\Models\\User',
          'authormatcher' => 'App\\Support\\AuthorMatcher',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
          'request' => 'Illuminate\\Http\\Request',
          'mail' => 'Illuminate\\Support\\Facades\\Mail',
          'storage' => 'Illuminate\\Support\\Facades\\Storage',
          'str' => 'Illuminate\\Support\\Str',
          'view' => 'Illuminate\\View\\View',
        ),
         'className' => 'App\\Http\\Controllers\\Admin\\FormationController',
         'functionName' => 'storeTrainer',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Http\\Controllers\\Admin',
           'uses' => 
          array (
            'extractformationfromdocuments' => 'App\\Actions\\ExtractFormationFromDocuments',
            'issueattestation' => 'App\\Actions\\IssueAttestation',
            'upsertparticipantforaccount' => 'App\\Actions\\UpsertParticipantForAccount',
            'aimanager' => 'App\\AI\\AiManager',
            'scopeseditionaccess' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
            'controller' => 'App\\Http\\Controllers\\Controller',
            'campaignmail' => 'App\\Mail\\CampaignMail',
            'email' => 'App\\Models\\Email',
            'event' => 'App\\Models\\Event',
            'formation' => 'App\\Models\\Formation',
            'formationtrainer' => 'App\\Models\\FormationTrainer',
            'participant' => 'App\\Models\\Participant',
            'user' => 'App\\Models\\User',
            'authormatcher' => 'App\\Support\\AuthorMatcher',
            'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
            'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
            'request' => 'Illuminate\\Http\\Request',
            'mail' => 'Illuminate\\Support\\Facades\\Mail',
            'storage' => 'Illuminate\\Support\\Facades\\Storage',
            'str' => 'Illuminate\\Support\\Str',
            'view' => 'Illuminate\\View\\View',
          ),
           'className' => 'App\\Http\\Controllers\\Admin\\FormationController',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '3dd99b1a8c512d73a5da22befb2cacb2' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Admin',
         'uses' => 
        array (
          'extractformationfromdocuments' => 'App\\Actions\\ExtractFormationFromDocuments',
          'issueattestation' => 'App\\Actions\\IssueAttestation',
          'upsertparticipantforaccount' => 'App\\Actions\\UpsertParticipantForAccount',
          'aimanager' => 'App\\AI\\AiManager',
          'scopeseditionaccess' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'campaignmail' => 'App\\Mail\\CampaignMail',
          'email' => 'App\\Models\\Email',
          'event' => 'App\\Models\\Event',
          'formation' => 'App\\Models\\Formation',
          'formationtrainer' => 'App\\Models\\FormationTrainer',
          'participant' => 'App\\Models\\Participant',
          'user' => 'App\\Models\\User',
          'authormatcher' => 'App\\Support\\AuthorMatcher',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
          'request' => 'Illuminate\\Http\\Request',
          'mail' => 'Illuminate\\Support\\Facades\\Mail',
          'storage' => 'Illuminate\\Support\\Facades\\Storage',
          'str' => 'Illuminate\\Support\\Str',
          'view' => 'Illuminate\\View\\View',
        ),
         'className' => 'App\\Http\\Controllers\\Admin\\FormationController',
         'functionName' => 'updateTrainerPhoto',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Http\\Controllers\\Admin',
           'uses' => 
          array (
            'extractformationfromdocuments' => 'App\\Actions\\ExtractFormationFromDocuments',
            'issueattestation' => 'App\\Actions\\IssueAttestation',
            'upsertparticipantforaccount' => 'App\\Actions\\UpsertParticipantForAccount',
            'aimanager' => 'App\\AI\\AiManager',
            'scopeseditionaccess' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
            'controller' => 'App\\Http\\Controllers\\Controller',
            'campaignmail' => 'App\\Mail\\CampaignMail',
            'email' => 'App\\Models\\Email',
            'event' => 'App\\Models\\Event',
            'formation' => 'App\\Models\\Formation',
            'formationtrainer' => 'App\\Models\\FormationTrainer',
            'participant' => 'App\\Models\\Participant',
            'user' => 'App\\Models\\User',
            'authormatcher' => 'App\\Support\\AuthorMatcher',
            'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
            'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
            'request' => 'Illuminate\\Http\\Request',
            'mail' => 'Illuminate\\Support\\Facades\\Mail',
            'storage' => 'Illuminate\\Support\\Facades\\Storage',
            'str' => 'Illuminate\\Support\\Str',
            'view' => 'Illuminate\\View\\View',
          ),
           'className' => 'App\\Http\\Controllers\\Admin\\FormationController',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '960e967c2afcb60ff19279f6ca1e0a48' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Admin',
         'uses' => 
        array (
          'extractformationfromdocuments' => 'App\\Actions\\ExtractFormationFromDocuments',
          'issueattestation' => 'App\\Actions\\IssueAttestation',
          'upsertparticipantforaccount' => 'App\\Actions\\UpsertParticipantForAccount',
          'aimanager' => 'App\\AI\\AiManager',
          'scopeseditionaccess' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'campaignmail' => 'App\\Mail\\CampaignMail',
          'email' => 'App\\Models\\Email',
          'event' => 'App\\Models\\Event',
          'formation' => 'App\\Models\\Formation',
          'formationtrainer' => 'App\\Models\\FormationTrainer',
          'participant' => 'App\\Models\\Participant',
          'user' => 'App\\Models\\User',
          'authormatcher' => 'App\\Support\\AuthorMatcher',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
          'request' => 'Illuminate\\Http\\Request',
          'mail' => 'Illuminate\\Support\\Facades\\Mail',
          'storage' => 'Illuminate\\Support\\Facades\\Storage',
          'str' => 'Illuminate\\Support\\Str',
          'view' => 'Illuminate\\View\\View',
        ),
         'className' => 'App\\Http\\Controllers\\Admin\\FormationController',
         'functionName' => 'updateTrainerSignature',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Http\\Controllers\\Admin',
           'uses' => 
          array (
            'extractformationfromdocuments' => 'App\\Actions\\ExtractFormationFromDocuments',
            'issueattestation' => 'App\\Actions\\IssueAttestation',
            'upsertparticipantforaccount' => 'App\\Actions\\UpsertParticipantForAccount',
            'aimanager' => 'App\\AI\\AiManager',
            'scopeseditionaccess' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
            'controller' => 'App\\Http\\Controllers\\Controller',
            'campaignmail' => 'App\\Mail\\CampaignMail',
            'email' => 'App\\Models\\Email',
            'event' => 'App\\Models\\Event',
            'formation' => 'App\\Models\\Formation',
            'formationtrainer' => 'App\\Models\\FormationTrainer',
            'participant' => 'App\\Models\\Participant',
            'user' => 'App\\Models\\User',
            'authormatcher' => 'App\\Support\\AuthorMatcher',
            'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
            'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
            'request' => 'Illuminate\\Http\\Request',
            'mail' => 'Illuminate\\Support\\Facades\\Mail',
            'storage' => 'Illuminate\\Support\\Facades\\Storage',
            'str' => 'Illuminate\\Support\\Str',
            'view' => 'Illuminate\\View\\View',
          ),
           'className' => 'App\\Http\\Controllers\\Admin\\FormationController',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      'de99e19ba96155db6bc0b430adb1e684' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Admin',
         'uses' => 
        array (
          'extractformationfromdocuments' => 'App\\Actions\\ExtractFormationFromDocuments',
          'issueattestation' => 'App\\Actions\\IssueAttestation',
          'upsertparticipantforaccount' => 'App\\Actions\\UpsertParticipantForAccount',
          'aimanager' => 'App\\AI\\AiManager',
          'scopeseditionaccess' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'campaignmail' => 'App\\Mail\\CampaignMail',
          'email' => 'App\\Models\\Email',
          'event' => 'App\\Models\\Event',
          'formation' => 'App\\Models\\Formation',
          'formationtrainer' => 'App\\Models\\FormationTrainer',
          'participant' => 'App\\Models\\Participant',
          'user' => 'App\\Models\\User',
          'authormatcher' => 'App\\Support\\AuthorMatcher',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
          'request' => 'Illuminate\\Http\\Request',
          'mail' => 'Illuminate\\Support\\Facades\\Mail',
          'storage' => 'Illuminate\\Support\\Facades\\Storage',
          'str' => 'Illuminate\\Support\\Str',
          'view' => 'Illuminate\\View\\View',
        ),
         'className' => 'App\\Http\\Controllers\\Admin\\FormationController',
         'functionName' => 'updateTrainer',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Http\\Controllers\\Admin',
           'uses' => 
          array (
            'extractformationfromdocuments' => 'App\\Actions\\ExtractFormationFromDocuments',
            'issueattestation' => 'App\\Actions\\IssueAttestation',
            'upsertparticipantforaccount' => 'App\\Actions\\UpsertParticipantForAccount',
            'aimanager' => 'App\\AI\\AiManager',
            'scopeseditionaccess' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
            'controller' => 'App\\Http\\Controllers\\Controller',
            'campaignmail' => 'App\\Mail\\CampaignMail',
            'email' => 'App\\Models\\Email',
            'event' => 'App\\Models\\Event',
            'formation' => 'App\\Models\\Formation',
            'formationtrainer' => 'App\\Models\\FormationTrainer',
            'participant' => 'App\\Models\\Participant',
            'user' => 'App\\Models\\User',
            'authormatcher' => 'App\\Support\\AuthorMatcher',
            'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
            'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
            'request' => 'Illuminate\\Http\\Request',
            'mail' => 'Illuminate\\Support\\Facades\\Mail',
            'storage' => 'Illuminate\\Support\\Facades\\Storage',
            'str' => 'Illuminate\\Support\\Str',
            'view' => 'Illuminate\\View\\View',
          ),
           'className' => 'App\\Http\\Controllers\\Admin\\FormationController',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '1b3ff11b3d6fff5d45a478e6fa05533e' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Admin',
         'uses' => 
        array (
          'extractformationfromdocuments' => 'App\\Actions\\ExtractFormationFromDocuments',
          'issueattestation' => 'App\\Actions\\IssueAttestation',
          'upsertparticipantforaccount' => 'App\\Actions\\UpsertParticipantForAccount',
          'aimanager' => 'App\\AI\\AiManager',
          'scopeseditionaccess' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'campaignmail' => 'App\\Mail\\CampaignMail',
          'email' => 'App\\Models\\Email',
          'event' => 'App\\Models\\Event',
          'formation' => 'App\\Models\\Formation',
          'formationtrainer' => 'App\\Models\\FormationTrainer',
          'participant' => 'App\\Models\\Participant',
          'user' => 'App\\Models\\User',
          'authormatcher' => 'App\\Support\\AuthorMatcher',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
          'request' => 'Illuminate\\Http\\Request',
          'mail' => 'Illuminate\\Support\\Facades\\Mail',
          'storage' => 'Illuminate\\Support\\Facades\\Storage',
          'str' => 'Illuminate\\Support\\Str',
          'view' => 'Illuminate\\View\\View',
        ),
         'className' => 'App\\Http\\Controllers\\Admin\\FormationController',
         'functionName' => 'destroyTrainer',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Http\\Controllers\\Admin',
           'uses' => 
          array (
            'extractformationfromdocuments' => 'App\\Actions\\ExtractFormationFromDocuments',
            'issueattestation' => 'App\\Actions\\IssueAttestation',
            'upsertparticipantforaccount' => 'App\\Actions\\UpsertParticipantForAccount',
            'aimanager' => 'App\\AI\\AiManager',
            'scopeseditionaccess' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
            'controller' => 'App\\Http\\Controllers\\Controller',
            'campaignmail' => 'App\\Mail\\CampaignMail',
            'email' => 'App\\Models\\Email',
            'event' => 'App\\Models\\Event',
            'formation' => 'App\\Models\\Formation',
            'formationtrainer' => 'App\\Models\\FormationTrainer',
            'participant' => 'App\\Models\\Participant',
            'user' => 'App\\Models\\User',
            'authormatcher' => 'App\\Support\\AuthorMatcher',
            'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
            'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
            'request' => 'Illuminate\\Http\\Request',
            'mail' => 'Illuminate\\Support\\Facades\\Mail',
            'storage' => 'Illuminate\\Support\\Facades\\Storage',
            'str' => 'Illuminate\\Support\\Str',
            'view' => 'Illuminate\\View\\View',
          ),
           'className' => 'App\\Http\\Controllers\\Admin\\FormationController',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '7a140d255c5dc28c2cc1f7e8f12c7963' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Admin',
         'uses' => 
        array (
          'extractformationfromdocuments' => 'App\\Actions\\ExtractFormationFromDocuments',
          'issueattestation' => 'App\\Actions\\IssueAttestation',
          'upsertparticipantforaccount' => 'App\\Actions\\UpsertParticipantForAccount',
          'aimanager' => 'App\\AI\\AiManager',
          'scopeseditionaccess' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'campaignmail' => 'App\\Mail\\CampaignMail',
          'email' => 'App\\Models\\Email',
          'event' => 'App\\Models\\Event',
          'formation' => 'App\\Models\\Formation',
          'formationtrainer' => 'App\\Models\\FormationTrainer',
          'participant' => 'App\\Models\\Participant',
          'user' => 'App\\Models\\User',
          'authormatcher' => 'App\\Support\\AuthorMatcher',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
          'request' => 'Illuminate\\Http\\Request',
          'mail' => 'Illuminate\\Support\\Facades\\Mail',
          'storage' => 'Illuminate\\Support\\Facades\\Storage',
          'str' => 'Illuminate\\Support\\Str',
          'view' => 'Illuminate\\View\\View',
        ),
         'className' => 'App\\Http\\Controllers\\Admin\\FormationController',
         'functionName' => 'issueAttestations',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Http\\Controllers\\Admin',
           'uses' => 
          array (
            'extractformationfromdocuments' => 'App\\Actions\\ExtractFormationFromDocuments',
            'issueattestation' => 'App\\Actions\\IssueAttestation',
            'upsertparticipantforaccount' => 'App\\Actions\\UpsertParticipantForAccount',
            'aimanager' => 'App\\AI\\AiManager',
            'scopeseditionaccess' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
            'controller' => 'App\\Http\\Controllers\\Controller',
            'campaignmail' => 'App\\Mail\\CampaignMail',
            'email' => 'App\\Models\\Email',
            'event' => 'App\\Models\\Event',
            'formation' => 'App\\Models\\Formation',
            'formationtrainer' => 'App\\Models\\FormationTrainer',
            'participant' => 'App\\Models\\Participant',
            'user' => 'App\\Models\\User',
            'authormatcher' => 'App\\Support\\AuthorMatcher',
            'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
            'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
            'request' => 'Illuminate\\Http\\Request',
            'mail' => 'Illuminate\\Support\\Facades\\Mail',
            'storage' => 'Illuminate\\Support\\Facades\\Storage',
            'str' => 'Illuminate\\Support\\Str',
            'view' => 'Illuminate\\View\\View',
          ),
           'className' => 'App\\Http\\Controllers\\Admin\\FormationController',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '4d2a68e090268178dcb1609d1cc130c6' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Admin',
         'uses' => 
        array (
          'extractformationfromdocuments' => 'App\\Actions\\ExtractFormationFromDocuments',
          'issueattestation' => 'App\\Actions\\IssueAttestation',
          'upsertparticipantforaccount' => 'App\\Actions\\UpsertParticipantForAccount',
          'aimanager' => 'App\\AI\\AiManager',
          'scopeseditionaccess' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'campaignmail' => 'App\\Mail\\CampaignMail',
          'email' => 'App\\Models\\Email',
          'event' => 'App\\Models\\Event',
          'formation' => 'App\\Models\\Formation',
          'formationtrainer' => 'App\\Models\\FormationTrainer',
          'participant' => 'App\\Models\\Participant',
          'user' => 'App\\Models\\User',
          'authormatcher' => 'App\\Support\\AuthorMatcher',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
          'request' => 'Illuminate\\Http\\Request',
          'mail' => 'Illuminate\\Support\\Facades\\Mail',
          'storage' => 'Illuminate\\Support\\Facades\\Storage',
          'str' => 'Illuminate\\Support\\Str',
          'view' => 'Illuminate\\View\\View',
        ),
         'className' => 'App\\Http\\Controllers\\Admin\\FormationController',
         'functionName' => 'participantForTrainer',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Http\\Controllers\\Admin',
           'uses' => 
          array (
            'extractformationfromdocuments' => 'App\\Actions\\ExtractFormationFromDocuments',
            'issueattestation' => 'App\\Actions\\IssueAttestation',
            'upsertparticipantforaccount' => 'App\\Actions\\UpsertParticipantForAccount',
            'aimanager' => 'App\\AI\\AiManager',
            'scopeseditionaccess' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
            'controller' => 'App\\Http\\Controllers\\Controller',
            'campaignmail' => 'App\\Mail\\CampaignMail',
            'email' => 'App\\Models\\Email',
            'event' => 'App\\Models\\Event',
            'formation' => 'App\\Models\\Formation',
            'formationtrainer' => 'App\\Models\\FormationTrainer',
            'participant' => 'App\\Models\\Participant',
            'user' => 'App\\Models\\User',
            'authormatcher' => 'App\\Support\\AuthorMatcher',
            'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
            'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
            'request' => 'Illuminate\\Http\\Request',
            'mail' => 'Illuminate\\Support\\Facades\\Mail',
            'storage' => 'Illuminate\\Support\\Facades\\Storage',
            'str' => 'Illuminate\\Support\\Str',
            'view' => 'Illuminate\\View\\View',
          ),
           'className' => 'App\\Http\\Controllers\\Admin\\FormationController',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '396f5d9ec34a1bd43410d66f164c33a5' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Admin',
         'uses' => 
        array (
          'extractformationfromdocuments' => 'App\\Actions\\ExtractFormationFromDocuments',
          'issueattestation' => 'App\\Actions\\IssueAttestation',
          'upsertparticipantforaccount' => 'App\\Actions\\UpsertParticipantForAccount',
          'aimanager' => 'App\\AI\\AiManager',
          'scopeseditionaccess' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'campaignmail' => 'App\\Mail\\CampaignMail',
          'email' => 'App\\Models\\Email',
          'event' => 'App\\Models\\Event',
          'formation' => 'App\\Models\\Formation',
          'formationtrainer' => 'App\\Models\\FormationTrainer',
          'participant' => 'App\\Models\\Participant',
          'user' => 'App\\Models\\User',
          'authormatcher' => 'App\\Support\\AuthorMatcher',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
          'request' => 'Illuminate\\Http\\Request',
          'mail' => 'Illuminate\\Support\\Facades\\Mail',
          'storage' => 'Illuminate\\Support\\Facades\\Storage',
          'str' => 'Illuminate\\Support\\Str',
          'view' => 'Illuminate\\View\\View',
        ),
         'className' => 'App\\Http\\Controllers\\Admin\\FormationController',
         'functionName' => 'message',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Http\\Controllers\\Admin',
           'uses' => 
          array (
            'extractformationfromdocuments' => 'App\\Actions\\ExtractFormationFromDocuments',
            'issueattestation' => 'App\\Actions\\IssueAttestation',
            'upsertparticipantforaccount' => 'App\\Actions\\UpsertParticipantForAccount',
            'aimanager' => 'App\\AI\\AiManager',
            'scopeseditionaccess' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
            'controller' => 'App\\Http\\Controllers\\Controller',
            'campaignmail' => 'App\\Mail\\CampaignMail',
            'email' => 'App\\Models\\Email',
            'event' => 'App\\Models\\Event',
            'formation' => 'App\\Models\\Formation',
            'formationtrainer' => 'App\\Models\\FormationTrainer',
            'participant' => 'App\\Models\\Participant',
            'user' => 'App\\Models\\User',
            'authormatcher' => 'App\\Support\\AuthorMatcher',
            'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
            'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
            'request' => 'Illuminate\\Http\\Request',
            'mail' => 'Illuminate\\Support\\Facades\\Mail',
            'storage' => 'Illuminate\\Support\\Facades\\Storage',
            'str' => 'Illuminate\\Support\\Str',
            'view' => 'Illuminate\\View\\View',
          ),
           'className' => 'App\\Http\\Controllers\\Admin\\FormationController',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '100f3b822db40fbd15ca3feb6ff48541' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Admin',
         'uses' => 
        array (
          'extractformationfromdocuments' => 'App\\Actions\\ExtractFormationFromDocuments',
          'issueattestation' => 'App\\Actions\\IssueAttestation',
          'upsertparticipantforaccount' => 'App\\Actions\\UpsertParticipantForAccount',
          'aimanager' => 'App\\AI\\AiManager',
          'scopeseditionaccess' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'campaignmail' => 'App\\Mail\\CampaignMail',
          'email' => 'App\\Models\\Email',
          'event' => 'App\\Models\\Event',
          'formation' => 'App\\Models\\Formation',
          'formationtrainer' => 'App\\Models\\FormationTrainer',
          'participant' => 'App\\Models\\Participant',
          'user' => 'App\\Models\\User',
          'authormatcher' => 'App\\Support\\AuthorMatcher',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
          'request' => 'Illuminate\\Http\\Request',
          'mail' => 'Illuminate\\Support\\Facades\\Mail',
          'storage' => 'Illuminate\\Support\\Facades\\Storage',
          'str' => 'Illuminate\\Support\\Str',
          'view' => 'Illuminate\\View\\View',
        ),
         'className' => 'App\\Http\\Controllers\\Admin\\FormationController',
         'functionName' => 'aiAssist',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Http\\Controllers\\Admin',
           'uses' => 
          array (
            'extractformationfromdocuments' => 'App\\Actions\\ExtractFormationFromDocuments',
            'issueattestation' => 'App\\Actions\\IssueAttestation',
            'upsertparticipantforaccount' => 'App\\Actions\\UpsertParticipantForAccount',
            'aimanager' => 'App\\AI\\AiManager',
            'scopeseditionaccess' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
            'controller' => 'App\\Http\\Controllers\\Controller',
            'campaignmail' => 'App\\Mail\\CampaignMail',
            'email' => 'App\\Models\\Email',
            'event' => 'App\\Models\\Event',
            'formation' => 'App\\Models\\Formation',
            'formationtrainer' => 'App\\Models\\FormationTrainer',
            'participant' => 'App\\Models\\Participant',
            'user' => 'App\\Models\\User',
            'authormatcher' => 'App\\Support\\AuthorMatcher',
            'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
            'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
            'request' => 'Illuminate\\Http\\Request',
            'mail' => 'Illuminate\\Support\\Facades\\Mail',
            'storage' => 'Illuminate\\Support\\Facades\\Storage',
            'str' => 'Illuminate\\Support\\Str',
            'view' => 'Illuminate\\View\\View',
          ),
           'className' => 'App\\Http\\Controllers\\Admin\\FormationController',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '436c657a2593c448c322ac673d6089ff' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Admin',
         'uses' => 
        array (
          'extractformationfromdocuments' => 'App\\Actions\\ExtractFormationFromDocuments',
          'issueattestation' => 'App\\Actions\\IssueAttestation',
          'upsertparticipantforaccount' => 'App\\Actions\\UpsertParticipantForAccount',
          'aimanager' => 'App\\AI\\AiManager',
          'scopeseditionaccess' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'campaignmail' => 'App\\Mail\\CampaignMail',
          'email' => 'App\\Models\\Email',
          'event' => 'App\\Models\\Event',
          'formation' => 'App\\Models\\Formation',
          'formationtrainer' => 'App\\Models\\FormationTrainer',
          'participant' => 'App\\Models\\Participant',
          'user' => 'App\\Models\\User',
          'authormatcher' => 'App\\Support\\AuthorMatcher',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
          'request' => 'Illuminate\\Http\\Request',
          'mail' => 'Illuminate\\Support\\Facades\\Mail',
          'storage' => 'Illuminate\\Support\\Facades\\Storage',
          'str' => 'Illuminate\\Support\\Str',
          'view' => 'Illuminate\\View\\View',
        ),
         'className' => 'App\\Http\\Controllers\\Admin\\FormationController',
         'functionName' => 'setMeeting',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Http\\Controllers\\Admin',
           'uses' => 
          array (
            'extractformationfromdocuments' => 'App\\Actions\\ExtractFormationFromDocuments',
            'issueattestation' => 'App\\Actions\\IssueAttestation',
            'upsertparticipantforaccount' => 'App\\Actions\\UpsertParticipantForAccount',
            'aimanager' => 'App\\AI\\AiManager',
            'scopeseditionaccess' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
            'controller' => 'App\\Http\\Controllers\\Controller',
            'campaignmail' => 'App\\Mail\\CampaignMail',
            'email' => 'App\\Models\\Email',
            'event' => 'App\\Models\\Event',
            'formation' => 'App\\Models\\Formation',
            'formationtrainer' => 'App\\Models\\FormationTrainer',
            'participant' => 'App\\Models\\Participant',
            'user' => 'App\\Models\\User',
            'authormatcher' => 'App\\Support\\AuthorMatcher',
            'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
            'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
            'request' => 'Illuminate\\Http\\Request',
            'mail' => 'Illuminate\\Support\\Facades\\Mail',
            'storage' => 'Illuminate\\Support\\Facades\\Storage',
            'str' => 'Illuminate\\Support\\Str',
            'view' => 'Illuminate\\View\\View',
          ),
           'className' => 'App\\Http\\Controllers\\Admin\\FormationController',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      'a2ee7a9102aed93dc83d40cc6f38b64f' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Admin',
         'uses' => 
        array (
          'extractformationfromdocuments' => 'App\\Actions\\ExtractFormationFromDocuments',
          'issueattestation' => 'App\\Actions\\IssueAttestation',
          'upsertparticipantforaccount' => 'App\\Actions\\UpsertParticipantForAccount',
          'aimanager' => 'App\\AI\\AiManager',
          'scopeseditionaccess' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'campaignmail' => 'App\\Mail\\CampaignMail',
          'email' => 'App\\Models\\Email',
          'event' => 'App\\Models\\Event',
          'formation' => 'App\\Models\\Formation',
          'formationtrainer' => 'App\\Models\\FormationTrainer',
          'participant' => 'App\\Models\\Participant',
          'user' => 'App\\Models\\User',
          'authormatcher' => 'App\\Support\\AuthorMatcher',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
          'request' => 'Illuminate\\Http\\Request',
          'mail' => 'Illuminate\\Support\\Facades\\Mail',
          'storage' => 'Illuminate\\Support\\Facades\\Storage',
          'str' => 'Illuminate\\Support\\Str',
          'view' => 'Illuminate\\View\\View',
        ),
         'className' => 'App\\Http\\Controllers\\Admin\\FormationController',
         'functionName' => 'validated',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Http\\Controllers\\Admin',
           'uses' => 
          array (
            'extractformationfromdocuments' => 'App\\Actions\\ExtractFormationFromDocuments',
            'issueattestation' => 'App\\Actions\\IssueAttestation',
            'upsertparticipantforaccount' => 'App\\Actions\\UpsertParticipantForAccount',
            'aimanager' => 'App\\AI\\AiManager',
            'scopeseditionaccess' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
            'controller' => 'App\\Http\\Controllers\\Controller',
            'campaignmail' => 'App\\Mail\\CampaignMail',
            'email' => 'App\\Models\\Email',
            'event' => 'App\\Models\\Event',
            'formation' => 'App\\Models\\Formation',
            'formationtrainer' => 'App\\Models\\FormationTrainer',
            'participant' => 'App\\Models\\Participant',
            'user' => 'App\\Models\\User',
            'authormatcher' => 'App\\Support\\AuthorMatcher',
            'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
            'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
            'request' => 'Illuminate\\Http\\Request',
            'mail' => 'Illuminate\\Support\\Facades\\Mail',
            'storage' => 'Illuminate\\Support\\Facades\\Storage',
            'str' => 'Illuminate\\Support\\Str',
            'view' => 'Illuminate\\View\\View',
          ),
           'className' => 'App\\Http\\Controllers\\Admin\\FormationController',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '2481a2a5287a1a4c855b65c8ce6b6007' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Admin',
         'uses' => 
        array (
          'extractformationfromdocuments' => 'App\\Actions\\ExtractFormationFromDocuments',
          'issueattestation' => 'App\\Actions\\IssueAttestation',
          'upsertparticipantforaccount' => 'App\\Actions\\UpsertParticipantForAccount',
          'aimanager' => 'App\\AI\\AiManager',
          'scopeseditionaccess' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'campaignmail' => 'App\\Mail\\CampaignMail',
          'email' => 'App\\Models\\Email',
          'event' => 'App\\Models\\Event',
          'formation' => 'App\\Models\\Formation',
          'formationtrainer' => 'App\\Models\\FormationTrainer',
          'participant' => 'App\\Models\\Participant',
          'user' => 'App\\Models\\User',
          'authormatcher' => 'App\\Support\\AuthorMatcher',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
          'request' => 'Illuminate\\Http\\Request',
          'mail' => 'Illuminate\\Support\\Facades\\Mail',
          'storage' => 'Illuminate\\Support\\Facades\\Storage',
          'str' => 'Illuminate\\Support\\Str',
          'view' => 'Illuminate\\View\\View',
        ),
         'className' => 'App\\Http\\Controllers\\Admin\\FormationController',
         'functionName' => 'trainerAccount',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Http\\Controllers\\Admin',
           'uses' => 
          array (
            'extractformationfromdocuments' => 'App\\Actions\\ExtractFormationFromDocuments',
            'issueattestation' => 'App\\Actions\\IssueAttestation',
            'upsertparticipantforaccount' => 'App\\Actions\\UpsertParticipantForAccount',
            'aimanager' => 'App\\AI\\AiManager',
            'scopeseditionaccess' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
            'controller' => 'App\\Http\\Controllers\\Controller',
            'campaignmail' => 'App\\Mail\\CampaignMail',
            'email' => 'App\\Models\\Email',
            'event' => 'App\\Models\\Event',
            'formation' => 'App\\Models\\Formation',
            'formationtrainer' => 'App\\Models\\FormationTrainer',
            'participant' => 'App\\Models\\Participant',
            'user' => 'App\\Models\\User',
            'authormatcher' => 'App\\Support\\AuthorMatcher',
            'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
            'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
            'request' => 'Illuminate\\Http\\Request',
            'mail' => 'Illuminate\\Support\\Facades\\Mail',
            'storage' => 'Illuminate\\Support\\Facades\\Storage',
            'str' => 'Illuminate\\Support\\Str',
            'view' => 'Illuminate\\View\\View',
          ),
           'className' => 'App\\Http\\Controllers\\Admin\\FormationController',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '4be3edef6e416551824076c5a8ca3030' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Admin',
         'uses' => 
        array (
          'extractformationfromdocuments' => 'App\\Actions\\ExtractFormationFromDocuments',
          'issueattestation' => 'App\\Actions\\IssueAttestation',
          'upsertparticipantforaccount' => 'App\\Actions\\UpsertParticipantForAccount',
          'aimanager' => 'App\\AI\\AiManager',
          'scopeseditionaccess' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'campaignmail' => 'App\\Mail\\CampaignMail',
          'email' => 'App\\Models\\Email',
          'event' => 'App\\Models\\Event',
          'formation' => 'App\\Models\\Formation',
          'formationtrainer' => 'App\\Models\\FormationTrainer',
          'participant' => 'App\\Models\\Participant',
          'user' => 'App\\Models\\User',
          'authormatcher' => 'App\\Support\\AuthorMatcher',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
          'request' => 'Illuminate\\Http\\Request',
          'mail' => 'Illuminate\\Support\\Facades\\Mail',
          'storage' => 'Illuminate\\Support\\Facades\\Storage',
          'str' => 'Illuminate\\Support\\Str',
          'view' => 'Illuminate\\View\\View',
        ),
         'className' => 'App\\Http\\Controllers\\Admin\\FormationController',
         'functionName' => 'accountName',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Http\\Controllers\\Admin',
           'uses' => 
          array (
            'extractformationfromdocuments' => 'App\\Actions\\ExtractFormationFromDocuments',
            'issueattestation' => 'App\\Actions\\IssueAttestation',
            'upsertparticipantforaccount' => 'App\\Actions\\UpsertParticipantForAccount',
            'aimanager' => 'App\\AI\\AiManager',
            'scopeseditionaccess' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
            'controller' => 'App\\Http\\Controllers\\Controller',
            'campaignmail' => 'App\\Mail\\CampaignMail',
            'email' => 'App\\Models\\Email',
            'event' => 'App\\Models\\Event',
            'formation' => 'App\\Models\\Formation',
            'formationtrainer' => 'App\\Models\\FormationTrainer',
            'participant' => 'App\\Models\\Participant',
            'user' => 'App\\Models\\User',
            'authormatcher' => 'App\\Support\\AuthorMatcher',
            'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
            'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
            'request' => 'Illuminate\\Http\\Request',
            'mail' => 'Illuminate\\Support\\Facades\\Mail',
            'storage' => 'Illuminate\\Support\\Facades\\Storage',
            'str' => 'Illuminate\\Support\\Str',
            'view' => 'Illuminate\\View\\View',
          ),
           'className' => 'App\\Http\\Controllers\\Admin\\FormationController',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '4880168d10b0173da674b4726435a1f8' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Admin',
         'uses' => 
        array (
          'extractformationfromdocuments' => 'App\\Actions\\ExtractFormationFromDocuments',
          'issueattestation' => 'App\\Actions\\IssueAttestation',
          'upsertparticipantforaccount' => 'App\\Actions\\UpsertParticipantForAccount',
          'aimanager' => 'App\\AI\\AiManager',
          'scopeseditionaccess' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'campaignmail' => 'App\\Mail\\CampaignMail',
          'email' => 'App\\Models\\Email',
          'event' => 'App\\Models\\Event',
          'formation' => 'App\\Models\\Formation',
          'formationtrainer' => 'App\\Models\\FormationTrainer',
          'participant' => 'App\\Models\\Participant',
          'user' => 'App\\Models\\User',
          'authormatcher' => 'App\\Support\\AuthorMatcher',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
          'request' => 'Illuminate\\Http\\Request',
          'mail' => 'Illuminate\\Support\\Facades\\Mail',
          'storage' => 'Illuminate\\Support\\Facades\\Storage',
          'str' => 'Illuminate\\Support\\Str',
          'view' => 'Illuminate\\View\\View',
        ),
         'className' => 'App\\Http\\Controllers\\Admin\\FormationController',
         'functionName' => 'trainerAttributes',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Http\\Controllers\\Admin',
           'uses' => 
          array (
            'extractformationfromdocuments' => 'App\\Actions\\ExtractFormationFromDocuments',
            'issueattestation' => 'App\\Actions\\IssueAttestation',
            'upsertparticipantforaccount' => 'App\\Actions\\UpsertParticipantForAccount',
            'aimanager' => 'App\\AI\\AiManager',
            'scopeseditionaccess' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
            'controller' => 'App\\Http\\Controllers\\Controller',
            'campaignmail' => 'App\\Mail\\CampaignMail',
            'email' => 'App\\Models\\Email',
            'event' => 'App\\Models\\Event',
            'formation' => 'App\\Models\\Formation',
            'formationtrainer' => 'App\\Models\\FormationTrainer',
            'participant' => 'App\\Models\\Participant',
            'user' => 'App\\Models\\User',
            'authormatcher' => 'App\\Support\\AuthorMatcher',
            'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
            'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
            'request' => 'Illuminate\\Http\\Request',
            'mail' => 'Illuminate\\Support\\Facades\\Mail',
            'storage' => 'Illuminate\\Support\\Facades\\Storage',
            'str' => 'Illuminate\\Support\\Str',
            'view' => 'Illuminate\\View\\View',
          ),
           'className' => 'App\\Http\\Controllers\\Admin\\FormationController',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '5ea687ef22a15d218d83c8877e7eeabd' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Admin',
         'uses' => 
        array (
          'extractformationfromdocuments' => 'App\\Actions\\ExtractFormationFromDocuments',
          'issueattestation' => 'App\\Actions\\IssueAttestation',
          'upsertparticipantforaccount' => 'App\\Actions\\UpsertParticipantForAccount',
          'aimanager' => 'App\\AI\\AiManager',
          'scopeseditionaccess' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'campaignmail' => 'App\\Mail\\CampaignMail',
          'email' => 'App\\Models\\Email',
          'event' => 'App\\Models\\Event',
          'formation' => 'App\\Models\\Formation',
          'formationtrainer' => 'App\\Models\\FormationTrainer',
          'participant' => 'App\\Models\\Participant',
          'user' => 'App\\Models\\User',
          'authormatcher' => 'App\\Support\\AuthorMatcher',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
          'request' => 'Illuminate\\Http\\Request',
          'mail' => 'Illuminate\\Support\\Facades\\Mail',
          'storage' => 'Illuminate\\Support\\Facades\\Storage',
          'str' => 'Illuminate\\Support\\Str',
          'view' => 'Illuminate\\View\\View',
        ),
         'className' => 'App\\Http\\Controllers\\Admin\\FormationController',
         'functionName' => 'lookupTrainer',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Http\\Controllers\\Admin',
           'uses' => 
          array (
            'extractformationfromdocuments' => 'App\\Actions\\ExtractFormationFromDocuments',
            'issueattestation' => 'App\\Actions\\IssueAttestation',
            'upsertparticipantforaccount' => 'App\\Actions\\UpsertParticipantForAccount',
            'aimanager' => 'App\\AI\\AiManager',
            'scopeseditionaccess' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
            'controller' => 'App\\Http\\Controllers\\Controller',
            'campaignmail' => 'App\\Mail\\CampaignMail',
            'email' => 'App\\Models\\Email',
            'event' => 'App\\Models\\Event',
            'formation' => 'App\\Models\\Formation',
            'formationtrainer' => 'App\\Models\\FormationTrainer',
            'participant' => 'App\\Models\\Participant',
            'user' => 'App\\Models\\User',
            'authormatcher' => 'App\\Support\\AuthorMatcher',
            'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
            'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
            'request' => 'Illuminate\\Http\\Request',
            'mail' => 'Illuminate\\Support\\Facades\\Mail',
            'storage' => 'Illuminate\\Support\\Facades\\Storage',
            'str' => 'Illuminate\\Support\\Str',
            'view' => 'Illuminate\\View\\View',
          ),
           'className' => 'App\\Http\\Controllers\\Admin\\FormationController',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '2966f3d93098171608cc2a591a2063ee' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Admin',
         'uses' => 
        array (
          'extractformationfromdocuments' => 'App\\Actions\\ExtractFormationFromDocuments',
          'issueattestation' => 'App\\Actions\\IssueAttestation',
          'upsertparticipantforaccount' => 'App\\Actions\\UpsertParticipantForAccount',
          'aimanager' => 'App\\AI\\AiManager',
          'scopeseditionaccess' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'campaignmail' => 'App\\Mail\\CampaignMail',
          'email' => 'App\\Models\\Email',
          'event' => 'App\\Models\\Event',
          'formation' => 'App\\Models\\Formation',
          'formationtrainer' => 'App\\Models\\FormationTrainer',
          'participant' => 'App\\Models\\Participant',
          'user' => 'App\\Models\\User',
          'authormatcher' => 'App\\Support\\AuthorMatcher',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
          'request' => 'Illuminate\\Http\\Request',
          'mail' => 'Illuminate\\Support\\Facades\\Mail',
          'storage' => 'Illuminate\\Support\\Facades\\Storage',
          'str' => 'Illuminate\\Support\\Str',
          'view' => 'Illuminate\\View\\View',
        ),
         'className' => 'App\\Http\\Controllers\\Admin\\FormationController',
         'functionName' => 'currentEdition',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Http\\Controllers\\Admin',
           'uses' => 
          array (
            'extractformationfromdocuments' => 'App\\Actions\\ExtractFormationFromDocuments',
            'issueattestation' => 'App\\Actions\\IssueAttestation',
            'upsertparticipantforaccount' => 'App\\Actions\\UpsertParticipantForAccount',
            'aimanager' => 'App\\AI\\AiManager',
            'scopeseditionaccess' => 'App\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess',
            'controller' => 'App\\Http\\Controllers\\Controller',
            'campaignmail' => 'App\\Mail\\CampaignMail',
            'email' => 'App\\Models\\Email',
            'event' => 'App\\Models\\Event',
            'formation' => 'App\\Models\\Formation',
            'formationtrainer' => 'App\\Models\\FormationTrainer',
            'participant' => 'App\\Models\\Participant',
            'user' => 'App\\Models\\User',
            'authormatcher' => 'App\\Support\\AuthorMatcher',
            'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
            'redirectresponse' => 'Illuminate\\Http\\RedirectResponse',
            'request' => 'Illuminate\\Http\\Request',
            'mail' => 'Illuminate\\Support\\Facades\\Mail',
            'storage' => 'Illuminate\\Support\\Facades\\Storage',
            'str' => 'Illuminate\\Support\\Str',
            'view' => 'Illuminate\\View\\View',
          ),
           'className' => 'App\\Http\\Controllers\\Admin\\FormationController',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
    ),
    1 => 
    array (
      'C:\\Users\\TOURE\\Herd\\gesco\\app\\Http\\Controllers\\Admin\\FormationController.php' => '7d7b0757652645b64dc1a937a97fc63ef1bfdc0210f5c7760bc99695a39561d5',
      'C:\\Users\\TOURE\\Herd\\gesco\\app\\Http\\Controllers\\Admin\\Concerns\\ScopesEditionAccess.php' => 'bb97f8926e83bf7c5201fd6bbf6d98eb83767a0b87a4ba1e8b79b8c25934c56a',
    ),
  ),
));