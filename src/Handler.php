<?php

/**
 * @file
 * Contains \DrupalComposer\DrupalCopyProfile\Handler.
 */

namespace DrupalComposer\DrupalCopyProfile;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Util\Filesystem;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

class Handler {

  /**
   * @var \Composer\Composer
   */
  protected $composer;

  /**
   * @var \Composer\IO\IOInterface
   */
  protected $io;

  /**
   * Handler constructor.
   *
   * @param Composer $composer
   * @param IOInterface $io
   */
  public function __construct(Composer $composer, IOInterface $io) {
    $this->composer = $composer;
    $this->io = $io;
  }

  /**
   * Copy profile command event to copy the profile.
   *
   * @param \Composer\Script\Event $event
   */
  public function copyProfile(\Composer\Script\Event $event) {
    $filesystem = new Filesystem();
    $symfonyfilesystem = new SymfonyFilesystem();
    $webroot = realpath($this->getWebRoot());
    $root = realpath(getcwd());
    $profile_dir = $webroot . '/profiles/contrib/' . $this->composer->getPackage()->getName();

    $excludes = $this->getExcludesDefault();

    // If the profile isn't there: create the folder, else, empty it.
    $filesystem->remove($profile_dir);
    $filesystem->ensureDirectoryExists($profile_dir);

    $iterator = self::getRecursiveIteratorIterator($excludes);
    $symfonyfilesystem->mirror($root, $profile_dir, $iterator);
  }

  /**
   * Get the path to the 'vendor' directory.
   *
   * @return string
   */
  public function getVendorPath() {
    $config = $this->composer->getConfig();
    $filesystem = new Filesystem();
    $filesystem->ensureDirectoryExists($config->get('vendor-dir'));
    $vendorPath = $filesystem->normalizePath(realpath($config->get('vendor-dir')));

    return $vendorPath;
  }

  /**
   * Look up the Drupal core package object.
   *
   * @return PackageInterface
   */
  public function getDrupalCorePackage() {
    return $this->getPackage('drupal/core');
  }

  /**
   * Retrieve the path to the web root.
   *
   *  @return string
   */
  public function getWebRoot() {
    $drupalCorePackage = $this->getDrupalCorePackage();
    $installationManager = $this->composer->getInstallationManager();
    $corePath = $installationManager->getInstallPath($drupalCorePackage);
    // Webroot is the parent path of the drupal core installation path.
    $webroot = dirname($corePath);

    return $webroot;
  }

  /**
   * Retrieve a package from the current composer process.
   *
   * @param string $name
   *   Name of the package to get from the current composer installation.
   *
   * @return PackageInterface
   */
  protected function getPackage($name) {
    return $this->composer->getRepositoryManager()->getLocalRepository()->findPackage($name, '*');
  }

  /**
   * Retrieve excludes from optional "extra" configuration.
   *
   * @return array
   */
  protected function getExcludes() {
    return $this->getNamedOptionList('excludes', 'getExcludesDefault');
  }

  /**
   * Retrieve a named list of options from optional "extra" configuration.
   * Respects 'omit-defaults', and either includes or does not include the
   * default values, as requested.
   *
   * @return array
   */
  protected function getNamedOptionList($optionName, $defaultFn) {
    $options = $this->getOptions();
    $result = array();
    if (empty($options['omit-defaults'])) {
      $result = $this->$defaultFn();
    }
    $result = array_merge($result, (array) $options[$optionName]);

    return $result;
  }

  /**
   * Holds default excludes.
   */
  protected function getExcludesDefault() {
    $common = [
      $this->getWebRoot(),
      $this->getVendorPath(),
      '.git',
    ];

    sort($common);
    return $common;
  }

  /**
   * Retrieve excludes from optional "extra" configuration.
   *
   * @return array
   */
  protected function getOptions() {
    $extra = $this->composer->getPackage()->getExtra() + ['drupal-copy-profile' => []];
    $options = $extra['drupal-copy-profile'] + [
        'omit-defaults' => FALSE,
        'excludes' => [],
        'web-root' => $this->getWebRoot(),
      ];
    return $options;
  }

  /**
   * Gets a recursiveIteratorIterator to be able to copy all files in this folder, excluding the given folder names.
   *
   * @param array[string] $exclude
   *   A list of folder names to exclude. The iterator will filter against these folder names.
   *
   * @return \RecursiveIteratorIterator
   */
  private static function getRecursiveIteratorIterator($exclude = []) {
    /**
     * Filters based on the given $exclude array, filters out directories with the names in that array.
     *
     * @param SplFileInfo $file
     *   The current item being processed for filtering.
     * @param mixed $key
     *   The key for the current item being processed.
     * @param RecursiveCallbackFilterIterator $iterator
     *   The iterator begin filtered.
     * @return bool TRUE
     *   if you need to recurse or if the item is acceptable
     */
    $filter = function ($file, $key, $iterator) use ($exclude) {
      if ($iterator->hasChildren() && !in_array($file->getFilename(), $exclude)) {
        return TRUE;
      }
      return $file->isFile();
    };

    $innerIterator = new \RecursiveDirectoryIterator(
      getcwd(),
      \RecursiveDirectoryIterator::SKIP_DOTS
    );

    $iterator = new \RecursiveIteratorIterator(
      new \RecursiveCallbackFilterIterator($innerIterator, $filter),
      \RecursiveIteratorIterator::SELF_FIRST
    );

    return $iterator;
  }

}