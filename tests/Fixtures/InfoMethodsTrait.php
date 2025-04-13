<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Tests\Fixtures;

/**
 * Trait InfoMethodsTrait.
 *
 * A fixture trait to test the UnitTestBase info() method.
 */
trait InfoMethodsTrait {

  /**
   * Info method that returns a string.
   */
  public static function firstInfo(): string {
    return 'First info value';
  }

  /**
   * Info method that returns an integer.
   */
  protected static function secondInfo(): int {
    return 42;
  }

  /**
   * Info method that returns an array.
   */
  public static function thirdInfo(): string {
    return (string) json_encode(['one', 'two', 'three']);
  }

  /**
   * Method that does not end with 'Info' and should not be called.
   */
  public static function notAnInfoMethod(): string {
    return 'This should not be included';
  }

  /**
   * Non-static method that ends with 'Info' but should not be called.
   */
  public function instanceInfo(): string {
    return 'This non-static method should not be included';
  }

  public function testFixtureInfo(): string {
    $this->addToAssertionCount(1);
    return 'This is a test fixture info method that should not be included';
  }

}
