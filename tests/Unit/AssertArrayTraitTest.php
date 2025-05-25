<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Tests\Unit;

use AlexSkrypnyk\PhpunitHelpers\Traits\AssertArrayTrait;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversTrait(AssertArrayTrait::class)]
class AssertArrayTraitTest extends TestCase {

  use AssertArrayTrait;

  public function testAssertArrayContainsStringFound(): void {
    $haystack = ['foo', 'bar', 'baz'];
    $needle = 'ba';

    $this->assertArrayContainsString($needle, $haystack);
  }

  public function testAssertArrayContainsStringNotFound(): void {
    $haystack = ['foo', 'bar', 'baz'];
    $needle = 'xyz';

    $this->expectException(AssertionFailedError::class);
    $this->expectExceptionMessage('Failed asserting that string "xyz" is present in array');

    $this->assertArrayContainsString($needle, $haystack);
  }

  public function testAssertArrayContainsStringWithMixedTypes(): void {
    $haystack = ['foo', 123, NULL, FALSE, new \stdClass()];
    $needle = '12';

    $this->assertArrayContainsString($needle, $haystack);
  }

  public function testAssertArrayContainsStringWithEmptyArray(): void {
    $haystack = [];
    $needle = 'foo';

    $this->expectException(AssertionFailedError::class);
    $this->expectExceptionMessage('Failed asserting that string "foo" is present in array');

    $this->assertArrayContainsString($needle, $haystack);
  }

  public function testAssertArrayNotContainsStringNotFound(): void {
    $haystack = ['foo', 'bar', 'baz'];
    $needle = 'xyz';

    $this->assertArrayNotContainsString($needle, $haystack);
  }

  public function testAssertArrayNotContainsStringFound(): void {
    $haystack = ['foo', 'bar', 'baz'];
    $needle = 'ba';

    $this->expectException(AssertionFailedError::class);
    $this->expectExceptionMessage('Failed asserting that string "ba" is not present in array');

    $this->assertArrayNotContainsString($needle, $haystack);
  }

  public function testAssertArrayNotContainsStringWithMixedTypes(): void {
    $haystack = ['foo', 123, NULL, FALSE, new \stdClass()];
    $needle = 'xyz';

    $this->assertArrayNotContainsString($needle, $haystack);
  }

  public function testAssertArrayNotContainsStringWithEmptyArray(): void {
    $haystack = [];
    $needle = 'foo';

    $this->assertArrayNotContainsString($needle, $haystack);
  }

  /**
   * Test assertArrayContainsArray method.
   *
   * @param array<mixed> $array
   *   The main array to search in.
   * @param array<mixed> $sub_array
   *   The sub-array to search for.
   * @param bool $should_pass
   *   Whether the assertion should pass or fail.
   */
  #[DataProvider('providerAssertArrayContainsArray')]
  public function testAssertArrayContainsArray(array $array, array $sub_array, bool $should_pass): void {
    if ($should_pass) {
      $this->assertArrayContainsArray($array, $sub_array);
      // If we get here without exception, the test passed as expected
      $this->addToAssertionCount(1);
    }
    else {
      $this->expectException(AssertionFailedError::class);
      $this->assertArrayContainsArray($array, $sub_array);
    }
  }

  /**
   * Data provider for testAssertArrayContainsArray().
   */
  public static function providerAssertArrayContainsArray(): array {
    return [
      // Simple array containment - should pass
      'simple_contains_success' => [
        ['a', 'b', 'c'],
        ['a', 'b'],
        TRUE,
      ],
      // Single element containment - should pass
      'single_element_success' => [
        ['apple', 'banana', 'cherry'],
        ['banana'],
        TRUE,
      ],
      // Empty sub_array - should pass (empty set is subset of any set)
      'empty_subarray_success' => [
        ['x', 'y', 'z'],
        [],
        TRUE,
      ],
      // Numeric arrays - should pass
      'numeric_arrays_success' => [
        [1, 2, 3, 4, 5],
        [2, 4],
        TRUE,
      ],
      // Mixed types - should pass
      'mixed_types_success' => [
        ['string', 123, TRUE, NULL],
        [123, 'string'],
        TRUE,
      ],
      // Nested arrays - should pass
      'nested_arrays_success' => [
        [
          'level1' => ['a', 'b'],
          'level2' => ['c', 'd'],
          'simple' => 'value',
        ],
        [['a', 'b']],
        TRUE,
      ],
      // Complex nested structure - should pass
      'complex_nested_success' => [
        [
          ['name' => 'John', 'age' => 30],
          ['name' => 'Jane', 'age' => 25],
          'simple_value',
        ],
        [['name' => 'John', 'age' => 30]],
        TRUE,
      ],
      // Element not in array - should fail
      'element_not_found_failure' => [
        ['a', 'b', 'c'],
        ['d'],
        FALSE,
      ],
      // Partial match failure - should fail
      'partial_match_failure' => [
        ['a', 'b', 'c'],
        ['a', 'b', 'd'],
        FALSE,
      ],
      // Nested array not found - should fail
      'nested_not_found_failure' => [
        [
          ['x', 'y'],
          ['z', 'w'],
        ],
        [['a', 'b']],
        FALSE,
      ],
      // Different order in nested array - should fail
      'nested_different_order_failure' => [
        [
          ['name' => 'John', 'age' => 30],
        ],
        [['age' => 30, 'name' => 'John']],
        FALSE,
      ],
    ];
  }

  /**
   * Test assertArrayNotContainsArray method.
   *
   * @param array<mixed> $array
   *   The main array to search in.
   * @param array<mixed> $sub_array
   *   The sub-array to search for.
   * @param bool $should_pass
   *   Whether the assertion should pass or fail.
   */
  #[DataProvider('providerAssertArrayNotContainsArray')]
  public function testAssertArrayNotContainsArray(array $array, array $sub_array, bool $should_pass): void {
    if ($should_pass) {
      $this->assertArrayNotContainsArray($array, $sub_array);
      // If we get here without exception, the test passed as expected
      $this->addToAssertionCount(1);
    }
    else {
      $this->expectException(AssertionFailedError::class);
      $this->assertArrayNotContainsArray($array, $sub_array);
    }
  }

  /**
   * Data provider for testAssertArrayNotContainsArray().
   *
   * @return array<string, array<mixed>>
   *   Test data with array, sub_array, and expected result.
   */
  public static function providerAssertArrayNotContainsArray(): array {
    return [
      // Element not in array - should pass
      'element_not_found_success' => [
        ['a', 'b', 'c'],
        ['d'],
        TRUE,
      ],
      // Completely different arrays - should pass
      'different_arrays_success' => [
        ['x', 'y', 'z'],
        ['a', 'b', 'c'],
        TRUE,
      ],
      // Nested array not found - should pass
      'nested_not_found_success' => [
        [
          ['x', 'y'],
          ['z', 'w'],
        ],
        [['a', 'b']],
        TRUE,
      ],
      // Empty sub_array against empty array - should pass
      'empty_both_success' => [
        [],
        [],
        TRUE,
      ],
      // Different types - should pass
      'different_types_success' => [
        ['string', 123],
        [456, 'other'],
        TRUE,
      ],
      // Partial match with some missing - should fail (because 'a' exists)
      'partial_with_missing_failure' => [
        ['a', 'b'],
        ['a', 'c'],
        FALSE,
      ],
      // Simple containment - should fail
      'simple_contains_failure' => [
        ['a', 'b', 'c'],
        ['a', 'b'],
        FALSE,
      ],
      // Single element found - should fail
      'single_element_failure' => [
        ['apple', 'banana', 'cherry'],
        ['banana'],
        FALSE,
      ],
      // Nested array found - should fail
      'nested_found_failure' => [
        [
          ['name' => 'John', 'age' => 30],
          'simple_value',
        ],
        [['name' => 'John', 'age' => 30]],
        FALSE,
      ],
      // Empty sub_array against non-empty array - should fail
      'empty_subarray_failure' => [
        ['a', 'b', 'c'],
        [],
        FALSE,
      ],
    ];
  }

  /**
   * Test edge cases for assertArrayContainsArray.
   */
  public function testAssertArrayContainsArrayEdgeCases(): void {
    // Test with NULL values
    $this->assertArrayContainsArray([NULL, 'a', 'b'], [NULL]);

    // Test with boolean values
    $this->assertArrayContainsArray([TRUE, FALSE, 'test'], [TRUE, FALSE]);

    // Test with numeric strings vs integers
    $this->assertArrayContainsArray(['1', '2', 3], ['1']);

    // Test deeply nested arrays
    $complex = [
      'level1' => [
        'level2' => [
          'level3' => ['deep_value'],
        ],
      ],
      'simple' => 'value',
    ];
    $this->assertArrayContainsArray($complex, [['level2' => ['level3' => ['deep_value']]]]);
  }

  /**
   * Test edge cases for assertArrayNotContainsArray.
   */
  public function testAssertArrayNotContainsArrayEdgeCases(): void {
    // Test that similar but not identical arrays are correctly identified as
    // different.
    $this->assertArrayNotContainsArray(
      [['name' => 'John', 'age' => 30]],
      [['name' => 'John', 'age' => 31]]
    );

    // Test that extra elements make arrays different
    $this->assertArrayNotContainsArray(
      [['a', 'b']],
      [['a', 'b', 'c']]
    );

    // Test with different array structures
    $this->assertArrayNotContainsArray(
      ['flat', 'array'],
      [['nested', 'array']]
    );

    // If we reach here, all assertions passed
    $this->addToAssertionCount(1);
  }

  /**
   * Test error messages are meaningful.
   */
  public function testErrorMessages(): void {
    // Test that error message includes the missing value
    try {
      $this->assertArrayContainsArray(['a', 'b'], ['c']);
      $this->fail('Expected exception was not thrown');
    }
    catch (AssertionFailedError $assertionFailedError) {
      $this->assertStringContainsString("Value 'c' not found in array", $assertionFailedError->getMessage());
    }

    // Test that error message for unexpected value is clear
    try {
      $this->assertArrayNotContainsArray(['a', 'b'], ['a']);
      $this->fail('Expected exception was not thrown');
    }
    catch (AssertionFailedError $assertionFailedError) {
      $this->assertStringContainsString("Unexpected value 'a' found in array", $assertionFailedError->getMessage());
    }
  }

  /**
   * Test recursive behavior with deeply nested structures.
   */
  public function testRecursiveBehavior(): void {
    $nested_structure = [
      'users' => [
        ['id' => 1, 'name' => 'Alice', 'preferences' => ['theme' => 'dark']],
        ['id' => 2, 'name' => 'Bob', 'preferences' => ['theme' => 'light']],
      ],
      'settings' => [
        'app_name' => 'Test App',
        'version' => '1.0.0',
      ],
    ];

    // Test finding a deeply nested array
    $this->assertArrayContainsArray(
      $nested_structure,
      [['id' => 1, 'name' => 'Alice', 'preferences' => ['theme' => 'dark']]]
    );

    // Test that a slightly different nested array is not found
    $this->assertArrayNotContainsArray(
      $nested_structure,
      [['id' => 1, 'name' => 'Alice', 'preferences' => ['theme' => 'blue']]]
    );
  }

}
