<?php declare(strict_types = 1);

// osfsl-C:/Users/TOURE/Herd/gesco/vendor/composer/../spatie/laravel-backup/src/Commands/BackupCommand.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Spatie\Backup\Commands\BackupCommand
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-4123f93f958a27d719f973b233d00d9ed2e9397674816dbe01e0087f673b4f47-8.4.24-6.70.0.3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Spatie\\Backup\\Commands\\BackupCommand',
        'filename' => 'C:/Users/TOURE/Herd/gesco/vendor/composer/../spatie/laravel-backup/src/Commands/BackupCommand.php',
      ),
    ),
    'namespace' => 'Spatie\\Backup\\Commands',
    'name' => 'Spatie\\Backup\\Commands\\BackupCommand',
    'shortName' => 'BackupCommand',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 15,
    'endLine' => 141,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Spatie\\Backup\\Commands\\BaseCommand',
    'implementsClassNames' => 
    array (
      0 => 'Illuminate\\Contracts\\Console\\Isolatable',
    ),
    'traitClassNames' => 
    array (
      0 => 'Spatie\\Backup\\Traits\\Retryable',
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'signature' => 
      array (
        'declaringClassName' => 'Spatie\\Backup\\Commands\\BackupCommand',
        'implementingClassName' => 'Spatie\\Backup\\Commands\\BackupCommand',
        'name' => 'signature',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'backup:run
        {--filename=}
        {--filename-suffix=}
        {--only-db}
        {--db-name=*}
        {--only-files}
        {--only-to-disk=}
        {--exclude=* : Directories or files to exclude from backup}
        {--destination-path= : Override the backup destination path}
        {--disable-notifications}
        {--timeout=}
        {--tries=}
        {--config=}\'',
          'attributes' => 
          array (
            'startLine' => 19,
            'endLine' => 31,
            'startTokenPos' => 77,
            'startFilePos' => 508,
            'endTokenPos' => 77,
            'endFilePos' => 892,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 19,
        'endLine' => 31,
        'startColumn' => 5,
        'endColumn' => 21,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'description' => 
      array (
        'declaringClassName' => 'Spatie\\Backup\\Commands\\BackupCommand',
        'implementingClassName' => 'Spatie\\Backup\\Commands\\BackupCommand',
        'name' => 'description',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'Run the backup.\'',
          'attributes' => 
          array (
            'startLine' => 33,
            'endLine' => 33,
            'startTokenPos' => 86,
            'startFilePos' => 925,
            'endTokenPos' => 86,
            'endFilePos' => 941,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 33,
        'endLine' => 33,
        'startColumn' => 5,
        'endColumn' => 47,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'config' => 
      array (
        'declaringClassName' => 'Spatie\\Backup\\Commands\\BackupCommand',
        'implementingClassName' => 'Spatie\\Backup\\Commands\\BackupCommand',
        'name' => 'config',
        'modifiers' => 2,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Spatie\\Backup\\Config\\Config',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 35,
        'endLine' => 35,
        'startColumn' => 33,
        'endColumn' => 56,
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
          'config' => 
          array (
            'name' => 'config',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Spatie\\Backup\\Config\\Config',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 35,
            'endLine' => 35,
            'startColumn' => 33,
            'endColumn' => 56,
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
        'startLine' => 35,
        'endLine' => 38,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Spatie\\Backup\\Commands',
        'declaringClassName' => 'Spatie\\Backup\\Commands\\BackupCommand',
        'implementingClassName' => 'Spatie\\Backup\\Commands\\BackupCommand',
        'currentClassName' => 'Spatie\\Backup\\Commands\\BackupCommand',
        'aliasName' => NULL,
      ),
      'handle' => 
      array (
        'name' => 'handle',
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
        'docComment' => NULL,
        'startLine' => 40,
        'endLine' => 127,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Spatie\\Backup\\Commands',
        'declaringClassName' => 'Spatie\\Backup\\Commands\\BackupCommand',
        'implementingClassName' => 'Spatie\\Backup\\Commands\\BackupCommand',
        'currentClassName' => 'Spatie\\Backup\\Commands\\BackupCommand',
        'aliasName' => NULL,
      ),
      'guardAgainstInvalidOptions' => 
      array (
        'name' => 'guardAgainstInvalidOptions',
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
        'docComment' => NULL,
        'startLine' => 129,
        'endLine' => 140,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Spatie\\Backup\\Commands',
        'declaringClassName' => 'Spatie\\Backup\\Commands\\BackupCommand',
        'implementingClassName' => 'Spatie\\Backup\\Commands\\BackupCommand',
        'currentClassName' => 'Spatie\\Backup\\Commands\\BackupCommand',
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