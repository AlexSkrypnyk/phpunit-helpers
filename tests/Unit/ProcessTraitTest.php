<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Tests\Unit;

use PHPUnit\Framework\ExpectationFailedException;
use AlexSkrypnyk\PhpunitHelpers\Traits\ProcessTrait;
use AlexSkrypnyk\PhpunitHelpers\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(ProcessTrait::class)]
class ProcessTraitTest extends UnitTestCase {

  use ProcessTrait;

  protected function setUp(): void {
    parent::setUp();
    $this->processShowOutput = FALSE;
  }

  protected function tearDown(): void {
    $this->processTearDown();
    parent::tearDown();
  }

  #[DataProvider('dataProviderProcessRunWithShellCommand')]
  public function testProcessRunWithShellCommand(array $options, array $args, array $inputs, array $env, array $expected): void {
    if (!static::$fixtures) {
      throw new \RuntimeException('Fixtures directory is not set.');
    }

    $command = static::$fixtures . '/shell-command.sh';
    $arguments = array_merge($options, $args);

    $this->processRun($command, $arguments, $inputs, $env, 60, 30);

    $this->assertProcessSuccessful();
    $this->assertProcessOutputContains($expected);
  }

  public static function dataProviderProcessRunWithShellCommand(): array {
    return [
      'no_options_no_args' => [
        [],
        [],
        ['Alice', 'Blue'],
        [],
        [
          'OPTION1: 0',
          'OPTION2: not provided',
          'ARG1: not provided',
          'ARG2: not provided',
          'ENV1: not provided',
          'ENV2: not provided',
          'NAME: Alice',
          'COLOR: Blue',
        ],
      ],
      'with_option1' => [
        ['--option1'],
        [],
        ['Bob', 'Red'],
        [],
        [
          'OPTION1: 1',
          'OPTION2: not provided',
          'ARG1: not provided',
          'ARG2: not provided',
          'ENV1: not provided',
          'ENV2: not provided',
          'NAME: Bob',
          'COLOR: Red',
        ],
      ],
      'with_option2' => [
        ['--option2=value2'],
        [],
        ['Charlie', 'Green'],
        [],
        [
          'OPTION1: 0',
          'OPTION2: value2',
          'ARG1: not provided',
          'ARG2: not provided',
          'ENV1: not provided',
          'ENV2: not provided',
          'NAME: Charlie',
          'COLOR: Green',
        ],
      ],
      'with_args' => [
        [],
        ['arg1', 'arg2'],
        ['David', 'Yellow'],
        [],
        [
          'OPTION1: 0',
          'OPTION2: not provided',
          'ARG1: arg1',
          'ARG2: arg2',
          'ENV1: not provided',
          'ENV2: not provided',
          'NAME: David',
          'COLOR: Yellow',
        ],
      ],
      'with_options_and_args' => [
        ['--option1', '--option2=value2'],
        ['arg1', 'arg2'],
        ['Eve', 'Purple'],
        [],
        [
          'OPTION1: 1',
          'OPTION2: value2',
          'ARG1: arg1',
          'ARG2: arg2',
          'ENV1: not provided',
          'ENV2: not provided',
          'NAME: Eve',
          'COLOR: Purple',
        ],
      ],
      'with_env' => [
        [],
        [],
        ['Frank', 'Orange'],
        ['ENV1' => 'value1', 'ENV2' => 'value2'],
        [
          'OPTION1: 0',
          'OPTION2: not provided',
          'ARG1: not provided',
          'ARG2: not provided',
          'ENV1: value1',
          'ENV2: value2',
          'NAME: Frank',
          'COLOR: Orange',
        ],
      ],
      'with_env_and_args' => [
        [],
        ['arg1', 'arg2'],
        ['Grace', 'Pink'],
        ['ENV1' => 'value1', 'ENV2' => 'value2'],
        [
          'OPTION1: 0',
          'OPTION2: not provided',
          'ARG1: arg1',
          'ARG2: arg2',
          'ENV1: value1',
          'ENV2: value2',
          'NAME: Grace',
          'COLOR: Pink',
        ],
      ],
    ];
  }

  public function testProcessOutputAssertions(): void {
    $this->processRun('echo', ['Test Output']);

    $this->assertProcessSuccessful();
    $this->assertProcessOutputContains('Test Output');
    $this->assertProcessOutputContains(['Test', 'Output']);
    $this->assertProcessOutputNotContains('Nonexistent String');
    $this->assertProcessOutputNotContains(['Nonexistent1', 'Nonexistent2']);

    $this->assertProcessOutputContainsOrNot([
      'Test',
      'Output',
      '---Nonexistent String',
    ]);
  }

  public function testProcessErrorOutputAssertions(): void {
    $this->processRun('sh', ['-c', 'echo "Test Error" 1>&2'], []);

    $this->assertProcessSuccessful();
    $this->assertProcessErrorOutputContains('Test Error');
    $this->assertProcessErrorOutputContains(['Test', 'Error']);
    $this->assertProcessErrorOutputNotContains('Nonexistent Error');
    $this->assertProcessErrorOutputNotContains(['NoError1', 'NoError2']);

    $this->assertProcessErrorOutputContainsOrNot([
      'Test',
      'Error',
      '---Nonexistent Error',
    ]);
  }

  public function testProcessFailed(): void {
    $command = 'nonexistent-command';

    $this->processRun($command);

    $this->assertProcessFailed();
  }

  public function testProcessInfo(): void {
    $this->processRun('echo', ['Test Output']);

    $info = $this->processInfo();

    $this->assertStringContainsString('PROCESS', $info);
    $this->assertStringContainsString('Output:', $info);
    $this->assertStringContainsString('Test Output', $info);
    $this->assertStringContainsString('Error:', $info);
  }

  public function testProcessInfoUninitializedProcess(): void {
    $this->process = NULL;
    $info = $this->processInfo();

    $this->assertStringContainsString('PROCESS: Not initialized', $info);
  }

  public function testProcessRunWithInvalidCommand(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid command: invalid$command. Only alphanumeric characters, dashes, underscores, and slashes are allowed.');

    $this->processRun('invalid$command');
  }

  public function testProcessRunWithInvalidArgument(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('All arguments must be scalar values.');

    $this->processRun('echo', [['non-scalar', 'argument']]);
  }

  public function testProcessRunWithInvalidEnvironmentVariable(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('All environment variables must be scalar values.');

    $this->processRun('echo', [], [], ['ENV1' => ['non-scalar', 'value']]);
  }

  public function testAssertProcessSuccessfulWhenNull(): void {
    $this->process = NULL;

    $this->expectException(ExpectationFailedException::class);
    $this->expectExceptionMessage('Process is not initialized');

    $this->assertProcessSuccessful();
  }

  public function testAssertProcessFailedWhenNull(): void {
    $this->process = NULL;

    $this->expectException(ExpectationFailedException::class);
    $this->expectExceptionMessage('Process is not initialized');

    $this->assertProcessFailed();
  }

  public function testAssertProcessOutputContainsWhenNull(): void {
    $this->process = NULL;

    $this->expectException(ExpectationFailedException::class);
    $this->expectExceptionMessage('Process is not initialized');

    $this->assertProcessOutputContains('test');
  }

  public function testAssertProcessOutputNotContainsWhenNull(): void {
    $this->process = NULL;

    $this->expectException(ExpectationFailedException::class);
    $this->expectExceptionMessage('Process is not initialized');

    $this->assertProcessOutputNotContains('test');
  }

  public function testAssertProcessErrorOutputContainsWhenNull(): void {
    $this->process = NULL;

    $this->expectException(ExpectationFailedException::class);
    $this->expectExceptionMessage('Process is not initialized');

    $this->assertProcessErrorOutputContains('test');
  }

  public function testAssertProcessErrorOutputNotContainsWhenNull(): void {
    $this->process = NULL;

    $this->expectException(ExpectationFailedException::class);
    $this->expectExceptionMessage('Process is not initialized');

    $this->assertProcessErrorOutputNotContains('test');
  }

  public function testAssertProcessOutputContainsOrNotWhenNull(): void {
    $this->process = NULL;

    $this->expectException(ExpectationFailedException::class);
    $this->expectExceptionMessage('Process is not initialized');

    $this->assertProcessOutputContainsOrNot('test');
  }

  public function testAssertProcessErrorOutputContainsOrNotWhenNull(): void {
    $this->process = NULL;

    $this->expectException(ExpectationFailedException::class);
    $this->expectExceptionMessage('Process is not initialized');

    $this->assertProcessErrorOutputContainsOrNot('test');
  }

}
