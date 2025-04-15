<?php

/**
 * @file
 * Application loader script.
 */

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/vendor/autoload.php';

use AlexSkrypnyk\PhpunitHelpers\Tests\Fixtures\Application\Command\GreetingCommand;
use Symfony\Component\Console\Application;

// Initialize the application.
$application = new Application('Test Application', '1.0.0');
$application->setAutoExit(FALSE);
$application->setCatchExceptions(FALSE);

// Add commands.
$application->add(new GreetingCommand());

// Execute the application if this file is run directly.
if (isset($_SERVER['SCRIPT_FILENAME']) && is_string($_SERVER['SCRIPT_FILENAME']) &&
    realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
  $application->run();
}
else {
  // If this file is included, return the application instance.
  return $application;
}
