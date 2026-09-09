<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Benchmarks;

use AlexSkrypnyk\PhpunitHelpers\Traits\AssertArrayTrait;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;

/**
 * Benchmarks for array assertions.
 *
 * The string subjects scan until they match, so a value at the end of the
 * haystack and a value that is absent both cost a full pass, while a value
 * at the front returns immediately. The sub-array subjects separate a value
 * found among the top-level elements from one reachable only by recursion,
 * which descends into every nested element and discards a thrown exception
 * for each one that does not hold the value.
 */
class AssertArrayBench extends BenchmarkCase {

  use AssertArrayTrait;

  /**
   * Elements in the generated haystacks.
   */
  protected const HAYSTACK_SIZE = 100;

  /**
   * Flat haystack of strings.
   *
   * @var array<int, string>
   */
  protected array $flat = [];

  /**
   * Haystack whose elements are themselves arrays.
   *
   * @var array<int, array<string, array<string, string>>>
   */
  protected array $nested = [];

  /**
   * Builds the haystacks (not timed).
   */
  public function setUp(): void {
    $this->flat = [];
    $this->nested = [];

    for ($index = 0; $index < self::HAYSTACK_SIZE; $index++) {
      $this->flat[] = sprintf('item_%d', $index);
      $this->nested[] = ['branch' => [sprintf('key_%d', $index) => sprintf('value_%d', $index)]];
    }
  }

  /**
   * Benchmarks a string found in the first element.
   */
  #[BeforeMethods('setUp')]
  #[Revs(200)]
  #[Warmup(2)]
  #[Iterations(10)]
  public function benchContainsStringFirstElement(): void {
    $this->assertArrayContainsString('item_0', $this->flat);
  }

  /**
   * Benchmarks a string found in the last element.
   */
  #[BeforeMethods('setUp')]
  #[Revs(200)]
  #[Warmup(2)]
  #[Iterations(10)]
  public function benchContainsStringLastElement(): void {
    $this->assertArrayContainsString(sprintf('item_%d', self::HAYSTACK_SIZE - 1), $this->flat);
  }

  /**
   * Benchmarks an absent string, which scans every element.
   */
  #[BeforeMethods('setUp')]
  #[Revs(200)]
  #[Warmup(2)]
  #[Iterations(10)]
  public function benchNotContainsString(): void {
    $this->assertArrayNotContainsString('absent', $this->flat);
  }

  /**
   * Benchmarks a sub-array reachable only by recursing into the elements.
   */
  #[BeforeMethods('setUp')]
  #[Revs(200)]
  #[Warmup(2)]
  #[Iterations(10)]
  public function benchContainsArrayNested(): void {
    $target = [sprintf('key_%d', self::HAYSTACK_SIZE - 1) => sprintf('value_%d', self::HAYSTACK_SIZE - 1)];

    $this->assertArrayContainsArray($this->nested, [$target]);
  }

  /**
   * Benchmarks scalar values matched against the top-level elements.
   */
  #[BeforeMethods('setUp')]
  #[Revs(200)]
  #[Warmup(2)]
  #[Iterations(10)]
  public function benchContainsArrayScalars(): void {
    $this->assertArrayContainsArray($this->flat, ['item_0', 'item_50', 'item_99']);
  }

}
