<?php

/**
 * @file
 * Resolves the Symfony version a CI leg installs from composer.json.
 *
 * A leg names a Symfony major and which end of it to test. The versions
 * behind that come from the constraint the Symfony packages share, so the
 * workflow holds no copy of them, and a leg naming a major the constraint no
 * longer offers fails instead of testing something else.
 *
 * Usage:
 *   symfony-constraint.php --major=6 --bound=lowest [--composer-json=PATH]
 *
 * Prints SYMFONY_VERSION and SYMFONY_EXPECTED as environment file lines.
 */

declare(strict_types=1);

/**
 * Exit code for a malformed invocation, matching the sysexits usage code.
 */
const EXIT_USAGE = 64;

/**
 * Reads the constraint every Symfony package in composer.json shares.
 *
 * @param string $path
 *   Path to composer.json.
 *
 * @return string
 *   The shared constraint, such as '^6.4 || ^7.2 || ^8.0'.
 */
function symfony_constraint_read(string $path): string {
  $contents = file_get_contents($path);
  if ($contents === FALSE) {
    throw new \RuntimeException(sprintf('Cannot read %s.', $path));
  }

  $composer = json_decode($contents, TRUE, 512, JSON_THROW_ON_ERROR);
  if (!is_array($composer)) {
    throw new \RuntimeException(sprintf('%s holds no object.', $path));
  }

  $require = is_array($composer['require'] ?? NULL) ? $composer['require'] : [];
  $require_dev = is_array($composer['require-dev'] ?? NULL) ? $composer['require-dev'] : [];

  $constraints = [];
  foreach (array_merge($require, $require_dev) as $package => $constraint) {
    if (is_string($package) && is_string($constraint) && str_starts_with($package, 'symfony/')) {
      $constraints[$package] = $constraint;
    }
  }

  if ($constraints === []) {
    throw new \RuntimeException(sprintf('%s requires no Symfony package.', $path));
  }

  if (count(array_unique($constraints)) > 1) {
    throw new \RuntimeException(sprintf('The Symfony packages do not share one constraint: %s', json_encode($constraints)));
  }

  return (string) reset($constraints);
}

/**
 * Picks the alternative of a constraint that covers a major version.
 *
 * @param string $constraint
 *   The constraint to read, such as '^6.4 || ^7.2 || ^8.0'.
 * @param string $major
 *   The major version to cover, such as '6'.
 *
 * @return string
 *   The matching alternative, as written in composer.json.
 */
function symfony_constraint_alternative(string $constraint, string $major): string {
  $alternatives = preg_split('/\s*\|\|\s*/', trim($constraint));
  if ($alternatives === FALSE) {
    throw new \RuntimeException(sprintf('Cannot read alternatives out of "%s".', $constraint));
  }

  foreach ($alternatives as $alternative) {
    if (preg_match('/^\D*' . preg_quote($major, '/') . '\./', $alternative) === 1) {
      return $alternative;
    }
  }

  throw new \RuntimeException(sprintf('The constraint "%s" covers no Symfony %s.', $constraint, $major));
}

/**
 * Derives the lowest version an alternative allows.
 *
 * @param string $alternative
 *   A single alternative of the constraint, such as '^6.4'.
 *
 * @return string
 *   The lowest version it allows, such as '6.4.0'.
 */
function symfony_constraint_floor(string $alternative): string {
  if (preg_match('/(\d+(?:\.\d+)*)/', $alternative, $matches) !== 1) {
    throw new \RuntimeException(sprintf('Cannot read a version out of "%s".', $alternative));
  }

  $parts = explode('.', $matches[1]);
  while (count($parts) < 3) {
    $parts[] = '0';
  }

  return implode('.', array_slice($parts, 0, 3));
}

/**
 * Runs the script.
 *
 * @param array<int, string> $arguments
 *   Command line arguments.
 */
function main(array $arguments): void {
  $major = '';
  $bound = '';
  $composer_json = dirname(__DIR__, 2) . '/composer.json';

  foreach (array_slice($arguments, 1) as $argument) {
    if (str_starts_with($argument, '--major=')) {
      $major = substr($argument, 8);
    }
    elseif (str_starts_with($argument, '--bound=')) {
      $bound = substr($argument, 8);
    }
    elseif (str_starts_with($argument, '--composer-json=')) {
      $composer_json = substr($argument, 16);
    }
    else {
      fwrite(STDERR, sprintf('Unknown argument: %s' . PHP_EOL, $argument));
      exit(EXIT_USAGE);
    }
  }

  if (preg_match('/^\d+$/', $major) !== 1 || !in_array($bound, ['lowest', 'highest'], TRUE)) {
    fwrite(STDERR, 'Usage: symfony-constraint.php --major=6 --bound=lowest [--composer-json=PATH]' . PHP_EOL);
    exit(EXIT_USAGE);
  }

  $alternative = symfony_constraint_alternative(symfony_constraint_read($composer_json), $major);

  // The lowest end is pinned exactly, while the highest end is left to
  // Composer, which resolves the newest release the alternative allows.
  $version = $bound === 'lowest' ? symfony_constraint_floor($alternative) : $alternative;
  $expected = $bound === 'lowest' ? 'v' . $version : 'v' . $major . '.';

  print 'SYMFONY_VERSION=' . $version . PHP_EOL;
  print 'SYMFONY_EXPECTED=' . $expected . PHP_EOL;
}

main($argv);
