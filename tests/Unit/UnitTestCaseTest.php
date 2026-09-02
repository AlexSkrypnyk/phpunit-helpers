<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Tests\Unit;

use AlexSkrypnyk\PhpunitHelpers\Tests\Fixtures\InfoMethodsTrait;
use AlexSkrypnyk\PhpunitHelpers\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UnitTestCase::class)]
final class UnitTestCaseTest extends UnitTestCase {

  use InfoMethodsTrait;

  public function testLocations(): void {
    $this->assertDirectoryExists(self::$workspace);
    $this->assertDirectoryExists(self::$repo);
    $this->assertDirectoryExists(self::$sut);
    $this->assertDirectoryExists(self::$tmp);
    $this->assertNotEmpty(self::$root);
    $this->assertStringEndsWith('/phpunit-helpers', self::$root);
    $this->assertNotNull(self::$fixtures);
    $this->assertDirectoryExists(self::$fixtures);
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

  public function testInfoExcludesMethodsContainingTest(): void {
    $info = $this->info();

    $this->assertStringNotContainsString('This is a test fixture info method that should not be included', $info);
    $this->assertStringNotContainsString('testFixtureInfo', $info);

    $this->assertStringNotContainsString('This method contains test in the middle and should be excluded', $info);
    $this->assertStringNotContainsString('contestableSituationInfo', $info);
  }

  public function testInfoIncludesMethodsNotContainingTest(): void {
    $info = $this->info();

    $this->assertStringContainsString('First info value', $info);
    $this->assertStringContainsString('42', $info);
    $this->assertStringContainsString('"one","two","three"', $info);
    $this->assertStringContainsString('This non-static info method should be included', $info);
  }

}
