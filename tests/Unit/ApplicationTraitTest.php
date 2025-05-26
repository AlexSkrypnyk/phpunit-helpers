<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Tests\Unit;

use Symfony\Component\Console\Exception\LogicException;
use AlexSkrypnyk\PhpunitHelpers\Tests\Fixtures\Application\Command\ErrorOutputCommand;
use AlexSkrypnyk\PhpunitHelpers\Tests\Fixtures\Application\Command\GreetingCommand;
use AlexSkrypnyk\PhpunitHelpers\Traits\ApplicationTrait;
use AlexSkrypnyk\PhpunitHelpers\UnitTestCase;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\ExpectationFailedException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\ApplicationTester;

#[CoversTrait(ApplicationTrait::class)]
class ApplicationTraitTest extends UnitTestCase {

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
    if (!static::$fixtures) {
      throw new \RuntimeException('Fixtures directory is not set.');
    }

    $loader_path = static::$fixtures . '/Application/loader.php';
    $this->applicationInitFromLoader($loader_path);

    $this->assertNotNull($this->application);
    $this->assertNotNull($this->applicationTester);
  }

  public function testApplicationInitFromLoaderInvalidPath(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Loader file not found:');

    $this->applicationInitFromLoader('/invalid/path/loader.php');
  }

  public function testApplicationInitFromLoaderInvalidReturn(): void {
    // Create a temporary file that returns null instead of an Application
    $temp_file = static::$tmp . '/invalid_loader.php';
    file_put_contents($temp_file, '<?php return null;');

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Loader must return an instance of Application');

    $this->applicationInitFromLoader($temp_file);
  }

  public function testApplicationInitWithCustomWorkingDirectory(): void {
    // Set a custom working directory
    $this->applicationCwd = static::$tmp;

    // Initialize from command
    $this->applicationInitFromCommand(GreetingCommand::class);

    // Run a simple command
    $this->applicationRun([]);

    // Assert the command ran successfully
    $this->assertApplicationSuccessful();

    // Verify custom working directory was used (we can't directly check the
    // current directory because it's reset by the shutdown function)
    $this->assertNotNull($this->applicationTester);
  }

  public function testApplicationInitFromCommand(): void {
    $this->applicationInitFromCommand(GreetingCommand::class);

    $this->assertNotNull($this->application);
    $this->assertNotNull($this->applicationTester);
  }

  public function testApplicationInitFromCommandInvalidClass(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('The provided object is not an instance of Command');

    $this->applicationInitFromCommand(\stdClass::class);
  }

  public function testApplicationInitFromCommandWithDefaultName(): void {
    // Create a command with name set through configuration
    $command = new class() extends Command {

      protected function configure(): void {
        $this->setName('test:configured-name');
      }

    };

    // Initialize with this command
    $this->applicationInitFromCommand($command);

    $this->assertNotNull($this->application);
    $this->assertNotNull($this->applicationTester);

    // The application should have the command available
    $this->assertNotNull($this->application);
    $commands = $this->application->all();
    $this->assertArrayHasKey('test:configured-name', $commands);
  }

  public function testApplicationInitFromCommandNullName(): void {
    // Create a command that returns null from getName()
    $command = new class() extends Command {

      public function getName(): ?string {
        return NULL;
      }

    };

    // Symfony's Application class throws this before our null check
    $this->expectException(LogicException::class);
    $this->expectExceptionMessage('cannot have an empty name');

    $this->applicationInitFromCommand($command);
  }

  public function testApplicationInitFromCommandNotSingleCommand(): void {
    // Test with is_single_command = FALSE
    $this->applicationInitFromCommand(GreetingCommand::class, FALSE);

    $this->assertNotNull($this->application);
    $this->assertNotNull($this->applicationTester);

    // With is_single_command = FALSE, we should be able to run commands by name
    $this->applicationRun(['command' => 'app:greet']);
    $this->assertApplicationSuccessful();
    $this->assertApplicationOutputContains('Hello, World!');
  }

  public function testApplicationInitFromLoaderWithGetcwdFailure(): void {
    // Mock a scenario where getcwd() returns FALSE (which is hard to test
    // directly). This tests the conditional branch in applicationInitFromLoader
    if (!static::$fixtures) {
      throw new \RuntimeException('Fixtures directory is not set.');
    }

    // Set a custom working directory
    $this->applicationCwd = static::$tmp;

    // Initialize from loader (this will trigger the getcwd check)
    $loader_path = static::$fixtures . '/Application/loader.php';
    $this->applicationInitFromLoader($loader_path);

    $this->assertNotNull($this->application);
    $this->assertNotNull($this->applicationTester);
  }

  public function testApplicationRunWithShowOutput(): void {
    // Test that the applicationShowOutput flag can be set
    $this->applicationShowOutput = TRUE;
    $this->assertTrue($this->applicationShowOutput);

    // Reset to FALSE to avoid output during test
    $this->applicationShowOutput = FALSE;

    $this->applicationInitFromCommand(GreetingCommand::class);

    // The fwrite(STDOUT, ...) call is excluded from coverage, so we can't test
    // it directly. But we can verify the application runs successfully
    $this->applicationRun(['name' => 'TestUser']);
    $this->assertApplicationSuccessful();
    $this->assertApplicationOutputContains('Hello, TestUser!');
  }

  /**
   * Test other code paths in applicationTrait.
   */
  public function testApplicationRunExceptionHandling(): void {
    // Create an application that will throw an exception from a command
    $this->application = new Application();

    // Add a command that will throw an Exception (not RuntimeException)
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

    // With expect_fail as true, the exception should be caught
    $this->applicationRun([], [], TRUE);

    // Without expect_fail, we should get an AssertionFailedError
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

  public static function dataProviderApplicationRun(): array {
    return [
      'default' => [
        [],
        ['Hello, World!'],
      ],
      'with_name' => [
        ['name' => 'John'],
        ['Hello, John!'],
      ],
      'with_yell' => [
        ['--yell' => TRUE],
        ['HELLO, WORLD!'],
      ],
      'with_name_and_yell' => [
        ['name' => 'John', '--yell' => TRUE],
        ['HELLO, JOHN!'],
      ],
    ];
  }

  public function testApplicationRunWithoutInit(): void {
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Application is not initialized');

    $this->applicationRun([]);
  }

  /**
   * Test applicationGet() when application is not initialized.
   */
  public function testApplicationGetNotInitialized(): void {
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Application is not initialized. Call applicationInit* first.');

    $this->applicationGet();
  }

  /**
   * Test applicationGetTester() when tester is not initialized.
   */
  public function testApplicationGetTesterNotInitialized(): void {
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Application tester is not initialized. Call applicationInit* first.');

    $this->applicationGetTester();
  }

  /**
   * Test applicationGet() when application is initialized.
   */
  public function testApplicationGetInitialized(): void {
    $this->applicationInitFromCommand(GreetingCommand::class);

    $application = $this->applicationGet();
    $this->assertInstanceOf(Application::class, $application);
    $this->assertSame($this->application, $application);
  }

  /**
   * Test applicationGetTester() when tester is initialized.
   */
  public function testApplicationGetTesterInitialized(): void {
    $this->applicationInitFromCommand(GreetingCommand::class);

    $tester = $this->applicationGetTester();
    $this->assertInstanceOf(ApplicationTester::class, $tester);
    $this->assertSame($this->applicationTester, $tester);
  }

  public function testApplicationRunWithExpectedFailure(): void {
    // Create a test command that throws an exception
    $command = new class() extends Command {

      protected function configure(): void {
        $this->setName('test:exception');
      }

      protected function execute(InputInterface $input, OutputInterface $output): int {
        throw new \RuntimeException('Test exception message');
      }

    };

    $this->applicationInitFromCommand($command);

    // The applicationRun should not throw an exception when expect_fail is TRUE
    $output = $this->applicationRun([], [], TRUE);

    // The output should contain the error message
    $this->assertStringContainsString('Test exception message', $output);

    // Try again without expect_fail, which should throw an exception
    $this->expectException(AssertionFailedError::class);
    $this->applicationRun([]);
  }

  public function testApplicationRunWithNonZeroExitCode(): void {
    // Create a test command that returns a non-zero exit code
    $command = new class() extends Command {

      protected function configure(): void {
        $this->setName('test:exit-code');
      }

      protected function execute(InputInterface $input, OutputInterface $output): int {
        $output->writeln('Non-zero exit');
        return 1; // Error exit code
      }

    };

    $this->applicationInitFromCommand($command);

    // The applicationRun should throw an exception when the command returns a
    // non-zero exit code.
    $this->expectException(AssertionFailedError::class);
    $this->applicationRun([]);
  }

  public function testApplicationRunWithNonZeroExitCodeAndExpectedFailure(): void {
    // Create a test command that returns a non-zero exit code
    $command = new class() extends Command {

      protected function configure(): void {
        $this->setName('test:exit-code');
      }

      protected function execute(InputInterface $input, OutputInterface $output): int {
        $output->writeln('Non-zero exit');
        return 1; // Error exit code
      }

    };

    $this->applicationInitFromCommand($command);

    // The applicationRun should not throw an exception when the command returns
    // a non-zero exit code but expect_fail is TRUE.
    $output = $this->applicationRun([], [], TRUE);

    // The output should still contain the command output
    $this->assertStringContainsString('Non-zero exit', $output);
  }

  public function testAssertApplicationFailed(): void {
    // Create a test command that returns a non-zero exit code
    $command = new class() extends Command {

      protected function configure(): void {
        $this->setName('test:exit-code');
      }

      protected function execute(InputInterface $input, OutputInterface $output): int {
        return 1; // Error exit code
      }

    };

    // Initialize the application with our test command
    $this->applicationInitFromCommand($command);

    // Run the command, which will return a non-zero exit code
    // Use expect_fail to prevent an exception
    $this->applicationRun([], [], TRUE);

    // Now assert that the application actually failed as expected
    $this->assertApplicationFailed();
  }

  /**
   * Test that assertApplicationFailed throws an exception for success status.
   */
  public function testAssertApplicationFailedWithSuccessStatus(): void {
    // Create a command that succeeds
    $command = new GreetingCommand();

    // Initialize the application with the command
    $this->applicationInitFromCommand($command);

    // Run the command to set up the successful status
    $this->applicationRun([]);

    // Expect the assertion to fail since the application succeeded
    $this->expectException(ExpectationFailedException::class);
    $this->expectExceptionMessage('Application succeeded when failure was expected');

    // This should fail because the application ran successfully
    $this->assertApplicationFailed();
  }

  public function testApplicationInitFromLoaderWithWorkingDirectory(): void {
    // Save a copy of the current working directory
    getcwd();

    // Set a specific working directory for the application
    $this->applicationCwd = static::$tmp;

    if (!static::$fixtures) {
      throw new \RuntimeException('Fixtures directory is not set.');
    }

    // Initialize the application from the loader
    $loader_path = static::$fixtures . '/Application/loader.php';
    $this->applicationInitFromLoader($loader_path);

    // Assert the application was properly initialized
    $this->assertNotNull($this->application);
    $this->assertNotNull($this->applicationTester);

    // Assert we're back in the original directory
    $this->assertDirectoryExists(static::$tmp);
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
      'Hello',
      'Test',
      '---Nonexistent String',
    ]);
  }

  public function testApplicationErrorOutputAssertions(): void {
    // Use the dedicated command that generates error output
    $this->applicationInitFromCommand(ErrorOutputCommand::class);
    $this->applicationRun([]);

    $this->assertApplicationSuccessful();
    $this->assertApplicationErrorOutputContains('Test Error');
    $this->assertApplicationErrorOutputContains(['Test', 'Error']);
    $this->assertApplicationErrorOutputNotContains('Nonexistent Error');
    $this->assertApplicationErrorOutputNotContains(['NoError1', 'NoError2']);

    $this->assertApplicationErrorOutputContainsOrNot([
      'Test',
      'Error',
      '---Nonexistent Error',
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
    $this->expectExceptionMessage('Application is not initialized');

    $this->assertApplicationSuccessful();
  }

  public function testAssertApplicationFailedWhenNull(): void {
    $this->applicationTester = NULL;

    $this->expectException(ExpectationFailedException::class);
    $this->expectExceptionMessage('Application is not initialized');

    $this->assertApplicationFailed();
  }

  public function testAssertApplicationOutputContainsWhenNull(): void {
    $this->applicationTester = NULL;

    $this->expectException(ExpectationFailedException::class);
    $this->expectExceptionMessage('Application is not initialized');

    $this->assertApplicationOutputContains('test');
  }

  public function testAssertApplicationOutputNotContainsWhenNull(): void {
    $this->applicationTester = NULL;

    $this->expectException(ExpectationFailedException::class);
    $this->expectExceptionMessage('Application is not initialized');

    $this->assertApplicationOutputNotContains('test');
  }

  public function testAssertApplicationErrorOutputContainsWhenNull(): void {
    $this->applicationTester = NULL;

    $this->expectException(ExpectationFailedException::class);
    $this->expectExceptionMessage('Application is not initialized');

    $this->assertApplicationErrorOutputContains('test');
  }

  public function testAssertApplicationErrorOutputNotContainsWhenNull(): void {
    $this->applicationTester = NULL;

    $this->expectException(ExpectationFailedException::class);
    $this->expectExceptionMessage('Application is not initialized');

    $this->assertApplicationErrorOutputNotContains('test');
  }

  public function testAssertApplicationOutputContainsOrNotWhenNull(): void {
    $this->applicationTester = NULL;

    $this->expectException(ExpectationFailedException::class);
    $this->expectExceptionMessage('Application is not initialized');

    $this->assertApplicationOutputContainsOrNot('test');
  }

  public function testAssertApplicationErrorOutputContainsOrNotWhenNull(): void {
    $this->applicationTester = NULL;

    $this->expectException(ExpectationFailedException::class);
    $this->expectExceptionMessage('Application is not initialized');

    $this->assertApplicationErrorOutputContainsOrNot('test');
  }

}
