<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Tests\Functional;

use AlexSkrypnyk\PhpunitHelpers\Traits\ProcessTrait;
use AlexSkrypnyk\PhpunitHelpers\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Asserts argument placement by inspecting a real subprocess argv.
 */
#[CoversTrait(ProcessTrait::class)]
final class ProcessTraitArgumentsTest extends UnitTestCase {

  use ProcessTrait;

  protected function setUp(): void {
    parent::setUp();
    $this->processStreamingOutput = FALSE;
  }

  protected function tearDown(): void {
    $this->processTearDown();
    parent::tearDown();
  }

  #[DataProvider('dataProviderArgumentPlacement')]
  public function testArgumentPlacement(string $suffix, array $arguments, array $expected): void {
    if (DIRECTORY_SEPARATOR === '\\') {
      $this->markTestSkipped('Requires POSIX utilities.');
    }

    if (!self::$fixtures) {
      throw new \RuntimeException('Fixtures directory is not set.');
    }

    $this->processRun(self::$fixtures . '/argv-echo.sh' . $suffix, $arguments);

    $this->assertProcessSuccessful();
    $this->assertProcessOutputContains($expected);

    $output = $this->processGet()->getOutput();
    $this->assertSame(count($expected), substr_count($output, 'ARGV['), 'Subprocess received exactly the expected number of arguments.');
  }

  public static function dataProviderArgumentPlacement(): \Iterator {
    yield 'no_marker_arguments_appended' => [
      ' --flag',
      ['extra'],
      ['ARGV[1]=--flag', 'ARGV[2]=extra'],
    ];
    yield 'marker_arguments_inserted_before_it' => [
      ' -- positional',
      ['--flag'],
      ['ARGV[1]=--flag', 'ARGV[2]=--', 'ARGV[3]=positional'],
    ];
    yield 'marker_first_token_arguments_lead' => [
      ' -- -abc',
      ['--extra'],
      ['ARGV[1]=--extra', 'ARGV[2]=--', 'ARGV[3]=-abc'],
    ];
    yield 'marker_with_options_before_it' => [
      ' -v -- --dry-run',
      ['--extra'],
      ['ARGV[1]=-v', 'ARGV[2]=--extra', 'ARGV[3]=--', 'ARGV[4]=--dry-run'],
    ];
    yield 'only_first_marker_is_the_boundary' => [
      ' -- first -- second',
      ['--extra'],
      ['ARGV[1]=--extra', 'ARGV[2]=--', 'ARGV[3]=first', 'ARGV[4]=--', 'ARGV[5]=second'],
    ];
    yield 'marker_without_arguments_passes_through' => [
      ' -- positional',
      [],
      ['ARGV[1]=--', 'ARGV[2]=positional'],
    ];
    yield 'quoted_marker_is_not_a_boundary' => [
      ' "-- quoted" tail',
      ['--extra'],
      ['ARGV[1]=-- quoted', 'ARGV[2]=tail', 'ARGV[3]=--extra'],
    ];
  }

}
