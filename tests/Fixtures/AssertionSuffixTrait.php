<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Tests\Fixtures;

use PHPUnit\Framework\TestCase;

trait AssertionSuffixTrait {

  /**
   * Whether the runtime appends the assertion suffix to failure messages.
   *
   * @return bool
   *   TRUE when PHPUnit provides the hook that UnitTestCase appends from.
   */
  protected static function supportsAssertionSuffix(): bool {
    return (new \ReflectionClass(TestCase::class))->hasMethod('invokeTestMethod');
  }

}
