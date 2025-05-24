<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Traits;

use PHPUnit\Framework\AssertionFailedError;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * Trait ApplicationTrait.
 *
 * Runs a Symfony application from a command or a loader and provides assertions
 * for its output.
 *
 * @mixin \PHPUnit\Framework\TestCase
 */
trait ApplicationTrait {

  use ReflectionTrait;

  /**
   * Application instance.
   */
  protected ?Application $application = NULL;

  /**
   * Application tester instance.
   */
  protected ?ApplicationTester $applicationTester = NULL;

  /**
   * The current working directory for the application.
   *
   * Use NULL to use the working dir of the current PHP process.
   */
  protected ?string $applicationCwd = NULL;

  /**
   * Show output of the application.
   */
  protected bool $applicationShowOutput = FALSE;

  /**
   * Get the application instance.
   *
   * @return \Symfony\Component\Console\Application
   *   The application instance.
   */
  public function applicationGet(): Application {
    if ($this->application === NULL) {
      throw new \RuntimeException('Application is not initialized. Call applicationInit* first.');
    }
    return $this->application;
  }

  /**
   * Get the application tester.
   *
   * @return \Symfony\Component\Console\Tester\ApplicationTester
   *   The application tester.
   *
   * @throws \RuntimeException
   *   If the application tester is not initialized.
   */
  public function applicationGetTester(): ApplicationTester {
    if ($this->applicationTester === NULL) {
      throw new \RuntimeException('Application tester is not initialized. Call applicationInit* first.');
    }
    return $this->applicationTester;
  }

  /**
   * Tears down the application.
   *
   * Resets the application and tester variables.
   */
  protected function applicationTearDown(): void {
    $this->application = NULL;
    $this->applicationTester = NULL;
  }

  /**
   * Initialize application from a loader file.
   *
   * @param string $loader_path
   *   Path to the application loader file.
   *
   * @return \Symfony\Component\Console\Tester\ApplicationTester
   *   The initialized application tester.
   */
  public function applicationInitFromLoader(string $loader_path): ApplicationTester {
    if (!file_exists($loader_path)) {
      throw new \InvalidArgumentException(sprintf('Loader file not found: %s', $loader_path));
    }

    $this->application = require $loader_path;

    if (!$this->application instanceof Application) {
      throw new \InvalidArgumentException('Loader must return an instance of Application');
    }

    $this->application->setAutoExit(FALSE);
    $this->application->setCatchExceptions(FALSE);

    // Change the working directory if specified.
    if ($this->applicationCwd !== NULL) {
      $original_cwd = getcwd();
      if ($original_cwd !== FALSE) {
        chdir($this->applicationCwd);
        // Register shutdown function to restore original working directory.
        // @codeCoverageIgnoreStart
        register_shutdown_function(function () use ($original_cwd): void {
          chdir($original_cwd);
        });
        // @codeCoverageIgnoreEnd
      }
    }

    $this->applicationTester = new ApplicationTester($this->application);
    return $this->applicationTester;
  }

  /**
   * Initialize application from a command object or class.
   *
   * @param string|object $object_or_class
   *   Command class or object.
   * @param bool $is_single_command
   *   Is single command. Defaults to TRUE.
   *
   * @return \Symfony\Component\Console\Tester\ApplicationTester
   *   The initialized application tester.
   */
  public function applicationInitFromCommand(string|object $object_or_class, bool $is_single_command = TRUE): ApplicationTester {
    $this->application = new Application();

    $instance = is_object($object_or_class) ? $object_or_class : new $object_or_class();
    if (!$instance instanceof Command) {
      throw new \InvalidArgumentException('The provided object is not an instance of Command');
    }

    $this->application->add($instance);

    $name = $instance->getName();
    if (empty($name)) {
      $ret = self::getProtectedValue($instance, 'defaultName');
      if (empty($ret) || !is_string($ret)) {
        throw new \InvalidArgumentException('The provided object does not have a valid name');
      }
      $name = $ret;
    }

    $this->application->setDefaultCommand($name, $is_single_command);

    $this->application->setAutoExit(FALSE);
    $this->application->setCatchExceptions(FALSE);

    // Change the working directory if specified.
    if ($this->applicationCwd !== NULL) {
      $original_cwd = getcwd();
      if ($original_cwd !== FALSE) {
        chdir($this->applicationCwd);
        // Register shutdown function to restore original working directory.
        // @codeCoverageIgnoreStart
        register_shutdown_function(function () use ($original_cwd): void {
          chdir($original_cwd);
        });
        // @codeCoverageIgnoreEnd
      }
    }

    $this->applicationTester = new ApplicationTester($this->application);
    return $this->applicationTester;
  }

  /**
   * Run the console application.
   *
   * @param array<string, string|bool> $input
   *   Input arguments and options.
   * @param array<string, string|bool> $options
   *   Application tester options.
   * @param bool $expect_fail
   *   Whether a failure is expected. Defaults to FALSE.
   *
   * @return string
   *   Application output.
   */
  public function applicationRun(array $input = [], array $options = [], bool $expect_fail = FALSE): string {
    if ($this->applicationTester === NULL) {
      throw new \RuntimeException('Application is not initialized. Call applicationInit* first.');
    }

    $options += ['capture_stderr_separately' => TRUE];

    $output = '';
    try {
      $this->applicationTester->run($input, $options);
      $output = $this->applicationTester->getDisplay();

      if ($this->applicationShowOutput) {
        // @codeCoverageIgnoreStart
        fwrite(STDOUT, $output);
        // @codeCoverageIgnoreEnd
      }

      if ($this->applicationTester->getStatusCode() !== 0) {
        throw new \Exception(sprintf("Application exited with non-zero code.\nThe output was:\n%s\nThe error output was:\n%s", $this->applicationTester->getDisplay(), $this->applicationTester->getErrorOutput()));
      }

      if ($expect_fail) {
        throw new AssertionFailedError(sprintf("Application exited successfully but should not.\nThe output was:\n%s\nThe error output was:\n%s", $this->applicationTester->getDisplay(), $this->applicationTester->getErrorOutput()));
      }
    }
    catch (\RuntimeException $exception) {
      if (!$expect_fail) {
        throw new AssertionFailedError('Application exited with an error:' . PHP_EOL . $exception->getMessage());
      }
      $output = $exception->getMessage();
    }
    catch (\Exception $exception) {
      if (!$expect_fail) {
        throw new AssertionFailedError('Application exited with an error:' . PHP_EOL . $exception->getMessage());
      }
    }

    return $output;
  }

  /**
   * Asserts that the application executed successfully.
   */
  public function assertApplicationSuccessful(): void {
    $this->assertNotNull($this->applicationTester, 'Application is not initialized');
    $this->assertSame(0, $this->applicationTester->getStatusCode(), sprintf(
      'Application failed with exit code %d: %s%sOutput:%s%s',
      $this->applicationTester->getStatusCode(),
      $this->applicationTester->getErrorOutput(),
      PHP_EOL,
      PHP_EOL,
      $this->applicationTester->getDisplay()
    ));
  }

  /**
   * Asserts that the application failed to execute.
   */
  public function assertApplicationFailed(): void {
    $this->assertNotNull($this->applicationTester, 'Application is not initialized');
    $this->assertNotSame(0, $this->applicationTester->getStatusCode(), sprintf(
      'Application succeeded when failure was expected.%sOutput:%s%s',
      PHP_EOL,
      PHP_EOL,
      $this->applicationTester->getDisplay()
    ));
  }

  /**
   * Asserts that the application output contains expected string(s).
   *
   * @param array|string $expected
   *   Expected string or strings to check for in the application output.
   */
  public function assertApplicationOutputContains(array|string $expected): void {
    $this->assertNotNull($this->applicationTester, 'Application is not initialized');
    $output = $this->applicationTester->getDisplay();

    $expected = is_array($expected) ? $expected : [$expected];

    foreach ($expected as $value) {
      if (is_string($value)) {
        $this->assertStringContainsString($value, $output, sprintf(
          "Application output does not contain '%s'.%sOutput:%s%s",
          $value,
          PHP_EOL,
          PHP_EOL,
          $output
        ));
      }
    }
  }

  /**
   * Asserts that the application output does not contain expected string(s).
   *
   * @param array|string $expected
   *   String or array of strings that should not be in the application output.
   */
  public function assertApplicationOutputNotContains(array|string $expected): void {
    $this->assertNotNull($this->applicationTester, 'Application is not initialized');
    $output = $this->applicationTester->getDisplay();

    $expected = is_array($expected) ? $expected : [$expected];

    foreach ($expected as $value) {
      if (is_string($value)) {
        $this->assertStringNotContainsString($value, $output, sprintf(
          "Application output contains '%s' but should not.%sOutput:%s%s",
          $value,
          PHP_EOL,
          PHP_EOL,
          $output
        ));
      }
    }
  }

  /**
   * Asserts that the application error output contains an expected string.
   *
   * @param array|string $expected
   *   Expected string to check for in the application error output.
   */
  public function assertApplicationErrorOutputContains(array|string $expected): void {
    $this->assertNotNull($this->applicationTester, 'Application is not initialized');
    $output = $this->applicationTester->getErrorOutput();

    $expected = is_array($expected) ? $expected : [$expected];

    foreach ($expected as $value) {
      if (is_string($value)) {
        $this->assertStringContainsString($value, $output, sprintf(
          "Application error output does not contain '%s'.%sOutput:%s%s",
          $value,
          PHP_EOL,
          PHP_EOL,
          $output
        ));
      }
    }
  }

  /**
   * Asserts that the application error output does not contain a string.
   *
   * @param array|string $expected
   *   String that should not be in the error output.
   */
  public function assertApplicationErrorOutputNotContains(array|string $expected): void {
    $this->assertNotNull($this->applicationTester, 'Application is not initialized');
    $output = $this->applicationTester->getErrorOutput();

    $expected = is_array($expected) ? $expected : [$expected];

    foreach ($expected as $value) {
      if (is_string($value)) {
        $this->assertStringNotContainsString($value, $output, sprintf(
          "Application error output contains '%s' but should not.%sOutput:%s%s",
          $value,
          PHP_EOL,
          PHP_EOL,
          $output
        ));
      }
    }
  }

  /**
   * Asserts application output contains or does not contain specified strings.
   *
   * For strings that should NOT be in the output, prefix them with '---'.
   *
   * @param string|array $expected
   *   String or array of strings to check in the application output.
   *   Prefix with '---' for strings that should not be present.
   */
  public function assertApplicationOutputContainsOrNot(string|array $expected): void {
    $this->assertNotNull($this->applicationTester, 'Application is not initialized');
    $output = $this->applicationTester->getDisplay();

    $expected = is_array($expected) ? $expected : [$expected];

    foreach ($expected as $value) {
      if (str_starts_with($value, '---')) {
        $value = substr($value, 4);

        $this->assertStringNotContainsString($value, $output, sprintf(
          "Application output contains '%s' but should not.%sOutput:%s%s",
          $value,
          PHP_EOL,
          PHP_EOL,
          $output
        ));
      }
      else {
        $this->assertStringContainsString($value, $output, sprintf(
          "Application output does not contain '%s'.%sOutput:%s%s",
          $value,
          PHP_EOL,
          PHP_EOL,
          $output
        ));
      }
    }
  }

  /**
   * Asserts application error output contains or does not contain strings.
   *
   * For strings that should NOT be in the error output, prefix them
   * with '---'.
   *
   * @param string|array $expected
   *   String or array of strings to check in the application error output.
   *   Prefix with '---' for strings that should not be present.
   */
  public function assertApplicationErrorOutputContainsOrNot(string|array $expected): void {
    $this->assertNotNull($this->applicationTester, 'Application is not initialized');
    $output = $this->applicationTester->getErrorOutput();

    $expected = is_array($expected) ? $expected : [$expected];

    foreach ($expected as $value) {
      if (str_starts_with($value, '---')) {
        $value = substr($value, 4);

        $this->assertStringNotContainsString($value, $output, sprintf(
          "Application error output contains '%s' but should not.%sOutput:%s%s",
          $value,
          PHP_EOL,
          PHP_EOL,
          $output
        ));
      }
      else {
        $this->assertStringContainsString($value, $output, sprintf(
          "Application error output does not contain '%s'.%sOutput:%s%s",
          $value,
          PHP_EOL,
          PHP_EOL,
          $output
        ));
      }
    }
  }

  /**
   * Print the application info.
   *
   * @return string
   *   The application info.
   */
  public function applicationInfo(): string {
    if ($this->applicationTester === NULL) {
      return 'APPLICATION: Not initialized' . PHP_EOL;
    }
    $lines[] = 'APPLICATION';
    $lines[] = 'Output:';
    $output = $this->applicationTester->getDisplay();
    $lines[] = $output ?: '(no output)';
    $lines[] = 'Error:';
    $error = $this->applicationTester->getErrorOutput();
    $lines[] = $error ?: '(no error output)';
    return implode(PHP_EOL, $lines) . PHP_EOL;
  }

}
