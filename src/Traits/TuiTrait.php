<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Traits;

/**
 * Trait TuiTrait.
 *
 * Provides constants and methods for interacting with a
 * Textual User Interface (TUI).
 *
 * A "keystroke" is a single key or special key.
 * An "entry" consists of one or more keystrokes that form a complete input.
 *
 * Different TUIs may accept entries in various ways:
 * - All at once (e.g., a full string followed by Enter).
 * - One keystroke at a time (e.g., character-by-character, including
 *   navigation or confirmation keys).
 * This trait supports both approaches.
 *
 * @code
 * // Default entries applied to all sets.
 * $default_entries = [
 *   'answer1' => 'value1',
 *   'answer2' => static::TUI_DEFAULT,
 *   'answer3' => 'value3',
 *   'answer4' => 'value4',
 * ];
 *
 * // First entry set: use default for 'answer1'.
 * $entries_set1 = ['answer1' => static::TUI_DEFAULT] + $default_entries;
 * my_tui_run(static::tuiEntries($entries_set1));
 *
 * // Second entry set: skip 'answer1' (will not be sent to the TUI).
 * $entries_set2 = ['answer1' => static::TUI_SKIP] + $default_entries;
 * my_tui_run(static::tuiEntries($entries_set2));
 *
 * // Convert entries to keystrokes if needed.
 * my_tui_run_keystrokes(static::tuiKeystrokes($entries_set1));
 * @endcode
 */
trait TuiTrait {

  /**
   * The TUI keys and their corresponding escape sequences.
   *
   * @var array
   */
  public const KEYS = [
    'UP' => "\e[A",
    'SHIFT_UP' => "\e[1;2A",
    'DOWN' => "\e[B",
    'SHIFT_DOWN' => "\e[1;2B",
    'RIGHT' => "\e[C",
    'LEFT' => "\e[D",
    'UP_ARROW' => "\eOA",
    'DOWN_ARROW' => "\eOB",
    'RIGHT_ARROW' => "\eOC",
    'LEFT_ARROW' => "\eOD",
    'ESCAPE' => "\e",
    'DELETE' => "\e[3~",
    'BACKSPACE' => "\177",
    'ENTER' => "\n",
    'SPACE' => ' ',
    'TAB' => "\t",
    'SHIFT_TAB' => "\e[Z",
    'HOME' => ["\e[1~", "\eOH", "\e[H", "\e[7~"],
    'END' => ["\e[4~", "\eOF", "\e[F", "\e[8~"],
    'CTRL_C' => "\x03",
    'CTRL_P' => "\x10",
    'CTRL_N' => "\x0E",
    'CTRL_F' => "\x06",
    'CTRL_B' => "\x02",
    'CTRL_H' => "\x08",
    'CTRL_A' => "\x01",
    'CTRL_D' => "\x04",
    'CTRL_E' => "\x05",
    'CTRL_U' => "\x15",
  ];

  /**
   * Defines a default value for the TUI entry.
   *
   * @var string
   */
  const TUI_DEFAULT = '__DEFAULT__';

  /**
   * Defines a value to skip the entry.
   *
   * Usually used in entries that may not exist based on some other conditions.
   * An entry will not be included in the resulting array of entries if it has
   * this value.
   *
   * @var null|string
   */
  const TUI_SKIP = '__SKIP__';

  /**
   * The "Yes" entry.
   *
   * Classes may override this value to use a different key for "yes" entry.
   *
   * @var string
   */
  protected static $tuiYes = 'y';

  /**
   * The "No" entry.
   *
   * Classes may override this value to use a different key for "no" entry.
   *
   * @var string
   */
  protected static $tuiNo = 'n';

  /**
   * Process TUI entries.
   *
   * @param array $entries
   *   The TUI entries to process.
   * @param string $default
   *   The default value to use for TUI_DEFAULT entries. Default is an empty
   *   string.
   *
   * @return array
   *   The processed TUI entries.
   */
  protected static function tuiEntries(array $entries, string $default = ''): array {
    foreach ($entries as $key => $value) {
      if (!is_scalar($value)) {
        throw new \InvalidArgumentException(sprintf('TUI entry "%s" must be a scalar value. Got: %s', $key, gettype($value)));
      }

      if ($value === static::TUI_SKIP) {
        unset($entries[$key]);
        continue;
      }

      $entries[$key] = $value === static::TUI_DEFAULT ? $default : $value;
    }

    return $entries;
  }

  /**
   * Process TUI entries and convert them to keystrokes.
   *
   * @param array $entries
   *   The TUI entries to process.
   * @param int $clear_size
   *   The number of characters to clear before entering the new entry.
   * @param string|null $accept_key
   *   The key to use for accepting the entry. Default is the Enter key.
   * @param string|null $clear_key
   *   The key to use for clearing the entry. Default is the Backspace key.
   *
   * @return array
   *   The processed TUI entries as keystrokes.
   */
  protected static function tuiKeystrokes(array $entries, int $clear_size = 0, ?string $accept_key = NULL, ?string $clear_key = NULL): array {
    $accept_key = $accept_key ?? static::KEYS['ENTER'];
    $clear_key = $clear_key ?? static::KEYS['BACKSPACE'];

    $entries = static::tuiEntries($entries, static::TUI_DEFAULT);

    $keystrokes = [];

    foreach ($entries as $entry) {
      // @codeCoverageIgnoreStart
      if ($entry === static::TUI_SKIP) {
        throw new \RuntimeException('Unexpected TUI_SKIP entry. This should have been filtered out.');
      }
      // @codeCoverageIgnoreEnd
      if ($entry === static::TUI_DEFAULT) {
        $keystrokes[] = $accept_key;
        continue;
      }

      // Preserve the entry as-is if it is a special key.
      if (self::tuiIsKey($entry)) {
        $keystrokes[] = $entry;
        continue;
      }

      // Clear the existing TUI value, if any, one character at a time.
      if ($clear_size > 0) {
        $keystrokes = array_merge($keystrokes, array_fill(0, $clear_size, $clear_key));
      }

      // Enter the entry, one character at a time.
      $entry_keystrokes = extension_loaded('mbstring') ? mb_str_split($entry) : str_split($entry);

      // Add the accept key at the end of the entry if it is not already there.
      if (end($entry_keystrokes) !== $accept_key) {
        $entry_keystrokes[] = $accept_key;
      }

      $keystrokes = array_merge($keystrokes, $entry_keystrokes);
    }

    return $keystrokes;
  }

  /**
   * Check if the given value is a special key.
   *
   * @param string $value
   *   The value to check.
   *
   * @return bool
   *   TRUE if the value is a special key, FALSE otherwise.
   */
  protected static function tuiIsKey(string $value): bool {
    $flattened_keys = [];
    foreach (static::KEYS as $seq) {
      if (is_array($seq)) {
        $flattened_keys = array_merge($flattened_keys, $seq);
      }
      else {
        $flattened_keys[] = $seq;
      }
    }
    return in_array($value, $flattened_keys, TRUE);
  }

}
