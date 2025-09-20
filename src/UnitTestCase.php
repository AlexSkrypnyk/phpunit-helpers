<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers;

use AlexSkrypnyk\PhpunitHelpers\Traits\LocationsTrait;
use AlexSkrypnyk\PhpunitHelpers\Traits\ReflectionTrait;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestStatus\Error;
use PHPUnit\Framework\TestStatus\Failure;

/**
 * Class UnitTestCase.
 *
 * Base class for unit tests.
 *
 * Use DEBUG=1 to prevent cleanup of the temp directories
 */
abstract class UnitTestCase extends TestCase {

  use ReflectionTrait;
  use LocationsTrait;

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
   * Collects and returns information from all methods in the class that end
   * with 'Info'.
   */
  public function info(): string {
    // Collect all methods of the class that end with 'Info'.
    $methods = array_values(array_filter(get_class_methods(static::class), fn($m): bool => !str_contains($m, 'test') && str_ends_with($m, 'Info')));

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
   * Suffix to be added to all assertion failure messages.
   *
   * This is to overcome the limitation of PHPUnit not allowing to alter
   * the message within the assertion Throwable in onNotSuccessfulTest().
   *
   * onNotSuccessfulTest() currently only allows to print to stdout/stderr
   * right after the test failure rather than collecting all information and
   * appending it to the failure message.
   */
  protected function assertionSuffix(): string {
    return $this->info();
  }

  /**
   * Check if the test is running in debug mode.
   */
  public static function isDebug(): bool {
    return getenv('DEBUG') || in_array('--debug', (array) ($_SERVER['argv'] ?? []));
  }

}
