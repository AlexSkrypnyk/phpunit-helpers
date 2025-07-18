<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Tests\Unit;

use AlexSkrypnyk\PhpunitHelpers\Traits\TuiTrait;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversTrait(TuiTrait::class)]
class TuiTraitTest extends TestCase {

  use TuiTrait;

  #[DataProvider('dataProviderTuiEntries')]
  public function testTuiEntries(array $entries, string $default, array $expected): void {
    $processed = static::tuiEntries($entries, $default);

    foreach ($expected as $key => $value) {
      if ($value === 'ASSERT_NOT_EXISTS') {
        $this->assertArrayNotHasKey($key, $processed);
      }
      else {
        $this->assertArrayHasKey($key, $processed);
        $this->assertEquals($value, $processed[$key]);
      }
    }
  }

  public static function dataProviderTuiEntries(): array {
    return [
      'with_defaults' => [
        [
          'answer1' => 'value1',
          'answer2' => self::TUI_DEFAULT,
          'answer3' => 'value3',
          'answer4' => self::TUI_SKIP,
        ],
        'default_value',
        [
          'answer1' => 'value1',
          'answer2' => 'default_value',
          'answer3' => 'value3',
          'answer4' => 'ASSERT_NOT_EXISTS',
        ],
      ],
      'with_scalar_values' => [
        [
          'answer1' => 'value1',
          'answer2' => 123,
          'answer3' => 45.6,
          'answer4' => TRUE,
        ],
        'default_value',
        [
          'answer1' => 'value1',
          'answer2' => '123',
          'answer3' => '45.6',
          'answer4' => '1',
        ],
      ],
      'empty_default' => [
        [
          'answer1' => 'value1',
          'answer2' => self::TUI_DEFAULT,
        ],
        '',
        [
          'answer1' => 'value1',
          'answer2' => '',
        ],
      ],
      'all_defaults' => [
        [
          'answer1' => self::TUI_DEFAULT,
          'answer2' => self::TUI_DEFAULT,
        ],
        'default_value',
        [
          'answer1' => 'default_value',
          'answer2' => 'default_value',
        ],
      ],
      'all_skips' => [
        [
          'answer1' => self::TUI_SKIP,
          'answer2' => self::TUI_SKIP,
        ],
        'default_value',
        [
          'answer1' => 'ASSERT_NOT_EXISTS',
          'answer2' => 'ASSERT_NOT_EXISTS',
        ],
      ],
    ];
  }

  #[DataProvider('dataProviderTuiKeystrokes')]
  public function testTuiKeystrokes(
    array $entries,
    int $clear_size,
    ?string $accept_key,
    ?string $clear_key,
    array $expected,
  ): void {
    $keystrokes = static::tuiKeystrokes($entries, $clear_size, $accept_key, $clear_key);
    $this->assertEquals($expected, $keystrokes);
  }

  public static function dataProviderTuiKeystrokes(): array {
    return [
      'basic_usage' => [
        [
          'answer1' => 'value1',
          'answer2' => self::TUI_DEFAULT,
          'answer3' => self::KEYS['ENTER'],
        ],
        0,
        NULL,
        NULL,
        [
          'v', 'a', 'l', 'u', 'e', '1', self::KEYS['ENTER'],
          self::KEYS['ENTER'],
          self::KEYS['ENTER'],
        ],
      ],
      'with_clear_size' => [
        [
          'answer1' => 'yes',
        ],
        3,
        NULL,
        NULL,
        [
          self::KEYS['BACKSPACE'], self::KEYS['BACKSPACE'], self::KEYS['BACKSPACE'],
          'y', 'e', 's', self::KEYS['ENTER'],
        ],
      ],
      'with_custom_keys' => [
        [
          'answer1' => 'yes',
        ],
        2,
        self::KEYS['TAB'],
        self::KEYS['DELETE'],
        [
          self::KEYS['DELETE'], self::KEYS['DELETE'],
          'y', 'e', 's', self::KEYS['TAB'],
        ],
      ],
      'custom_array_key' => [
        [
          'custom_key' => 'custom_value',
        ],
        0,
        NULL,
        NULL,
        [
          'c', 'u', 's', 't', 'o', 'm', '_', 'v', 'a', 'l', 'u', 'e', self::KEYS['ENTER'],
        ],
      ],
      'single_character' => [
        [
          'answer1' => 'y',
        ],
        0,
        NULL,
        NULL,
        [
          'y', self::KEYS['ENTER'],
        ],
      ],
      'empty_string' => [
        [
          'answer1' => '',
        ],
        0,
        NULL,
        NULL,
        [
          self::KEYS['ENTER'],
        ],
      ],

      'multiple_characters' => [
        [
          'answer1' => self::KEYS['DOWN'],
          'answer2' => 'y' . self::KEYS['DOWN'] . 'n' . self::KEYS['ENTER'],
          'answer3' => self::KEYS['DOWN'] . 'n' . self::KEYS['ENTER'],
        ],
        0,
        NULL,
        NULL,
        [
          self::KEYS['DOWN'],
          'y', self::KEYS['DOWN'], 'n', self::KEYS['ENTER'],
          self::KEYS['DOWN'], 'n', self::KEYS['ENTER'],
        ],
      ],
      'string_with_spaces' => [
        [
          'answer1' => 'hello world',
        ],
        0,
        NULL,
        NULL,
        [
          'h', 'e', 'l', 'l', 'o', ' ', 'w', 'o', 'r', 'l', 'd', self::KEYS['ENTER'],
        ],
      ],
      'string_with_spaces_an_key' => [
        [
          'answer1' => 'hello world',
        ],
        0,
        NULL,
        NULL,
        [
          'h', 'e', 'l', 'l', 'o', self::KEYS['SPACE'], 'w', 'o', 'r', 'l', 'd', self::KEYS['ENTER'],
        ],
      ],
      'string_with_spaces_clear_size' => [
        [
          'answer1' => 'hello world',
        ],
        2,
        NULL,
        NULL,
        [
          self::KEYS['BACKSPACE'], self::KEYS['BACKSPACE'],
          'h', 'e', 'l', 'l', 'o', self::KEYS['SPACE'], 'w', 'o', 'r', 'l', 'd', self::KEYS['ENTER'],
        ],
      ],
    ];
  }

  #[DataProvider('dataProviderTuiIsKey')]
  public function testTuiIsKey(string $value, bool $expected): void {
    $this->assertEquals($expected, static::tuiIsKey($value));
  }

  public static function dataProviderTuiIsKey(): array {
    return [
      'enter_key' => [self::KEYS['ENTER'], TRUE],
      'space_key' => [self::KEYS['SPACE'], TRUE],
      'tab_key' => [self::KEYS['TAB'], TRUE],
      'backspace_key' => [self::KEYS['BACKSPACE'], TRUE],
      'delete_key' => [self::KEYS['DELETE'], TRUE],
      'escape_key' => [self::KEYS['ESCAPE'], TRUE],
      'arrow_key' => [self::KEYS['UP'], TRUE],
      'not_a_key' => ['not_a_key', FALSE],
      'key_name_as_string' => ['ENTER', FALSE],
      'empty_string' => ['', FALSE],
    ];
  }

  #[DataProvider('dataProviderTuiHasKey')]
  public function testTuiHasKey(string $value, array $exclude, bool $expected): void {
    $this->assertEquals($expected, static::tuiHasKey($value, $exclude));
  }

  public static function dataProviderTuiHasKey(): array {
    return [
      'string_with_enter_key' => ['some text' . self::KEYS['ENTER'] . 'more text', [], TRUE],
      'string_with_space_key' => ['text with' . self::KEYS['SPACE'] . 'space', [], TRUE],
      'string_with_tab_key' => ['text' . self::KEYS['TAB'] . 'tab', [], TRUE],
      'string_with_backspace_key' => ['text' . self::KEYS['BACKSPACE'] . 'backspace', [], TRUE],
      'string_with_arrow_key' => ['text' . self::KEYS['UP'] . 'arrow', [], TRUE],
      'string_without_keys' => ['plaintext', [], FALSE],
      'empty_string' => ['', [], FALSE],
      'string_with_excluded_key' => ['text' . self::KEYS['SPACE'] . 'space', [self::KEYS['SPACE']], FALSE],
      'string_with_non_excluded_key' => ['text' . self::KEYS['ENTER'] . 'enter', [self::KEYS['SPACE']], TRUE],
      'string_with_multiple_keys' => ['text' . self::KEYS['ENTER'] . self::KEYS['TAB'] . 'keys', [], TRUE],
      'string_with_multiple_excluded_keys' => ['text' . self::KEYS['SPACE'] . 'space', [self::KEYS['SPACE'], self::KEYS['TAB']], FALSE],
      'string_with_mixed_keys_and_exclusions' => ['text' . self::KEYS['ENTER'] . self::KEYS['SPACE'] . 'mixed', [self::KEYS['SPACE']], TRUE],
    ];
  }

  public function testTuiEntriesNonScalarException(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('TUI entry "invalid_entry" must be a scalar value. Got: array');

    static::tuiEntries([
      'invalid_entry' => ['not', 'scalar'],
    ]);
  }

}
