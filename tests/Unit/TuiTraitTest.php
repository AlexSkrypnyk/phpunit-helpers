<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Tests\Unit;

use AlexSkrypnyk\PhpunitHelpers\Traits\TuiTrait;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversTrait(TuiTrait::class)]
final class TuiTraitTest extends TestCase {

  use TuiTrait;

  #[DataProvider('dataProviderTuiEntries')]
  public function testTuiEntries(array $entries, string $default, array $expected): void {
    $processed = self::tuiEntries($entries, $default);

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

  public static function dataProviderTuiEntries(): \Iterator {
    yield 'with_defaults' => [
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
    ];
    yield 'with_scalar_values' => [
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
    ];
    yield 'empty_default' => [
      [
        'answer1' => 'value1',
        'answer2' => self::TUI_DEFAULT,
      ],
      '',
      [
        'answer1' => 'value1',
        'answer2' => '',
      ],
    ];
    yield 'all_defaults' => [
      [
        'answer1' => self::TUI_DEFAULT,
        'answer2' => self::TUI_DEFAULT,
      ],
      'default_value',
      [
        'answer1' => 'default_value',
        'answer2' => 'default_value',
      ],
    ];
    yield 'all_skips' => [
      [
        'answer1' => self::TUI_SKIP,
        'answer2' => self::TUI_SKIP,
      ],
      'default_value',
      [
        'answer1' => 'ASSERT_NOT_EXISTS',
        'answer2' => 'ASSERT_NOT_EXISTS',
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
    $keystrokes = self::tuiKeystrokes($entries, $clear_size, $accept_key, $clear_key);
    $this->assertEquals($expected, $keystrokes);
  }

  public static function dataProviderTuiKeystrokes(): \Iterator {
    yield 'basic_usage' => [
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
    ];
    yield 'with_clear_size' => [
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
    ];
    yield 'with_custom_keys' => [
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
    ];
    yield 'custom_array_key' => [
      [
        'custom_key' => 'custom_value',
      ],
      0,
      NULL,
      NULL,
      [
        'c', 'u', 's', 't', 'o', 'm', '_', 'v', 'a', 'l', 'u', 'e', self::KEYS['ENTER'],
      ],
    ];
    yield 'single_character' => [
      [
        'answer1' => 'y',
      ],
      0,
      NULL,
      NULL,
      [
        'y', self::KEYS['ENTER'],
      ],
    ];
    yield 'empty_string' => [
      [
        'answer1' => '',
      ],
      0,
      NULL,
      NULL,
      [
        self::KEYS['ENTER'],
      ],
    ];
    yield 'multiple_characters' => [
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
    ];
    yield 'string_with_spaces' => [
      [
        'answer1' => 'hello world',
      ],
      0,
      NULL,
      NULL,
      [
        'h', 'e', 'l', 'l', 'o', ' ', 'w', 'o', 'r', 'l', 'd', self::KEYS['ENTER'],
      ],
    ];
    yield 'string_with_spaces_as_key' => [
      [
        'answer1' => 'hello world',
      ],
      0,
      NULL,
      NULL,
      [
        'h', 'e', 'l', 'l', 'o', self::KEYS['SPACE'], 'w', 'o', 'r', 'l', 'd', self::KEYS['ENTER'],
      ],
    ];
    yield 'string_with_spaces_clear_size' => [
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
    ];
  }

  #[DataProvider('dataProviderTuiIsKey')]
  public function testTuiIsKey(string $value, bool $expected): void {
    $this->assertSame($expected, self::tuiIsKey($value));
  }

  public static function dataProviderTuiIsKey(): \Iterator {
    yield 'enter_key' => [self::KEYS['ENTER'], TRUE];
    yield 'space_key' => [self::KEYS['SPACE'], TRUE];
    yield 'tab_key' => [self::KEYS['TAB'], TRUE];
    yield 'backspace_key' => [self::KEYS['BACKSPACE'], TRUE];
    yield 'delete_key' => [self::KEYS['DELETE'], TRUE];
    yield 'escape_key' => [self::KEYS['ESCAPE'], TRUE];
    yield 'arrow_key' => [self::KEYS['UP'], TRUE];
    yield 'not_a_key' => ['not_a_key', FALSE];
    yield 'key_name_as_string' => ['ENTER', FALSE];
    yield 'empty_string' => ['', FALSE];
  }

  #[DataProvider('dataProviderTuiHasKey')]
  public function testTuiHasKey(string $value, array $exclude, bool $expected): void {
    $this->assertSame($expected, self::tuiHasKey($value, $exclude));
  }

  public static function dataProviderTuiHasKey(): \Iterator {
    yield 'string_with_enter_key' => ['some text' . self::KEYS['ENTER'] . 'more text', [], TRUE];
    yield 'string_with_space_key' => ['text with' . self::KEYS['SPACE'] . 'space', [], TRUE];
    yield 'string_with_tab_key' => ['text' . self::KEYS['TAB'] . 'tab', [], TRUE];
    yield 'string_with_backspace_key' => ['text' . self::KEYS['BACKSPACE'] . 'backspace', [], TRUE];
    yield 'string_with_arrow_key' => ['text' . self::KEYS['UP'] . 'arrow', [], TRUE];
    yield 'string_without_keys' => ['plaintext', [], FALSE];
    yield 'empty_string' => ['', [], FALSE];
    yield 'string_with_excluded_key' => ['text' . self::KEYS['SPACE'] . 'space', [self::KEYS['SPACE']], FALSE];
    yield 'string_with_non_excluded_key' => ['text' . self::KEYS['ENTER'] . 'enter', [self::KEYS['SPACE']], TRUE];
    yield 'string_with_multiple_keys' => ['text' . self::KEYS['ENTER'] . self::KEYS['TAB'] . 'keys', [], TRUE];
    yield 'string_with_multiple_excluded_keys' => ['text' . self::KEYS['SPACE'] . 'space', [self::KEYS['SPACE'], self::KEYS['TAB']], FALSE];
    yield 'string_with_mixed_keys_and_exclusions' => ['text' . self::KEYS['ENTER'] . self::KEYS['SPACE'] . 'mixed', [self::KEYS['SPACE']], TRUE];
  }

  public function testTuiEntriesNonScalarException(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('TUI entry "invalid_entry" must be a scalar value. Got: array');

    self::tuiEntries([
      'invalid_entry' => ['not', 'scalar'],
    ]);
  }

}
