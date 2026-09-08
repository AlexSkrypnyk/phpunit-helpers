<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Benchmarks;

use AlexSkrypnyk\PhpunitHelpers\Traits\ReflectionTrait;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;

/**
 * Benchmarks for reflection access to protected members.
 *
 * Every method in the trait builds a ReflectionClass and discards it, so a
 * consuming suite pays that construction once per access rather than once
 * per class. The subjects separate method invocation from property access,
 * and an instance call from a static one, so a change to the shared lookup
 * is visible apart from the invocation wrapping it.
 */
class ReflectionBench {

  use ReflectionTrait;

  /**
   * Argument passed to the invoked methods.
   */
  protected const ARGUMENT = 'argument';

  /**
   * Object the subjects reflect over.
   */
  protected ReflectionSubject $subject;

  /**
   * Creates the reflected object (not timed).
   */
  public function setUp(): void {
    $this->subject = new ReflectionSubject();
  }

  /**
   * Benchmarks invoking a protected instance method.
   */
  #[BeforeMethods('setUp')]
  #[Revs(1000)]
  #[Warmup(2)]
  #[Iterations(10)]
  public function benchCallProtectedInstanceMethod(): void {
    static::callProtectedMethod($this->subject, 'instanceMethod', [self::ARGUMENT]);
  }

  /**
   * Benchmarks invoking a protected static method by class name.
   */
  #[Revs(1000)]
  #[Warmup(2)]
  #[Iterations(10)]
  public function benchCallProtectedStaticMethod(): void {
    static::callProtectedMethod(ReflectionSubject::class, 'staticMethod', [self::ARGUMENT]);
  }

  /**
   * Benchmarks reading a protected property.
   */
  #[BeforeMethods('setUp')]
  #[Revs(1000)]
  #[Warmup(2)]
  #[Iterations(10)]
  public function benchGetProtectedValue(): void {
    static::getProtectedValue($this->subject, 'value');
  }

  /**
   * Benchmarks writing a protected property.
   */
  #[BeforeMethods('setUp')]
  #[Revs(1000)]
  #[Warmup(2)]
  #[Iterations(10)]
  public function benchSetProtectedValue(): void {
    static::setProtectedValue($this->subject, 'value', self::ARGUMENT);
  }

}
