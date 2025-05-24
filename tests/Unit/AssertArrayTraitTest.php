<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Tests\Unit;

use AlexSkrypnyk\PhpunitHelpers\Traits\AssertArrayTrait;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\TestCase;

#[CoversTrait(AssertArrayTrait::class)]
class AssertArrayTraitTest extends TestCase {

  use AssertArrayTrait;

  public function testAssertArrayContainsStringFound(): void {
    $haystack = ['foo', 'bar', 'baz'];
    $needle = 'ba';

    $this->assertArrayContainsString($needle, $haystack);
  }

  public function testAssertArrayContainsStringNotFound(): void {
    $haystack = ['foo', 'bar', 'baz'];
    $needle = 'xyz';

    $this->expectException(AssertionFailedError::class);
    $this->expectExceptionMessage('Failed asserting that string "xyz" is present in array');

    $this->assertArrayContainsString($needle, $haystack);
  }

  public function testAssertArrayContainsStringWithMixedTypes(): void {
    $haystack = ['foo', 123, NULL, FALSE, new \stdClass()];
    $needle = '12';

    $this->assertArrayContainsString($needle, $haystack);
  }

  public function testAssertArrayContainsStringWithEmptyArray(): void {
    $haystack = [];
    $needle = 'foo';

    $this->expectException(AssertionFailedError::class);
    $this->expectExceptionMessage('Failed asserting that string "foo" is present in array');

    $this->assertArrayContainsString($needle, $haystack);
  }

}
