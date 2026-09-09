<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Benchmarks;

/**
 * Subject carrying the protected members that ReflectionBench reaches.
 */
class ReflectionSubject {

  /**
   * Value read and written by the property benchmarks.
   */
  protected string $value = 'value';

  /**
   * Returns the argument unchanged.
   *
   * @param string $argument
   *   Value to return.
   *
   * @return string
   *   The unchanged argument.
   */
  protected function instanceMethod(string $argument): string {
    return $argument;
  }

  /**
   * Returns the argument unchanged without an instance.
   *
   * @param string $argument
   *   Value to return.
   *
   * @return string
   *   The unchanged argument.
   */
  protected static function staticMethod(string $argument): string {
    return $argument;
  }

}
