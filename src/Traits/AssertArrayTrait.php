<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Traits;

/**
 * Trait AssertTrait.
 *
 * Provides custom assertions.
 *
 * @mixin \PHPUnit\Framework\TestCase
 */
trait AssertArrayTrait {

  /**
   * Assert that a string is present in an array.
   *
   * @param string $needle
   *   The string to search for.
   * @param array $haystack
   *   The array to search in.
   * @param ?string $message
   *   Optional failure message.
   *
   * @throws \PHPUnit\Framework\AssertionFailedError
   *   If the string is not present in the array.
   */
  public function assertArrayContainsString(string $needle, array $haystack, ?string $message = NULL): void {
    foreach ($haystack as $hay) {
      if (str_contains((string) $hay, $needle)) {
        $this->addToAssertionCount(1);

        return;
      }
    }
    $this->fail($message ?: sprintf('Failed asserting that string "%s" is present in array %s.', $needle, print_r($haystack, TRUE)));
  }

  /**
   * Assert that a string is not present in an array.
   *
   * @param string $needle
   *   The string to search for.
   * @param array $haystack
   *   The array to search in.
   * @param ?string $message
   *   Optional failure message.
   *
   * @throws \PHPUnit\Framework\AssertionFailedError
   *   If the string is present in the array.
   */
  public function assertArrayNotContainsString(string $needle, array $haystack, ?string $message = NULL): void {
    foreach ($haystack as $hay) {
      if (is_object($hay)) {
        continue;
      }
      if (str_contains((string) $hay, $needle)) {
        $this->fail($message ?: sprintf('Failed asserting that string "%s" is not present in array %s.', $needle, print_r($haystack, TRUE)));
      }
    }
    $this->addToAssertionCount(1);
  }

  /**
   * Assert that an array contains all elements from a sub-array.
   *
   * @param array $array
   *   The main array to search in.
   * @param array $sub_array
   *   The sub-array to search for.
   * @param ?string $message
   *   Optional failure message.
   *
   * @throws \PHPUnit\Framework\AssertionFailedError
   *   If any element from the sub-array is not found in the main array.
   */
  public function assertArrayContainsArray(array $array, array $sub_array, ?string $message = NULL): void {
    foreach ($sub_array as $value) {
      if (is_array($value)) {
        $found = FALSE;

        // Check if value exists as a direct value in array.
        if (in_array($value, $array, TRUE)) {
          $found = TRUE;
        }
        else {
          // Check each element in array recursively.
          foreach ($array as $item) {
            if (is_array($item)) {
              // Recursively search within this item.
              try {
                $this->assertArrayContainsArray($item, [$value], $message);
                $found = TRUE;
                break;
              }
              catch (\Throwable $e) {
                // Continue searching.
              }
            }
          }
        }

        $this->assertTrue($found, $message ?: 'Expected sub-array not found.');
      }
      else {
        $this->assertContains($value, $array, $message ?: sprintf("Value '%s' not found in array.", $value));
      }
    }
  }

  /**
   * Assert that an array does not contain any elements from a sub-array.
   *
   * @param array $array
   *   The main array to search in.
   * @param array $sub_array
   *   The sub-array to search for.
   * @param ?string $message
   *   Optional failure message.
   *
   * @throws \PHPUnit\Framework\AssertionFailedError
   *   If any element from the sub-array is found in the main array.
   */
  public function assertArrayNotContainsArray(array $array, array $sub_array, ?string $message = NULL): void {
    // Empty subArray against empty array should pass (both are empty)
    // Empty subArray against non-empty array should fail (empty set is subset)
    if (empty($sub_array)) {
      if (!empty($array)) {
        $this->fail($message ?: 'Empty sub-array is a subset of any non-empty array.');
      }
      return;
    }

    // Check that NONE of the elements in subArray are found in array.
    foreach ($sub_array as $value) {
      if (is_array($value)) {
        // Check if value exists as a direct value in array.
        if (in_array($value, $array, TRUE)) {
          $this->fail($message ?: 'Unexpected sub-array found.');
        }

        // Check each element in array recursively.
        foreach ($array as $item) {
          if (is_array($item)) {
            // Recursively search within this item.
            try {
              $this->assertArrayNotContainsArray($item, [$value], $message);
            }
            catch (\Throwable $e) {
              $this->fail($message ?: 'Unexpected sub-array found.');
            }
          }
        }
      }
      else {
        $this->assertNotContains($value, $array, $message ?: sprintf("Unexpected value '%s' found in array.", $value));
      }
    }
  }

}
