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
   * @return \Laravel\SerializableClosure\SerializableClosure
   *   The serialized closure class instance.
   */
  public static function cw(callable $callable): SerializableClosure {
    if (!$callable instanceof \Closure) {
      $callable = \Closure::fromCallable($callable);
    }

    return new SerializableClosure($callable);
  }

  /**
   * Unwrap serialized closure.
   *
   * @param \Laravel\SerializableClosure\SerializableClosure $serialized
   *   The serialized closure to unwrap.
   *
   * @return \Closure
   *   The unwrapped closure.
   */
  public static function cu(SerializableClosure $serialized): \Closure {
    return $serialized->getClosure();
  }

}
