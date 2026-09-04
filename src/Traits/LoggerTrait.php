<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Traits;

/**
 * Provides logging functionality.
 *
 * @mixin \PHPUnit\Framework\TestCase
 */
trait LoggerTrait {

  /**
   * Controls whether logging output is enabled.
   */
  protected static bool $logIsVerbose = FALSE;

  /**
   * The output stream for logging. Defaults to STDERR if not set.
   *
   * @var resource|null
   */
  protected static $logOutputStream;

  /**
   * Stores all step tracking information.
   *
   * Each entry contains:
   * - 'name': The step name
   * - 'start_time': The start timestamp
   * - 'end_time': The end timestamp (null if not finished)
   * - 'elapsed': The elapsed time in seconds (null if not finished)
   * - 'parent_stack': Array of parent step names for hierarchy.
   */
  protected static array $logSteps = [];

  /**
   * Stack of currently running steps for hierarchy tracking.
   *
   * @var array<string>
   */
  protected static array $logStepStack = [];

  /**
   * Prefix for identifying step methods.
   */
  protected static string $logStepMethodPrefix = 'step';

  /**
   * Sets the verbose mode for logging.
   *
   * @param bool $verbose
   *   TRUE to enable verbose logging, FALSE to disable.
   */
  public static function logSetVerbose(bool $verbose): void {
    static::$logIsVerbose = $verbose;
  }

  /**
   * Sets the output stream for logging.
   *
   * @param resource|null $stream
   *   The stream resource to write to, or NULL to use STDERR.
   *
   * @throws \InvalidArgumentException
   *   When the provided stream is not a valid resource or NULL.
   */
  public static function logSetOutputStream($stream): void {
    if (!is_resource($stream) && $stream !== NULL) {
      throw new \InvalidArgumentException('Stream must be a valid resource or NULL.');
    }

    static::$logOutputStream = $stream;
  }

  /**
   * Gets the output stream for logging.
   *
   * @return resource
   *   The output stream resource (STDERR if not set).
   */
  protected static function logGetOutputStream() {
    return static::$logOutputStream ?: STDERR;
  }

  /**
   * Logs a message to the configured output stream.
   *
   * @param string $message
   *   The message to log.
   */
  public static function log(string $message): void {
    if (!static::$logIsVerbose) {
      return;
    }
    fwrite(static::logGetOutputStream(), PHP_EOL . $message . PHP_EOL);
  }

  /**
   * Logs a message within a bordered section.
   *
   * @param string $title
   *   The title to display in the header.
   * @param string|null $message
   *   Optional message content to display within the section.
   * @param bool $double_border
   *   Whether to use double border characters (=) instead of single (-).
   * @param int $min_width
   *   Minimum width of the section.
   *
   * @throws \InvalidArgumentException
   *   When the minimum width is not a positive integer.
   */
  public static function logSection(string $title, ?string $message = NULL, bool $double_border = FALSE, int $min_width = 60): void {
    if ($min_width <= 0) {
      throw new \InvalidArgumentException('Minimum width must be a positive integer.');
    }
    if (!static::$logIsVerbose) {
      return;
    }
    $delimiter_char = $double_border ? '=' : '-';
    $header_format = '[ %s ]';

    $header = sprintf($header_format, $title);
    $header_length = strlen($header);

    $min_padding = 3;
    $total_length = max($min_width, $header_length + (2 * $min_padding));

    $padding_length = ($total_length - $header_length) / 2;
    $left_padding = max($min_padding, (int) ceil($padding_length));
    $right_padding = max($min_padding, (int) floor($padding_length));

    $top_line = str_repeat($delimiter_char, $left_padding) . $header . str_repeat($delimiter_char, $right_padding);

    $bottom_line = str_repeat($delimiter_char, strlen($top_line));

    fwrite(static::logGetOutputStream(), PHP_EOL . $top_line . PHP_EOL);

    if (!empty($message)) {
      $message = trim($message);
      $header_width = strlen($top_line);

      $wrapped_lines = [];
      $lines = explode(PHP_EOL, $message);

      foreach ($lines as $line) {
        if (strlen($line) <= $header_width) {
          $wrapped_lines[] = $line;
        }
        else {
          $wrapped = wordwrap($line, $header_width, "\n", FALSE);
          $wrapped_lines = array_merge($wrapped_lines, explode("\n", $wrapped));
        }
      }

      foreach ($wrapped_lines as $line) {
        fwrite(static::logGetOutputStream(), $line . PHP_EOL);
      }

      fwrite(static::logGetOutputStream(), $bottom_line . PHP_EOL);
    }
  }

  /**
   * Logs the contents of a file with a bordered section header and footer.
   *
   * @param string $path
   *   The path to the file to log.
   * @param string|null $message
   *   Optional message to display with the file path.
   *
   * @throws \InvalidArgumentException
   *   When the file does not exist.
   * @throws \RuntimeException
   *   When the file cannot be read.
   */
  public static function logFile(string $path, ?string $message = NULL): void {
    if (!static::$logIsVerbose) {
      return;
    }
    if (!file_exists($path)) {
      throw new \InvalidArgumentException(sprintf('File %s does not exist.', $path));
    }

    $content = @file_get_contents($path);
    if ($content === FALSE) {
      throw new \RuntimeException(sprintf('Failed to read file %s.', $path));
    }

    $message = $message ? $message . ' (' . $path . ')' : $path;

    static::logSection('FILE START', $message);
    static::log($content);
    static::logSection('FILE END', $message);
  }

  /**
   * Logs the start of a step, inferred from the calling function name.
   *
   * @param string|null $message
   *   Optional message to log with the step start.
   */
  public static function logStepStart(?string $message = NULL): void {
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
    $step = $trace[1]['function'] ?? 'unknown';

    $parent_stack = static::$logStepStack;

    static::$logSteps[] = [
      'name' => $step,
      'start_time' => microtime(TRUE),
      'end_time' => NULL,
      'elapsed' => NULL,
      'parent_stack' => $parent_stack,
    ];

    static::$logStepStack[] = $step;

    $prefix = str_starts_with($step, static::$logStepMethodPrefix) ? sprintf('%s START', strtoupper(static::$logStepMethodPrefix)) : 'STEP START';
    static::logSection($prefix . ' | ' . $step, $message, FALSE, 40);

    if (static::$logIsVerbose) {
      fwrite(static::logGetOutputStream(), PHP_EOL);
    }
  }

  /**
   * Logs the completion of a step, inferred from the calling function name.
   *
   * @param string|null $message
   *   Optional message to log with the step completion.
   */
  public static function logStepFinish(?string $message = NULL): void {
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
    $step = $trace[1]['function'] ?? 'unknown';

    $prefix = str_starts_with($step, static::$logStepMethodPrefix) ? sprintf('%s DONE', strtoupper(static::$logStepMethodPrefix)) : 'STEP DONE';
    $section_title = $prefix . ' | ' . $step;
    $step_index = NULL;

    for ($i = count(static::$logSteps) - 1; $i >= 0; $i--) {
      if (static::$logSteps[$i]['name'] === $step && static::$logSteps[$i]['end_time'] === NULL) {
        $step_index = $i;
        break;
      }
    }

    if ($step_index !== NULL) {
      $end_time = microtime(TRUE);
      $elapsed_time = $end_time - static::$logSteps[$step_index]['start_time'];
      $formatted_time = static::logFormatElapsedTime($elapsed_time);

      static::$logSteps[$step_index]['end_time'] = $end_time;
      static::$logSteps[$step_index]['elapsed'] = $elapsed_time;

      $section_title .= ' | ' . $formatted_time;

      $stack_key = array_search($step, static::$logStepStack, TRUE);
      if ($stack_key !== FALSE) {
        array_splice(static::$logStepStack, (int) $stack_key, 1);
      }
    }

    static::logSection($section_title, $message, FALSE, 40);
    if (static::$logIsVerbose) {
      fwrite(static::logGetOutputStream(), PHP_EOL);
    }
  }

  /**
   * Logs a substep message with indentation.
   *
   * @param string $message
   *   The substep message to log.
   */
  public static function logSubstep(string $message): void {
    if (!static::$logIsVerbose) {
      return;
    }
    fwrite(static::logGetOutputStream(), '  --> ' . $message . PHP_EOL);
  }

  /**
   * Logs a note message with indentation.
   *
   * @param string $message
   *   The note message to log.
   */
  public static function logNote(string $message): void {
    if (!static::$logIsVerbose) {
      return;
    }
    fwrite(static::logGetOutputStream(), '    > ' . $message . PHP_EOL);
  }

  /**
   * Generates a summary table of all tracked steps as a string.
   *
   * @param string $indent
   *   Indentation string for hierarchical display (e.g., '  ', '    ', '\t').
   *
   * @return string
   *   The formatted summary table.
   */
  public static function logStepSummary(string $indent = '  '): string {
    if (empty(static::$logSteps)) {
      return '';
    }

    $lines = [];

    $name_lengths = array_map(function (array $step) use ($indent): int {
      $depth = count($step['parent_stack']);
      $indentation = str_repeat($indent, $depth);
      return strlen($indentation . $step['name']);
    }, static::$logSteps);
    $max_name_length = max($name_lengths);
    // Minimum for "Step" header.
    $max_name_length = max($max_name_length, 4);

    // "Complete" or "Running"
    $max_status_length = 8;
    // "Elapsed" header length
    $max_elapsed_length = 7;

    $header = sprintf(
      '| %-' . $max_name_length . 's | %-' . $max_status_length . 's | %-' . $max_elapsed_length . 's |',
      'Step',
      'Status',
      'Elapsed'
    );

    $separator = '+' . str_repeat('-', $max_name_length + 2) . '+' .
      str_repeat('-', $max_status_length + 2) . '+' .
      str_repeat('-', $max_elapsed_length + 2) . '+';

    $lines[] = $separator;
    $lines[] = $header;
    $lines[] = $separator;

    foreach (static::$logSteps as $step) {
      $status = $step['end_time'] === NULL ? 'Running' : 'Complete';
      $elapsed = $step['elapsed'] === NULL ? '-' : static::logFormatElapsedTime($step['elapsed']);

      $depth = count($step['parent_stack']);
      $indentation = str_repeat($indent, $depth);
      $indented_name = $indentation . $step['name'];

      $row = sprintf(
        '| %-' . $max_name_length . 's | %-' . $max_status_length . 's | %-' . $max_elapsed_length . 's |',
        $indented_name,
        $status,
        $elapsed
      );

      $lines[] = $row;
    }

    $lines[] = $separator;
    $lines[] = '';

    return implode(PHP_EOL, $lines);
  }

  /**
   * Formats elapsed time into a human-readable string.
   *
   * @param float $elapsed_seconds
   *   The elapsed time in seconds.
   *
   * @return string
   *   The formatted time string (e.g., "1m 23s" or "45s").
   */
  protected static function logFormatElapsedTime(float $elapsed_seconds): string {
    $total_seconds = (int) round($elapsed_seconds);

    if ($total_seconds < 60) {
      return $total_seconds . 's';
    }

    $minutes = (int) floor($total_seconds / 60);
    $seconds = $total_seconds % 60;

    if ($seconds === 0) {
      return $minutes . 'm';
    }

    return $minutes . 'm ' . $seconds . 's';
  }

  /**
   * Print the logger info.
   *
   * @return string
   *   The locations' info.
   */
  public function logInfo(): string {
    $lines = '';
    $lines .= 'STEP SUMMARY' . PHP_EOL;
    return $lines . static::logStepSummary();
  }

}
