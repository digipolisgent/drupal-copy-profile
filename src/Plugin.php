<?php

namespace DigipolisGent\DrupalCopyProfile;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

/**
 * Composer plugin for handling drupal scaffold.
 */
class Plugin implements PluginInterface, EventSubscriberInterface
{

    /**
     * @var \DigipolisGent\DrupalCopyProfile\Handler
     *   The handler to use to actually copy the profile.
     */
    protected $handler;

    /**
     * {@inheritdoc}
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        // We use a separate PluginScripts object. This way we separate
        // functionality and also avoid some debug issues with the plugin being
        // copied on initialisation.
        // @see \Composer\Plugin\PluginManager::registerPackage()
        $this->handler = new Handler($composer, $io);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ScriptEvents::POST_INSTALL_CMD => 'postCmd',
            ScriptEvents::POST_UPDATE_CMD => 'postCmd',
        ];
    }

    /**
     * Copy profile event callback.
     *
     * @param \Composer\Script\Event $event
     *   The Composer Event being reacted to.
     */
    public function postCmd(Event $event)
    {
        $this->handler->copyProfile($event);
    }

    /**
     * Script callback for copying the profile.
     *
     * @param \Composer\Script\Event $event
     *   The Composer Event being reacted to.
     */
    public static function copyProfile(Event $event)
    {
        $handler = new Handler($event->getComposer(), $event->getIO());
        $handler->copyProfile($event);
    }
}
