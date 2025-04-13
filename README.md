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

- [`UnitTestBase`](#unittestbase)([src](src/UnitTestBase.php)) - Base test class that includes
  essential traits for PHPUnit testing
- Traits:
  - [`AssertArrayTrait`](#assertarraytrait)([src](src/Traits/AssertArrayTrait.php)) - Custom assertions
    for arrays
  - [`EnvTrait`](#envtrait)([src](src/Traits/EnvTrait.php)) - Manage environment variables during
    tests
  - [`LocationsTrait`](#locationstrait)([src](src/Traits/LocationsTrait.php)) - Manage file system
    locations and directories for tests
  - [`ReflectionTrait`](#reflectiontrait)([src](src/Traits/ReflectionTrait.php)) - Access
    protected/private methods and properties
  - [`SerializableClosureTrait`](#serializableclosuretrait)([src](src/Traits/SerializableClosureTrait.php)) - Make closures
    serializable for use in data providers

## Installation

    composer require --dev alexskrypnyk/phpunit-helpers

## Usage

This package provides a collection of traits that can be used in your PHPUnit
tests to make testing easier. Below is a description of each trait and how to
use it.

### `UnitTestBase`

The `UnitTestBase` class is the base class for unit tests. It includes the
`ReflectionTrait` and `LocationsTrait` to provide useful methods for testing.

```php
use AlexSkrypnyk\PhpunitHelpers\UnitTestBase;

class MyTest extends UnitTestBase {
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

### `SerializableClosureTrait`

The `SerializableClosureTrait` makes closures serializable so they can be used in
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

### Using Multiple Traits

You can combine multiple traits in a single test class:

```php
use AlexSkrypnyk\PhpunitHelpers\Traits\AssertArrayTrait;
use AlexSkrypnyk\PhpunitHelpers\Traits\EnvTrait;
use AlexSkrypnyk\PhpunitHelpers\Traits\ReflectionTrait;
use PHPUnit\Framework\TestCase;

class MyCombinedTest extends TestCase {
  use AssertArrayTrait;
  use EnvTrait;
  use ReflectionTrait;

  // Your test methods.
}
```

Or simply extend the `UnitTestBase` class which already includes some of the
most useful traits:

```php
use AlexSkrypnyk\PhpunitHelpers\UnitTestBase;
use AlexSkrypnyk\PhpunitHelpers\Traits\EnvTrait;

class MyTest extends UnitTestBase {
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
