<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Traits;

/**
 * Provides string assertion utilities with prefix support.
 *
 * @mixin \PHPUnit\Framework\TestCase
 */
trait StringTrait {

  /**
   * Asserts string contains or does not contain expected values with prefixes.
   *
   * Supports four single-character prefixes to control matching:
   * - '+' = exact match present
   * - '*' = substring present
   * - '-' = exact match absent
   * - '!' = substring absent.
   *
   * @param string $haystack
   *   The string to search in.
   * @param array|string $expected
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
   * @throws \PHPUnit\Framework\AssertionFailedError
   *   When an assertion on the haystack fails.
   */
  protected function assertStringContainsOrNot(
    string $haystack,
    array|string $expected,
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
        throw new \InvalidArgumentException('All prefix arguments must be exactly one character long.');
      }
    }

    if (count(array_unique($prefixes)) !== 4) {
      throw new \InvalidArgumentException('All prefix arguments must be unique.');
    }

    $expected = is_array($expected) ? $expected : [$expected];

    if (empty($expected)) {
      return;
    }

    $has_prefix_count = 0;
    foreach ($expected as $value) {
      if (static::stringMatchPrefix($value, $prefixes, $prefix_separator) !== NULL) {
        $has_prefix_count++;
      }
    }

    $mixed_mode = $has_prefix_count > 0;

    if ($mixed_mode && $has_prefix_count !== count($expected)) {
      $first_invalid = NULL;

      foreach ($expected as $value) {
        if (static::stringMatchPrefix($value, $prefixes, $prefix_separator) === NULL) {
          $first_invalid = $value;
          break;
        }
      }

      throw new \RuntimeException(sprintf('All strings must have valid prefixes in mixed mode. First invalid: "%s".', $first_invalid));
    }

    foreach ($expected as $expected_value) {
      if ($mixed_mode) {
        $prefix = static::stringMatchPrefix($expected_value, $prefixes, $prefix_separator);
        $value = $prefix === NULL ? NULL : substr($expected_value, strlen($prefix . $prefix_separator));

        if ($value === '') {
          throw new \RuntimeException(sprintf('Value cannot be empty after stripping prefix: "%s".', $expected_value));
        }
      }
      else {
        // Shortcut mode: treat as substring present.
        $prefix = $prefix_present_contains;
        $value = $expected_value;
      }

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

  /**
   * Find which prefix a value starts with.
   *
   * @param string $value
   *   The value to inspect.
   * @param array<int, string> $prefixes
   *   The prefixes to match against.
   * @param string $separator
   *   Separator between a prefix and the value.
   *
   * @return string|null
   *   The matched prefix, or NULL when the value carries none.
   */
  protected static function stringMatchPrefix(string $value, array $prefixes, string $separator): ?string {
    foreach ($prefixes as $prefix) {
      if (str_starts_with($value, $prefix . $separator)) {
        return $prefix;
      }
    }

    return NULL;
  }

}
