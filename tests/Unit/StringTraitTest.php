<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Tests\Unit;

use PHPUnit\Framework\AssertionFailedError;
use AlexSkrypnyk\PhpunitHelpers\Traits\StringTrait;
use AlexSkrypnyk\PhpunitHelpers\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversTrait(StringTrait::class)]
class StringTraitTest extends UnitTestCase {

  use StringTrait;

  #[DataProvider('dataProviderAssertStringContainsOrNot')]
  public function testAssertStringContainsOrNot(
    string $name,
    string $haystack,
    string|array $expected,
    bool $case_insensitive,
    ?array $custom_prefixes,
    ?array $custom_messages,
    ?string $exception_class,
    ?string $exception_message,
  ): void {
    if ($exception_class) {
      /** @var class-string<\Throwable> $exception_class */
      $this->expectException($exception_class);
      if ($exception_message) {
        $this->expectExceptionMessage($exception_message);
      }
    }

    $args = [$haystack, $expected];

    if ($custom_messages) {
      $args = array_merge($args, $custom_messages);
    }

    if ($custom_prefixes) {
      if (!$custom_messages) {
        // Add default messages if custom prefixes but no custom messages
        $args = array_merge($args, [
          'Expected exact match for "%s" in haystack',
          'Expected substring "%s" in haystack',
          'Expected no exact match for "%s" in haystack',
          'Expected substring "%s" not in haystack',
        ]);
      }
      $args = array_merge($args, $custom_prefixes);
    }

    if (!$case_insensitive && ($custom_prefixes || $custom_messages)) {
      $args[] = $case_insensitive;
    }

    $this->assertStringContainsOrNot(...$args);

    // Add assertion to avoid risky test warning for success cases
    if (!$exception_class) {
      $this->addToAssertionCount(1);
    }
  }

  public static function dataProviderAssertStringContainsOrNot(): array {
    return [
      'shortcut_mode_substring_matching' => [
        'shortcut_mode_substring_matching',
        'This is a test string with some content',
        ['test', 'string', 'content'],
        TRUE,
        NULL,
        NULL,
        NULL,
        NULL,
      ],

      'mixed_mode_all_prefixes' => [
        'mixed_mode_all_prefixes',
        'apple pie banana split',
        ['* apple', '* pie', '! orange', '! cake'],
        TRUE,
        NULL,
        NULL,
        NULL,
        NULL,
      ],

      'present_matching' => [
        'present_matching',
        'apple pie and banana split',
        ['* apple', '* pie'],
        TRUE,
        NULL,
        NULL,
        NULL,
        NULL,
      ],

      'absent_matching' => [
        'absent_matching',
        'apple pie and banana split',
        ['- orange', '! cake'],
        TRUE,
        NULL,
        NULL,
        NULL,
        NULL,
      ],

      'case_insensitive_matching' => [
        'case_insensitive_matching',
        'Apple PIE and Banana SPLIT',
        ['* apple', '* pie', '! ORANGE'],
        TRUE,
        NULL,
        NULL,
        NULL,
        NULL,
      ],

      'case_sensitive_matching' => [
        'case_sensitive_matching',
        'Apple PIE and Banana SPLIT',
        ['* Apple', '* PIE', '! apple'],
        FALSE,
        ['+', '*', '-', '!', ' '],
        ['Expected exact match for "%s" in haystack', 'Expected substring "%s" in haystack', 'Expected no exact match for "%s" in haystack', 'Expected substring "%s" not in haystack'],
        NULL,
        NULL,
      ],

      'single_string_input' => [
        'single_string_input',
        'This is a test string',
        '* test',
        TRUE,
        NULL,
        NULL,
        NULL,
        NULL,
      ],

      'empty_expected_array' => [
        'empty_expected_array',
        'This is a test string',
        [],
        TRUE,
        NULL,
        NULL,
        NULL,
        NULL,
      ],

      'complex_real_world_scenario' => [
        'complex_real_world_scenario',
        'The quick brown fox jumps over the lazy dog. Error: file not found.',
        [
          '* quick',
          '* brown',
          '* lazy',
          '* Error:',
          '! slow',
          '! elephant',
          '* fox',
          '! success',
        ],
        TRUE,
        NULL,
        NULL,
        NULL,
        NULL,
      ],

      'invalid_prefix_length' => [
        'invalid_prefix_length',
        'test',
        ['* test'],
        TRUE,
        ['++', '*', '-', '!', ' '],
        ['Expected exact match for "%s" in haystack', 'Expected substring "%s" in haystack', 'Expected no exact match for "%s" in haystack', 'Expected substring "%s" not in haystack'],
        \InvalidArgumentException::class,
        'All prefix arguments must be exactly one character long',
      ],

      'non_unique_prefixes' => [
        'non_unique_prefixes',
        'test',
        ['* test'],
        TRUE,
        ['+', '+', '-', '!', ' '],
        ['Expected exact match for "%s" in haystack', 'Expected substring "%s" in haystack', 'Expected no exact match for "%s" in haystack', 'Expected substring "%s" not in haystack'],
        \InvalidArgumentException::class,
        'All prefix arguments must be unique',
      ],

      'inconsistent_prefix_usage' => [
        'inconsistent_prefix_usage',
        'test string',
        ['* test', 'string'],
        TRUE,
        NULL,
        NULL,
        \RuntimeException::class,
        'All strings must have valid prefixes in mixed mode. First invalid: "string"',
      ],

      'empty_value_after_stripping_prefix' => [
        'empty_value_after_stripping_prefix',
        'test',
        ['+ '],
        TRUE,
        NULL,
        NULL,
        \RuntimeException::class,
        'Value cannot be empty after stripping prefix: "+ "',
      ],

      'present_failure' => [
        'present_failure',
        'apple pie',
        ['* nonexistent'],
        TRUE,
        NULL,
        NULL,
        AssertionFailedError::class,
        NULL,
      ],

      'absent_failure' => [
        'absent_failure',
        'apple pie banana',
        ['! apple'],
        TRUE,
        NULL,
        NULL,
        AssertionFailedError::class,
        NULL,
      ],

      'case_sensitive_exact_match_present' => [
        'case_sensitive_exact_match_present',
        'Hello',
        ['+ Hello'],
        FALSE,
        ['+', '*', '-', '!', ' '],
        ['Expected exact match for "%s" in haystack', 'Expected substring "%s" in haystack', 'Expected no exact match for "%s" in haystack', 'Expected substring "%s" not in haystack'],
        NULL,
        NULL,
      ],

      'case_sensitive_substring_present' => [
        'case_sensitive_substring_present',
        'Hello World',
        ['* Hello'],
        FALSE,
        ['+', '*', '-', '!', ' '],
        ['Expected exact match for "%s" in haystack', 'Expected substring "%s" in haystack', 'Expected no exact match for "%s" in haystack', 'Expected substring "%s" not in haystack'],
        NULL,
        NULL,
      ],

      'case_sensitive_exact_match_absent' => [
        'case_sensitive_exact_match_absent',
        'Hello World',
        ['- hello'],
        FALSE,
        ['+', '*', '-', '!', ' '],
        ['Expected exact match for "%s" in haystack', 'Expected substring "%s" in haystack', 'Expected no exact match for "%s" in haystack', 'Expected substring "%s" not in haystack'],
        NULL,
        NULL,
      ],

      'case_sensitive_substring_absent' => [
        'case_sensitive_substring_absent',
        'Hello World',
        ['! goodbye'],
        FALSE,
        ['+', '*', '-', '!', ' '],
        ['Expected exact match for "%s" in haystack', 'Expected substring "%s" in haystack', 'Expected no exact match for "%s" in haystack', 'Expected substring "%s" not in haystack'],
        NULL,
        NULL,
      ],

      'custom_messages_with_exact_match' => [
        'custom_messages_with_exact_match',
        'test',
        ['+ test'],
        TRUE,
        NULL,
        ['Custom exact match failed for "%s"', 'Custom substring missing "%s"', 'Custom exact match should not exist for "%s"', 'Custom substring should not exist for "%s"'],
        NULL,
        NULL,
      ],

      'empty_separator_mixed_mode_present_exact' => [
        'empty_separator_mixed_mode_present_exact',
        'apple',
        ['+apple'],
        TRUE,
        ['+', '*', '-', '!', ''], // Empty separator
        ['Expected exact match for "%s" in haystack', 'Expected substring "%s" in haystack', 'Expected no exact match for "%s" in haystack', 'Expected substring "%s" not in haystack'],
        NULL,
        NULL,
      ],

      'empty_separator_mixed_mode_present_contains' => [
        'empty_separator_mixed_mode_present_contains',
        'This contains apple and banana',
        ['*apple', '*banana'],
        TRUE,
        ['+', '*', '-', '!', ''], // Empty separator
        ['Expected exact match for "%s" in haystack', 'Expected substring "%s" in haystack', 'Expected no exact match for "%s" in haystack', 'Expected substring "%s" not in haystack'],
        NULL,
        NULL,
      ],

      'empty_separator_mixed_mode_absent_exact' => [
        'empty_separator_mixed_mode_absent_exact',
        'apple',
        ['-orange'],
        TRUE,
        ['+', '*', '-', '!', ''], // Empty separator
        ['Expected exact match for "%s" in haystack', 'Expected substring "%s" in haystack', 'Expected no exact match for "%s" in haystack', 'Expected substring "%s" not in haystack'],
        NULL,
        NULL,
      ],

      'empty_separator_mixed_mode_absent_contains' => [
        'empty_separator_mixed_mode_absent_contains',
        'This contains apple and banana',
        ['!orange', '!grape'],
        TRUE,
        ['+', '*', '-', '!', ''], // Empty separator
        ['Expected exact match for "%s" in haystack', 'Expected substring "%s" in haystack', 'Expected no exact match for "%s" in haystack', 'Expected substring "%s" not in haystack'],
        NULL,
        NULL,
      ],

      'empty_separator_inconsistent_prefix_usage' => [
        'empty_separator_inconsistent_prefix_usage',
        'test string',
        ['*test', 'string'],
        TRUE,
        ['+', '*', '-', '!', ''], // Empty separator
        ['Expected exact match for "%s" in haystack', 'Expected substring "%s" in haystack', 'Expected no exact match for "%s" in haystack', 'Expected substring "%s" not in haystack'],
        \RuntimeException::class,
        'All strings must have valid prefixes in mixed mode. First invalid: "string"',
      ],

      'empty_separator_empty_value_after_stripping' => [
        'empty_separator_empty_value_after_stripping',
        'test',
        ['+'],
        TRUE,
        ['+', '*', '-', '!', ''], // Empty separator
        ['Expected exact match for "%s" in haystack', 'Expected substring "%s" in haystack', 'Expected no exact match for "%s" in haystack', 'Expected substring "%s" not in haystack'],
        \RuntimeException::class,
        'Value cannot be empty after stripping prefix: "+"',
      ],
    ];
  }

}
