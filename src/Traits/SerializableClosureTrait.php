<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Traits;

use Laravel\SerializableClosure\SerializableClosure;

/**
 * Trait SerializableClosureTrait.
 *
 * Provides a wrapper for closures that allows to use them as arguments in data
 * providers.
 *
 * The methods are deliberately named as short as possible to avoid long lines
 * in data providers:
 * - cw() stands for "closure wrap"
 * - cu() stands for "closure unwrap"
 *
 * @see https://github.com/sebastianbergmann/phpunit/issues/2739
 *
 * @mixin \PHPUnit\Framework\TestCase
 */
trait SerializableClosureTrait {

  /**
   * Wrap closure into serializable object.
   *
   * @param callable $callable
   *   The closure to wrap.
   *
   * @return mixed
   *   The serialized closure if it is a closure, otherwise the original
   *   callable.
   */
  public static function cw(callable $callable): mixed {
    if (!$callable instanceof \Closure) {
      throw new \InvalidArgumentException('The provided callable is not a closure.');
    }

    return new SerializableClosure($callable);
  }

  /**
   * Unwrap serialized closure back to a callable.
   *
   * @param mixed $serialized
   *   The serialized closure to unwrap.
   *
   * @return mixed
   *   The unwrapped closure if it is a serialized closure, otherwise the
   *   original value.
   */
  public static function cu(mixed $serialized): mixed {
    return $serialized instanceof SerializableClosure ? $serialized->getClosure() : $serialized;
  }

}
