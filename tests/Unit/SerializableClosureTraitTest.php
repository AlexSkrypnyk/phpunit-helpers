<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Tests\Unit;

use AlexSkrypnyk\PhpunitHelpers\Traits\SerializableClosureTrait;
use Laravel\SerializableClosure\SerializableClosure;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\TestCase;

#[CoversTrait(SerializableClosureTrait::class)]
final class SerializableClosureTraitTest extends TestCase {

  use SerializableClosureTrait;

  public function testCwClosure(): void {
    $closure = (fn(): string => 'test');

    $actual = self::cw($closure);

    $this->assertInstanceOf(SerializableClosure::class, $actual);
    $this->assertSame('test', $actual());
  }

  public function testCwSerializedClosure(): void {
    $closure = (fn(): string => 'test');

    $wrapper = self::cw($closure);
    $serialized = serialize($wrapper);
    $unserialized = unserialize($serialized);
    if (!$unserialized instanceof SerializableClosure) {
      throw new \RuntimeException('Failed to unserialize the closure.');
    }
    $actual = self::cu($unserialized);

    $this->assertInstanceOf(\Closure::class, $actual);
    $this->assertSame('test', $actual());
  }

  public function testCwCallable(): void {
    $actual = self::cw($this->fixtureCallable(...));

    $this->assertInstanceOf(SerializableClosure::class, $actual);
    $this->assertSame('test', $actual());
  }

  public function testCwSerializedCallable(): void {
    $wrapper = self::cw($this->fixtureCallable(...));
    $serialized = serialize($wrapper);
    $unserialized = unserialize($serialized);
    if (!$unserialized instanceof SerializableClosure) {
      throw new \RuntimeException('Failed to unserialize the closure.');
    }
    $actual = self::cu($unserialized);

    $this->assertInstanceOf(\Closure::class, $actual);
    $this->assertSame('test', $actual());
  }

  public function fixtureCallable(): string {
    return 'test';
  }

}
