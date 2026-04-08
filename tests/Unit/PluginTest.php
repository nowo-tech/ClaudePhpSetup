<?php

declare(strict_types=1);

namespace NowoTech\ClaudePhpSetup\Tests\Unit;

use Composer\Composer;
use Composer\Config;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use NowoTech\ClaudePhpSetup\Plugin;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Represents the PluginTest class.
 */
final class PluginTest extends TestCase
{
    #[Test]
    /**
     * Handles the itImplementsComposerPluginAndEventSubscriberInterfaces operation.
     */
    public function itImplementsComposerPluginAndEventSubscriberInterfaces(): void
    {
        $implements = class_implements(new Plugin());
        self::assertContains(PluginInterface::class, $implements);
        self::assertContains(EventSubscriberInterface::class, $implements);
    }

    #[Test]
    /**
     * Handles the itSubscribesToPostInstallAndPostUpdateEvents operation.
     */
    public function itSubscribesToPostInstallAndPostUpdateEvents(): void
    {
        $events = Plugin::getSubscribedEvents();

        self::assertArrayHasKey(ScriptEvents::POST_INSTALL_CMD, $events);
        self::assertArrayHasKey(ScriptEvents::POST_UPDATE_CMD, $events);
    }

    #[Test]
    /**
     * Handles the itActivatesWithoutError operation.
     */
    public function itActivatesWithoutError(): void
    {
        $plugin   = new Plugin();
        $composer = $this->createMock(Composer::class);
        $io       = $this->createMock(IOInterface::class);

        $plugin->activate($composer, $io);

        // No exception thrown
        $this->addToAssertionCount(1);
    }

    #[Test]
    /**
     * Handles the itDeactivatesWithoutError operation.
     */
    public function itDeactivatesWithoutError(): void
    {
        $plugin   = new Plugin();
        $composer = $this->createMock(Composer::class);
        $io       = $this->createMock(IOInterface::class);

        $plugin->deactivate($composer, $io);

        $this->addToAssertionCount(1);
    }

    #[Test]
    /**
     * Handles the itUninstallsWithoutError operation.
     */
    public function itUninstallsWithoutError(): void
    {
        $plugin   = new Plugin();
        $composer = $this->createMock(Composer::class);
        $io       = $this->createMock(IOInterface::class);

        $plugin->uninstall($composer, $io);

        $this->addToAssertionCount(1);
    }

    #[Test]
    /**
     * Handles the itNotifiesOnPostInstallWhenBinExistsUnderVendor operation.
     */
    public function itNotifiesOnPostInstallWhenBinExistsUnderVendor(): void
    {
        $tmp       = sys_get_temp_dir() . '/claude-plugin-' . uniqid();
        $vendorDir = $tmp . '/vendor';
        mkdir($vendorDir . '/bin', 0755, true);
        touch($vendorDir . '/bin/claude-php-setup');

        $plugin   = new Plugin();
        $composer = $this->createMock(Composer::class);
        $io       = $this->createMock(IOInterface::class);
        $config   = $this->createMock(Config::class);
        $config->method('get')->with('vendor-dir')->willReturn($vendorDir);
        $composer->method('getConfig')->willReturn($config);

        $plugin->activate($composer, $io);

        $event = $this->createMock(Event::class);
        $event->method('getIO')->willReturn($io);

        $io->expects(self::atLeastOnce())->method('write');

        $plugin->onPostInstall($event);

        $this->removeTree($tmp);
    }

    #[Test]
    /**
     * Handles the itNotifiesOnPostUpdateWhenBinPathMissing operation.
     */
    public function itNotifiesOnPostUpdateWhenBinPathMissing(): void
    {
        $tmp       = sys_get_temp_dir() . '/claude-plugin-' . uniqid();
        $vendorDir = $tmp . '/vendor';
        mkdir($vendorDir, 0755, true);

        $plugin   = new Plugin();
        $composer = $this->createMock(Composer::class);
        $io       = $this->createMock(IOInterface::class);
        $config   = $this->createMock(Config::class);
        $config->method('get')->with('vendor-dir')->willReturn($vendorDir);
        $composer->method('getConfig')->willReturn($config);

        $plugin->activate($composer, $io);

        $event = $this->createMock(Event::class);
        $event->method('getIO')->willReturn($io);

        $io->expects(self::atLeastOnce())->method('write');

        $plugin->onPostUpdate($event);

        $this->removeTree($tmp);
    }

    /** @param non-empty-string $dir */
    private function removeTree(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->removeTree($path) : unlink($path);
        }
        rmdir($dir);
    }
}
