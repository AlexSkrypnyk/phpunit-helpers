<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Tests\Unit;

use AlexSkrypnyk\PhpunitHelpers\Traits\EnvTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(EnvTrait::class)]
class EnvTraitTest extends TestCase {

  use EnvTrait;

  protected function setUp(): void {
    parent::setUp();
    $this->envReset();
  }

  protected function tearDown(): void {
    $this->envReset();
    parent::tearDown();
  }

  public function testEnvSetAndGet(): void {
    $name = 'TEST_ENV_VAR';
    $value = 'test_value';

    $this->envSet($name, $value);

    $this->assertEquals($value, $this->envGet($name));
    $this->assertTrue($this->envIsSet($name));
    $this->assertFalse($this->envIsUnset($name));
  }

  public function testEnvSetMultiple(): void {
    $vars = [
      'TEST_ENV_VAR1' => 'value1',
      'TEST_ENV_VAR2' => 'value2',
      'TEST_ENV_VAR3' => 'value3',
    ];

    $this->envSetMultiple($vars);

    foreach ($vars as $name => $value) {
      $this->assertEquals($value, $this->envGet($name));
      $this->assertTrue($this->envIsSet($name));
    }
  }

  public function testEnvUnset(): void {
    $name = 'TEST_ENV_VAR';
    $value = 'test_value';

    $this->envSet($name, $value);
    $this->assertTrue($this->envIsSet($name));

    $this->envUnset($name);

    $this->assertFalse($this->envIsSet($name));
    $this->assertTrue($this->envIsUnset($name));
  }

  public function testEnvUnsetPrefix(): void {
    $vars = [
      'TEST_PREFIX_VAR1' => 'value1',
      'TEST_PREFIX_VAR2' => 'value2',
      'OTHER_VAR' => 'value3',
    ];

    $this->envSetMultiple($vars);

    $this->envUnsetPrefix('TEST_PREFIX_');

    $this->assertFalse($this->envIsSet('TEST_PREFIX_VAR1'));
    $this->assertFalse($this->envIsSet('TEST_PREFIX_VAR2'));
    $this->assertTrue($this->envIsSet('OTHER_VAR'));
  }

  public function testEnvUnsetPrefixWithSystemEnv(): void {
    $this->envReset();
    putenv('TEST_SYS_PREFIX_VAR=test_value');
    $this->assertTrue($this->envIsSet('TEST_SYS_PREFIX_VAR'));

    $this->envUnsetPrefix('TEST_SYS_PREFIX_');

    $this->assertFalse($this->envIsSet('TEST_SYS_PREFIX_VAR'));
    putenv('TEST_SYS_PREFIX_VAR');
  }

  public function testEnvReset(): void {
    $vars = [
      'TEST_ENV_VAR1' => 'value1',
      'TEST_ENV_VAR2' => 'value2',
      'TEST_ENV_VAR3' => 'value3',
    ];

    $this->envSetMultiple($vars);

    foreach (array_keys($vars) as $name) {
      $this->assertTrue($this->envIsSet($name));
    }

    $this->envReset();

    foreach (array_keys($vars) as $name) {
      $this->assertFalse($this->envIsSet($name));
    }
  }

  public function testEnvFromInput(): void {
    $input = [
      'TEST_PREFIX_VAR1' => 'value1',
      'TEST_PREFIX_VAR2' => 'value2',
      'OTHER_VAR' => 'value3',
    ];

    $input_copy = $input;

    $this->envFromInput($input, 'TEST_PREFIX_');

    $this->assertEquals('value1', $this->envGet('TEST_PREFIX_VAR1'));
    $this->assertEquals('value2', $this->envGet('TEST_PREFIX_VAR2'));
    $this->assertFalse($this->envIsSet('OTHER_VAR'));
    $this->assertArrayNotHasKey('TEST_PREFIX_VAR1', $input);
    $this->assertArrayNotHasKey('TEST_PREFIX_VAR2', $input);
    $this->assertArrayHasKey('OTHER_VAR', $input);

    $this->envReset();
    $input = $input_copy;

    $this->envFromInput($input, 'TEST_PREFIX_', FALSE);

    $this->assertEquals('value1', $this->envGet('TEST_PREFIX_VAR1'));
    $this->assertEquals('value2', $this->envGet('TEST_PREFIX_VAR2'));
    $this->assertArrayHasKey('TEST_PREFIX_VAR1', $input);
    $this->assertArrayHasKey('TEST_PREFIX_VAR2', $input);
    $this->assertArrayHasKey('OTHER_VAR', $input);
  }

}
