<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers;

use AlexSkrypnyk\PhpunitHelpers\Traits\LocationsTrait;
use AlexSkrypnyk\PhpunitHelpers\Traits\ReflectionTrait;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestStatus\Error;
use PHPUnit\Framework\TestStatus\Failure;

/**
 * Base class for unit tests.
 *
 * DEBUG=1 prevents cleanup of the temp directories.
 */
abstract class UnitTestCase extends TestCase {

  use LocationsTrait;
  use ReflectionTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    static::locationsInit();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    if ($this->tearDownShouldCleanup()) {
      static::locationsTearDown();
    }
  }

  /**
   * Determine if tearDown should clean up the temporary directories.
   *
   * @return bool
   *   TRUE if the temporary directories should be cleaned up.
   */
  protected function tearDownShouldCleanup(): bool {
    return !$this->status() instanceof Failure && !$this->status() instanceof Error && !static::isDebug();
  }

  /**
   * {@inheritdoc}
   */
  protected function onNotSuccessfulTest(\Throwable $t): never {
    // @codeCoverageIgnoreStart
    if (static::isDebug()) {
      fwrite(STDERR, PHP_EOL . PHP_EOL . 'Error: ' . $t->getMessage() . PHP_EOL);
    }
    parent::onNotSuccessfulTest($t);
    // @codeCoverageIgnoreEnd
  }

  /**
   * Additional information about the test.
   *
   * Collects and returns information from methods in the class whose name ends
   * with 'Info' and does not contain 'test'.
   *
   * @return string
   *   The additional information.
   */
  public function info(): string {
    $methods = array_values(array_filter(get_class_methods(static::class), fn(string $m): bool => !str_contains($m, 'test') && str_ends_with($m, 'Info')));

    $info = '';
    foreach ($methods as $key => $method) {
      $reflection = new \ReflectionMethod(static::class, $method);
      if ($reflection->isStatic()) {
        $info .= static::{$method}() . PHP_EOL;
      }
      else {
        $info .= $this->{$method}() . PHP_EOL;
      }

      if ($key < count($methods) - 1) {
        $info .= '----------------------------------------------' . PHP_EOL . PHP_EOL;
      }
    }

    $lines = [];
    if (!empty(trim($info))) {
      $lines[] = PHP_EOL . '==============================================' . PHP_EOL;
      $lines[] = 'Additional information:' . PHP_EOL . PHP_EOL;
      $lines[] = $info;
      $lines[] = '==============================================' . PHP_EOL;
    }

    return implode(PHP_EOL, $lines);
  }

  /**
   * Suffix appended to every assertion failure message.
   *
   * PHPUnit 11 exposes no hook that can reach the message, so nothing is
   * appended there.
   *
   * @return string
   *   The assertion suffix.
   */
  protected function assertionSuffix(): string {
    return $this->info();
  }

  /**
   * {@inheritdoc}
   */
  protected function invokeTestMethod(string $methodName, array $testArguments): mixed {
    try {
      return parent::invokeTestMethod($methodName, $testArguments);
    }
    catch (AssertionFailedError $exception) {
      // runBare() is final and emits the failure event from its own catch
      // block, so onNotSuccessfulTest() runs too late to change the reported
      // message. This hook is called from inside that block, and PHPUnit 11
      // has no equivalent, so it never calls this method there.
      $suffix = $this->assertionSuffix();

      if ($suffix !== '') {
        // Mutating the caught Throwable keeps its stack trace, so the failure
        // still reports the assertion line rather than this method.
        $property = new \ReflectionProperty(\Exception::class, 'message');
        $property->setValue($exception, $exception->getMessage() . $suffix);
      }

      throw $exception;
    }
  }

  /**
   * Check if the test is running in debug mode.
   *
   * @return bool
   *   TRUE if debug mode is enabled, FALSE otherwise.
   */
  public static function isDebug(): bool {
    return getenv('DEBUG') || in_array('--debug', (array) ($_SERVER['argv'] ?? []));
  }

}
