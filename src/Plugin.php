<?php

declare(strict_types=1);

namespace NowoTech\ClaudePhpSetup;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

/**
 * Represents the Plugin class.
 */
final class Plugin implements PluginInterface, EventSubscriberInterface
{
    private Composer $composer;

    /**
     * Handles the activate operation.
     */
    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
    }

    /**
     * Handles the deactivate operation.
     */
    public function deactivate(Composer $composer, IOInterface $io): void
    {
    }

    /**
     * Handles the uninstall operation.
     */
    public function uninstall(Composer $composer, IOInterface $io): void
    {
    }

    /**
     * Handles the getSubscribedEvents operation.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ScriptEvents::POST_INSTALL_CMD => [['onPostInstall', 0]],
            ScriptEvents::POST_UPDATE_CMD  => [['onPostUpdate', 0]],
        ];
    }

    /**
     * Handles the onPostInstall operation.
     */
    public function onPostInstall(Event $event): void
    {
        $this->notifySetup($event->getIO());
    }

    /**
     * Handles the onPostUpdate operation.
     */
    public function onPostUpdate(Event $event): void
    {
        $this->notifySetup($event->getIO());
    }

    /**
     * Handles the notifySetup operation.
     */
    private function notifySetup(IOInterface $io): void
    {
        $vendorDir = $this->composer->getConfig()->get('vendor-dir');
        $binPath   = $vendorDir . '/../vendor/bin/claude-php-setup';

        if (!file_exists($binPath)) {
            $binPath = 'vendor/bin/claude-php-setup';
        }

        $io->write('');
        $io->write('<info>╔══════════════════════════════════════════════════════════╗</info>');
        $io->write('<info>║          Claude PHP Setup — nowo-tech/claude-php-setup   ║</info>');
        $io->write('<info>╚══════════════════════════════════════════════════════════╝</info>');
        $io->write('');
        $io->write('  Generate customized Claude Code markdown files for your project:');
        $io->write('  CLAUDE.md · commands · agents · skills');
        $io->write('');
        $io->write('  Run the interactive wizard:');
        $io->write('  <comment>  vendor/bin/claude-php-setup</comment>');
        $io->write('');
    }
}
