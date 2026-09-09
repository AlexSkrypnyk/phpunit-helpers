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

## Benchmarking

`composer benchmark` runs PHPBench once and reports the timings:

    composer benchmark

There is no stored baseline. A comparison measures both revisions on the machine it runs on, because the spread between two hosts is several times larger than the change most benchmarks are meant to detect.

`composer benchmark-compare` measures two checkouts back to back and asserts that no subject in the head one got slower by more than the threshold, which defaults to 15%:

    mkdir -p .artifacts/bench
    git clone -q --no-hardlinks . .artifacts/bench/base
    git -C .artifacts/bench/base checkout main
    git clone -q --no-hardlinks . .artifacts/bench/head
    cp -R vendor .artifacts/bench/base/vendor
    cp -R vendor .artifacts/bench/head/vendor
    composer benchmark-compare -- --base=.artifacts/bench/base --head=.artifacts/bench/head

Give the two checkouts names of the same length and the same toolchain. A subject that resolves against the working directory pays for every character of that path, so unequal names would be reported as a difference between the revisions. The script warns when the two paths differ in length.

Reports are written to `.logs/performance-report.*` as JSON, CSV and HTML.

Benchmark subjects live in `benchmarks/` and cover the traits that do pure computation. The traits that spawn processes or touch the filesystem are excluded on purpose: their deviation on a shared runner swamps the cost being measured.

## Pull requests

Continuous integration runs the linting and the test suite against PHP 8.3, 8.4 and 8.5, with both the newest and the lowest supported dependencies. Please make sure `composer lint` and `composer test` pass locally before opening a pull request.
