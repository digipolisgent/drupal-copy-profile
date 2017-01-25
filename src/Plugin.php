<?php
/**
 * @file
 * Contains DigipolisGent\DrupalCopyProfile\Plugin.
 */

namespace DigipolisGent\DrupalCopyProfile;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\ScriptEvents;

/**
 * Composer plugin for handling drupal scaffold.
 */
class Plugin implements PluginInterface, EventSubscriberInterface {

  /**
   * @var \DigipolisGent\DrupalCopyProfile\Handler
   */
  protected $handler;

  /**
   * {@inheritdoc}
   */
  public function activate(Composer $composer, IOInterface $io) {
    // We use a separate PluginScripts object. This way we separate
    // functionality and also avoid some debug issues with the plugin being
    // copied on initialisation.
    // @see \Composer\Plugin\PluginManager::registerPackage()
    $this->handler = new Handler($composer, $io);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return array(
      ScriptEvents::POST_INSTALL_CMD => 'copyProfile',
      ScriptEvents::POST_UPDATE_CMD => 'copyProfile',
    );
  }

  /**
   * Copy profile event callback. Can be used as postCmdCallback and Script callback to be put in composer scripts.
   *
   * @param \Composer\Script\Event $event
   */
  public function copyProfile(\Composer\Script\Event $event) {
    if ($this->handler === null) {
      $this->handler = new Handler($event->getComposer(), $event->getIO());
    }
    $this->handler->copyProfile($event);
  }
}