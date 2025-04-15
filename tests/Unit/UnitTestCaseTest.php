<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Tests\Unit;

use AlexSkrypnyk\PhpunitHelpers\Tests\Fixtures\InfoMethodsTrait;
use AlexSkrypnyk\PhpunitHelpers\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for UnitTestCase.
 */
#[CoversClass(UnitTestCase::class)]
class UnitTestCaseTest extends UnitTestCase {

  use InfoMethodsTrait;

  public function testLocations(): void {
    $this->assertDirectoryExists(static::$workspace);
    $this->assertDirectoryExists(static::$repo);
    $this->assertDirectoryExists(static::$sut);
    $this->assertDirectoryExists(static::$tmp);
    $this->assertNotEmpty(static::$root);
    $this->assertStringEndsWith('/phpunit-helpers', static::$root);
    $this->assertNotNull(static::$fixtures);
    $this->assertDirectoryExists(static::$fixtures);
  }

  public function testInfo(): void {
    $info = $this->info();

    $this->assertStringContainsString('Additional information:', $info);
    $this->assertStringContainsString('First info value', $info);
    $this->assertStringContainsString('42', $info);
    $this->assertStringContainsString('"one","two","three"', $info);
    $this->assertStringContainsString('This non-static info method should be included', $info);

    $this->assertStringNotContainsString('This should not be included', $info);
    $this->assertStringNotContainsString('This is a test fixture info method that should not be included', $info);
  }

}
