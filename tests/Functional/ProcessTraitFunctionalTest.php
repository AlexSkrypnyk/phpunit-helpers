<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Tests\Functional;

use AlexSkrypnyk\PhpunitHelpers\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Meta-test for ProcessTrait streaming and assertion suffix functionality.
 */
#[CoversNothing]
class ProcessTraitFunctionalTest extends UnitTestCase {

  public function testProcessTraitStreamingWithDebugEnabled(): void {
    $output = $this->runPhpunit(TRUE);
    $combined = $output['combined'];

    // Assert streaming output appears when DEBUG=1
    $this->assertStringContainsString('>> Success stdout', $combined);
    $this->assertStringContainsString('XX Success stderr', $combined);
    $this->assertStringContainsString('>> Failed stdout', $combined);
    $this->assertStringContainsString('XX Failed stderr', $combined);

    // Assert onNotSuccessfulTest() debug output appears in stderr
    $this->assertStringContainsString('Error: ', $output['stderr']);
    $this->assertStringContainsString('Additional information:', $output['stderr']);

    // Assert common failure output
    $this->assertStringContainsString('PROCESS FAILED', $combined);
    $this->assertStringContainsString('PROCESS SUCCEEDED but failure was expected', $combined);
    $this->assertStringContainsString('Additional information:', $combined);
    $this->assertStringContainsString('LOCATIONS', $combined);
    $this->assertStringContainsString('Tests: 4', $combined);
    $this->assertStringContainsString('Failures: 2', $combined);

    // Assert that actual process output appears in failure messages
    $this->assertStringContainsString('Success stdout', $combined);
    $this->assertStringContainsString('Success stderr', $combined);
    $this->assertStringContainsString('Failed stdout', $combined);
    $this->assertStringContainsString('Failed stderr', $combined);

    // Assert that process output headers and footers appear
    $this->assertStringContainsString('⬇⬇⬇ STANDARD OUTPUT ⬇⬇⬇', $combined);
    $this->assertStringContainsString('⬆⬆⬆ STANDARD OUTPUT ⬆⬆⬆', $combined);
    $this->assertStringContainsString('▼▼▼ ERROR OUTPUT ▼▼▼', $combined);
    $this->assertStringContainsString('▲▲▲ ERROR OUTPUT ▲▲▲', $combined);

    // Assert streaming timing
    $this->assertStreamingTiming($output['real_time_output']);
  }

  public function testProcessTraitStreamingWithDebugDisabled(): void {
    $output = $this->runPhpunit(FALSE);
    $combined = $output['combined'];

    // Assert streaming output does NOT appear when DEBUG=0
    $this->assertStringNotContainsString('>> Success stdout', $combined);
    $this->assertStringNotContainsString('XX Success stderr', $combined);
    $this->assertStringNotContainsString('>> Failed stdout', $combined);
    $this->assertStringNotContainsString('XX Failed stderr', $combined);

    // Assert onNotSuccessfulTest() debug output does NOT appear when DEBUG=0
    $this->assertStringNotContainsString('Error: ', $output['stderr']);

    // Assert common failure output still appears
    $this->assertStringContainsString('PROCESS FAILED', $combined);
    $this->assertStringContainsString('PROCESS SUCCEEDED but failure was expected', $combined);
    $this->assertStringContainsString('Additional information:', $combined);
    $this->assertStringContainsString('LOCATIONS', $combined);
    $this->assertStringContainsString('Tests: 4', $combined);
    $this->assertStringContainsString('Failures: 2', $combined);

    // Assert that actual process output appears in failure messages
    $this->assertStringContainsString('Success stdout', $combined);
    $this->assertStringContainsString('Success stderr', $combined);
    $this->assertStringContainsString('Failed stdout', $combined);
    $this->assertStringContainsString('Failed stderr', $combined);

    // Assert that process output headers and footers appear
    $this->assertStringContainsString('⬇⬇⬇ STANDARD OUTPUT ⬇⬇⬇', $combined);
    $this->assertStringContainsString('⬆⬆⬆ STANDARD OUTPUT ⬆⬆⬆', $combined);
    $this->assertStringContainsString('▼▼▼ ERROR OUTPUT ▼▼▼', $combined);
    $this->assertStringContainsString('▲▲▲ ERROR OUTPUT ▲▲▲', $combined);
  }

  private function runPhpunit(bool $with_debug): array {
    $vendor_dir = dirname(__DIR__, 2) . '/vendor';
    $phpunit_bin = $vendor_dir . '/bin/phpunit';

    $env = $with_debug ? 'DEBUG=1' : 'DEBUG=0';
    $cmd = sprintf('%s %s --no-coverage --group=manual', $env, $phpunit_bin);

    $descriptors = [
      0 => ['pipe', 'r'],
      1 => ['pipe', 'w'],
      2 => ['pipe', 'w'],
    ];

    $process = proc_open($cmd, $descriptors, $pipes);

    if (!is_resource($process)) {
      $this->fail('Failed to start PHPUnit process');
    }

    fclose($pipes[0]);

    $stdout = '';
    $stderr = '';
    $real_time_output = [];

    stream_set_blocking($pipes[1], FALSE);
    stream_set_blocking($pipes[2], FALSE);

    while (TRUE) {
      $read = [$pipes[1], $pipes[2]];
      $write = NULL;
      $except = NULL;

      if (stream_select($read, $write, $except, 0, 100000) > 0) {
        foreach ($read as $stream) {
          $data = fread($stream, 8192);
          if ($data !== FALSE && $data !== '') {
            if ($stream === $pipes[1]) {
              $stdout .= $data;
              $real_time_output[] = ['type' => 'stdout', 'data' => $data, 'time' => microtime(TRUE)];
            }
            else {
              $stderr .= $data;
              $real_time_output[] = ['type' => 'stderr', 'data' => $data, 'time' => microtime(TRUE)];
            }
          }
        }
      }

      $status = proc_get_status($process);
      if (!$status['running']) {
        $remaining_stdout = stream_get_contents($pipes[1]);
        $remaining_stderr = stream_get_contents($pipes[2]);
        if ($remaining_stdout) {
          $stdout .= $remaining_stdout;
          $real_time_output[] = ['type' => 'stdout', 'data' => $remaining_stdout, 'time' => microtime(TRUE)];
        }
        if ($remaining_stderr) {
          $stderr .= $remaining_stderr;
          $real_time_output[] = ['type' => 'stderr', 'data' => $remaining_stderr, 'time' => microtime(TRUE)];
        }
        break;
      }
    }

    fclose($pipes[1]);
    fclose($pipes[2]);
    $exit_code = proc_close($process);

    return [
      'stdout' => $stdout,
      'stderr' => $stderr,
      'exit_code' => $exit_code,
      'real_time_output' => $real_time_output,
      'combined' => $stderr . $stdout,
    ];
  }

  private function assertStreamingTiming(array $real_time_output): void {
    $streaming_phase = [];
    $final_phase = [];

    foreach ($real_time_output as $item) {
      if (str_contains($item['data'], '>>') || str_contains($item['data'], 'XX')) {
        $streaming_phase[] = $item;
      }
      elseif (str_contains($item['data'], 'Additional information:') ||
                str_contains($item['data'], 'LOCATIONS') ||
                str_contains($item['data'], 'FAILURES!')) {
        $final_phase[] = $item;
      }
    }

    foreach ($streaming_phase as $item) {
      $this->assertStringNotContainsString('Additional information:', $item['data']);
      $this->assertStringNotContainsString('LOCATIONS', $item['data']);
    }

    $final_output = implode('', array_column($final_phase, 'data'));
    $this->assertStringContainsString('Additional information:', $final_output);
  }

}
