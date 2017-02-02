<?php

namespace DigipolisGent\DrupalCopyProfile;

use Composer\Composer;
use Composer\Config;
use Composer\IO\IOInterface;
use Composer\Script\Event;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class Handler for DrupalCopyProfile that will copy the profile being
 * developed to the correct directory.
 * @package DigipolisGent\DrupalCopyProfile
 */
class Handler
{

    /**
     * The composer instance for the top-level project.
     *
     * @var \Composer\Composer
     */
    protected $composer;

    /**
     * The IOInterface to use for IO.
     *
     * @var \Composer\IO\IOInterface
     */
    protected $io;

    /**
     * Handler constructor.
     *
     * @param \Composer\Composer $composer
     *   The composer instance for the top-level composer.json file.
     * @param \Composer\IO\IOInterface $io
     *   The IOInterface to use for IO.
     */
    public function __construct(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    /**
     * Copy profile command event to copy the profile.
     *
     * @param \Composer\Script\Event $event
     *   The Composer Event being reacted to.
     */
    public function copyProfile(Event $event)
    {
        $profilename = $this->getProfileName();
        $filesystem = new Filesystem();
        $webroot = realpath($this->getWebRoot());
        $root = realpath(getcwd());
        $profileDir = $webroot.'/profiles/contrib/'.$profilename;

        $excludes = $this->getExcludesDefault();

        // Recreate the profile folder.
        $filesystem->remove($profileDir);
        $filesystem->mkdir($profileDir);

        $iterator = $this->getRecursiveIteratorIterator($excludes);
        $filesystem->mirror($root, $profileDir, $iterator);
    }

    /**
     * Get the path to the 'vendor' directory.
     *
     * @return string
     *   The path to the vendor directory.
     */
    public function getVendorPath()
    {
        $config = $this->composer->getConfig();

        return $config->get('vendor-dir', Config::RELATIVE_PATHS);
    }

    /**
     * Look up the Drupal core package object.
     *
     * @return \Composer\Package\PackageInterface
     *   The drupal/core Package object.
     */
    public function getDrupalCorePackage()
    {
        return $this->getPackage('drupal/core');
    }

    /**
     * Retrieve the install profile name.
     *
     * @return string
     *   The name of the install profile being installed.
     */
    public function getProfileName()
    {
        $options = $this->getOptions();

        return $options['profile-name'];
    }

    /**
     * Retrieve the path to the web root.
     *
     * @return string
     *   The path of the web root, being where drupal/core is installed.
     */
    public function getWebRoot()
    {
        $drupalCorePackage = $this->getDrupalCorePackage();
        $installationManager = $this->composer->getInstallationManager();
        $corePath = $installationManager->getInstallPath($drupalCorePackage);

        // Webroot is the parent path of the drupal core installation path.
        return dirname($corePath);
    }

    /**
     * Retrieve a package from the current composer process.
     *
     * @param string $name
     *   Name of the package to get from the current composer installation.
     *
     * @return \Composer\Package\PackageInterface
     *   The Package object with the name being requested.
     */
    protected function getPackage($name)
    {
        return $this->composer->getRepositoryManager()
            ->getLocalRepository()
            ->findPackage($name, '*');
    }

    /**
     * Retrieve excludes from optional "extra" configuration.
     *
     * @return array
     *   The excludes defined in "extra" configuration in the composer.json being
     *   used.
     */
    protected function getExcludes()
    {
        return $this->getNamedOptionList('excludes', 'getExcludesDefault');
    }

    /**
     * Retrieve a named list of options from optional "extra" configuration.
     * Respects 'omit-defaults', and either includes or does not include the
     * default values, as requested.
     *
     * @return array
     *   The named options defined in "extra" configuration in the composer.json
     *   being used merged with the defaults, unless "omit-defaults" is true.
     */
    protected function getNamedOptionList($optionName, $defaultFn)
    {
        $options = $this->getOptions();
        $result = [];
        if (empty($options['omit-defaults'])) {
            $result = $this->$defaultFn();
        }

        return array_merge($result, (array)$options[$optionName]);
    }

    /**
     * Holds default excludes.
     *
     * @return string[]
     *   The default excludes.
     */
    protected function getExcludesDefault()
    {
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
     *   The full list of options from composer.json merged with some default
     *   values.
     */
    protected function getOptions()
    {
        $extra = $this->composer->getPackage()->getExtra(
            ) + ['drupal-copy-profile' => []];
        $packageParts = explode(
            '/',
            $this->composer->getPackage()->getPrettyName()
        );
        $profileName = array_key_exists(
            1,
            $packageParts
        ) ? $packageParts[1] : 'profile';

        return $extra['drupal-copy-profile'] + [
                'omit-defaults' => false,
                'excludes' => [],
                'profile-name' => $profileName,
                'web-root' => $this->getWebRoot(),
            ];
    }

    /**
     * Gets a recursiveIteratorIterator to be able to copy all files in this
     * folder, excluding the given folder names.
     *
     * @param string[] $exclude
     *   A list of folder names to exclude. The iterator will filter against these
     *   folder names.
     *
     * @return \RecursiveIteratorIterator
     *   The recursive iterator used to loop over the profile source files.
     */
    private function getRecursiveIteratorIterator($exclude = [])
    {
        /**
         * Filters based on the given $exclude array, filters out directories with
         * the names in that array.
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
        $filter = function (\SplFileInfo $file, $key, \Iterator $iterator) use (
            $exclude
        ) {
            if ($iterator->hasChildren() && !in_array(
                    $file->getFilename(),
                    $exclude
                )
            ) {
                return true;
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
