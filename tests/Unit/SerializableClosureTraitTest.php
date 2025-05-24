<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Tests\Unit;

use AlexSkrypnyk\PhpunitHelpers\Traits\SerializableClosureTrait;
use Laravel\SerializableClosure\SerializableClosure;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\TestCase;

#[CoversTrait(SerializableClosureTrait::class)]
class SerializableClosureTraitTest extends TestCase {

  use SerializableClosureTrait;

  public function testCwClosure(): void {
    $closure = function (): string {
      return 'test';
    };

    $actual = static::cw($closure);

    $this->assertInstanceOf(SerializableClosure::class, $actual);
    $this->assertEquals('test', $actual());
  }

  public function testCwSerializedClosure(): void {
    $closure = function (): string {
      return 'test';
    };

    $wrapper = static::cw($closure);
    $serialized = serialize($wrapper);
    $unserialized = unserialize($serialized);
    if (!$unserialized instanceof SerializableClosure) {
      throw new \RuntimeException('Failed to unserialize the closure.');
    }
    $actual = static::cu($unserialized);

    $this->assertInstanceOf(\Closure::class, $actual);
    $this->assertEquals('test', $actual());
  }

  public function fixtureCallable(): string {
    return 'test';
  }

  public function testCwCallable(): void {
    $actual = static::cw([$this, 'fixtureCallable']);

    $this->assertInstanceOf(SerializableClosure::class, $actual);
    $this->assertEquals('test', $actual());
  }

  public function testCwSerializedCallable(): void {
    $wrapper = static::cw([$this, 'fixtureCallable']);
    $serialized = serialize($wrapper);
    $unserialized = unserialize($serialized);
    if (!$unserialized instanceof SerializableClosure) {
      throw new \RuntimeException('Failed to unserialize the closure.');
    }
    $actual = static::cu($unserialized);

    $this->assertInstanceOf(\Closure::class, $actual);
    $this->assertEquals('test', $actual());
  }

}
