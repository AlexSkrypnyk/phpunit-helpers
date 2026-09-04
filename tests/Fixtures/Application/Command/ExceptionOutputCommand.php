<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Tests\Fixtures\Application\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command that throws an exception after generating output.
 */
#[AsCommand(
    name: 'app:exception',
    description: 'Command that throws an exception'
)]
class ExceptionOutputCommand extends Command {

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $output->writeln('Standard output before exception');

    if ($output instanceof ConsoleOutputInterface) {
      $error_output = $output->getErrorOutput();
      $error_output->writeln('Error output before exception');
    }
    else {
      fwrite(STDERR, 'Error output before exception' . PHP_EOL);
    }

    throw new \RuntimeException('Test exception message');
  }

}
