<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Tests\Unit;

use AlexSkrypnyk\PhpunitHelpers\Tests\Fixtures\InfoMethodsTrait;
use AlexSkrypnyk\PhpunitHelpers\UnitTestBase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for UnitTestBase.
 */
#[CoversClass(UnitTestBase::class)]
class UnitTestBaseTest extends UnitTestBase {

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
    $info = self::info();

    $this->assertStringContainsString('Additional information:', $info);
    $this->assertStringContainsString('First info value', $info);
    $this->assertStringContainsString('42', $info);
    $this->assertStringContainsString('"one","two","three"', $info);

    $this->assertStringNotContainsString('This should not be included', $info);
    $this->assertStringNotContainsString('This non-static method should not be included', $info);
    $this->assertStringNotContainsString('This is a test fixture info method that should not be included', $info);
  }

}
