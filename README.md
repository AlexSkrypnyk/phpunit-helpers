<p align="center">
  <a href="" rel="noopener">
  <img width=200px height=200px src="https://placehold.jp/000000/ffffff/200x200.png?text=PHPUnit+Helpers&css=%7B%22border-radius%22%3A%22%20100px%22%7D" alt="PHPUnit Helpers logo"></a>
</p>

<h1 align="center">Helpers to work with PHPUnit</h1>

<div align="center">

[![GitHub Issues](https://img.shields.io/github/issues/AlexSkrypnyk/phpunit-helpers.svg)](https://github.com/AlexSkrypnyk/phpunit-helpers/issues)
[![GitHub Pull Requests](https://img.shields.io/github/issues-pr/AlexSkrypnyk/phpunit-helpers.svg)](https://github.com/AlexSkrypnyk/phpunit-helpers/pulls)
[![Test PHP](https://github.com/AlexSkrypnyk/phpunit-helpers/actions/workflows/test-php.yml/badge.svg)](https://github.com/AlexSkrypnyk/phpunit-helpers/actions/workflows/test-php.yml)
[![codecov](https://codecov.io/gh/AlexSkrypnyk/phpunit-helpers/graph/badge.svg?token=7WEB1IXBYT)](https://codecov.io/gh/AlexSkrypnyk/phpunit-helpers)
![GitHub release (latest by date)](https://img.shields.io/github/v/release/AlexSkrypnyk/phpunit-helpers)
![LICENSE](https://img.shields.io/github/license/AlexSkrypnyk/phpunit-helpers)
![Renovate](https://img.shields.io/badge/renovate-enabled-green?logo=renovatebot)

</div>

---

## Features

| Name                                                    | Source                                         | Description                                                        |
|---------------------------------------------------------|------------------------------------------------|--------------------------------------------------------------------|
| [`UnitTestCase`](#UnitTestCase)                         | [src](src/UnitTestCase.php)                    | Base test class that includes essential traits for PHPUnit testing |
| [`AssertArrayTrait`](#assertarraytrait)                 | [src](src/Traits/AssertArrayTrait.php)         | Custom assertions for arrays                                       |
| [`ApplicationTrait`](#applicationtrait)                 | [src](src/Traits/ApplicationTrait.php)         | Test Symfony Console applications with assertions                  |
| [`EnvTrait`](#envtrait)                                 | [src](src/Traits/EnvTrait.php)                 | Manage environment variables during tests                          |
| [`LocationsTrait`](#locationstrait)                     | [src](src/Traits/LocationsTrait.php)           | Manage file system locations and directories for tests             |
| [`ProcessTrait`](#processtrait)                         | [src](src/Traits/ProcessTrait.php)             | Run and assert on command line processes during tests              |
| [`ReflectionTrait`](#reflectiontrait)                   | [src](src/Traits/ReflectionTrait.php)          | Access protected/private methods and properties                    |
| [`SerializableClosureTrait`](#serializableclosuretrait) | [src](src/Traits/SerializableClosureTrait.php) | Make closures serializable for use in data providers               |
| [`TuiTrait`](#tuitrait)                                 | [src](src/Traits/TuiTrait.php)                 | Interact with and test Textual User Interfaces                     |
| [`LoggerTrait`](#loggertrait)                           | [src](src/Traits/LoggerTrait.php)              | Comprehensive hierarchical logging system for test debugging       |

## Installation

    composer require --dev alexskrypnyk/phpunit-helpers

## Usage

This package provides a collection of traits that can be used in your PHPUnit
tests to make testing easier. Below is a description of each trait and how to
use it.

### `UnitTestCase`

The `UnitTestCase` class is the base class for unit tests. It includes the
`ReflectionTrait` and `LocationsTrait` to provide useful methods for testing.

```php
use AlexSkrypnyk\PhpunitHelpers\UnitTestCase;

class MyTest extends UnitTestCase {
  public function testExample() {
    // Test implementation that benefits from included traits.
  }
}
```

### `AssertArrayTrait`

The `AssertArrayTrait` provides custom assertions for arrays.

```php
use AlexSkrypnyk\PhpunitHelpers\Traits\AssertArrayTrait;
use PHPUnit\Framework\TestCase;

class MyAssertArrayTest extends TestCase {
  use AssertArrayTrait;

  public function testCustomAssertions() {
    $array = ['This is a test', 'Another value'];

    // Assert that a string is present in an array.
    $this->assertArrayContainsString('test', $array);
  }
}
```

### `ApplicationTrait`

The `ApplicationTrait` provides methods to test Symfony Console applications and their commands with comprehensive assertions.

```php
use AlexSkrypnyk\PhpunitHelpers\Traits\ApplicationTrait;
use PHPUnit\Framework\TestCase;

class MyApplicationTest extends TestCase {
  use ApplicationTrait;

  protected function setUp(): void {
    // Configure application behavior
    $this->applicationCwd = NULL; // Current working directory (NULL for current PHP process dir)
    $this->applicationShowOutput = FALSE; // Whether to show output during execution
  }

  protected function tearDown(): void {
    // Clean up application resources
    $this->applicationTearDown();
  }

  public function testConsoleApplication() {
    // Initialize application from a loader file
    $this->applicationInitFromLoader('/path/to/application_loader.php');

    // Or initialize from a command class
    $this->applicationInitFromCommand(MyCommand::class, TRUE); // TRUE for making it the default command

    // Run the application with input arguments and options
    $output = $this->applicationRun(
      ['argument1', '--option1=value1'],  // Input arguments and options
      ['capture_stderr_separately' => TRUE], // Application tester options
      FALSE // Whether a failure is expected (default: FALSE)
    );

    // Assert that the application executed successfully
    $this->assertApplicationSuccessful();

    // Or assert that the application failed
    $this->assertApplicationFailed();

    // Assert that the application output contains string(s)
    $this->assertApplicationOutputContains('Expected output');
    $this->assertApplicationOutputContains(['String1', 'String2']); // Can check multiple strings

    // Assert that the application output does not contain string(s)
    $this->assertApplicationOutputNotContains('Unexpected output');

    // Assert that the application error output contains string(s)
    $this->assertApplicationErrorOutputContains('Expected error');

    // Assert that the application error output does not contain string(s)
    $this->assertApplicationErrorOutputNotContains('Unexpected error');

    // Assert in one call - prefix with '---' for strings that should NOT be present
    $this->assertApplicationOutputContainsOrNot(['Expected', '---Unexpected']);
    $this->assertApplicationErrorOutputContainsOrNot(['Expected error', '---Unexpected error']);

    // Get debug info about the application (output, error output)
    echo $this->applicationInfo();
  }
}
```

### `EnvTrait`

The `EnvTrait` helps manage environment variables during tests.

```php
use AlexSkrypnyk\PhpunitHelpers\Traits\EnvTrait;
use PHPUnit\Framework\TestCase;

class MyEnvTest extends TestCase {
  use EnvTrait;

  public function testEnvironmentVariables() {
    // Set an environment variable.
    self::envSet('MY_VAR', 'value');

    // Set multiple environment variables.
    self::envSetMultiple(['VAR1' => 'value1', 'VAR2' => 'value2']);

    // Get an environment variable.
    $value = self::envGet('MY_VAR');

    // Check if an environment variable is set.
    $isSet = self::envIsSet('MY_VAR');

    // Unset an environment variable.
    self::envUnset('MY_VAR');

    // Unset all environment variables with a specific prefix.
    self::envUnsetPrefix('MY_');

    // Reset all environment variables.
    self::envReset();
  }
}
```

### `LocationsTrait`

The `LocationsTrait` provides methods to manage file system locations during
tests. It maintains a set of predefined directories as static properties.

```php
use AlexSkrypnyk\PhpunitHelpers\Traits\LocationsTrait;
use PHPUnit\Framework\TestCase;

class MyLocationsTest extends TestCase {
  use LocationsTrait;

  protected function setUp(): void {
    // Initialize test directories.
    self::locationsInit();

    // Now you can use the predefined directory properties:
    echo self::$root;      // Root directory of the project
    echo self::$fixtures;  // Path to fixtures directory
    echo self::$workspace; // Main workspace directory for test run
    echo self::$repo;      // Source directory for operations
    echo self::$sut;       // System Under Test directory where tests run
    echo self::$tmp;       // Temporary files directory

    // You can also print all locations with:
    echo self::locationsInfo();
  }

  protected function tearDown(): void {
    // Clean up test directories.
    self::locationsTearDown();
  }

  public function testFileOperations() {
    // Get a specific fixtures directory path.
    $fixturesDir = self::locationsFixtureDir('my-fixture');

    // Copy files to the SUT directory.
    $files = self::locationsCopyFilesToSut(['file1.txt', 'file2.txt']);

    // Files will be available in self::$sut directory
    $this->assertFileExists(self::$sut . '/file1.txt1234'); // Note: random suffix added by default
  }
}
```

### `ProcessTrait`

The `ProcessTrait` provides methods to run command line processes and assert on
their output and exit codes. It integrates with the Symfony Process component
for
safe and controlled command execution.

```php
use AlexSkrypnyk\PhpunitHelpers\Traits\ProcessTrait;
use PHPUnit\Framework\TestCase;

class MyProcessTest extends TestCase {
  use ProcessTrait;

  protected function setUp(): void {
    // Configure process behavior.
    $this->processCwd = NULL; // Current working directory (NULL for current PHP process dir).
    $this->processStreamOutput = FALSE; // Whether to stream an output during process execution.
  }

  protected function tearDown(): void {
    // Stop any running processes.
    $this->processTearDown();
  }

  public function testCommandExecution() {
    // Run a command with arguments, inputs, environment variables, and timeouts.
    // The method validates command safety and ensures all arguments are scalar values.
    $process = $this->processRun(
      'echo',                        // Command to execute
      ['Hello', 'World'],            // Command arguments
      ['Input1', 'Input2'],          // Interactive process inputs
      ['ENV_VAR' => 'value'],        // Environment variables
      60,                            // Process timeout in seconds
      30                             // Process idle timeout in seconds
    );

    // Assert that the process executed successfully.
    $this->assertProcessSuccessful();

    // Assert that the process failed.
    $this->assertProcessFailed();

    // Assert that the process output contains string(s).
    $this->assertProcessOutputContains('Hello World');
    $this->assertProcessOutputContains(['Hello', 'World']); // Can check for multiple strings

    // Assert that the process output does not contain string(s).
    $this->assertProcessOutputNotContains('Error');
    $this->assertProcessOutputNotContains(['Error1', 'Error2']); // Can check multiple strings

    // Assert that the process error output contains string(s).
    $this->assertProcessErrorOutputContains('Warning');
    $this->assertProcessErrorOutputContains(['Warning1', 'Warning2']); // Can check multiple strings

    // Assert that the process error output does not contain string(s).
    $this->assertProcessErrorOutputNotContains('Critical');
    $this->assertProcessErrorOutputNotContains(['Critical1', 'Critical2']); // Can check multiple strings

    // Assert in one call - prefix with '---' for strings that should NOT be present.
    $this->assertProcessOutputContainsOrNot(['Hello', '---Error']);
    $this->assertProcessErrorOutputContainsOrNot(['Warning', '---Critical']);

    // Assert that combined output (stdout + stderr) contains string(s).
    $this->assertProcessAnyOutputContains('Expected in either output');
    $this->assertProcessAnyOutputContains(['String1', 'String2']); // Can check multiple strings

    // Assert that combined output (stdout + stderr) does not contain string(s).
    $this->assertProcessAnyOutputNotContains('Should not appear anywhere');
    $this->assertProcessAnyOutputNotContains(['Unwanted1', 'Unwanted2']); // Can check multiple strings

    // Assert combined output in one call - prefix with '---' for strings that should NOT be present.
    $this->assertProcessAnyOutputContainsOrNot(['Expected', '---Unwanted']);

    // Get debug info about the process (output, error output).
    echo $this->processInfo();
  }
}
```

### `SerializableClosureTrait`

The `SerializableClosureTrait` makes closures serializable so they can be used
in
data providers. It works with both traditional closures and arrow functions.

```php
use AlexSkrypnyk\PhpunitHelpers\Traits\SerializableClosureTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MyClosureTest extends TestCase {
  use SerializableClosureTrait;

  #[DataProvider('dataProvider')]
  public function testWithClosure($callback) {
    // Unwrap the closure before using it.
    $callback = self::cu($callback);
    $result = $callback('argument');
    $this->assertEquals('ARGUMENT', $result);
  }

  public static function dataProvider() {
    return [
      'traditional' => [
        self::cw(function($value) {
          return strtoupper($value);
        })
      ],
      'arrow_function' => [
        self::cw(fn($value) => strtoupper($value))
      ],
    ];
  }
}
```

### `ReflectionTrait`

The `ReflectionTrait` provides methods to access and manipulate protected or
private members of classes or objects.

```php
use AlexSkrypnyk\PhpunitHelpers\Traits\ReflectionTrait;
use PHPUnit\Framework\TestCase;

class MyReflectionTest extends TestCase {
  use ReflectionTrait;

  public function testProtectedMethod() {
    $object = new SomeClass();

    // Call a protected method.
    $result = self::callProtectedMethod($object, 'protectedMethod', ['argument']);

    // Set a protected property value.
    self::setProtectedValue($object, 'protectedProperty', 'new value');

    // Get a protected property value.
    $value = self::getProtectedValue($object, 'protectedProperty');
  }
}
```

### `TuiTrait`

The `TuiTrait` provides constants and methods for interacting with a Textual
User Interface (TUI) during tests, handling keystroke simulation and input
entries. It supports both full-string input and character-by-character input
simulation.

```php
use AlexSkrypnyk\PhpunitHelpers\Traits\TuiTrait;
use PHPUnit\Framework\TestCase;

class MyTuiTest extends TestCase {
  use TuiTrait;

  public function testTuiInteraction() {
    // Define default entries for all sets.
    $default_entries = [
      'answer1' => 'value1',
      'answer2' => self::TUI_DEFAULT, // Use default value (empty string by default)
      'answer3' => 'value3',
      'answer4' => 'value4',
    ];

    // First entry set: use default for 'answer1'.
    $entries_set1 = ['answer1' => self::TUI_DEFAULT] + $default_entries;
    $processed_entries = self::tuiEntries($entries_set1);

    // Process entries with a custom default value instead of empty string
    $processed_entries = self::tuiEntries($entries_set1, 'custom_default');

    // Second entry set: skip 'answer2' (will not be included in the output).
    $entries_set2 = ['answer2' => self::TUI_SKIP] + $default_entries;
    $processed_entries = self::tuiEntries($entries_set2);

    // Convert entries to keystrokes for testing character-by-character input.
    // This is useful for testing TUIs that accept input one character at a time.
    $keystrokes = self::tuiKeystrokes($entries_set1);

    // Advanced keystroke conversion with options
    $keystrokes = self::tuiKeystrokes(
      $entries_set1,           // Entries to convert
      3,                       // Number of characters to clear before entering new text
      self::KEYS['TAB'],       // Custom accept key (Enter key by default)
      self::KEYS['BACKSPACE']  // Custom clear key (Backspace by default)
    );

    // Special keys are available via constants for simulating keyboard interaction.
    // Some examples of available special keys:
    $up_key = self::KEYS['UP'];
    $enter_key = self::KEYS['ENTER'];
    $tab_key = self::KEYS['TAB'];
    $esc_key = self::KEYS['ESCAPE'];
    $ctrl_c = self::KEYS['CTRL_C'];
    $backspace = self::KEYS['BACKSPACE'];

    // Arrow keys are supported in multiple formats for compatibility
    $up_arrow = self::KEYS['UP_ARROW']; // Alternative up arrow format

    // Yes/No entries are predefined for convenience.
    $yes = self::$tuiYes; // 'y' by default
    $no = self::$tuiNo;   // 'n' by default

    // Check if a value is a special key.
    $is_key = self::tuiIsKey($enter_key); // Returns true
    $is_key = self::tuiIsKey('not_a_key'); // Returns false
  }
}
```

### `LoggerTrait`

The `LoggerTrait` provides comprehensive hierarchical logging system for test debugging with step tracking, timing, and nested workflows. All logging is controlled by a verbose flag that defaults to `FALSE` for clean test output.

```php
use AlexSkrypnyk\PhpunitHelpers\Traits\LoggerTrait;
use PHPUnit\Framework\TestCase;

class MyLoggerTest extends TestCase {
  use LoggerTrait;

  protected function setUp(): void {
    // Enable verbose logging for debugging
    static::loggerSetVerbose(TRUE);
  }

  public function testHierarchicalWorkflow() {
    // Basic logging methods
    static::log('Basic debug message');
    static::logSection('SECTION TITLE', 'Section content');
    static::logFile('/path/to/file.txt', 'Optional description');
    
    // Step tracking with automatic timing and hierarchy
    static::logStepStart('Optional step message');
    static::logSubstep('Processing data');
    static::logNote('Additional context information');
    
    // Nested steps automatically create hierarchy
    $this->nestedStepMethod();
    
    static::logStepFinish('Step completed successfully');
    
    // Generate hierarchical summary with timing
    static::logStepSummary('WORKFLOW SUMMARY');
  }
  
  private function nestedStepMethod(): void {
    static::logStepStart('Nested operation');
    // Work here creates deeper hierarchy level
    static::logStepFinish('Nested operation complete');
  }
}
```

**Available logging methods:**
- `log(string)` - Basic message logging
- `logSection(string, ?string, bool, int)` - Bordered sections with optional double borders and custom width
- `logFile(string, ?string)` - File content logging with borders
- `logStepStart(?string)` - Begin step tracking with automatic method name detection
- `logStepFinish(?string)` - End step tracking with elapsed time calculation
- `logSubstep(string)` - Indented substep messages
- `logNote(string)` - Indented note messages
- `logStepSummary(?string, string)` - Hierarchical step summary table with configurable indentation
- `loggerSetVerbose(bool)` - Control verbose mode
- `loggerSetOutputStream(resource|null)` - Set custom output stream (defaults to STDERR)

**Key features:**
- Hierarchical step tracking with parent-child relationships
- Automatic timing and elapsed time calculation
- Configurable indentation for nested workflows
- Method name detection via debug_backtrace
- Memory-efficient step stack management
- Support for custom output streams and silent mode

**Example hierarchical summary output:**
```
===============================[ WORKFLOW SUMMARY ]===============================

+----------------------------------+----------+---------+
| Step                             | Status   | Elapsed |
+----------------------------------+----------+---------+
| stepDeploymentProcess            | Complete | 2m 15s  |
|   stepDatabaseMigration          | Complete | 1m 23s  |
|   stepApplicationDeployment      | Complete | 45s     |
|     stepAssetCompilation         | Complete | 32s     |
|   stepHealthChecks               | Complete | 27s     |
+----------------------------------+----------+---------+
```

### Using Multiple Traits

You can combine multiple traits in a single test class:

```php
use AlexSkrypnyk\PhpunitHelpers\Traits\AssertArrayTrait;
use AlexSkrypnyk\PhpunitHelpers\Traits\ApplicationTrait;
use AlexSkrypnyk\PhpunitHelpers\Traits\EnvTrait;
use AlexSkrypnyk\PhpunitHelpers\Traits\LoggerTrait;
use AlexSkrypnyk\PhpunitHelpers\Traits\ProcessTrait;
use AlexSkrypnyk\PhpunitHelpers\Traits\ReflectionTrait;
use AlexSkrypnyk\PhpunitHelpers\Traits\TuiTrait;
use PHPUnit\Framework\TestCase;

class MyCombinedTest extends TestCase {
  use AssertArrayTrait;
  use ApplicationTrait;
  use EnvTrait;
  use LoggerTrait;
  use ProcessTrait;
  use ReflectionTrait;
  use TuiTrait;

  // Your test methods.
}
```

Or simply extend the `UnitTestCase` class which already includes some of the
most useful traits:

```php
use AlexSkrypnyk\PhpunitHelpers\UnitTestCase;
use AlexSkrypnyk\PhpunitHelpers\Traits\EnvTrait;

class MyTest extends UnitTestCase {
  use EnvTrait; // Add additional traits as needed.

  // Your test methods will have access to all traits.
}
```

## Maintenance

    composer install
    composer lint
    composer test

---
_This repository was created using the [Scaffold](https://getscaffold.dev/)
project template_
