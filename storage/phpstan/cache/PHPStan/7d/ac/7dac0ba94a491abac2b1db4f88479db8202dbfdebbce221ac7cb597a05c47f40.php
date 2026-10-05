<?php declare(strict_types = 1);

// odsl-C:\Users\TOURE\Herd\gesco\app\Documents\LivretTemplate.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Documents\LivretTemplate
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.4.24-d7b7addcc6646ad864360fe7e34fb4f3b4c48720859cd94b4e5932d819291851',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Documents\\LivretTemplate',
        'filename' => 'C:/Users/TOURE/Herd/gesco/app/Documents/LivretTemplate.php',
      ),
    ),
    'namespace' => 'App\\Documents',
    'name' => 'App\\Documents\\LivretTemplate',
    'shortName' => 'LivretTemplate',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Génération du livre des résumés À PARTIR D\'UN MODÈLE .docx téléversé (logique du
 * projet source uk-colloques, `ukc_bt_render`) : l\'administrateur personnalise la
 * couverture, la charte et les pages liminaires dans Word en conservant des balises
 * entre doubles crochets ; à la génération, on ouvre le modèle et on remplace :
 *  - les balises « bloc » seules sur leur ligne ([[AVANT_PROPOS]], [[COMITES]],
 *    [[SOMMAIRE]], [[PROGRAMME]], [[RESUMES]]) par le fragment OOXML généré ;
 *  - les balises « en ligne » ([[TITRE]], [[EDITION]], [[THEME]], [[DATES]],
 *    [[LIEU]], [[ANNEE]], [[NB_RESUMES]]) par leur valeur.
 * Le design du modèle est conservé ; le contenu vient de {@see LivretDocx}.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 26,
    'endLine' => 288,
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
      'BLOCK_TAGS' => 
      array (
        'declaringClassName' => 'App\\Documents\\LivretTemplate',
        'implementingClassName' => 'App\\Documents\\LivretTemplate',
        'name' => 'BLOCK_TAGS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'[[AVANT_PROPOS]]\' => \'AVANT_PROPOS\', \'[[COMITES]]\' => \'COMITES\', \'[[SOMMAIRE]]\' => \'SOMMAIRE\', \'[[PROGRAMME]]\' => \'PROGRAMME\', \'[[RESUMES]]\' => \'RESUMES\']',
          'attributes' => 
          array (
            'startLine' => 29,
            'endLine' => 35,
            'startTokenPos' => 70,
            'startFilePos' => 1233,
            'endTokenPos' => 107,
            'endFilePos' => 1435,
          ),
        ),
        'docComment' => '/** Balise bloc (texte seul du paragraphe) => nom de bloc pour LivretDocx::blockXml(). */',
        'attributes' => 
        array (
        ),
        'startLine' => 29,
        'endLine' => 35,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
    ),
    'immediateProperties' => 
    array (
      'event' => 
      array (
        'declaringClassName' => 'App\\Documents\\LivretTemplate',
        'implementingClassName' => 'App\\Documents\\LivretTemplate',
        'name' => 'event',
        'modifiers' => 132,
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
        'startLine' => 41,
        'endLine' => 41,
        'startColumn' => 9,
        'endColumn' => 37,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'groups' => 
      array (
        'declaringClassName' => 'App\\Documents\\LivretTemplate',
        'implementingClassName' => 'App\\Documents\\LivretTemplate',
        'name' => 'groups',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Support\\Collection',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 42,
        'endLine' => 42,
        'startColumn' => 9,
        'endColumn' => 43,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'templatePath' => 
      array (
        'declaringClassName' => 'App\\Documents\\LivretTemplate',
        'implementingClassName' => 'App\\Documents\\LivretTemplate',
        'name' => 'templatePath',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 43,
        'endLine' => 43,
        'startColumn' => 9,
        'endColumn' => 45,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'harmonize' => 
      array (
        'declaringClassName' => 'App\\Documents\\LivretTemplate',
        'implementingClassName' => 'App\\Documents\\LivretTemplate',
        'name' => 'harmonize',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => 'false',
          'attributes' => 
          array (
            'startLine' => 44,
            'endLine' => 44,
            'startTokenPos' => 156,
            'startFilePos' => 1768,
            'endTokenPos' => 156,
            'endFilePos' => 1772,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 44,
        'endLine' => 44,
        'startColumn' => 9,
        'endColumn' => 48,
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
            'startLine' => 41,
            'endLine' => 41,
            'startColumn' => 9,
            'endColumn' => 37,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'groups' => 
          array (
            'name' => 'groups',
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 42,
            'endLine' => 42,
            'startColumn' => 9,
            'endColumn' => 43,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'templatePath' => 
          array (
            'name' => 'templatePath',
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 43,
            'endLine' => 43,
            'startColumn' => 9,
            'endColumn' => 45,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'harmonize' => 
          array (
            'name' => 'harmonize',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 44,
                'endLine' => 44,
                'startTokenPos' => 156,
                'startFilePos' => 1768,
                'endTokenPos' => 156,
                'endFilePos' => 1772,
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 44,
            'endLine' => 44,
            'startColumn' => 9,
            'endColumn' => 48,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param  Collection<int|string, \\Illuminate\\Database\\Eloquent\\Collection<int, Submission>>  $groups
 */',
        'startLine' => 40,
        'endLine' => 45,
        'startColumn' => 5,
        'endColumn' => 8,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LivretTemplate',
        'implementingClassName' => 'App\\Documents\\LivretTemplate',
        'currentClassName' => 'App\\Documents\\LivretTemplate',
        'aliasName' => NULL,
      ),
      'download' => 
      array (
        'name' => 'download',
        'parameters' => 
        array (
          'filename' => 
          array (
            'name' => 'filename',
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
            'startLine' => 48,
            'endLine' => 48,
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
            'name' => 'Symfony\\Component\\HttpFoundation\\Response',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Réponse HTTP de téléchargement du .docx fusionné. */',
        'startLine' => 48,
        'endLine' => 57,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LivretTemplate',
        'implementingClassName' => 'App\\Documents\\LivretTemplate',
        'currentClassName' => 'App\\Documents\\LivretTemplate',
        'aliasName' => NULL,
      ),
      'render' => 
      array (
        'name' => 'render',
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
        'docComment' => '/** Fusionne le contenu généré dans le modèle et renvoie le binaire .docx. */',
        'startLine' => 60,
        'endLine' => 147,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LivretTemplate',
        'implementingClassName' => 'App\\Documents\\LivretTemplate',
        'currentClassName' => 'App\\Documents\\LivretTemplate',
        'aliasName' => NULL,
      ),
      'baseModel' => 
      array (
        'name' => 'baseModel',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PhpOffice\\PhpWord\\PhpWord',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Construit un modèle de base (.docx) à balises, à télécharger puis personnaliser. */',
        'startLine' => 150,
        'endLine' => 196,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LivretTemplate',
        'implementingClassName' => 'App\\Documents\\LivretTemplate',
        'currentClassName' => 'App\\Documents\\LivretTemplate',
        'aliasName' => NULL,
      ),
      'downloadBase' => 
      array (
        'name' => 'downloadBase',
        'parameters' => 
        array (
          'filename' => 
          array (
            'name' => 'filename',
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
            'startLine' => 199,
            'endLine' => 199,
            'startColumn' => 41,
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
            'name' => 'Symfony\\Component\\HttpFoundation\\Response',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Réponse de téléchargement du modèle de base. */',
        'startLine' => 199,
        'endLine' => 208,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LivretTemplate',
        'implementingClassName' => 'App\\Documents\\LivretTemplate',
        'currentClassName' => 'App\\Documents\\LivretTemplate',
        'aliasName' => NULL,
      ),
      'paragraphText' => 
      array (
        'name' => 'paragraphText',
        'parameters' => 
        array (
          'xp' => 
          array (
            'name' => 'xp',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'DOMXPath',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 211,
            'endLine' => 211,
            'startColumn' => 36,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'p' => 
          array (
            'name' => 'p',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'DOMElement',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 211,
            'endLine' => 211,
            'startColumn' => 51,
            'endColumn' => 64,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Texte concaténé des <w:t> d\'un paragraphe. */',
        'startLine' => 211,
        'endLine' => 219,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LivretTemplate',
        'implementingClassName' => 'App\\Documents\\LivretTemplate',
        'currentClassName' => 'App\\Documents\\LivretTemplate',
        'aliasName' => NULL,
      ),
      'importFragment' => 
      array (
        'name' => 'importFragment',
        'parameters' => 
        array (
          'dom' => 
          array (
            'name' => 'dom',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'DOMDocument',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 226,
            'endLine' => 226,
            'startColumn' => 37,
            'endColumn' => 53,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'xmlFrag' => 
          array (
            'name' => 'xmlFrag',
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
            'startLine' => 226,
            'endLine' => 226,
            'startColumn' => 56,
            'endColumn' => 70,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Importe un fragment OOXML (chaîne de <w:p>/<w:tbl>) en nœuds du document cible.
 *
 * @return list<\\DOMNode>
 */',
        'startLine' => 226,
        'endLine' => 245,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LivretTemplate',
        'implementingClassName' => 'App\\Documents\\LivretTemplate',
        'currentClassName' => 'App\\Documents\\LivretTemplate',
        'aliasName' => NULL,
      ),
      'ensureUpdateFields' => 
      array (
        'name' => 'ensureUpdateFields',
        'parameters' => 
        array (
          'files' => 
          array (
            'name' => 'files',
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
            'startLine' => 253,
            'endLine' => 253,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Garantit word/settings.xml avec updateFields (met à jour un éventuel TOC du modèle).
 *
 * @param  array<string, string>  $files
 * @return array<string, string>
 */',
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
        'declaringClassName' => 'App\\Documents\\LivretTemplate',
        'implementingClassName' => 'App\\Documents\\LivretTemplate',
        'currentClassName' => 'App\\Documents\\LivretTemplate',
        'aliasName' => NULL,
      ),
      'rezip' => 
      array (
        'name' => 'rezip',
        'parameters' => 
        array (
          'files' => 
          array (
            'name' => 'files',
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
            'startLine' => 274,
            'endLine' => 274,
            'startColumn' => 28,
            'endColumn' => 39,
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
 * Réécrit le .docx à partir des entrées en mémoire.
 *
 * @param  array<string, string>  $files
 */',
        'startLine' => 274,
        'endLine' => 287,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Documents',
        'declaringClassName' => 'App\\Documents\\LivretTemplate',
        'implementingClassName' => 'App\\Documents\\LivretTemplate',
        'currentClassName' => 'App\\Documents\\LivretTemplate',
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