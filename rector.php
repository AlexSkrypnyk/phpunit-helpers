<?php

/**
 * @file
 * Rector configuration.
 *
 * Usage:
 * ./vendor/bin/rector process .
 */

declare(strict_types=1);

use Rector\CodeQuality\Rector\ClassMethod\InlineArrayReturnAssignRector;
use Rector\CodeQuality\Rector\Empty_\SimplifyEmptyCheckOnEmptyArrayRector;
use Rector\CodingStyle\Rector\Catch_\CatchExceptionNameMatchingTypeRector;
use Rector\CodingStyle\Rector\ClassLike\NewlineBetweenClassLikeStmtsRector;
use Rector\CodingStyle\Rector\ClassMethod\NewlineBeforeNewAssignSetRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\If_\RemoveAlwaysTrueIfConditionRector;
use Rector\Php71\Rector\Assign\AssignArrayToStringRector;
use Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector;
use Rector\Privatization\Rector\Class_\FinalizeTestCaseClassRector;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
  ->withPaths([
    __DIR__ . '/**',
  ])
  ->withPhpVersion(PhpVersion::PHP_83)
  ->withPhpSets(php83: TRUE)
  ->withPreparedSets(
    deadCode: TRUE,
    codeQuality: TRUE,
    codingStyle: TRUE,
    typeDeclarations: TRUE,
    phpunitCodeQuality: TRUE,
  )
  ->withComposerBased(phpunit: TRUE)
  ->withSkip([
    // Infers an array from a string variable that is only ever concatenated,
    // which breaks the assignment it rewrites.
    AssignArrayToStringRector::class,
    // The test builds a partial mock of itself, which a final class forbids.
    FinalizeTestCaseClassRector::class => [
      __DIR__ . '/tests/Unit/LocationsTraitTest.php',
    ],
    // Rules added by Rector's rule sets.
    CatchExceptionNameMatchingTypeRector::class,
    ChangeSwitchToMatchRector::class,
    InlineArrayReturnAssignRector::class,
    NewlineBeforeNewAssignSetRector::class,
    NewlineBetweenClassLikeStmtsRector::class,
    RemoveAlwaysTrueIfConditionRector::class,
    SimplifyEmptyCheckOnEmptyArrayRector::class,
    // Dependencies.
    '*/vendor/*',
    '*/node_modules/*',
  ])
  ->withFileExtensions([
    'php',
    'inc',
  ])
  ->withImportNames(importNames: TRUE, importDocBlockNames: FALSE, importShortClasses: FALSE);
