<?php

/**
 * @file
 * Contains \DigipolisGent\DrupalCopyProfile\Tests\PluginTest.
 */

namespace DigipolisGent\DrupalCopyProfile\Tests;

use Composer\Util\Filesystem;
use Symfony\Component\Yaml\Yaml;

/**
 * Tests composer plugin functionality.
 */
class PluginTest extends \PHPUnit_Framework_TestCase {

  /**
   * @var \Composer\Util\Filesystem
   */
  protected $fs;

  /**
   * @var string
   */
  protected $tmpDir;

  /**
   * @var string
   */
  protected $rootDir;

  /**
   * @var string
   */
  protected $tmpReleaseTag;

  /**
   * SetUp test
   */
  public function setUp() {
    $this->rootDir = realpath(realpath(__DIR__ . '/..'));

    // Prepare temp directory.
    $this->fs = new Filesystem();
    $this->tmpDir = realpath(sys_get_temp_dir()) . DIRECTORY_SEPARATOR . 'install-profile';
    $this->ensureDirectoryExistsAndClear($this->tmpDir);

    $this->writeTestReleaseTag();
    $this->writeComposerJSON();
    $this->writeInstallProfile();

    chdir($this->tmpDir);
  }

  /**
   * tearDown
   *
   * @return void
   */
  public function tearDown()
  {
    $this->fs->removeDirectory($this->tmpDir);
    $this->git(sprintf('tag -d "%s"', $this->tmpReleaseTag));
  }

  /**
   * Tests a simple composer install without core, but adding core later.
   */
  public function testComposerInstallAndUpdate() {
    // TODO
    $exampleProfileFile = $this->tmpDir . DIRECTORY_SEPARATOR . 'profiles' . DIRECTORY_SEPARATOR . 'profile' . DIRECTORY_SEPARATOR . 'profile.profile';
    $exampleInstallFile = $this->tmpDir . DIRECTORY_SEPARATOR . 'profiles' . DIRECTORY_SEPARATOR . 'profile' . DIRECTORY_SEPARATOR . 'profile.install';
    $exampleInfoFile = $this->tmpDir . DIRECTORY_SEPARATOR . 'profiles' . DIRECTORY_SEPARATOR . 'profile' . DIRECTORY_SEPARATOR . 'profile.info.yml';
    $this->assertFileNotExists($exampleProfileFile, 'Profile file should not exist.');
    $this->assertFileNotExists($exampleInstallFile, 'Install file should not exist.');
    $this->assertFileNotExists($exampleInfoFile, 'Info.yml file should not exist.');
    $this->composer('install');
    $this->assertFileExists($this->tmpDir . DIRECTORY_SEPARATOR . 'core', 'Drupal core is installed.');
    $this->assertFileExists($exampleProfileFile, 'Profile file should be copied.');
    $this->assertFileExists($exampleInstallFile, 'Install file should be copied.');
    $this->assertFileExists($exampleInfoFile, 'Info.yml file should be copied.');
    $this->fs->remove($exampleProfileFile);
    $this->fs->remove($exampleInstallFile);
    $this->fs->remove($exampleInfoFile);
    $this->assertFileNotExists($exampleProfileFile, 'Profile file should not exist.');
    $this->assertFileNotExists($exampleInstallFile, 'Install file should not exist.');
    $this->assertFileNotExists($exampleInfoFile, 'Info.yml file should not exist.');
    $this->composer('drupal-copy-profile');
    $this->assertFileExists($exampleProfileFile, 'Profile file should be installed by "drupal-copy-profile" command.');
    $this->assertFileExists($exampleInstallFile, 'Install file should be installed by "drupal-copy-profile" command.');
    $this->assertFileExists($exampleInfoFile, 'Info.yml file should be installed by "drupal-copy-profile" command.');

    foreach (['8.0.1', '8.1.x-dev'] as $version) {
      // We touch a profile file, so we can check the file was modified after
      // the copy profile.
      touch($exampleProfileFile);
      $mtime_touched = filemtime($exampleProfileFile);
      // Requiring a newer version triggers "composer update"
      $this->composer('require --update-with-dependencies drupal/core:"' . $version .'"');
      clearstatcache();
      $mtime_after = filemtime($exampleProfileFile);
      $this->assertNotEquals($mtime_after, $mtime_touched, 'Profile file was modified by composer update. (' . $version . ')');
    }

    // We touch a scaffold file, so we can check the file was modified after
    // the custom commandscaffold update.
    touch($exampleProfileFile);
    clearstatcache();
    $mtime_touched = filemtime($exampleProfileFile);
    $this->composer('drupal-copy-profile');
    clearstatcache();
    $mtime_after = filemtime($exampleProfileFile);
    $this->assertNotEquals($mtime_after, $mtime_touched, 'Profile file was modified by custom command.');
  }

  /**
   * Writes the install profile files to the temp directory.
   */
  protected function writeInstallProfile() {
    $contents = '<?php';
    // Write composer.json.
    file_put_contents($this->tmpDir . '/profile.profile', $contents);
    $contents = '<?php';
    // Write composer.json.
    file_put_contents($this->tmpDir . '/profile.install', $contents);
    $infoYaml = [
      'name' => 'Profile',
      'type' => 'profile',
      'description' => 'Install profile for Digipolis sites',
      'version' => '8.x-1.x',
      'core' => '8.x',
      'dependencies' => [],
    ];
    $infoYaml = Yaml::dump($infoYaml);
    // Write composer.json.
    file_put_contents($this->tmpDir . '/profile.info.yml', $infoYaml);
  }

  /**
   * Writes the default composer json to the temp directory.
   */
  protected function writeComposerJSON() {
    $json = json_encode($this->composerJSONDefaults(), JSON_PRETTY_PRINT);
    // Write composer.json.
    file_put_contents($this->tmpDir . '/composer.json', $json);
  }

  /**
   * Writes a tag for the current commit, so we can reference it directly in the
   * composer.json.
   */
  protected function writeTestReleaseTag() {
    // Tag the current state.
    $this->tmpReleaseTag = '999.0.' . time();
    $this->git(sprintf('tag -a "%s" -m "%s"', $this->tmpReleaseTag, 'Tag for testing this exact commit'));
  }

  /**
   * Provides the default composer.json data.
   *
   * @return array
   */
  protected function composerJSONDefaults() {
    return array(
      'repositories' => array(
        array(
          'type' => 'vcs',
          'url' => $this->rootDir,
        )
      ),
      'require' => array(
        'digipolisgent/drupal-copy-profile' => $this->tmpReleaseTag,
        'composer/installers' => '^1.0.20',
        'drupal/core' => '8.0.0',
      ),
      'scripts' => array(
        'drupal-copy-profile' =>  'DigipolisGent\\DrupalCopyProfile\\Plugin::copyProfile'
      ),
      'minimum-stability' => 'dev',
    );
  }

  /**
   * Wrapper for the composer command.
   *
   * @param string $command
   *   Composer command name, arguments and/or options
   */
  protected function composer($command) {
    chdir($this->tmpDir);
    passthru(escapeshellcmd($this->rootDir . '/vendor/bin/composer ' . $command), $exit_code);
    if ($exit_code !== 0) {
      throw new \Exception('Composer returned a non-zero exit code');
    }
  }

  /**
   * Wrapper for git command in the root directory.
   *
   * @param $command
   *   Git command name, arguments and/or options.
   */
  protected function git($command) {
    chdir($this->rootDir);
    passthru(escapeshellcmd('git ' . $command), $exit_code);
    if ($exit_code !== 0) {
      throw new \Exception('Git returned a non-zero exit code');
    }
  }

  /**
   * Makes sure the given directory exists and has no content.
   *
   * @param string $directory
   */
  protected function ensureDirectoryExistsAndClear($directory) {
    if (is_dir($directory)) {
      $this->fs->removeDirectory($directory);
    }
    mkdir($directory, 0777, true);
  }
}