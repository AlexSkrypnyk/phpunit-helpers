<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Benchmarks;

use PHPUnit\Framework\Assert;

/**
 * Base class for benchmarks that measure the assertion traits.
 *
 * The traits resolve their assertions against $this. TestCase declares
 * those, but its constructor is final and requires a test name, which
 * PHPBench cannot supply when it instantiates a benchmark. Assert carries
 * the assertions without that constructor, and addToAssertionCount() is
 * declared here because only TestCase defines it.
 */
abstract class BenchmarkCase extends Assert {

  /**
   * Assertions recorded by the traits under measurement.
   */
  protected int $benchmarkAssertions = 0;

  /**
   * Records assertions made by the traits.
   *
   * @param int $count
   *   Number of assertions to record.
   */
  public function addToAssertionCount(int $count): void {
    $this->benchmarkAssertions += $count;
  }

}
