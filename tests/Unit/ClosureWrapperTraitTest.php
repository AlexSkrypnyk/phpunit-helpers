<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Tests\Unit;

use AlexSkrypnyk\PhpunitHelpers\Traits\SerializableClosureTrait;
use Laravel\SerializableClosure\SerializableClosure;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ClosureWrapperTrait.
 */
#[CoversClass(SerializableClosureTrait::class)]
class ClosureWrapperTraitTest extends TestCase {

  use SerializableClosureTrait;

  public function testCw(): void {
    $closure = function (): string {
      return 'test';
    };

    $result = $this->cw($closure);

    $this->assertInstanceOf(SerializableClosure::class, $result);
    $this->assertEquals('test', $result());
  }

  public function testCuWithSerializableClosure(): void {
    $closure = static function (): string {
      return 'test';
    };

    $wrapped = $this->cw($closure);
    $unwrapped = $this->cu($wrapped);

    $this->assertTrue(is_callable($unwrapped));
    $this->assertEquals('test', $unwrapped());
  }

  public function testCuWithRegularClosure(): void {
    $closure = fn(): string => 'test';

    $unwrapped = $this->cu($closure);

    $this->assertSame($closure, $unwrapped);
    $this->assertEquals('test', $unwrapped());
  }

  public function testCuWithNull(): void {
    $this->assertNull($this->cu(NULL));
  }

  public function testCwWithNonClosureCallable(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('The provided callable is not a closure.');
    $this->cw('trim');
  }

  public function testSerializationOfWrappedClosure(): void {
    $closure = function (string $input): string {
      return strtoupper($input);
    };

    $wrapped = $this->cw($closure);
    $serialized = serialize($wrapped);
    $unserialized = unserialize($serialized);

    $this->assertInstanceOf(SerializableClosure::class, $unserialized);
    $this->assertEquals('TEST', $unserialized('test'));
  }

}
