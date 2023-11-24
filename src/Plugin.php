<?php

declare(strict_types=1);

namespace GarvinHicking\TdkCore;

use Composer\Composer;
use Composer\Config;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;
use Composer\Plugin\Capable as CapableInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Util\Filesystem;
use Composer\Script\ScriptEvents;
use GarvinHicking\TdkCore\Command\CommandProvider;

final class Plugin implements PluginInterface, CapableInterface, EventSubscriberInterface
{
    protected IOInterface $io;

    public static function getSubscribedEvents()
    {
        return [
            ScriptEvents::PRE_AUTOLOAD_DUMP => ['listen'],
            ScriptEvents::POST_AUTOLOAD_DUMP => ['listen'],
        ];
    }

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->io = $io;

        $io->writeError('TDK-CORE: Activate event.');
        // MAYBE: Only ensure the folder is created, if "typo3-core-packages" is a local path
        // @todo: This is not ideal, but in case the repository is defined but
        //      the folder does not exist, composer simply breaks.
        //      This way we achieve a "always working" state.
//        if($composer->getConfig()->getRepositories()['typo3-core-packages'] ?? false) {
//            $fs = new Filesystem();
//            $fs->ensureDirectoryExists('typo3-core/typo3/sysext');
//        }

        $composer->getEventDispatcher()->addSubscriber($this);
    }

    public function listen(Event $event)
    {
        if ($event->getName() === ScriptEvents::POST_AUTOLOAD_DUMP) {
            $event->getIO()->writeError('TDK-Core: listen[post_autoload_dump] event');
        }
    }

    public function getCapabilities(): array
    {
        return [
            CommandProviderCapability::class => CommandProvider::class
        ];
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
        $io->writeError('TDK-CORE: De-Activate event.');
        // TODO: Implement deactivate() method.
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
        $io->writeError('TDK-CORE: UnInstall event.');
        // TODO: Implement uninstall() method.
    }

    public function install(Composer $composer, IOInterface $io): void
    {
        $io->writeError('TDK-CORE: Install event.');
        // TODO: Implement install() method.
    }

    protected static function extractBaseDir(Config $config)
    {
        $reflectionClass = new \ReflectionClass($config);
        $reflectionProperty = $reflectionClass->getProperty('baseDir');
        /** @noinspection PhpExpressionResultUnusedInspection */
        $reflectionProperty->setAccessible(true);
        return $reflectionProperty->getValue($config);
    }
}
