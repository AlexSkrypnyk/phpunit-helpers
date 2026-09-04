<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Tests\Fixtures;

/**
 * Collects everything written to a stream instead of letting it through.
 *
 * Attach with stream_filter_append() to read what code under test writes to a
 * stream it opened itself, such as STDOUT.
 */
class StreamCaptureFilter extends \php_user_filter {

  /**
   * Data written to the filtered stream since the last reset.
   */
  public static string $captured = '';

  /**
   * {@inheritdoc}
   */
  public function filter($in, $out, &$consumed, bool $closing): int {
    while ($bucket = stream_bucket_make_writeable($in)) {
      static::$captured .= $bucket->data;
      $consumed += $bucket->datalen;
    }

    return PSFS_PASS_ON;
  }

}
