<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Tests\Functional;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * Tests the matrix CI builds from the Symfony constraint in composer.json.
 */
#[CoversNothing]
final class SymfonyMatrixTest extends TestCase {

  /**
   * Exit code the script uses for a malformed invocation.
   */
  protected const int EXIT_USAGE = 64;

  /**
   * PHP requirement of each Symfony release the stub Composer knows about.
   */
  protected const array RELEASES = [
    'v8.1.7' => '>=8.4.1',
    'v8.0.0' => '>=8.4.1',
    'v6.4.46' => '>=8.1',
    'v6.4.0' => '>=8.1',
  ];

  /**
   * Root of the repository.
   */
  protected string $root;

  /**
   * Directory holding the fixtures built for a single test.
   */
  protected string $workspace;

  #[\Override]
  protected function setUp(): void {
    $this->root = dirname(__DIR__, 2);
    $this->workspace = $this->root . '/.artifacts/tmp/symfony-matrix-' . uniqid();
    $this->assertTrue(mkdir($this->workspace, 0777, TRUE));
  }

  #[\Override]
  protected function tearDown(): void {
    exec(sprintf('rm -rf %s', escapeshellarg($this->workspace)));
  }

  public function testBuildsOneLegPerBoundAndSupportedPhpVersion(): void {
    $path = $this->createComposerJson('^6.4 || ^8.0');

    [$exit_code, $output] = $this->build(['--php=8.3,8.4', '--composer-json=' . $path]);

    $this->assertSame(0, $exit_code, 'Output: ' . $output);

    $legs = $this->decodeLegs($output);

    $this->assertSame([
      ['8.3', '6 highest'],
      ['8.3', '6.4 lowest'],
      ['8.4', '6 highest'],
      ['8.4', '6.4 lowest'],
      ['8.4', '8 highest'],
      ['8.4', '8.0 lowest'],
    ], array_map(fn(array $leg): array => [$leg['php-versions'], $leg['symfony']], $legs));
  }

  public function testPinsEachBoundAndFloorsTheWholeTreeOnce(): void {
    $path = $this->createComposerJson('^6.4 || ^8.0');

    [, $output] = $this->build(['--php=8.4', '--composer-json=' . $path]);

    $by_bound = array_column($this->decodeLegs($output), NULL, 'symfony');

    $this->assertSame('6.4.0', $by_bound['6.4 lowest']['constraint']);
    $this->assertSame('v6.4.0', $by_bound['6.4 lowest']['expected']);
    $this->assertSame('--prefer-lowest --prefer-stable', $by_bound['6.4 lowest']['flags']);

    $this->assertSame('^6.4', $by_bound['6 highest']['constraint']);
    $this->assertSame('v6.', $by_bound['6 highest']['expected']);
    $this->assertSame('', $by_bound['6 highest']['flags']);

    $this->assertSame('8.0.0', $by_bound['8.0 lowest']['constraint']);
    $this->assertSame('', $by_bound['8.0 lowest']['flags'], 'Only the oldest floor carries the whole tree down.');

    $this->assertSame('symfony/console symfony/finder symfony/process', $by_bound['8 highest']['packages']);
  }

  public function testFloorsTheTreeOnTheOldestAlternativeWhicheverOrderItIsWrittenIn(): void {
    $path = $this->createComposerJson('^8.0 || ^6.4');

    [, $output] = $this->build(['--php=8.4', '--composer-json=' . $path]);

    $by_bound = array_column($this->decodeLegs($output), NULL, 'symfony');

    $this->assertSame('--prefer-lowest --prefer-stable', $by_bound['6.4 lowest']['flags']);
    $this->assertSame('', $by_bound['8.0 lowest']['flags']);
  }

  public function testFailsWhenTheSymfonyPackagesDisagree(): void {
    $path = $this->workspace . '/composer.json';
    file_put_contents($path, json_encode([
      'require' => ['symfony/finder' => '^6.4', 'symfony/process' => '^7.2'],
    ], JSON_THROW_ON_ERROR));

    [$exit_code, $output] = $this->build(['--php=8.4', '--composer-json=' . $path]);

    $this->assertNotSame(0, $exit_code);
    $this->assertStringContainsString('do not share one constraint', $output);
  }

  public function testReportsUsageWithoutPhpVersions(): void {
    [$exit_code, $output] = $this->build([]);

    $this->assertSame(self::EXIT_USAGE, $exit_code);
    $this->assertStringContainsString('Usage:', $output);
  }

  /**
   * Decodes the matrix the script printed.
   *
   * @param string $output
   *   The output of a script run.
   *
   * @return array<int, array<string, string>>
   *   One entry per leg, with every value as a string.
   */
  protected function decodeLegs(string $output): array {
    $decoded = json_decode($output, TRUE, 512, JSON_THROW_ON_ERROR);

    if (!is_array($decoded) || !is_array($decoded['include'] ?? NULL)) {
      $this->fail('The script printed no matrix: ' . $output);
    }

    $legs = [];
    foreach ($decoded['include'] as $leg) {
      if (!is_array($leg)) {
        $this->fail('The script printed a leg that is not an object: ' . $output);
      }

      $values = [];
      foreach ($leg as $key => $value) {
        $values[(string) $key] = is_scalar($value) ? (string) $value : '';
      }

      $legs[] = $values;
    }

    return $legs;
  }

  /**
   * Returns the PHP binary the stub runs under.
   */
  protected function phpBinary(): string {
    return PHP_BINARY;
  }

  /**
   * Writes a composer.json requiring Symfony under the given constraint.
   */
  protected function createComposerJson(string $constraint): string {
    $path = $this->workspace . '/composer.json';

    file_put_contents($path, json_encode([
      'require' => [
        'php' => '>=8.3',
        'symfony/finder' => $constraint,
        'symfony/process' => $constraint,
      ],
      'require-dev' => [
        'symfony/console' => $constraint,
      ],
    ], JSON_THROW_ON_ERROR));

    return $path;
  }

  /**
   * Runs the script against a Composer stub that answers from RELEASES.
   *
   * @param array<int, string> $arguments
   *   Arguments to pass to the script.
   *
   * @return array{0: int, 1: string}
   *   The exit code and the combined output.
   */
  protected function build(array $arguments): array {
    $stub = $this->workspace . '/composer-stub.php';

    $releases = var_export(self::RELEASES, TRUE);
    file_put_contents($stub, <<<PHP
    #!{$this->phpBinary()}
    <?php
    \$positional = array_values(array_filter(array_slice(\$argv, 1), fn(\$argument) => !str_starts_with(\$argument, '--')));
    \$releases = {$releases};
    \$version = \$positional[2] ?? '';
    print \$version === ''
      ? json_encode(['versions' => array_merge(array_keys(\$releases), ['6.4.x-dev'])])
      : json_encode(['requires' => ['php' => \$releases[\$version] ?? '>=8.1']]);
    PHP);
    $this->assertTrue(chmod($stub, 0755));

    $command = sprintf(
      'SYMFONY_MATRIX_COMPOSER=%s %s %s %s 2>&1',
      escapeshellarg($stub),
      escapeshellarg(PHP_BINARY),
      escapeshellarg($this->root . '/.github/scripts/symfony-matrix.php'),
      implode(' ', array_map(escapeshellarg(...), $arguments))
    );

    exec($command, $output, $exit_code);

    return [$exit_code, implode(PHP_EOL, $output)];
  }

}
