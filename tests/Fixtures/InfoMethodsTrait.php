<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Tests\Fixtures;

trait InfoMethodsTrait {

  public static function firstInfo(): string {
    return 'First info value';
  }

  protected static function secondInfo(): int {
    return 42;
  }

  public static function thirdInfo(): string {
    return (string) json_encode(['one', 'two', 'three']);
  }

  public static function notAnInfoMethod(): string {
    return 'This should not be included';
  }

  public function instanceInfo(): string {
    return 'This non-static info method should be included';
  }

  public function testFixtureInfo(): string {
    $this->addToAssertionCount(1);
    return 'This is a test fixture info method that should not be included';
  }

}
