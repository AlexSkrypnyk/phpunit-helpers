<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Benchmarks;

use AlexSkrypnyk\PhpunitHelpers\Traits\StringTrait;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;

/**
 * Benchmarks for prefixed string assertions.
 *
 * Every call scans the expected values twice before matching any of them,
 * once to count prefixes and once to resolve each one, so the cost grows
 * with the number of values rather than with the haystack alone. The
 * subjects separate the shortcut mode from the prefixed mode, and
 * case-insensitive matching from case-sensitive, because each lowercases
 * both operands on every comparison.
 */
class StringBench extends BenchmarkCase {

  use StringTrait;

  /**
   * Lines in the generated haystack.
   */
  protected const HAYSTACK_LINES = 200;

  /**
   * Haystack the subjects match against.
   */
  protected string $haystack = '';

  /**
   * Builds the haystack (not timed).
   */
  public function setUp(): void {
    $lines = [];

    for ($line = 0; $line < self::HAYSTACK_LINES; $line++) {
      $lines[] = sprintf('Line %d of generated output with a Needle and some trailing text.', $line);
    }

    $this->haystack = implode(PHP_EOL, $lines);
  }

  /**
   * Benchmarks the shortcut mode, where every value is a substring.
   */
  #[BeforeMethods('setUp')]
  #[Revs(500)]
  #[Warmup(2)]
  #[Iterations(10)]
  public function benchShortcutMode(): void {
    $this->assertStringContainsOrNot($this->haystack, ['Needle', 'generated output', 'trailing text']);
  }

  /**
   * Benchmarks the prefixed mode across the present and absent branches.
   */
  #[BeforeMethods('setUp')]
  #[Revs(500)]
  #[Warmup(2)]
  #[Iterations(10)]
  public function benchPrefixedMode(): void {
    $this->assertStringContainsOrNot($this->haystack, ['* Needle', '! Haystack', '- Line 0 of generated output']);
  }

  /**
   * Benchmarks case-sensitive matching, which skips lowercasing.
   */
  #[BeforeMethods('setUp')]
  #[Revs(500)]
  #[Warmup(2)]
  #[Iterations(10)]
  public function benchCaseSensitiveMode(): void {
    $this->assertStringContainsOrNot($this->haystack, ['* Needle', '! Haystack'], case_insensitive: FALSE);
  }

  /**
   * Benchmarks an exact match, which compares the haystack in full.
   */
  #[Revs(500)]
  #[Warmup(2)]
  #[Iterations(10)]
  public function benchExactMatch(): void {
    $this->assertStringContainsOrNot('exact haystack value', ['+ exact haystack value']);
  }

}
