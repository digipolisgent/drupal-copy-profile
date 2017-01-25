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
      ScriptEvents::POST_INSTALL_CMD => 'postCmd',
      ScriptEvents::POST_UPDATE_CMD => 'postCmd',
    );
  }

  /**
   * Copy profile event callback.
   *
   * @param \Composer\Script\Event $event
   */
  public function postCmd(\Composer\Script\Event $event) {
    $this->handler->copyProfile($event);
  }

  /**
   * Script callback for copying the profile.
   *
   * @param \Composer\Script\Event $event
   */
  public static function copyProfile(\Composer\Script\Event $event) {
    $handler = new Handler($event->getComposer(), $event->getIO());
    $handler->copyProfile($event);
  }
}
