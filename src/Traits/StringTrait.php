<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Traits;

/**
 * Trait StringTrait.
 *
 * Provides string assertion utilities with prefix support.
 *
 * @mixin \PHPUnit\Framework\TestCase
 */
trait StringTrait {

  /**
   * Asserts string contains or does not contain expected values with prefixes.
   *
   * Supports four single-character prefixes for precise matching control:
   * - '+' = exact match present
   * - '*' = substring present
   * - '-' = exact match absent
   * - '!' = substring absent.
   *
   * @param string $haystack
   *   The string to search in.
   * @param string|array $expected
   *   String or array of strings to check with optional prefixes.
   * @param string $message_present_exact
   *   Message template for failed exact match present assertions.
   * @param string $message_present_contains
   *   Message template for failed substring present assertions.
   * @param string $message_absent_exact
   *   Message template for failed exact match absent assertions.
   * @param string $message_absent_contains
   *   Message template for failed substring absent assertions.
   * @param string $prefix_present_exact
   *   Prefix for exact string presence. Defaults to '+'.
   * @param string $prefix_present_contains
   *   Prefix for substring presence. Defaults to '*'.
   * @param string $prefix_absent_exact
   *   Prefix for exact string absence. Defaults to '-'.
   * @param string $prefix_absent_contains
   *   Prefix for substring absence. Defaults to '!'.
   * @param string $prefix_separator
   *   Separator between prefixes and a string. Defaults to a space.
   * @param bool $case_insensitive
   *   Whether to perform case-insensitive matching. Defaults to TRUE.
   *
   * @throws \InvalidArgumentException
   *   When prefix arguments are invalid (not single characters or not unique).
   * @throws \RuntimeException
   *   When prefix usage is inconsistent or values are empty after stripping.
   */
  protected function assertStringContainsOrNot(
    string $haystack,
    string|array $expected,
    string $message_present_exact = 'Expected exact match for "%s" in haystack',
    string $message_present_contains = 'Expected substring "%s" in haystack',
    string $message_absent_exact = 'Expected no exact match for "%s" in haystack',
    string $message_absent_contains = 'Expected substring "%s" not in haystack',
    string $prefix_present_exact = '+',
    string $prefix_present_contains = '*',
    string $prefix_absent_exact = '-',
    string $prefix_absent_contains = '!',
    string $prefix_separator = ' ',
    bool $case_insensitive = TRUE,
  ): void {
    $prefixes = [
      $prefix_present_exact,
      $prefix_present_contains,
      $prefix_absent_exact,
      $prefix_absent_contains,
    ];

    foreach ($prefixes as $prefix) {
      if (strlen($prefix) !== 1) {
        throw new \InvalidArgumentException('All prefix arguments must be exactly one character long');
      }
    }

    if (count(array_unique($prefixes)) !== 4) {
      throw new \InvalidArgumentException('All prefix arguments must be unique');
    }

    $expected = is_array($expected) ? $expected : [$expected];

    if (empty($expected)) {
      return;
    }

    // Determine mode by checking for any prefixes.
    $has_prefix_count = 0;
    foreach ($expected as $value) {
      foreach ($prefixes as $prefix) {
        $prefix_with_separator = $prefix . $prefix_separator;
        if ($prefix_separator === '') {
          // When separator is empty, check only prefix.
          if (substr($value, 0, strlen($prefix)) === $prefix) {
            $has_prefix_count++;
            break;
          }
        }
        elseif (substr($value, 0, strlen($prefix_with_separator)) === $prefix_with_separator) {
          // When separator exists, check prefix + separator.
          $has_prefix_count++;
          break;
        }
      }
    }

    $mixed_mode = $has_prefix_count > 0;

    // In mixed mode, all values must have valid prefixes.
    if ($mixed_mode && $has_prefix_count !== count($expected)) {
      $first_invalid = NULL;
      foreach ($expected as $value) {
        $has_valid_prefix = FALSE;
        foreach ($prefixes as $prefix) {
          $prefix_with_separator = $prefix . $prefix_separator;
          if ($prefix_separator === '') {
            // When separator is empty, check only prefix.
            if (substr($value, 0, strlen($prefix)) === $prefix) {
              $has_valid_prefix = TRUE;
              break;
            }
          }
          elseif (substr($value, 0, strlen($prefix_with_separator)) === $prefix_with_separator) {
            // When separator exists, check prefix + separator.
            $has_valid_prefix = TRUE;
            break;
          }
        }
        if (!$has_valid_prefix) {
          $first_invalid = $value;
          break;
        }
      }
      throw new \RuntimeException(sprintf('All strings must have valid prefixes in mixed mode. First invalid: "%s"', $first_invalid));
    }

    // Process each expected value.
    foreach ($expected as $expected_value) {
      if ($mixed_mode) {
        // Find which prefix matches this value.
        $prefix = NULL;
        $value = NULL;
        foreach ($prefixes as $test_prefix) {
          $prefix_with_separator = $test_prefix . $prefix_separator;
          if ($prefix_separator === '') {
            // When separator is empty, check only prefix.
            if (substr($expected_value, 0, strlen($test_prefix)) === $test_prefix) {
              $prefix = $test_prefix;
              $value = substr($expected_value, strlen($test_prefix));
              break;
            }
          }
          elseif (substr($expected_value, 0, strlen($prefix_with_separator)) === $prefix_with_separator) {
            // When separator exists, check prefix + separator.
            $prefix = $test_prefix;
            $value = substr($expected_value, strlen($prefix_with_separator));
            break;
          }
        }

        // Validate value is not empty after stripping prefix.
        if ($value === '') {
          throw new \RuntimeException(sprintf('Value cannot be empty after stripping prefix: "%s"', $expected_value));
        }
      }
      else {
        // Shortcut mode: treat as substring present.
        $prefix = $prefix_present_contains;
        $value = $expected_value;
      }

      // Perform the appropriate assertion based on prefix type.
      if ($prefix === $prefix_present_exact) {
        $message = sprintf($message_present_exact, $value);
        if ($case_insensitive) {
          $this->assertEquals(strtolower($value), strtolower($haystack), $message);
        }
        else {
          $this->assertEquals($value, $haystack, $message);
        }
      }
      elseif ($prefix === $prefix_present_contains) {
        $message = sprintf($message_present_contains, $value);
        if ($case_insensitive) {
          $this->assertStringContainsStringIgnoringCase($value, $haystack, $message);
        }
        else {
          $this->assertStringContainsString($value, $haystack, $message);
        }
      }
      elseif ($prefix === $prefix_absent_exact) {
        $message = sprintf($message_absent_exact, $value);
        if ($case_insensitive) {
          $this->assertNotEquals(strtolower($value), strtolower($haystack), $message);
        }
        else {
          $this->assertNotEquals($value, $haystack, $message);
        }
      }
      elseif ($prefix === $prefix_absent_contains) {
        $message = sprintf($message_absent_contains, $value);
        if ($case_insensitive) {
          $this->assertStringNotContainsStringIgnoringCase($value, $haystack, $message);
        }
        else {
          $this->assertStringNotContainsString($value, $haystack, $message);
        }
      }
    }
  }

}
