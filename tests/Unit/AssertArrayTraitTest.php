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

  /**
   * Test assertArrayContainsString method.
   */
  #[DataProvider('dataProviderAssertArrayContainsString')]
  public function testAssertArrayContainsString(string $needle, array $haystack, bool $should_pass, ?string $expected_exception = NULL): void {
    if (!$should_pass) {
      $this->expectException(AssertionFailedError::class);
      if ($expected_exception) {
        $this->expectExceptionMessage($expected_exception);
      }
    }

    $this->assertArrayContainsString($needle, $haystack);

    if ($should_pass) {
      $this->addToAssertionCount(1);
    }
  }

  /**
   * Data provider for assertArrayContainsString tests.
   */
  public static function dataProviderAssertArrayContainsString(): array {
    return [
      // Success cases
      'basic_string_match' => [
        'needle' => 'bar',
        'haystack' => ['foo', 'bar', 'baz'],
        'should_pass' => TRUE,
      ],
      'partial_string_match' => [
        'needle' => 'ba',
        'haystack' => ['foo', 'bar', 'baz'],
        'should_pass' => TRUE,
      ],
      'numeric_conversion' => [
        'needle' => '12',
        'haystack' => ['foo', 123, NULL, FALSE],
        'should_pass' => TRUE,
      ],
      'empty_string_match' => [
        'needle' => '',
        'haystack' => ['', 'non-empty'],
        'should_pass' => TRUE,
      ],
      'boolean_false_conversion' => [
        'needle' => '',
        'haystack' => [FALSE, TRUE, 'test'],
        'should_pass' => TRUE,
      ],
      'boolean_true_conversion' => [
        'needle' => '1',
        'haystack' => [FALSE, TRUE, 'test'],
        'should_pass' => TRUE,
      ],
      'null_conversion' => [
        'needle' => '',
        'haystack' => [NULL, 'test'],
        'should_pass' => TRUE,
      ],
      'mixed_types_with_objects' => [
        'needle' => '0',
        'haystack' => [0, new \stdClass(), 'test'],
        'should_pass' => TRUE,
      ],

      // Failure cases
      'string_not_found' => [
        'needle' => 'xyz',
        'haystack' => ['foo', 'bar', 'baz'],
        'should_pass' => FALSE,
        'expected_exception' => 'Failed asserting that string "xyz" is present in array',
      ],
      'empty_array' => [
        'needle' => 'foo',
        'haystack' => [],
        'should_pass' => FALSE,
        'expected_exception' => 'Failed asserting that string "foo" is present in array',
      ],
      'no_partial_match' => [
        'needle' => 'xyz',
        'haystack' => ['abc', 'def', 'ghi'],
        'should_pass' => FALSE,
      ],
    ];
  }

  /**
   * Test assertArrayNotContainsString method.
   */
  #[DataProvider('dataProviderAssertArrayNotContainsString')]
  public function testAssertArrayNotContainsString(string $needle, array $haystack, bool $should_pass, ?string $expected_exception = NULL): void {
    if (!$should_pass) {
      $this->expectException(AssertionFailedError::class);
      if ($expected_exception) {
        $this->expectExceptionMessage($expected_exception);
      }
    }

    $this->assertArrayNotContainsString($needle, $haystack);

    if ($should_pass) {
      $this->addToAssertionCount(1);
    }
  }

  /**
   * Data provider for assertArrayNotContainsString tests.
   */
  public static function dataProviderAssertArrayNotContainsString(): array {
    return [
      // Success cases
      'string_not_found' => [
        'needle' => 'xyz',
        'haystack' => ['foo', 'bar', 'baz'],
        'should_pass' => TRUE,
      ],
      'empty_array' => [
        'needle' => 'foo',
        'haystack' => [],
        'should_pass' => TRUE,
      ],
      'mixed_types_no_match' => [
        'needle' => 'xyz',
        'haystack' => ['foo', 123, NULL, FALSE],
        'should_pass' => TRUE,
      ],
      'objects_skipped' => [
        'needle' => 'property',
        'haystack' => ['string', new \stdClass(), 123],
        'should_pass' => TRUE,
      ],

      // Failure cases
      'string_found' => [
        'needle' => 'ba',
        'haystack' => ['foo', 'bar', 'baz'],
        'should_pass' => FALSE,
        'expected_exception' => 'Failed asserting that string "ba" is not present in array',
      ],
      'partial_match_found' => [
        'needle' => 'oo',
        'haystack' => ['foo', 'bar'],
        'should_pass' => FALSE,
      ],
    ];
  }

  /**
   * Test assertArrayContainsArray method.
   */
  #[DataProvider('dataProviderAssertArrayContainsArray')]
  public function testAssertArrayContainsArray(array $array, array $sub_array, bool $should_pass, ?string $expected_exception = NULL): void {
    if (!$should_pass) {
      $this->expectException(AssertionFailedError::class);
      if ($expected_exception) {
        $this->expectExceptionMessage($expected_exception);
      }
    }

    $this->assertArrayContainsArray($array, $sub_array);

    if ($should_pass) {
      $this->addToAssertionCount(1);
    }
  }

  /**
   * Data provider for assertArrayContainsArray tests.
   */
  public static function dataProviderAssertArrayContainsArray(): array {
    return [
      // Success cases
      'simple_contains_success' => [
        'array' => ['a', 'b', 'c'],
        'sub_array' => ['a', 'b'],
        'should_pass' => TRUE,
      ],
      'single_element_success' => [
        'array' => ['apple', 'banana', 'cherry'],
        'sub_array' => ['banana'],
        'should_pass' => TRUE,
      ],
      'empty_subarray_success' => [
        'array' => ['x', 'y', 'z'],
        'sub_array' => [],
        'should_pass' => TRUE,
      ],
      'numeric_arrays_success' => [
        'array' => [1, 2, 3, 4, 5],
        'sub_array' => [2, 4],
        'should_pass' => TRUE,
      ],
      'mixed_types_success' => [
        'array' => ['string', 123, TRUE, NULL],
        'sub_array' => [123, 'string'],
        'should_pass' => TRUE,
      ],
      'nested_arrays_success' => [
        'array' => [
          'level1' => ['a', 'b'],
          'level2' => ['c', 'd'],
          'simple' => 'value',
        ],
        'sub_array' => [['a', 'b']],
        'should_pass' => TRUE,
      ],
      'complex_nested_success' => [
        'array' => [
          ['name' => 'John', 'age' => 30],
          ['name' => 'Jane', 'age' => 25],
          'simple_value',
        ],
        'sub_array' => [['name' => 'John', 'age' => 30]],
        'should_pass' => TRUE,
      ],
      'deep_recursive_search' => [
        'array' => [
          'users' => [
            ['id' => 1, 'name' => 'Alice', 'preferences' => ['theme' => 'dark']],
            ['id' => 2, 'name' => 'Bob', 'preferences' => ['theme' => 'light']],
          ],
          'settings' => ['app_name' => 'Test App'],
        ],
        'sub_array' => [['id' => 1, 'name' => 'Alice', 'preferences' => ['theme' => 'dark']]],
        'should_pass' => TRUE,
      ],
      'direct_array_element' => [
        'array' => [
          'other',
          ['exact', 'match'],
          'more',
        ],
        'sub_array' => [['exact', 'match']],
        'should_pass' => TRUE,
      ],
      'nested_wrapper_structure' => [
        'array' => [
          'wrapper' => [
            ['nested', 'target'],
          ],
        ],
        'sub_array' => [['nested', 'target']],
        'should_pass' => TRUE,
      ],

      // Failure cases
      'element_not_found_failure' => [
        'array' => ['a', 'b', 'c'],
        'sub_array' => ['d'],
        'should_pass' => FALSE,
        'expected_exception' => "Value 'd' not found in array",
      ],
      'partial_match_failure' => [
        'array' => ['a', 'b', 'c'],
        'sub_array' => ['a', 'b', 'd'],
        'should_pass' => FALSE,
      ],
      'nested_not_found_failure' => [
        'array' => [
          ['x', 'y'],
          ['z', 'w'],
        ],
        'sub_array' => [['a', 'b']],
        'should_pass' => FALSE,
        'expected_exception' => 'Expected sub-array not found',
      ],
      'different_order_failure' => [
        'array' => [
          ['name' => 'John', 'age' => 30],
        ],
        'sub_array' => [['age' => 30, 'name' => 'John']],
        'should_pass' => FALSE,
      ],
    ];
  }

  /**
   * Test assertArrayNotContainsArray method.
   */
  #[DataProvider('dataProviderAssertArrayNotContainsArray')]
  public function testAssertArrayNotContainsArray(array $array, array $sub_array, bool $should_pass, ?string $expected_exception = NULL): void {
    if (!$should_pass) {
      $this->expectException(AssertionFailedError::class);
      if ($expected_exception) {
        $this->expectExceptionMessage($expected_exception);
      }
    }

    $this->assertArrayNotContainsArray($array, $sub_array);

    if ($should_pass) {
      $this->addToAssertionCount(1);
    }
  }

  /**
   * Data provider for assertArrayNotContainsArray tests.
   */
  public static function dataProviderAssertArrayNotContainsArray(): array {
    return [
      // Success cases
      'element_not_found_success' => [
        'array' => ['a', 'b', 'c'],
        'sub_array' => ['d'],
        'should_pass' => TRUE,
      ],
      'different_arrays_success' => [
        'array' => ['x', 'y', 'z'],
        'sub_array' => ['a', 'b', 'c'],
        'should_pass' => TRUE,
      ],
      'nested_not_found_success' => [
        'array' => [
          ['x', 'y'],
          ['z', 'w'],
        ],
        'sub_array' => [['a', 'b']],
        'should_pass' => TRUE,
      ],
      'empty_both_success' => [
        'array' => [],
        'sub_array' => [],
        'should_pass' => TRUE,
      ],
      'different_types_success' => [
        'array' => ['string', 123],
        'sub_array' => [456, 'other'],
        'should_pass' => TRUE,
      ],
      'similar_but_different_nested' => [
        'array' => [['name' => 'John', 'age' => 30]],
        'sub_array' => [['name' => 'John', 'age' => 31]],
        'should_pass' => TRUE,
      ],
      'extra_elements_different' => [
        'array' => [['a', 'b']],
        'sub_array' => [['a', 'b', 'c']],
        'should_pass' => TRUE,
      ],

      // Failure cases
      'partial_with_missing_failure' => [
        'array' => ['a', 'b'],
        'sub_array' => ['a', 'c'],
        'should_pass' => FALSE,
        'expected_exception' => "Unexpected value 'a' found in array",
      ],
      'simple_contains_failure' => [
        'array' => ['a', 'b', 'c'],
        'sub_array' => ['a', 'b'],
        'should_pass' => FALSE,
      ],
      'single_element_failure' => [
        'array' => ['apple', 'banana', 'cherry'],
        'sub_array' => ['banana'],
        'should_pass' => FALSE,
      ],
      'nested_found_failure' => [
        'array' => [
          ['name' => 'John', 'age' => 30],
          'simple_value',
        ],
        'sub_array' => [['name' => 'John', 'age' => 30]],
        'should_pass' => FALSE,
        'expected_exception' => 'Unexpected sub-array found',
      ],
      'empty_subarray_failure' => [
        'array' => ['a', 'b', 'c'],
        'sub_array' => [],
        'should_pass' => FALSE,
        'expected_exception' => 'Empty sub-array is a subset of any non-empty array',
      ],
      'recursive_match_failure' => [
        'array' => [
          'level1' => [
            'level2' => [
              ['target', 'found'],
            ],
          ],
        ],
        'sub_array' => [['target', 'found']],
        'should_pass' => FALSE,
        'expected_exception' => 'Unexpected sub-array found',
      ],
    ];
  }

  /**
   * Test edge cases and special scenarios.
   */
  #[DataProvider('dataProviderEdgeCases')]
  public function testEdgeCases(string $method, array $args, bool $should_pass, ?string $expected_exception = NULL): void {
    if (!$should_pass) {
      $this->expectException(AssertionFailedError::class);
      if ($expected_exception) {
        $this->expectExceptionMessage($expected_exception);
      }
    }

    // Use match statement for type-safe method calls
    match ($method) {
      'assertArrayContainsArray' => $this->assertArrayContainsArray($args[0], $args[1]),
      'assertArrayNotContainsString' => $this->assertArrayNotContainsString($args[0], $args[1]),
      default => throw new \InvalidArgumentException('Unknown method: ' . $method),
    };

    if ($should_pass) {
      $this->addToAssertionCount(1);
    }
  }

  /**
   * Data provider for edge cases.
   */
  public static function dataProviderEdgeCases(): array {
    $object = new \stdClass();
    $object->property = 'test';

    return [
      // assertArrayContainsArray edge cases
      'null_values_in_contains' => [
        'method' => 'assertArrayContainsArray',
        'args' => [[NULL, 'a', 'b'], [NULL]],
        'should_pass' => TRUE,
      ],
      'boolean_values_in_contains' => [
        'method' => 'assertArrayContainsArray',
        'args' => [[TRUE, FALSE, 'test'], [TRUE, FALSE]],
        'should_pass' => TRUE,
      ],
      'numeric_strings_vs_integers' => [
        'method' => 'assertArrayContainsArray',
        'args' => [['1', '2', 3], ['1']],
        'should_pass' => TRUE,
      ],
      'very_deep_nesting' => [
        'method' => 'assertArrayContainsArray',
        'args' => [
          [
            'a' => [
              'b' => [
                'c' => [
                  'd' => [
                    'e' => ['deep_target'],
                  ],
                ],
              ],
            ],
          ],
          [['deep_target']],
        ],
        'should_pass' => TRUE,
      ],

      // assertArrayNotContainsString with objects
      'objects_ignored_in_not_contains_string' => [
        'method' => 'assertArrayNotContainsString',
        'args' => ['property', ['string', $object, 123]],
        'should_pass' => TRUE,
      ],
    ];
  }

  /**
   * Test custom failure messages work correctly.
   */
  public function testCustomFailureMessages(): void {
    $custom_message = 'This is a custom failure message';

    $this->expectException(AssertionFailedError::class);
    $this->expectExceptionMessage($custom_message);

    $this->assertArrayContainsString('nonexistent', ['test', 'array'], $custom_message);
  }

}
