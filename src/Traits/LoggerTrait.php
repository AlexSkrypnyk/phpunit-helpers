<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Traits;

/**
 * Provides logging functionality.
 */
trait LoggerTrait {

  public static function log(string $message): void {
    fwrite(STDERR, PHP_EOL . $message . PHP_EOL);
  }

  public static function logStepStart(?string $message = NULL): void {
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
    $step = $trace[1]['function'] ?? 'unknown';

    static::logBox('STEP START | ' . $step, $message, FALSE, 40);
  }

  public static function logStepFinish(?string $message = NULL): void {
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
    $step = $trace[1]['function'] ?? 'unknown';

    static::logBox('STEP DONE | ' . $step, $message, FALSE, 40);
  }

  public static function logSubstep(string $message): void {
    static::log('  --> ' . $message . PHP_EOL);
  }

  public static function logFile(string $path, ?string $message = NULL): void {
    if (!file_exists($path)) {
      throw new \InvalidArgumentException(sprintf('File %s does not exist.', $path));
    }

    $content = file_get_contents($path);
    if ($content === FALSE) {
      throw new \RuntimeException(sprintf('Failed to read file %s.', $path));
    }

    $message = $message ? $message . ' (' . $path . ')' : $path;

    static::logBox('FILE START', $message);
    static::log($content);
    static::logBox('FILE END', $message);
  }

  public static function logBox(string $title, ?string $message = NULL, bool $double_border = FALSE, int $min_width = 60): void {
    if ($double_border) {
      $delimiter_char = '=';
      $header_format = '[ %s ]';
    }
    else {
      $delimiter_char = '-';
      $header_format = '[ %s ]';
    }

    $header = sprintf($header_format, $title);
    $header_length = strlen($header);

    // Ensure minimum 3 characters on each side of the header
    $min_padding = 3;
    $total_length = max($min_width, $header_length + (2 * $min_padding));

    // Calculate padding for centering
    $padding_length = ($total_length - $header_length) / 2;
    $left_padding = max($min_padding, (int) floor($padding_length));
    $right_padding = max($min_padding, (int) ceil($padding_length));

    // Create the top delimiter line
    $top_line = str_repeat($delimiter_char, $left_padding) . $header . str_repeat($delimiter_char, $right_padding);

    // Create the bottom delimiter line
    $bottom_line = str_repeat($delimiter_char, strlen($top_line));

    fwrite(STDERR, PHP_EOL . $top_line . PHP_EOL);

    if (!empty($message)) {
      $message = trim($message);
      $header_width = strlen($top_line);

      // Word wrap the message to match the header width
      $wrapped_lines = [];
      $lines = explode(PHP_EOL, $message);

      foreach ($lines as $line) {
        if (strlen($line) <= $header_width) {
          $wrapped_lines[] = $line;
        }
        else {
          // Use wordwrap to split long lines
          $wrapped = wordwrap($line, $header_width, "\n", FALSE);
          $wrapped_lines = array_merge($wrapped_lines, explode("\n", $wrapped));
        }
      }

      // Output the wrapped message lines
      foreach ($wrapped_lines as $line) {
        fwrite(STDERR, $line . PHP_EOL);
      }

      fwrite(STDERR, $bottom_line . PHP_EOL);
    }
  }

}
