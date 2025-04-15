<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Tests\Fixtures\Application\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command that generates error output.
 */
#[AsCommand(
    name: 'app:error',
    description: 'Command that generates error output'
)]
class ErrorOutputCommand extends Command {

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    // Use ConsoleOutput if it's available, otherwise write to stderr
    if ($output instanceof ConsoleOutputInterface) {
      $error_output = $output->getErrorOutput();
      $error_output->writeln('Test Error');
    }
    else {
      fwrite(STDERR, "Test Error" . PHP_EOL);
    }
    $output->writeln('Output message');

    return Command::SUCCESS;
  }

}
