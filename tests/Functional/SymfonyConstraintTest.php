<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Tests\Functional;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests the Symfony version CI resolves for a leg out of composer.json.
 */
#[CoversNothing]
final class SymfonyConstraintTest extends TestCase {

  /**
   * Exit code the script uses for a malformed invocation.
   */
  protected const int EXIT_USAGE = 64;

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
    $this->workspace = $this->root . '/.artifacts/tmp/symfony-constraint-' . uniqid();
    $this->assertTrue(mkdir($this->workspace, 0777, TRUE));
  }

  #[\Override]
  protected function tearDown(): void {
    exec(sprintf('rm -rf %s', escapeshellarg($this->workspace)));
  }

  #[DataProvider('dataProviderResolvesEachDepsPreferenceOfEachMajor')]
  public function testResolvesEachDepsPreferenceOfEachMajor(string $major, string $deps, string $version, string $expected): void {
    $path = $this->createComposerJson('^6.4 || ^7.2 || ^8.0');

    [$exit_code, $output] = $this->resolve(['--major=' . $major, '--deps=' . $deps, '--composer-json=' . $path]);

    $this->assertSame(0, $exit_code, 'Output: ' . $output);
    $this->assertSame(['SYMFONY_VERSION=' . $version, 'SYMFONY_EXPECTED=' . $expected], explode(PHP_EOL, $output));
  }

  public static function dataProviderResolvesEachDepsPreferenceOfEachMajor(): \Iterator {
    yield '6 lowest' => ['6', 'lowest', '6.4.0', 'v6.4.0'];
    yield '6 normal' => ['6', 'normal', '^6.4', 'v6.'];
    yield '7 lowest' => ['7', 'lowest', '7.2.0', 'v7.2.0'];
    yield '7 normal' => ['7', 'normal', '^7.2', 'v7.'];
    yield '8 lowest' => ['8', 'lowest', '8.0.0', 'v8.0.0'];
    yield '8 normal' => ['8', 'normal', '^8.0', 'v8.'];
  }

  public function testFollowsTheConstraintWhenItMoves(): void {
    $path = $this->createComposerJson('^6.4 || ^7.3');

    [, $output] = $this->resolve(['--major=7', '--deps=lowest', '--composer-json=' . $path]);

    $this->assertStringContainsString('SYMFONY_VERSION=7.3.0', $output);
  }

  public function testFailsWhenTheConstraintCoversNoSuchMajor(): void {
    $path = $this->createComposerJson('^6.4 || ^7.2');

    [$exit_code, $output] = $this->resolve(['--major=8', '--deps=lowest', '--composer-json=' . $path]);

    $this->assertNotSame(0, $exit_code);
    $this->assertStringContainsString('covers no Symfony 8', $output);
  }

  public function testFailsWhenTheSymfonyPackagesDisagree(): void {
    $path = $this->workspace . '/composer.json';
    file_put_contents($path, json_encode([
      'require' => ['symfony/finder' => '^6.4', 'symfony/process' => '^7.2'],
    ], JSON_THROW_ON_ERROR));

    [$exit_code, $output] = $this->resolve(['--major=6', '--deps=lowest', '--composer-json=' . $path]);

    $this->assertNotSame(0, $exit_code);
    $this->assertStringContainsString('do not share one constraint', $output);
  }

  #[DataProvider('dataProviderReportsUsageForMalformedArguments')]
  public function testReportsUsageForMalformedArguments(array $arguments): void {
    [$exit_code, $output] = $this->resolve($arguments);

    $this->assertSame(self::EXIT_USAGE, $exit_code);
    $this->assertStringContainsString('Usage:', $output);
  }

  public static function dataProviderReportsUsageForMalformedArguments(): \Iterator {
    yield 'no arguments' => [[]];
    yield 'no deps' => [['--major=6']];
    yield 'no major' => [['--deps=lowest']];
    yield 'unknown deps' => [['--major=6', '--deps=middle']];
    yield 'major is not a number' => [['--major=six', '--deps=lowest']];
  }

  public function testReportsUsageForAnUnknownArgument(): void {
    [$exit_code, $output] = $this->resolve(['--flavour=vanilla']);

    $this->assertSame(self::EXIT_USAGE, $exit_code);
    $this->assertStringContainsString('Unknown argument', $output);
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
   * Runs the script.
   *
   * @param array<int, string> $arguments
   *   Arguments to pass to the script.
   *
   * @return array{0: int, 1: string}
   *   The exit code and the combined output.
   */
  protected function resolve(array $arguments): array {
    $command = sprintf(
      '%s %s %s 2>&1',
      escapeshellarg(PHP_BINARY),
      escapeshellarg($this->root . '/.github/scripts/symfony-constraint.php'),
      implode(' ', array_map(escapeshellarg(...), $arguments))
    );

    exec($command, $output, $exit_code);

    return [$exit_code, implode(PHP_EOL, $output)];
  }

}
