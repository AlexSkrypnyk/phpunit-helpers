<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Tests\Unit;

use AlexSkrypnyk\PhpunitHelpers\Tests\Fixtures\Application\Command\ErrorOutputCommand;
use AlexSkrypnyk\PhpunitHelpers\Tests\Fixtures\Application\Command\ExceptionOutputCommand;
use AlexSkrypnyk\PhpunitHelpers\Tests\Fixtures\Application\Command\GreetingCommand;
use AlexSkrypnyk\PhpunitHelpers\Traits\ApplicationTrait;
use AlexSkrypnyk\PhpunitHelpers\UnitTestCase;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\ExpectationFailedException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\ApplicationTester;

#[CoversTrait(ApplicationTrait::class)]
final class ApplicationTraitTest extends UnitTestCase {

  use ApplicationTrait;

  protected function setUp(): void {
    parent::setUp();
    $this->applicationShowOutput = FALSE;
  }

  protected function tearDown(): void {
    $this->applicationTearDown();
    parent::tearDown();
  }

  public function testApplicationInitFromLoader(): void {
    if (!self::$fixtures) {
      throw new \RuntimeException('Fixtures directory is not set.');
    }

    $loader_path = self::$fixtures . '/Application/loader.php';
    $this->applicationInitFromLoader($loader_path);

    $this->assertInstanceOf(Application::class, $this->application);
    $this->assertInstanceOf(ApplicationTester::class, $this->applicationTester);
  }

  public function testApplicationInitFromLoaderInvalidPath(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Loader file not found:');

    $this->applicationInitFromLoader('/invalid/path/loader.php');
  }

  #[DataProvider('dataProviderApplicationInitFromLoaderInvalidReturn')]
  public function testApplicationInitFromLoaderInvalidReturn(string $returned): void {
    $temp_file = self::$tmp . '/invalid_loader.php';
    file_put_contents($temp_file, '<?php return ' . $returned . ';');

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Loader must return an instance of Application.');

    $this->applicationInitFromLoader($temp_file);
  }

  public static function dataProviderApplicationInitFromLoaderInvalidReturn(): \Iterator {
    yield 'null' => ['null'];
    yield 'object_of_other_type' => ['new \stdClass()'];
    yield 'scalar' => ["'not an application'"];
    yield 'array' => ['[]'];
  }

  public function testApplicationInitWithCustomCwd(): void {
    $this->applicationCwd = self::$tmp;

    $this->applicationInitFromCommand(GreetingCommand::class);

    $this->applicationRun([]);

    $this->assertApplicationSuccessful();

    $this->assertInstanceOf(ApplicationTester::class, $this->applicationTester);
  }

  public function testApplicationInitFromCommand(): void {
    $this->applicationInitFromCommand(GreetingCommand::class);

    $this->assertInstanceOf(Application::class, $this->application);
    $this->assertInstanceOf(ApplicationTester::class, $this->applicationTester);
  }

  public function testApplicationInitFromCommandInvalidClass(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('The provided object is not an instance of Command.');

    $this->applicationInitFromCommand(\stdClass::class);
  }

  public function testApplicationInitFromCommandWithDefaultName(): void {
    $command = new class() extends Command {

      protected function configure(): void {
        $this->setName('test:configured-name');
      }

    };

    $this->applicationInitFromCommand($command);

    $this->assertInstanceOf(Application::class, $this->application);
    $this->assertInstanceOf(ApplicationTester::class, $this->applicationTester);

    $this->assertInstanceOf(Application::class, $this->application);
    $commands = $this->application->all();
    $this->assertArrayHasKey('test:configured-name', $commands);
  }

  public function testApplicationInitFromCommandNullName(): void {
    $command = new class() extends Command {

      public function getName(): ?string {
        return NULL;
      }

    };

    // Symfony's Application throws this before the trait's null check.
    $this->expectException(LogicException::class);
    $this->expectExceptionMessage('cannot have an empty name');

    $this->applicationInitFromCommand($command);
  }

  public function testApplicationInitFromCommandNotSingleCommand(): void {
    $this->applicationInitFromCommand(GreetingCommand::class, FALSE);

    $this->assertInstanceOf(Application::class, $this->application);
    $this->assertInstanceOf(ApplicationTester::class, $this->applicationTester);

    // With is_single_command = FALSE, commands can be run by name.
    $this->applicationRun(['command' => 'app:greet']);
    $this->assertApplicationSuccessful();
    $this->assertApplicationOutputContains('Hello, World!');
  }

  public function testApplicationRunWithShowOutput(): void {
    $this->applicationShowOutput = TRUE;
    $this->assertTrue($this->applicationShowOutput);

    // Reset to FALSE to avoid output during test.
    $this->applicationShowOutput = FALSE;

    $this->applicationInitFromCommand(GreetingCommand::class);

    // The fwrite(STDOUT, ...) call is excluded from coverage, so it cannot
    // be tested directly. The test only verifies the application runs
    // successfully.
    $this->applicationRun(['name' => 'TestUser']);
    $this->assertApplicationSuccessful();
    $this->assertApplicationOutputContains('Hello, TestUser!');
  }

  public function testApplicationRunExpectedFailureReturnsOutput(): void {
    $command = new class() extends Command {

      protected function configure(): void {
        $this->setName('test:exit-code');
      }

      protected function execute(InputInterface $input, OutputInterface $output): int {
        $output->writeln('Important output line');

        return Command::FAILURE;
      }

    };

    $this->applicationInitFromCommand($command);

    $output = $this->applicationRun([], [], TRUE);

    $this->assertStringContainsString('Important output line', $output);
    $this->assertStringNotContainsString('Application exited with non-zero code', $output);
  }

  public function testApplicationRunExpectedFailureButSucceeded(): void {
    $this->applicationInitFromCommand(GreetingCommand::class);

    $this->expectException(AssertionFailedError::class);
    $this->expectExceptionMessage('Application exited successfully but should not.');

    $this->applicationRun([], [], TRUE);
  }

  public function testApplicationRunExceptionHandling(): void {
    $this->application = new Application();

    $command = new class() extends Command {

      protected function configure(): void {
        $this->setName('test:exception');
      }

      protected function execute(InputInterface $input, OutputInterface $output): int {
        throw new \Exception('Generic exception test');
      }

    };

    $this->application->add($command);
    $this->application->setDefaultCommand('test:exception');
    $this->application->setAutoExit(FALSE);
    $this->application->setCatchExceptions(FALSE);

    $this->applicationTester = new ApplicationTester($this->application);

    // With expect_fail as true, the exception should be caught.
    $this->applicationRun([], [], TRUE);

    $this->expectException(AssertionFailedError::class);
    $this->applicationRun([]);
  }

  #[DataProvider('dataProviderApplicationRun')]
  public function testApplicationRun(array $input, array $expected): void {
    $this->applicationInitFromCommand(GreetingCommand::class);

    $this->applicationRun($input);

    $this->assertApplicationSuccessful();
    $this->assertApplicationOutputContains($expected);
  }

  public static function dataProviderApplicationRun(): \Iterator {
    yield 'default' => [
      [],
      ['Hello, World!'],
    ];
    yield 'with_name' => [
      ['name' => 'John'],
      ['Hello, John!'],
    ];
    yield 'with_yell' => [
      ['--yell' => TRUE],
      ['HELLO, WORLD!'],
    ];
    yield 'with_name_and_yell' => [
      ['name' => 'John', '--yell' => TRUE],
      ['HELLO, JOHN!'],
    ];
  }

  public function testApplicationRunWithoutInit(): void {
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Application is not initialized.');

    $this->applicationRun([]);
  }

  public function testApplicationGetNotInitialized(): void {
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Application is not initialized. Call applicationInit* first.');

    $this->applicationGet();
  }

  public function testApplicationGetTesterNotInitialized(): void {
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Application tester is not initialized. Call applicationInit* first.');

    $this->applicationGetTester();
  }

  public function testApplicationGetInitialized(): void {
    $this->applicationInitFromCommand(GreetingCommand::class);

    $application = $this->applicationGet();
    $this->assertInstanceOf(Application::class, $application);
    $this->assertSame($this->application, $application);
  }

  public function testApplicationGetTesterInitialized(): void {
    $this->applicationInitFromCommand(GreetingCommand::class);

    $tester = $this->applicationGetTester();
    $this->assertInstanceOf(ApplicationTester::class, $tester);
    $this->assertSame($this->applicationTester, $tester);
  }

  public function testApplicationRunWithExpectedFailure(): void {
    $command = new class() extends Command {

      protected function configure(): void {
        $this->setName('test:exception');
      }

      protected function execute(InputInterface $input, OutputInterface $output): int {
        throw new \RuntimeException('Test exception message');
      }

    };

    $this->applicationInitFromCommand($command);

    // applicationRun() does not throw when expect_fail is TRUE.
    $output = $this->applicationRun([], [], TRUE);

    $this->assertStringContainsString('Test exception message', $output);

    $this->expectException(AssertionFailedError::class);
    $this->applicationRun([]);
  }

  public function testApplicationRunWithNonZeroExitCode(): void {
    $command = new class() extends Command {

      protected function configure(): void {
        $this->setName('test:exit-code');
      }

      protected function execute(InputInterface $input, OutputInterface $output): int {
        $output->writeln('Non-zero exit');
        return 1;
      }

    };

    $this->applicationInitFromCommand($command);

    $this->expectException(AssertionFailedError::class);
    $this->applicationRun([]);
  }

  public function testApplicationRunWithNonZeroExitCodeAndExpectedFailure(): void {
    $command = new class() extends Command {

      protected function configure(): void {
        $this->setName('test:exit-code');
      }

      protected function execute(InputInterface $input, OutputInterface $output): int {
        $output->writeln('Non-zero exit');
        return 1;
      }

    };

    $this->applicationInitFromCommand($command);

    // The applicationRun should not throw an exception when the command returns
    // a non-zero exit code but expect_fail is TRUE.
    $output = $this->applicationRun([], [], TRUE);

    $this->assertStringContainsString('Non-zero exit', $output);
  }

  public function testAssertApplicationFailed(): void {
    $command = new class() extends Command {

      protected function configure(): void {
        $this->setName('test:exit-code');
      }

      protected function execute(InputInterface $input, OutputInterface $output): int {
        return 1;
      }

    };

    $this->applicationInitFromCommand($command);

    // Use expect_fail to prevent an exception.
    $this->applicationRun([], [], TRUE);

    $this->assertApplicationFailed();
  }

  public function testAssertApplicationFailedWithSuccessStatus(): void {
    $command = new GreetingCommand();

    $this->applicationInitFromCommand($command);

    $this->applicationRun([]);

    $this->expectException(ExpectationFailedException::class);
    $this->expectExceptionMessage('Application succeeded when failure was expected');

    $this->assertApplicationFailed();
  }

  public function testApplicationInitFromLoaderWithCwd(): void {
    $original_cwd = getcwd();

    if ($original_cwd === FALSE) {
      $this->markTestSkipped('Could not determine current working directory.');
    }

    $this->applicationCwd = self::$tmp;

    if (!self::$fixtures) {
      throw new \RuntimeException('Fixtures directory is not set.');
    }

    try {
      $loader_path = self::$fixtures . '/Application/loader.php';
      $this->applicationInitFromLoader($loader_path);

      $this->assertInstanceOf(Application::class, $this->application);
      $this->assertInstanceOf(ApplicationTester::class, $this->applicationTester);

      // The original directory is restored on shutdown, so the process is
      // still in the configured directory at this point.
      $this->assertSame(self::locationsRealpath(self::$tmp), self::locationsRealpath((string) getcwd()));
    }
    finally {
      chdir($original_cwd);
    }
  }

  public function testApplicationOutputAssertions(): void {
    $this->applicationInitFromCommand(GreetingCommand::class);
    $this->applicationRun(['name' => 'Test']);

    $this->assertApplicationSuccessful();
    $this->assertApplicationOutputContains('Hello, Test!');
    $this->assertApplicationOutputContains(['Hello', 'Test']);
    $this->assertApplicationOutputNotContains('Nonexistent String');
    $this->assertApplicationOutputNotContains(['Nonexistent1', 'Nonexistent2']);

    $this->assertApplicationOutputContainsOrNot([
      '* Hello',
      '* Test',
      '! Nonexistent String',
    ]);
  }

  public function testApplicationErrorOutputAssertions(): void {
    $this->applicationInitFromCommand(ErrorOutputCommand::class);
    $this->applicationRun([]);

    $this->assertApplicationSuccessful();
    $this->assertApplicationErrorOutputContains('Test Error');
    $this->assertApplicationErrorOutputContains(['Test', 'Error']);
    $this->assertApplicationErrorOutputNotContains('Nonexistent Error');
    $this->assertApplicationErrorOutputNotContains(['NoError1', 'NoError2']);

    $this->assertApplicationErrorOutputContainsOrNot([
      '* Test',
      '* Error',
      '! Nonexistent Error',
    ]);
  }

  public function testApplicationInfo(): void {
    $this->applicationInitFromCommand(GreetingCommand::class);
    $this->applicationRun(['name' => 'Test']);

    $info = $this->applicationInfo();

    $this->assertStringContainsString('APPLICATION', $info);
    $this->assertStringContainsString('Output:', $info);
    $this->assertStringContainsString('Hello, Test!', $info);
    $this->assertStringContainsString('Error:', $info);
  }

  public function testApplicationInfoUninitializedApplication(): void {
    $this->applicationTester = NULL;
    $info = $this->applicationInfo();

    $this->assertStringContainsString('APPLICATION: Not initialized', $info);
  }

  public function testAssertApplicationSuccessfulWhenNull(): void {
    $this->applicationTester = NULL;

    $this->expectException(ExpectationFailedException::class);
    $this->expectExceptionMessage('Application is not initialized.');

    $this->assertApplicationSuccessful();
  }

  public function testAssertApplicationFailedWhenNull(): void {
    $this->applicationTester = NULL;

    $this->expectException(ExpectationFailedException::class);
    $this->expectExceptionMessage('Application is not initialized.');

    $this->assertApplicationFailed();
  }

  public function testAssertApplicationOutputContainsWhenNull(): void {
    $this->applicationTester = NULL;

    $this->expectException(ExpectationFailedException::class);
    $this->expectExceptionMessage('Application is not initialized.');

    $this->assertApplicationOutputContains('test');
  }

  public function testAssertApplicationOutputNotContainsWhenNull(): void {
    $this->applicationTester = NULL;

    $this->expectException(ExpectationFailedException::class);
    $this->expectExceptionMessage('Application is not initialized.');

    $this->assertApplicationOutputNotContains('test');
  }

  public function testAssertApplicationErrorOutputContainsWhenNull(): void {
    $this->applicationTester = NULL;

    $this->expectException(ExpectationFailedException::class);
    $this->expectExceptionMessage('Application is not initialized.');

    $this->assertApplicationErrorOutputContains('test');
  }

  public function testAssertApplicationErrorOutputNotContainsWhenNull(): void {
    $this->applicationTester = NULL;

    $this->expectException(ExpectationFailedException::class);
    $this->expectExceptionMessage('Application is not initialized.');

    $this->assertApplicationErrorOutputNotContains('test');
  }

  public function testAssertApplicationOutputContainsOrNotWhenNull(): void {
    $this->applicationTester = NULL;

    $this->expectException(ExpectationFailedException::class);
    $this->expectExceptionMessage('Application is not initialized.');

    $this->assertApplicationOutputContainsOrNot('test');
  }

  public function testAssertApplicationErrorOutputContainsOrNotWhenNull(): void {
    $this->applicationTester = NULL;

    $this->expectException(ExpectationFailedException::class);
    $this->expectExceptionMessage('Application is not initialized.');

    $this->assertApplicationErrorOutputContainsOrNot('test');
  }

  public function testAssertApplicationAnyOutputContainsOrNotWhenNull(): void {
    $this->applicationTester = NULL;

    $this->expectException(ExpectationFailedException::class);
    $this->expectExceptionMessage('Application is not initialized.');

    $this->assertApplicationAnyOutputContainsOrNot('test');
  }

  public function testApplicationAnyOutputErrorCommandAssertions(): void {
    $this->applicationInitFromCommand(ErrorOutputCommand::class);
    $output = $this->applicationRun([]);

    $this->assertApplicationSuccessful();

    $this->assertStringContainsString('Output message', $output);
    $this->assertStringContainsString('Test Error', $output);

    $this->assertApplicationOutputContains('Output message');
    $this->assertApplicationOutputNotContains('Test Error');

    $this->assertApplicationErrorOutputNotContains('Output message');
    $this->assertApplicationErrorOutputContains('Test Error');

    $this->assertApplicationAnyOutputContainsOrNot([
      '* Test Error',
      '* Output message',
      '! Nonexistent String',
    ]);
  }

  public function testApplicationAnyOutputExceptionCommandAssertions(): void {
    $this->applicationInitFromCommand(ExceptionOutputCommand::class);
    $output = $this->applicationRun([], [], TRUE);

    $this->assertApplicationFailed();

    $this->assertStringContainsString('Standard output before exception', $output);
    $this->assertStringContainsString('Error output before exception', $output);
    $this->assertStringContainsString('Test exception message', $output);

    $this->assertApplicationOutputContains('Standard output before exception');
    $this->assertApplicationOutputNotContains('Error output before exception');
    $this->assertApplicationOutputNotContains('Test exception message');

    $this->assertApplicationErrorOutputNotContains('Standard output before exception');
    $this->assertApplicationErrorOutputContains('Error output before exception');
    $this->assertApplicationErrorOutputContains('Test exception message');

    $this->assertApplicationAnyOutputContainsOrNot([
      'Standard output before exception',
      'Error output before exception',
      'Test exception message',
    ]);
  }

  public function testApplicationOutputContainsOrNotShortcutMode(): void {
    $this->applicationInitFromCommand(GreetingCommand::class);
    $this->applicationRun(['name' => 'Test']);

    $this->assertApplicationSuccessful();

    // Test shortcut mode - no prefixes, all should be present as substrings.
    $this->assertApplicationAnyOutputContainsOrNot([
      'Hello',
      'Test',
    ]);
  }

  public function testApplicationOutputContainsOrNotExactMatch(): void {
    $this->applicationInitFromCommand(GreetingCommand::class);
    $this->applicationRun(['name' => 'World']);

    $this->assertApplicationSuccessful();

    // Test exact match present (trailing whitespace is trimmed)
    $this->assertApplicationOutputContainsOrNot([
      '+ Hello, World!',
    ]);

    // Test exact match absent.
    $this->assertApplicationOutputContainsOrNot([
      '- Not exact match',
    ]);
  }

  public function testApplicationOutputContainsOrNotInconsistentPrefixUsage(): void {
    $this->applicationInitFromCommand(GreetingCommand::class);
    $this->applicationRun(['name' => 'Test']);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('All strings must have valid prefixes in mixed mode');

    $this->assertApplicationOutputContainsOrNot([
      '* Hello',
      'Missing prefix',
    ]);
  }

  public function testApplicationErrorOutputContainsOrNotInconsistentPrefixUsage(): void {
    $this->applicationInitFromCommand(ErrorOutputCommand::class);
    $this->applicationRun([]);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('All strings must have valid prefixes in mixed mode');

    $this->assertApplicationErrorOutputContainsOrNot([
      '* Test Error',
      'Missing prefix',
    ]);
  }

  public function testApplicationAnyOutputContains(): void {
    $this->applicationInitFromCommand(ErrorOutputCommand::class);
    $this->applicationRun([]);

    $this->assertApplicationSuccessful();

    $this->assertApplicationAnyOutputContains('Test Error');
    $this->assertApplicationAnyOutputContains(['Test Error', 'Output message']);
  }

  public function testApplicationAnyOutputContainsFailure(): void {
    $this->applicationInitFromCommand(ErrorOutputCommand::class);
    $this->applicationRun([]);

    $this->expectException(ExpectationFailedException::class);

    $this->assertApplicationAnyOutputContains('Nonexistent string');
  }

  public function testApplicationAnyOutputNotContains(): void {
    $this->applicationInitFromCommand(ErrorOutputCommand::class);
    $this->applicationRun([]);

    $this->assertApplicationSuccessful();

    $this->assertApplicationAnyOutputNotContains('Nonexistent string');
    $this->assertApplicationAnyOutputNotContains(['NotFound1', 'NotFound2']);
  }

  public function testApplicationAnyOutputNotContainsFailure(): void {
    $this->applicationInitFromCommand(ErrorOutputCommand::class);
    $this->applicationRun([]);

    $this->expectException(ExpectationFailedException::class);

    $this->assertApplicationAnyOutputNotContains('Test Error');
  }

  public function testApplicationAnyOutputContainsOrNotShortcutMode(): void {
    $this->applicationInitFromCommand(ErrorOutputCommand::class);
    $this->applicationRun([]);

    $this->assertApplicationSuccessful();

    // Test shortcut mode - no prefixes, all should be present as substrings.
    $this->assertApplicationAnyOutputContainsOrNot([
      'Test Error',
      'Output message',
    ]);
  }

  public function testApplicationAnyOutputContainsOrNotInconsistentPrefixUsage(): void {
    $this->applicationInitFromCommand(ErrorOutputCommand::class);
    $this->applicationRun([]);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('All strings must have valid prefixes in mixed mode');

    $this->assertApplicationAnyOutputContainsOrNot([
      '* Test Error',
      'Missing prefix',
    ]);
  }

  public function testApplicationOutputTrimsTrailingWhitespace(): void {
    $this->applicationInitFromCommand(GreetingCommand::class);
    $this->applicationRun(['name' => 'World']);

    $this->assertApplicationSuccessful();

    // Exact match works without needing to add \n.
    $this->assertApplicationOutputContainsOrNot([
      '+ Hello, World!',
    ]);

    // Single-quoted, so this holds a literal backslash-n rather than a newline
    // and therefore never equals the output.
    $this->assertApplicationOutputContainsOrNot([
      '- Hello, World!\nExtra',
    ]);
  }

  public function testApplicationErrorOutputTrimsTrailingWhitespace(): void {
    $this->applicationInitFromCommand(ErrorOutputCommand::class);
    $this->applicationRun([]);

    $this->assertApplicationSuccessful();

    // Exact match works without needing to add \n.
    $this->assertApplicationErrorOutputContainsOrNot([
      '+ Test Error',
    ]);
  }

  public function testApplicationAnyOutputTrimsTrailingWhitespace(): void {
    $this->applicationInitFromCommand(ErrorOutputCommand::class);
    $this->applicationRun([]);

    $this->assertApplicationSuccessful();

    // Combined output is trimmed.
    $this->assertApplicationAnyOutputContainsOrNot([
      '+ Output message' . "\n" . 'Test Error',
    ]);
  }

  public function testApplicationOutputExactMatchWithMultipleLines(): void {
    $command = new class() extends Command {

      protected function configure(): void {
        $this->setName('test:multiline');
      }

      protected function execute(InputInterface $input, OutputInterface $output): int {
        $output->writeln('Line 1');
        $output->writeln('Line 2');
        $output->writeln('Line 3');
        return Command::SUCCESS;
      }

    };

    $this->applicationInitFromCommand($command);
    $this->applicationRun([]);

    $this->assertApplicationSuccessful();

    // Exact match with multi-line output (no need for trailing \n)
    $this->assertApplicationOutputContainsOrNot([
      '+ Line 1' . "\n" . 'Line 2' . "\n" . 'Line 3',
    ]);

    // Partial match still works.
    $this->assertApplicationOutputContainsOrNot([
      '* Line 2',
    ]);
  }

}
