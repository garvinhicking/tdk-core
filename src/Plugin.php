<?php

declare(strict_types=1);

namespace GarvinHicking\TdkCore;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;
use Composer\Plugin\Capable as CapableInterface;
use Composer\Plugin\PluginInterface;
use Composer\Util\Filesystem;
use GarvinHicking\TdkCore\Command\CommandProvider;

final class Plugin implements PluginInterface, CapableInterface, EventSubscriberInterface
{
    protected IOInterface $io;

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->io = $io;

        $io->writeln('TDK-CORE: Activate event.');
        // MAYBE: Only ensure the folder is created, if "typo3-core-packages" is a local path
        // @todo: This is not ideal, but in case the repository is defined but
        //      the folder does not exist, composer simply breaks.
        //      This way we achieve a "always working" state.
//        if($composer->getConfig()->getRepositories()['typo3-core-packages'] ?? false) {
//            $fs = new Filesystem();
//            $fs->ensureDirectoryExists('typo3-core/typo3/sysext');
//        }
    }

    public static function getSubscribedEvents(): array
    {
        return [];
    }

    public function getCapabilities(): array
    {
        return [
            CommandProviderCapability::class => CommandProvider::class
        ];
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
        $io->writeln('TDK-CORE: De-Activate event.');
        // TODO: Implement deactivate() method.
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
        $io->writeln('TDK-CORE: UnInstall event.');
        // TODO: Implement uninstall() method.
    }

    public function install(Composer $composer, IOInterface $io): void
    {
        $io->writeln('TDK-CORE: Install event.');
        // TODO: Implement install() method.
    }
}
