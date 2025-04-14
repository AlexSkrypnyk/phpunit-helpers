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
    self::locationsInit();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    if (!$this->status() instanceof Failure && !$this->status() instanceof Error && !static::isDebug()) {
      self::locationsTearDown();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function onNotSuccessfulTest(\Throwable $t): never {
    // @codeCoverageIgnoreStart
    fwrite(STDERR, PHP_EOL . PHP_EOL . 'Error: ' . $t->getMessage() . PHP_EOL);
    fwrite(STDERR, static::info());
    parent::onNotSuccessfulTest($t);
    // @codeCoverageIgnoreEnd
  }

  /**
   * Additional information about the test.
   */
  public static function info(): string {
    // Collect all methods of the class that end with 'Info'.
    $methods = array_filter(get_class_methods(static::class), fn($m): bool => !str_starts_with($m, 'test') && str_ends_with($m, 'Info'));
    $info = implode(PHP_EOL, array_map(fn($method): mixed => is_callable([static::class, $method]) ? static::{$method}() : '', $methods)) . PHP_EOL;

    $lines = [];
    if (!empty(trim($info))) {
      $lines[] = PHP_EOL . '-----------------------' . PHP_EOL;
      $lines[] = 'Additional information:' . PHP_EOL . PHP_EOL;
      $lines[] = $info;
      $lines[] = '-----------------------' . PHP_EOL;
    }

    return implode(PHP_EOL, $lines);
  }

  /**
   * Check if the test is running in debug mode.
   */
  public static function isDebug(): bool {
    return getenv('DEBUG') || in_array('--debug', (array) ($_SERVER['argv'] ?? []));
  }

}
