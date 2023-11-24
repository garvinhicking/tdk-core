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
use Symfony\Component\Finder\Finder;

final class Plugin implements PluginInterface, EventSubscriberInterface
{
    protected IOInterface $io;

    protected string $REPOSITORY_KEY = 'typo3-core';
    protected string $REPOSITORY_DIR = 'typo3-core';
    protected bool $initSuccess = false;

    public static function getSubscribedEvents(): array
    {
        return [
            ScriptEvents::PRE_AUTOLOAD_DUMP => ['listen'],
            ScriptEvents::POST_AUTOLOAD_DUMP => ['listen'],
        ];
    }

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->io = $io;

        $composer->getEventDispatcher()->addSubscriber($this);
    }

    public function listen(Event $event): void
    {
        if ($event->getName() === ScriptEvents::POST_AUTOLOAD_DUMP) {
            // Somehow our post_autoload_dump event gets executed twice.
            // TODO: Find out why, remove this hack.
            if ($this->initSuccess) {
                return;
            }
            $this->initSuccess = true;

            $this->initializeTYPO3Repository($event);
        }
    }

    /**
     * This is the vital logic of the plugin. This hook allows our plugin
     * to inject a custom repository to the root composer.json. This can
     * only happen AFTER the typo3-core has been actually checked out,
     * because if the repository does not exist, executing "composer install"
     * would fail.
     *
     * Once the plugin has utilized the tdk-cli package and its provided
     * scripts to GIT checkout the `typo3-core` directory, a follow-up
     * `composer install` can be issued to actually setup all packages
     * provided for the core through the local GIT-working directory (and NOT
     * packagist).
     *
     * This allows us to operate via GIT on the typo3-core subdirectory
     * with more flexibility, and the ability to edit/commit files in there,
     * cherry-pick other things.
     *
     * And it allows us to utilize the tdk-cli and tdk-core to any other
     * projects, so that a
     *
     * @param Event $event
     * @return bool
     */
    protected function initializeTYPO3Repository(Event $event): bool
    {
        // Check the project's composer.json and see if our repository is listed.
        $existingRepositories = $event->getComposer()->getConfig()->getRepositories();
        if (!isset($existingRepositories[$this->REPOSITORY_KEY])) {
            // Repository missing, our plugin cannot take action.
            $event->getIO()->writeError(sprintf("TDK: Missing repository <error>%s</error>. TYPO3 cannot be set-up.", $this->REPOSITORY_KEY));
            return false;
        }

        // Check if base directory for the GIT checkout is provided...
        $projectBaseDir = $event->getComposer()->getConfig()->get('vendor-dir') . '/../';
        if (!is_dir($projectBaseDir . $this->REPOSITORY_DIR)) {
            $event->getIO()->writeError(sprintf("TDK: Missing directory <error>%s</error>. TYPO3 cannot be set-up.", $this->REPOSITORY_DIR));
            return false;
        }

        $gitTargetDir = $projectBaseDir . $this->REPOSITORY_DIR;
        // Check if it maybe already is a TYPO3 git repository
        if (file_exists($gitTargetDir . '/.gitignore')) {
            $event->getIO()->writeError(sprintf("TDK: Directory <info>%s</info> has active TYPO3 set-up.", $this->REPOSITORY_DIR));
            return false;
        }

        // If the workflow continues until here, it means that:
        // * We have a repository key for typo3-core/
        // * The directory typo3-core/ exists
        // * There is no .gitignore entry
        // That means, now we have to make sure that the directory
        // can be overwritten with a GIT-clone working directory.
        // To be really safe, we check that NOTHING else besides
        // our "composer-repository.bak" file exists inside the directory.
        $finder = Finder::create()
            ->ignoreVCS(false)
            ->ignoreDotFiles(false)
            ->depth(0)
            ->notName(['composer-repository.bak', '.DS_Store'])
            ->in($gitTargetDir);

        if (count($finder) > 0) {
            $event->getIO()->writeError(sprintf("TDK: Directory <info>%s</info> is populated with unknown files. Please reset directory to vanilla state.", $this->REPOSITORY_DIR));
            return false;
        }

        // If we have reached this place, we can finally create the GIT checkout.
        // \GarvinHicking\TdkCli\Cli::help($event);
        $process = new ProcessExecutor();
        $command = sprintf('vendor/bin/tdk-cli clone');
        $event->getIO()->writeError('Create sub-process <info>vendor/bin/tdk-cli clone</info>');
        $status = $process->executeTty($command);

        if ($status) {
            $event->getIO()->writeError('<error>Sub-Process returned failure.</error>');
            return false;
        }

        $event->getIO()->writeError('<info>Repository cloned. You can now run composer install to activate the extensions!</info>');

        return true;
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
        // TODO: Implement deactivate() method.
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
        // TODO: Implement uninstall() method.
    }

    public function install(Composer $composer, IOInterface $io): void
    {
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
