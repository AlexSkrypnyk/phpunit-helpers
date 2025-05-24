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
  public static $tuiYes = 'y';

  /**
   * The "No" entry.
   *
   * Classes may override this value to use a different key for "no" entry.
   *
   * @var string
   */
  public static $tuiNo = 'n';

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
  public static function tuiEntries(array $entries, string $default = ''): array {
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
  public static function tuiKeystrokes(array $entries, int $clear_size = 0, ?string $accept_key = NULL, ?string $clear_key = NULL): array {
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

      $entry_keystrokes = [];

      // If an entry has a special key - we consider that any additional
      // functionality like clearing the existing entry or appending an accept
      // key is handled by the consumer.
      $skip_additional_processing = static::tuiHasKey($entry);

      // Clear the existing TUI value, if any, one character at a time.
      if (!$skip_additional_processing && $clear_size > 0) {
        $entry_keystrokes = array_fill(0, $clear_size, $clear_key);
      }

      $split_entry = self::tuiEntryToKeystroke($entry);
      $entry_keystrokes = array_merge($entry_keystrokes, $split_entry);

      // Add the accept key at the end of the entry if it is not already there.
      if (!$skip_additional_processing && end($entry_keystrokes) !== $accept_key) {
        $entry_keystrokes[] = $accept_key;
      }

      $keystrokes = array_merge($keystrokes, $entry_keystrokes);
    }

    return $keystrokes;
  }

  /**
   * Convert a TUI entry to keystrokes.
   *
   * @param string $entry
   *   The TUI entry to convert.
   *
   * @return array
   *   The converted keystrokes.
   */
  public static function tuiEntryToKeystroke(string $entry): array {
    $keystrokes = [];

    $keys = static::tuiKeysFlattened();

    // Sort by length to match longer sequences first.
    usort($keys, fn($a, $b): int => strlen($b) <=> strlen($a));

    $entry_length = strlen($entry);

    $offset = 0;
    while ($offset < $entry_length) {
      $matched = FALSE;

      foreach ($keys as $key) {
        $key_length = strlen($key);
        if (substr($entry, $offset, $key_length) === $key) {
          $keystrokes[] = $key;
          $offset += $key_length;
          $matched = TRUE;
          break;
        }
      }

      if (!$matched) {
        $keystrokes[] = $entry[$offset];
        $offset++;
      }
    }

    return $keystrokes;
  }

  /**
   * Check if the given value contains a special key.
   *
   * @param string $value
   *   The value to check.
   *
   * @return bool
   *   TRUE if the value contains a special key, FALSE otherwise.
   */
  public static function tuiHasKey(string $value): bool {
    $flattened_keys = static::tuiKeysFlattened();

    foreach ($flattened_keys as $key_seq) {
      if (str_contains($value, $key_seq)) {
        return TRUE;
      }
    }

    return FALSE;
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
  public static function tuiIsKey(string $value): bool {
    return in_array($value, static::tuiKeysFlattened(), TRUE);
  }

  /**
   * Flatten the TUI keys array.
   *
   * @return array
   *   Flattened array of TUI keys.
   */
  protected static function tuiKeysFlattened(): array {
    $flattened_keys = [];
    foreach (static::KEYS as $seq) {
      if (is_array($seq)) {
        $flattened_keys = array_merge($flattened_keys, $seq);
      }
      else {
        $flattened_keys[] = $seq;
      }
    }
    return $flattened_keys;
  }

}
