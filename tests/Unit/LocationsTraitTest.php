<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Tests\Unit;

use AlexSkrypnyk\PhpunitHelpers\Traits\LocationsTrait;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

#[CoversTrait(LocationsTrait::class)]
class LocationsTraitTest extends TestCase {

  use LocationsTrait;

  protected string $testTmp;

  protected string $testCwd;

  protected string $testFixtures;

  protected function setUp(): void {
    $this->testTmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('locations_trait_test_tmp_', TRUE);
    mkdir($this->testTmp, 0777, TRUE);

    $this->testCwd = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('locations_trait_test_cwd_', TRUE);
    mkdir($this->testCwd, 0777, TRUE);

    $this->testFixtures = $this->testCwd . DIRECTORY_SEPARATOR . static::locationsFixturesDir();
    mkdir($this->testFixtures, 0777, TRUE);
  }

  protected function tearDown(): void {
    if (is_dir($this->testTmp)) {
      rmdir($this->testTmp);
    }
    if (is_dir($this->testCwd)) {
      rmdir($this->testCwd);
    }
    if (is_dir($this->testFixtures)) {
      rmdir($this->testFixtures);
    }
  }

  public function testLocationsInit(): void {
    $this->locationsInit($this->testCwd);

    $this->assertSame(static::locationsRealpath($this->testCwd), static::locationsRealpath(static::$root));
    $this->assertDirectoryExists(static::$workspace);
    $this->assertStringContainsString('workspace-', static::$workspace);
    $this->assertDirectoryExists(static::$repo);
    $this->assertDirectoryExists(static::$sut);
    $this->assertDirectoryExists(static::$tmp);

    $this->assertNotEmpty(static::$fixtures);
    $this->assertSame(static::locationsRealpath($this->testFixtures), static::locationsRealpath(static::$fixtures ?? ''));

    $after_called = FALSE;
    $after = function () use (&$after_called): void {
      $after_called = TRUE;
    };

    $this->locationsInit($this->testCwd, $after);
    $this->assertTrue($after_called, 'Closure was called after initialization');

    $info = self::locationsInfo();

    $this->assertStringContainsString('Root', $info);
    $this->assertStringContainsString('Fixtures', $info);
    $this->assertStringContainsString('Workspace', $info);
    $this->assertStringContainsString('Repo', $info);
    $this->assertStringContainsString('SUT', $info);
    $this->assertStringContainsString('Temp', $info);
  }

  public function testLocationsInitWithAfter(): void {
    $after_called = FALSE;
    $after = function () use (&$after_called): void {
      $after_called = TRUE;
    };

    $this->locationsInit($this->testCwd, $after);
    $this->assertTrue($after_called, 'Closure was called after initialization');
  }

  public function testLocationsInitWithDefaultCwd(): void {
    $original_cwd = getcwd();
    if ($original_cwd === FALSE) {
      $this->markTestSkipped('Could not determine current working directory.');
    }

    chdir($this->testCwd);

    try {
      $this->locationsInit();

      $this->assertSame(static::locationsRealpath($this->testCwd), static::locationsRealpath(static::$root));
      $this->assertDirectoryExists(static::$workspace);
      $this->assertDirectoryExists(static::$repo);
      $this->assertDirectoryExists(static::$sut);
      $this->assertDirectoryExists(static::$tmp);
    }
    finally {
      // Restore original working directory.
      chdir($original_cwd);
    }
  }

  public function testLocationsInitWithoutFixturesDir(): void {
    $test_cwd_no_fixtures = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('locations_trait_test_no_fixtures_', TRUE);
    mkdir($test_cwd_no_fixtures, 0777, TRUE);

    $mock_fixture_dir = 'nonexistent_fixtures_directory';
    $original_fixtures = static::$fixtures;
    $original_cwd = getcwd();
    if ($original_cwd === FALSE) {
      $this->markTestSkipped('Could not determine current working directory.');
    }

    static::$fixtures = NULL;

    try {
      // Change to the test directory.
      chdir($test_cwd_no_fixtures);

      $mock = $this->createPartialMock(static::class, ['locationsFixturesDir']);
      $mock->expects($this->any())
        ->method('locationsFixturesDir')
        ->willReturn($mock_fixture_dir);

      $reflection_method = new \ReflectionMethod(static::class, 'locationsInit');
      $reflection_method->setAccessible(TRUE);
      $reflection_method->invoke($this);

      $this->assertNull(static::$fixtures, 'Fixtures property should be null when directory does not exist.');
    }
    finally {
      // Restore original state.
      static::$fixtures = $original_fixtures;
      chdir($original_cwd);

      if (is_dir($test_cwd_no_fixtures)) {
        rmdir($test_cwd_no_fixtures);
      }
    }
  }

  public function testLocationsFixtureDirCustomName(): void {
    $this->locationsInit($this->testCwd);

    mkdir($this->testFixtures . DIRECTORY_SEPARATOR . 'test_fixture_custom_name', 0777, TRUE);
    touch($this->testFixtures . DIRECTORY_SEPARATOR . 'test_fixture_custom_name' . DIRECTORY_SEPARATOR . 'test_file.txt');
    $fixture_dir = $this->locationsFixtureDir('test_fixture_custom_name');
    $this->assertStringContainsString($this->testCwd, $fixture_dir);
    $this->assertStringEndsWith('test_fixture_custom_name', $fixture_dir);
  }

  public function testLocationsFixtureDirTestName(): void {
    $this->locationsInit($this->testCwd);

    mkdir($this->testFixtures . DIRECTORY_SEPARATOR . 'locations_fixture_dir_name', 0777, TRUE);
    touch($this->testFixtures . DIRECTORY_SEPARATOR . 'locations_fixture_dir_name' . DIRECTORY_SEPARATOR . 'test_file.txt');

    $fixture_dir = $this->locationsFixtureDir();

    $this->assertStringContainsString($this->testCwd, $fixture_dir);
    $this->assertStringEndsWith('locations_fixture_dir_name', $fixture_dir);
  }

  #[DataProvider('dataProviderLocationsFixtureDirTestNameWithDataSetNames')]
  public function testLocationsFixtureDirTestNameWithDataSetNames(string $expected): void {
    $this->locationsInit($this->testCwd);

    mkdir($this->testFixtures . DIRECTORY_SEPARATOR . 'locations_fixture_dir_name_with_data_set_names' . DIRECTORY_SEPARATOR . $expected, 0777, TRUE);
    touch($this->testFixtures . DIRECTORY_SEPARATOR . 'locations_fixture_dir_name_with_data_set_names' . DIRECTORY_SEPARATOR . $expected . DIRECTORY_SEPARATOR . 'test_file.txt');

    $fixture_dir = $this->locationsFixtureDir();

    $this->assertNotEmpty($fixture_dir);
    $this->assertNotEmpty($expected);
    $this->assertStringContainsString($this->testCwd, $fixture_dir);
    $this->assertStringContainsString('locations_fixture_dir_name_with_data_set_names', $fixture_dir);
    if ($expected !== '') {
      $this->assertStringEndsWith($expected, $fixture_dir);
    }
  }

  public static function dataProviderLocationsFixtureDirTestNameWithDataSetNames(): array {
    return [
      'simple name' => ['simple_name'],
      'Complex-Name' => ['complex_name'],
      'name with spaces' => ['name_with_spaces'],
      'name_with_underscores' => ['name_with_underscores'],
      'name@with#special$chars' => ['namewithspecialchars'],
    ];
  }

  public function testLocationsCopyFilesToSut(): void {
    $this->locationsInit($this->testCwd);

    $source_dir = $this->testTmp . DIRECTORY_SEPARATOR . 'source';
    mkdir($source_dir, 0777, TRUE);

    $file1 = $source_dir . DIRECTORY_SEPARATOR . 'file1.txt';
    $file2 = $source_dir . DIRECTORY_SEPARATOR . 'file2.txt';

    file_put_contents($file1, 'Test file 1');
    file_put_contents($file2, 'Test file 2');

    // Test with default parameters.
    $files = [$file1, $file2];
    $copied_files = self::locationsCopyFilesToSut($files);

    $this->assertCount(0, $copied_files);

    // Test with explicit base directory and no random suffix.
    $copied_files = self::locationsCopyFilesToSut($files, $source_dir, FALSE);

    $this->assertCount(2, $copied_files);
    foreach ($copied_files as $copied_file) {
      $this->assertFileExists($copied_file);
      $this->assertNotEmpty(static::$sut);
      if (static::$sut !== '') {
        $this->assertStringStartsWith(static::$sut, $copied_file);
      }
      $filename = basename($copied_file);
      $this->assertContains($filename, ['file1.txt', 'file2.txt'], 'No random suffix was added');
    }
  }

  public function testLocationsTearDown(): void {
    // Create a workspace directory with some content to test removal.
    static::$workspace = $this->testTmp . DIRECTORY_SEPARATOR . 'test_workspace_' . uniqid();
    mkdir(static::$workspace, 0777, TRUE);
    touch(static::$workspace . DIRECTORY_SEPARATOR . 'test_file.txt');
    $this->locationsTearDown();
    $this->assertDirectoryDoesNotExist(static::$workspace, 'Workspace should be removed.');
  }

  public function testLocationsTearDownWithRestrictedPermissions(): void {
    // Create a workspace directory with restricted permissions to test chmod
    // handling.
    static::$workspace = $this->testTmp . DIRECTORY_SEPARATOR . 'test_workspace_restricted_' . uniqid();
    mkdir(static::$workspace, 0777, TRUE);

    // Create a subdirectory structure with files.
    $subdir = static::$workspace . DIRECTORY_SEPARATOR . 'subdir';
    mkdir($subdir, 0777, TRUE);
    touch($subdir . DIRECTORY_SEPARATOR . 'test_file.txt');

    // Make the subdirectory read-only to simulate permission issues.
    chmod($subdir, 0555);

    // Verify the directory is read-only.
    $this->assertFalse(is_writable($subdir), 'Subdirectory should be read-only.');

    // locationsTearDown should handle the chmod and remove the directory
    // successfully.
    $this->locationsTearDown();

    // Verify the workspace was completely removed.
    $this->assertDirectoryDoesNotExist(static::$workspace, 'Workspace with restricted permissions should be removed.');
  }

  #[DataProvider('dataProviderLocationsCopy')]
  public function testLocationsCopy(
    array $source_files,
    array $include_files,
    array $exclude_dirs,
    bool $use_before_callback,
    int $expected_count,
  ): void {
    $this->locationsInit($this->testCwd);

    $source_dir = $this->testTmp . DIRECTORY_SEPARATOR . 'copy_source_' . uniqid();
    mkdir($source_dir, 0777, TRUE);
    if (!is_dir($source_dir)) {
      throw new \RuntimeException('Failed to create source directory: ' . $source_dir);
    }

    $file_paths = [];
    foreach ($source_files as $relative_path => $content) {
      $full_path = $source_dir . DIRECTORY_SEPARATOR . $relative_path;
      $dir = dirname($full_path);
      if (!is_dir($dir)) {
        mkdir($dir, 0777, TRUE);
      }
      file_put_contents($full_path, $content);
      $file_paths[$relative_path] = $full_path;
    }

    // Create a symlink for the symlink test case.
    if (isset($source_files['link_target.txt'])) {
      $target = $source_dir . DIRECTORY_SEPARATOR . 'link_target.txt';
      $link = $source_dir . DIRECTORY_SEPARATOR . 'symlink.txt';
      if (!file_exists($link) && file_exists($target)) {
        symlink($target, $link);
        $file_paths['symlink.txt'] = $link;
      }
    }

    $dest_dir = $this->testTmp . DIRECTORY_SEPARATOR . 'copy_dest_' . uniqid();
    mkdir($dest_dir, 0777, TRUE);

    $processed_include_files = [];
    foreach ($include_files as $include_file) {
      if (isset($file_paths[$include_file])) {
        $processed_include_files[] = $file_paths[$include_file];
      }
    }

    $before_callback = NULL;
    if ($use_before_callback) {
      $before_callback = function (string &$src, string &$dst): void {
        $dst .= '.modified';
      };
    }

    $result = static::locationsCopy(
      $source_dir,
      $dest_dir,
      $processed_include_files,
      $exclude_dirs,
      $before_callback,
    );

    $this->assertCount($expected_count, $result, 'Unexpected number of files copied.');

    foreach ($result as $file) {
      $this->assertFileExists($file, 'Copied file should exist.');
      if ($use_before_callback) {
        $this->assertStringEndsWith('.modified', $file, 'File should have been modified by callback.');
      }
    }

    (new Filesystem())->remove($source_dir);
    (new Filesystem())->remove($dest_dir);
  }

  public static function dataProviderLocationsCopy(): array {
    return [
      'empty_include' => [
        [
          'file1.txt' => 'content1',
          'file2.txt' => 'content2',
          'subdir/file3.txt' => 'content3',
        ],
        [],
        [],
        FALSE,
        3,
      ],
      'include_specific_files' => [
        [
          'file1.txt' => 'content1',
          'file2.txt' => 'content2',
          'subdir/file3.txt' => 'content3',
        ],
        ['file1.txt'],
        [],
        FALSE,
        1,
      ],
      'include_multiple_files' => [
        [
          'file1.txt' => 'content1',
          'file2.txt' => 'content2',
          'subdir/file3.txt' => 'content3',
        ],
        ['file1.txt', 'file2.txt'],
        [],
        FALSE,
        2,
      ],
      'include_with_exclude' => [
        [
          'file1.txt' => 'content1',
          'file2.txt' => 'content2',
          'subdir/file3.txt' => 'content3',
        ],
        ['file1.txt', 'file2.txt', 'subdir/file3.txt'],
        ['subdir'],
        FALSE,
        2,
      ],
      'with_before_callback' => [
        [
          'file1.txt' => 'content1',
          'file2.txt' => 'content2',
        ],
        ['file1.txt', 'file2.txt'],
        [],
        TRUE,
        2,
      ],
      'empty_source' => [
        [],
        [],
        [],
        FALSE,
        0,
      ],
      'include_nested_files' => [
        [
          'file1.txt' => 'content1',
          'subdir/file2.txt' => 'content2',
          'subdir/nested/file3.txt' => 'content3',
        ],
        ['subdir/file2.txt', 'subdir/nested/file3.txt'],
        [],
        FALSE,
        2,
      ],
      'dotfiles_included' => [
        [
          '.file1.txt' => 'content1',
          '.hidden/file2.txt' => 'content2',
        ],
        ['.file1.txt', '.hidden/file2.txt'],
        [],
        FALSE,
        2,
      ],
      'symlink_handling' => [
        [
          'file1.txt' => 'content1',
          'link_target.txt' => 'target content',
        ],
        ['file1.txt', 'link_target.txt', 'symlink.txt'],
        [],
        FALSE,
        3,
      ],
    ];
  }

  public function testLocationGetters(): void {
    $this->locationsInit($this->testCwd);

    // Test all getter methods return the same values as direct property access
    $this->assertSame(static::$root, static::locationsRoot());
    $this->assertSame(static::$fixtures, static::locationsFixtures());
    $this->assertSame(static::$workspace, static::locationsWorkspace());
    $this->assertSame(static::$repo, static::locationsRepo());
    $this->assertSame(static::$sut, static::locationsSut());
    $this->assertSame(static::$tmp, static::locationsTmp());

    // Test that getters return expected directory types
    $this->assertDirectoryExists(static::locationsRoot());
    $this->assertDirectoryExists(static::locationsWorkspace());
    $this->assertDirectoryExists(static::locationsRepo());
    $this->assertDirectoryExists(static::locationsSut());
    $this->assertDirectoryExists(static::locationsTmp());

    // Test fixtures getter specifically (can be null)
    if (static::locationsFixtures() !== NULL) {
      $this->assertDirectoryExists(static::locationsFixtures());
    }
  }

  public function testLocationsTearDownWithChmodException(): void {
    // Create a workspace directory with restricted permissions to simulate
    // chmod issues.
    static::$workspace = $this->testTmp . DIRECTORY_SEPARATOR . 'test_workspace_chmod_fail_' . uniqid();
    mkdir(static::$workspace, 0777, TRUE);

    // Create a subdirectory and file structure that might cause chmod issues.
    $subdir = static::$workspace . DIRECTORY_SEPARATOR . 'readonly_subdir';
    mkdir($subdir, 0777, TRUE);
    touch($subdir . DIRECTORY_SEPARATOR . 'test_file.txt');

    // Make the subdirectory read-only to potentially trigger chmod issues
    // when locationsTearDown tries to modify permissions.
    chmod($subdir, 0444);

    // Test that locationsTearDown completes successfully even if chmod fails.
    // This tests the exception handling path in the actual implementation.
    $this->locationsTearDown();

    // Verify the workspace was removed despite potential chmod exceptions.
    $this->assertDirectoryDoesNotExist(static::$workspace, 'Workspace should be removed even with chmod exception.');
  }

  public function testLocationsFixtureDirThrowsExceptionWhenFixturesDirectoryMissing(): void {
    // Create a temporary directory without fixtures subdirectory.
    $test_cwd_no_fixtures = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('locations_trait_test_no_fixtures_dir_', TRUE);
    mkdir($test_cwd_no_fixtures, 0777, TRUE);

    try {
      $this->locationsInit($test_cwd_no_fixtures);
      static::$fixtures = NULL;

      $this->expectException(\RuntimeException::class);
      $this->expectExceptionMessage('Fixtures directory');

      $this->locationsFixtureDir();
    }
    finally {
      if (is_dir($test_cwd_no_fixtures)) {
        rmdir($test_cwd_no_fixtures);
      }
    }
  }

  #[DataProvider('dataProviderBaselineDataset')]
  public function testLocationsFixtureDirWithBaselineDataset(string $expected_suffix): void {
    /** @var non-empty-string $expected_suffix */
    $this->locationsInit($this->testCwd);

    // Create dataset directory structure for each test case.
    $dataset_dir = $this->testFixtures . DIRECTORY_SEPARATOR . 'locations_fixture_dir_with_baseline_dataset' . DIRECTORY_SEPARATOR . $expected_suffix;
    mkdir($dataset_dir, 0777, TRUE);
    touch($dataset_dir . DIRECTORY_SEPARATOR . 'test_file.txt');

    $fixture_dir = $this->locationsFixtureDir();

    $this->assertStringContainsString($this->testCwd, $fixture_dir);
    $this->assertNotEmpty($expected_suffix);
    $this->assertStringEndsWith($expected_suffix, $fixture_dir);
  }

  public static function dataProviderBaselineDataset(): array {
    return [
      static::BASELINE_DATASET => [static::BASELINE_DIR],
      'custom-dataset' => ['custom_dataset'],
      'another-test-case' => ['another_test_case'],
    ];
  }

  public function testLocationsCopyWithNonExistentIncludeFile(): void {
    $this->locationsInit($this->testCwd);

    $source_dir = $this->testTmp . DIRECTORY_SEPARATOR . 'copy_source_nonexistent_' . uniqid();
    mkdir($source_dir, 0777, TRUE);

    $dest_dir = $this->testTmp . DIRECTORY_SEPARATOR . 'copy_dest_nonexistent_' . uniqid();
    mkdir($dest_dir, 0777, TRUE);

    // Create one existing file.
    $existing_file = $source_dir . DIRECTORY_SEPARATOR . 'existing.txt';
    file_put_contents($existing_file, 'content');

    // Try to include a non-existent file along with the existing one.
    $include_files = [$existing_file, $source_dir . DIRECTORY_SEPARATOR . 'nonexistent.txt'];

    $result = static::locationsCopy($source_dir, $dest_dir, $include_files);

    // Should only copy the existing file.
    $this->assertCount(1, $result);
    $this->assertFileExists($result[0]);

    (new Filesystem())->remove($source_dir);
    (new Filesystem())->remove($dest_dir);
  }

}
