<?php

/**
 * @file
 * Builds the CI matrix of PHP and Symfony combinations from composer.json.
 *
 * Every alternative in the Symfony constraint contributes 2 legs: the floor
 * version the alternative allows and the newest release it allows. The floor
 * leg of the oldest alternative resolves every dependency at its floor, which
 * is what covers the lowest end of the supported PHPUnit range.
 *
 * A leg is emitted only for the PHP versions that the Symfony release itself
 * supports, read from that release's own requirements. Widening the constraint
 * in composer.json is therefore the only edit needed to test another major.
 *
 * Usage:
 *   symfony-matrix.php --php=8.3,8.4,8.5 [--composer-json=PATH]
 *
 * Prints the matrix as a single line of JSON for 'fromJson()' to consume.
 */

declare(strict_types=1);

/**
 * Exit code for a malformed invocation, matching the sysexits usage code.
 */
const EXIT_USAGE = 64;

/**
 * Runs a Composer subcommand and decodes its JSON output.
 *
 * @param array<int, string> $arguments
 *   Arguments to pass to Composer.
 *
 * @return array<array-key, mixed>
 *   The decoded output.
 */
function symfony_matrix_composer(array $arguments): array {
  $binary = getenv('SYMFONY_MATRIX_COMPOSER');
  $binary = is_string($binary) && $binary !== '' ? $binary : 'composer';

  $command = implode(' ', array_map('escapeshellarg', array_merge([$binary], $arguments)));
  $output = shell_exec($command . ' 2>/dev/null');

  if (!is_string($output) || trim($output) === '') {
    throw new \RuntimeException(sprintf('Composer returned nothing for: %s', implode(' ', $arguments)));
  }

  $decoded = json_decode($output, TRUE, 512, JSON_THROW_ON_ERROR);

  if (!is_array($decoded)) {
    throw new \RuntimeException(sprintf('Composer returned no object for: %s', implode(' ', $arguments)));
  }

  return $decoded;
}

/**
 * Reads an array member out of a decoded structure.
 *
 * @param array<array-key, mixed> $payload
 *   The structure to read from.
 * @param string $key
 *   The member to read.
 *
 * @return array<array-key, mixed>
 *   The member, or an empty array when it is absent or not an array.
 */
function symfony_matrix_member(array $payload, string $key): array {
  $value = $payload[$key] ?? NULL;

  return is_array($value) ? $value : [];
}

/**
 * Collects the Symfony packages and the constraint they share.
 *
 * @param string $path
 *   Path to composer.json.
 *
 * @return array{0: array<int, string>, 1: string}
 *   The package names and their shared constraint.
 */
function symfony_matrix_requirements(string $path): array {
  $contents = file_get_contents($path);
  if ($contents === FALSE) {
    throw new \RuntimeException(sprintf('Cannot read %s.', $path));
  }

  $composer = json_decode($contents, TRUE, 512, JSON_THROW_ON_ERROR);

  if (!is_array($composer)) {
    throw new \RuntimeException(sprintf('%s holds no object.', $path));
  }

  $requirements = array_merge(symfony_matrix_member($composer, 'require'), symfony_matrix_member($composer, 'require-dev'));

  $constraints = [];
  foreach ($requirements as $package => $constraint) {
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

  ksort($constraints);

  return [array_keys($constraints), (string) reset($constraints)];
}

/**
 * Derives the floor version an alternative allows.
 *
 * @param string $alternative
 *   A single alternative of the constraint, such as '^6.4'.
 *
 * @return string
 *   The lowest version the alternative allows, such as '6.4.0'.
 */
function symfony_matrix_floor(string $alternative): string {
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
 * Lists the releases of a package, newest first.
 *
 * @param string $package
 *   The package to look up.
 *
 * @return array<int, string>
 *   The release tags, without the branch aliases Composer also reports.
 */
function symfony_matrix_releases(string $package): array {
  static $cache = [];

  if (!isset($cache[$package])) {
    $payload = symfony_matrix_composer(['show', '--all', $package, '--format=json']);

    $releases = [];
    foreach (symfony_matrix_member($payload, 'versions') as $version) {
      if (is_string($version) && preg_match('/^v?\d+\.\d+\.\d+$/', $version) === 1) {
        $releases[] = $version;
      }
    }

    $cache[$package] = $releases;
  }

  return $cache[$package];
}

/**
 * Finds the release tag of an exact version.
 *
 * @param string $package
 *   The package to look up.
 * @param string $version
 *   The version to find, such as '6.4.0'.
 *
 * @return string
 *   The release tag, such as 'v6.4.0'.
 */
function symfony_matrix_release(string $package, string $version): string {
  foreach (symfony_matrix_releases($package) as $release) {
    if (ltrim($release, 'v') === $version) {
      return $release;
    }
  }

  throw new \RuntimeException(sprintf('%s has no release %s.', $package, $version));
}

/**
 * Finds the newest release of a package within a major version.
 *
 * @param string $package
 *   The package to look up.
 * @param string $major
 *   The major version to stay within.
 *
 * @return string
 *   The newest release, such as 'v6.4.46'.
 */
function symfony_matrix_newest(string $package, string $major): string {
  foreach (symfony_matrix_releases($package) as $release) {
    if (str_starts_with(ltrim($release, 'v'), $major . '.')) {
      return $release;
    }
  }

  throw new \RuntimeException(sprintf('%s has no release in major version %s.', $package, $major));
}

/**
 * Reads the lowest PHP version a release supports.
 *
 * @param string $package
 *   The package to look up.
 * @param string $version
 *   The release to read the requirement from.
 *
 * @return string
 *   The lowest supported PHP version, such as '8.4'.
 */
function symfony_matrix_php_floor(string $package, string $version): string {
  $payload = symfony_matrix_composer(['show', '--all', $package, $version, '--format=json']);
  $requirement = symfony_matrix_member($payload, 'requires')['php'] ?? '';

  if (!is_string($requirement) || preg_match('/>=\s*v?(\d+\.\d+)/', $requirement, $matches) !== 1) {
    throw new \RuntimeException(sprintf('Cannot read a PHP floor for %s %s.', $package, $version));
  }

  return $matches[1];
}

/**
 * Builds the matrix legs.
 *
 * @param array<int, string> $packages
 *   The Symfony packages to pin.
 * @param string $constraint
 *   The constraint the packages share.
 * @param array<int, string> $php_versions
 *   The PHP versions to test against.
 *
 * @return array<int, array<string, string>>
 *   One entry per leg, shaped for a GitHub Actions matrix include.
 */
function symfony_matrix_legs(array $packages, string $constraint, array $php_versions): array {
  $alternatives = preg_split('/\s*\|\|\s*/', trim($constraint));
  if ($alternatives === FALSE || $alternatives === []) {
    throw new \RuntimeException(sprintf('Cannot read alternatives out of "%s".', $constraint));
  }

  $lookup = $packages[0];
  $legs = [];

  foreach ($alternatives as $index => $alternative) {
    $floor = symfony_matrix_floor($alternative);
    $major = explode('.', $floor)[0];

    $bounds = [
      [
        'symfony' => sprintf('%s lowest', implode('.', array_slice(explode('.', $floor), 0, 2))),
        'constraint' => $floor,
        'expected' => 'v' . $floor,
        // The oldest floor doubles as the floor of every other dependency.
        'flags' => $index === 0 ? '--prefer-lowest --prefer-stable' : '',
        'version' => symfony_matrix_release($lookup, $floor),
      ],
      [
        'symfony' => sprintf('%s highest', $major),
        'constraint' => $alternative,
        'expected' => 'v' . $major . '.',
        'flags' => '',
        'version' => symfony_matrix_newest($lookup, $major),
      ],
    ];

    foreach ($bounds as $bound) {
      $php_floor = symfony_matrix_php_floor($lookup, $bound['version']);

      foreach ($php_versions as $php_version) {
        if (version_compare($php_version, $php_floor, '>=')) {
          $legs[] = [
            'php-versions' => $php_version,
            'symfony' => $bound['symfony'],
            'constraint' => $bound['constraint'],
            'expected' => $bound['expected'],
            'flags' => $bound['flags'],
            'packages' => implode(' ', $packages),
          ];
        }
      }
    }
  }

  usort($legs, function (array $a, array $b): int {
    return [$a['php-versions'], $a['symfony']] <=> [$b['php-versions'], $b['symfony']];
  });

  return $legs;
}

/**
 * Runs the script.
 *
 * @param array<int, string> $arguments
 *   Command line arguments.
 */
function main(array $arguments): void {
  $php_versions = [];
  $composer_json = dirname(__DIR__, 2) . '/composer.json';

  foreach (array_slice($arguments, 1) as $argument) {
    if (str_starts_with($argument, '--php=')) {
      $php_versions = array_values(array_filter(array_map('trim', explode(',', substr($argument, 6)))));
    }
    elseif (str_starts_with($argument, '--composer-json=')) {
      $composer_json = substr($argument, 16);
    }
    else {
      fwrite(STDERR, sprintf('Unknown argument: %s' . PHP_EOL, $argument));
      exit(EXIT_USAGE);
    }
  }

  if ($php_versions === []) {
    fwrite(STDERR, 'Usage: symfony-matrix.php --php=8.3,8.4,8.5 [--composer-json=PATH]' . PHP_EOL);
    exit(EXIT_USAGE);
  }

  [$packages, $constraint] = symfony_matrix_requirements($composer_json);

  print json_encode(['include' => symfony_matrix_legs($packages, $constraint, $php_versions)], JSON_UNESCAPED_SLASHES) . PHP_EOL;
}

main($argv);
