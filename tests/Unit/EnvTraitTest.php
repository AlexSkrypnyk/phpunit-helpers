<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Tests\Unit;

use AlexSkrypnyk\PhpunitHelpers\Traits\EnvTrait;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\TestCase;

#[CoversTrait(EnvTrait::class)]
final class EnvTraitTest extends TestCase {

  use EnvTrait;

  protected function setUp(): void {
    parent::setUp();
    self::envReset();
  }

  protected function tearDown(): void {
    self::envReset();
    parent::tearDown();
  }

  public function testEnvSetAndGet(): void {
    $name = 'TEST_ENV_VAR';
    $value = 'test_value';

    self::envSet($name, $value);

    $this->assertSame($value, self::envGet($name));
    $this->assertTrue(self::envIsSet($name));
    $this->assertFalse(self::envIsUnset($name));
  }

  public function testEnvSetMultiple(): void {
    $vars = [
      'TEST_ENV_VAR1' => 'value1',
      'TEST_ENV_VAR2' => 'value2',
      'TEST_ENV_VAR3' => 'value3',
    ];

    self::envSetMultiple($vars);

    foreach ($vars as $name => $value) {
      $this->assertSame($value, self::envGet($name));
      $this->assertTrue(self::envIsSet($name));
    }
  }

  public function testEnvUnset(): void {
    $name = 'TEST_ENV_VAR';
    $value = 'test_value';

    self::envSet($name, $value);
    $this->assertTrue(self::envIsSet($name));

    self::envUnset($name);

    $this->assertFalse(self::envIsSet($name));
    $this->assertTrue(self::envIsUnset($name));
  }

  public function testEnvUnsetMultiple(): void {
    $vars = [
      'TEST_ENV_VAR1' => 'value1',
      'TEST_ENV_VAR2' => 'value2',
      'TEST_ENV_VAR3' => 'value3',
      'TEST_ENV_VAR4' => 'value4',
    ];

    self::envSetMultiple($vars);

    foreach (array_keys($vars) as $name) {
      $this->assertTrue(self::envIsSet($name));
    }

    self::envUnsetMultiple(['TEST_ENV_VAR1', 'TEST_ENV_VAR2', 'TEST_ENV_VAR3']);

    $this->assertFalse(self::envIsSet('TEST_ENV_VAR1'));
    $this->assertFalse(self::envIsSet('TEST_ENV_VAR2'));
    $this->assertFalse(self::envIsSet('TEST_ENV_VAR3'));
    $this->assertTrue(self::envIsSet('TEST_ENV_VAR4'));
  }

  public function testEnvUnsetPrefix(): void {
    $vars = [
      'TEST_PREFIX_VAR1' => 'value1',
      'TEST_PREFIX_VAR2' => 'value2',
      'OTHER_VAR' => 'value3',
    ];

    self::envSetMultiple($vars);

    self::envUnsetPrefix('TEST_PREFIX_');

    $this->assertFalse(self::envIsSet('TEST_PREFIX_VAR1'));
    $this->assertFalse(self::envIsSet('TEST_PREFIX_VAR2'));
    $this->assertTrue(self::envIsSet('OTHER_VAR'));
  }

  public function testEnvUnsetPrefixWithSystemEnv(): void {
    self::envReset();
    putenv('TEST_SYS_PREFIX_VAR=test_value');
    $this->assertTrue(self::envIsSet('TEST_SYS_PREFIX_VAR'));

    self::envUnsetPrefix('TEST_SYS_PREFIX_');

    $this->assertFalse(self::envIsSet('TEST_SYS_PREFIX_VAR'));
    putenv('TEST_SYS_PREFIX_VAR');
  }

  public function testEnvReset(): void {
    $vars = [
      'TEST_ENV_VAR1' => 'value1',
      'TEST_ENV_VAR2' => 'value2',
      'TEST_ENV_VAR3' => 'value3',
    ];

    self::envSetMultiple($vars);

    foreach (array_keys($vars) as $name) {
      $this->assertTrue(self::envIsSet($name));
    }

    self::envReset();

    foreach (array_keys($vars) as $name) {
      $this->assertFalse(self::envIsSet($name));
    }
  }

  public function testEnvFromInput(): void {
    $input = [
      'TEST_PREFIX_VAR1' => 'value1',
      'TEST_PREFIX_VAR2' => 'value2',
      'OTHER_VAR' => 'value3',
    ];

    $input_copy = $input;

    self::envFromInput($input, 'TEST_PREFIX_');

    $this->assertSame('value1', self::envGet('TEST_PREFIX_VAR1'));
    $this->assertSame('value2', self::envGet('TEST_PREFIX_VAR2'));
    $this->assertFalse(self::envIsSet('OTHER_VAR'));
    $this->assertArrayNotHasKey('TEST_PREFIX_VAR1', $input);
    $this->assertArrayNotHasKey('TEST_PREFIX_VAR2', $input);
    $this->assertArrayHasKey('OTHER_VAR', $input);

    self::envReset();
    $input = $input_copy;

    self::envFromInput($input, 'TEST_PREFIX_', FALSE);

    $this->assertSame('value1', self::envGet('TEST_PREFIX_VAR1'));
    $this->assertSame('value2', self::envGet('TEST_PREFIX_VAR2'));
    $this->assertArrayHasKey('TEST_PREFIX_VAR1', $input);
    $this->assertArrayHasKey('TEST_PREFIX_VAR2', $input);
    $this->assertArrayHasKey('OTHER_VAR', $input);
  }

}
