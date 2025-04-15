<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Tests\Fixtures\Application\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to greet a user.
 */
#[AsCommand(
    name: 'app:greet',
    description: 'Greets a user with a message'
)]
class GreetingCommand extends Command {

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this
      ->addArgument('name', InputArgument::OPTIONAL, 'Name to greet', 'World')
      ->addOption('yell', 'y', InputOption::VALUE_NONE, 'Yell the greeting');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $name = $input->getArgument('name');
    $yell = $input->getOption('yell');

    // Convert name to string safely
    $name_str = is_scalar($name) ? (string) $name : 'Unknown';
    $greeting = sprintf('Hello, %s!', $name_str);

    if ($yell) {
      $greeting = strtoupper($greeting);
      $output->writeln('<info>' . $greeting . '</info>');
    }
    else {
      $output->writeln($greeting);
    }

    return Command::SUCCESS;
  }

}
