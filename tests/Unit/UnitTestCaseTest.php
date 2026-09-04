<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Tests\Unit;

use AlexSkrypnyk\PhpunitHelpers\Tests\Fixtures\InfoMethodsTrait;
use AlexSkrypnyk\PhpunitHelpers\UnitTestCase;
use PHPUnit\Framework\AssertionFailedError;
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

  public function testInvokeTestMethodAppendsSuffixToFailure(): void {
    $message = '';

    try {
      $this->invokeTestMethod('fixtureFailingAssertion', []);
    }
    catch (AssertionFailedError $assertion_failed_error) {
      $message = $assertion_failed_error->getMessage();
    }

    $this->assertStringContainsString('Failed asserting', $message);
    $this->assertStringContainsString('Additional information:', $message);
    $this->assertStringContainsString('First info value', $message);
  }

  public function testInvokeTestMethodLeavesPassingMethodAlone(): void {
    $this->assertNull($this->invokeTestMethod('fixturePassingAssertion', []));
  }

  /**
   * Fails an assertion so the suffix path runs.
   */
  public function fixtureFailingAssertion(): void {
    $this->assertSame('expected', 'actual');
  }

  /**
   * Returns without failing so the suffix path is skipped.
   */
  public function fixturePassingAssertion(): void {
    $this->addToAssertionCount(1);
  }

}
