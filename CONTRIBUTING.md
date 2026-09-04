# Contributing

Thank you for considering a contribution to this project. This guide covers setting up a local environment and running the linting and tests.

## Requirements

- PHP 8.3 or newer
- Composer

## Setup

    composer install

To start from a clean state, remove the installed dependencies and the lock file first:

    composer reset
    composer install

## Linting

`composer lint` runs PHP_CodeSniffer, PHPStan at level 9 and Rector in dry-run mode:

    composer lint

`composer lint-fix` applies the fixes that Rector and PHP_CodeSniffer can make automatically:

    composer lint-fix

## Testing

    composer test

`composer test-coverage` runs the same suite with coverage, writing an HTML report to `.logs/.coverage-html/index.html` and a Cobertura report to `.logs/cobertura.xml`:

    composer test-coverage

A single file or a single test method can be run directly:

    ./vendor/bin/phpunit tests/Unit/ProcessTraitTest.php
    ./vendor/bin/phpunit --filter testMethodName

Tests in the `manual` group are excluded from the default suite. They exist to be read rather than asserted on, and some of them fail by design so that the failure output can be inspected. Run them on demand:

    ./vendor/bin/phpunit --group=manual

## Pull requests

Continuous integration runs the linting and the test suite against PHP 8.3, 8.4 and 8.5, with both the newest and the lowest supported dependencies. Please make sure `composer lint` and `composer test` pass locally before opening a pull request.
