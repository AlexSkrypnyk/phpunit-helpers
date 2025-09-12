<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Traits;

/**
 * Provides logging functionality.
 */
trait LoggerTrait {

  /**
   * Controls whether logging output is enabled.
   */
  protected static bool $loggerIsVerbose = FALSE;

  /**
   * The output stream for logging. Defaults to STDERR if not set.
   *
   * @var resource|null
   */
  protected static $loggerOutputStream;

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
  protected static array $loggerSteps = [];

  /**
   * Stack of currently running steps for hierarchy tracking.
   *
   * @var array<string>
   */
  protected static array $loggerStepStack = [];

  /**
   * Sets the verbose mode for logging.
   *
   * @param bool $verbose
   *   TRUE to enable verbose logging, FALSE to disable.
   */
  public static function loggerSetVerbose(bool $verbose): void {
    static::$loggerIsVerbose = $verbose;
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
  public static function loggerSetOutputStream($stream): void {
    if (!is_resource($stream) && $stream !== NULL) {
      throw new \InvalidArgumentException('Stream must be a valid resource or NULL.');
    }

    static::$loggerOutputStream = $stream;
  }

  /**
   * Gets the output stream for logging.
   *
   * @return resource
   *   The output stream resource (STDERR if not set).
   */
  protected static function getOutputStream() {
    return static::$loggerOutputStream ?: STDERR;
  }

  /**
   * Logs a message to the configured output stream.
   *
   * @param string $message
   *   The message to log.
   */
  public static function log(string $message): void {
    if (!static::$loggerIsVerbose) {
      return;
    }
    fwrite(static::getOutputStream(), PHP_EOL . $message . PHP_EOL);
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
   */
  public static function logSection(string $title, ?string $message = NULL, bool $double_border = FALSE, int $min_width = 60): void {
    if ($min_width <= 0) {
      throw new \InvalidArgumentException('Minimum width must be a positive integer.');
    }
    if (!static::$loggerIsVerbose) {
      return;
    }
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

    // Ensure minimum 3 characters on each side of the header.
    $min_padding = 3;
    $total_length = max($min_width, $header_length + (2 * $min_padding));

    // Calculate padding for centering.
    $padding_length = ($total_length - $header_length) / 2;
    $left_padding = max($min_padding, (int) ceil($padding_length));
    $right_padding = max($min_padding, (int) floor($padding_length));

    // Create the top delimiter line.
    $top_line = str_repeat($delimiter_char, $left_padding) . $header . str_repeat($delimiter_char, $right_padding);

    // Create the bottom delimiter line.
    $bottom_line = str_repeat($delimiter_char, strlen($top_line));

    fwrite(static::getOutputStream(), PHP_EOL . $top_line . PHP_EOL);

    if (!empty($message)) {
      $message = trim($message);
      $header_width = strlen($top_line);

      // Word wrap the message to match the header width.
      $wrapped_lines = [];
      $lines = explode(PHP_EOL, $message);

      foreach ($lines as $line) {
        if (strlen($line) <= $header_width) {
          $wrapped_lines[] = $line;
        }
        else {
          // Use wordwrap to split long lines.
          $wrapped = wordwrap($line, $header_width, "\n", FALSE);
          $wrapped_lines = array_merge($wrapped_lines, explode("\n", $wrapped));
        }
      }

      // Output the wrapped message lines.
      foreach ($wrapped_lines as $line) {
        fwrite(static::getOutputStream(), $line . PHP_EOL);
      }

      fwrite(static::getOutputStream(), $bottom_line . PHP_EOL);
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
    if (!static::$loggerIsVerbose) {
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

    // Capture current parent stack for hierarchy.
    $parent_stack = static::$loggerStepStack;

    // Add step to tracking array with hierarchy information.
    static::$loggerSteps[] = [
      'name' => $step,
      'start_time' => microtime(TRUE),
      'end_time' => NULL,
      'elapsed' => NULL,
      'parent_stack' => $parent_stack,
    ];

    // Push current step onto the stack for nested steps.
    static::$loggerStepStack[] = $step;

    static::logSection('STEP START | ' . $step, $message, FALSE, 40);
    if (static::$loggerIsVerbose) {
      fwrite(static::getOutputStream(), PHP_EOL);
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

    // Find the most recent unfinished step with matching name.
    $section_title = 'STEP DONE | ' . $step;
    $step_index = NULL;

    // Search backwards for the most recent matching step.
    for ($i = count(static::$loggerSteps) - 1; $i >= 0; $i--) {
      if (static::$loggerSteps[$i]['name'] === $step && static::$loggerSteps[$i]['end_time'] === NULL) {
        $step_index = $i;
        break;
      }
    }

    if ($step_index !== NULL) {
      $end_time = microtime(TRUE);
      $elapsed_time = $end_time - static::$loggerSteps[$step_index]['start_time'];
      $formatted_time = static::formatElapsedTime($elapsed_time);

      // Update the step entry with completion info.
      static::$loggerSteps[$step_index]['end_time'] = $end_time;
      static::$loggerSteps[$step_index]['elapsed'] = $elapsed_time;

      $section_title .= ' | ' . $formatted_time;

      // Pop the step from the stack when it finishes.
      // Find and remove the step from the stack.
      $stack_key = array_search($step, static::$loggerStepStack, TRUE);
      if ($stack_key !== FALSE) {
        array_splice(static::$loggerStepStack, (int) $stack_key, 1);
      }
    }

    static::logSection($section_title, $message, FALSE, 40);
    if (static::$loggerIsVerbose) {
      fwrite(static::getOutputStream(), PHP_EOL);
    }
  }

  /**
   * Logs a substep message with indentation.
   *
   * @param string $message
   *   The substep message to log.
   */
  public static function logSubstep(string $message): void {
    if (!static::$loggerIsVerbose) {
      return;
    }
    fwrite(static::getOutputStream(), '  --> ' . $message . PHP_EOL);
  }

  /**
   * Logs a note message with indentation.
   *
   * @param string $message
   *   The note message to log.
   */
  public static function logNote(string $message): void {
    if (!static::$loggerIsVerbose) {
      return;
    }
    fwrite(static::getOutputStream(), '    > ' . $message . PHP_EOL);
  }

  /**
   * Generates a summary table of all tracked steps as a string.
   *
   * @param string $indent
   *   Indentation string for hierarchical display (e.g., '  ', '    ', '\t').
   */
  public static function logStepSummary(string $indent = '  '): string {
    if (empty(static::$loggerSteps)) {
      return '';
    }

    $lines = [];

    // Calculate column widths including indentation.
    $name_lengths = array_map(function (array $step) use ($indent): int {
      $depth = count($step['parent_stack']);
      $indentation = str_repeat($indent, $depth);
      return strlen($indentation . $step['name']);
    }, static::$loggerSteps);
    $max_name_length = max($name_lengths);
    // Minimum for "Step" header.
    $max_name_length = max($max_name_length, 4);

    // "Complete" or "Running"
    $max_status_length = 8;
    // "Elapsed" header length
    $max_elapsed_length = 7;

    // Create table header.
    $header = sprintf(
      '| %-' . $max_name_length . 's | %-' . $max_status_length . 's | %-' . $max_elapsed_length . 's |',
      'Step',
      'Status',
      'Elapsed'
    );

    $separator = '+' . str_repeat('-', $max_name_length + 2) . '+' .
      str_repeat('-', $max_status_length + 2) . '+' .
      str_repeat('-', $max_elapsed_length + 2) . '+';

    // Build table output.
    $lines[] = $separator;
    $lines[] = $header;
    $lines[] = $separator;

    // Create table rows with hierarchical indentation.
    foreach (static::$loggerSteps as $step) {
      $status = $step['end_time'] === NULL ? 'Running' : 'Complete';
      $elapsed = $step['elapsed'] === NULL ? '-' : static::formatElapsedTime($step['elapsed']);

      // Calculate depth and add indentation.
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
  protected static function formatElapsedTime(float $elapsed_seconds): string {
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
  public function loggerInfo(): string {
    $lines = '';
    $lines .= 'STEP SUMMARY' . PHP_EOL;
    return $lines . static::logStepSummary();
  }

}
