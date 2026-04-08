<?php

declare(strict_types=1);

namespace NowoTech\ClaudePhpSetup\Tests\Integration;

use NowoTech\ClaudePhpSetup\Cli\Console;
use NowoTech\ClaudePhpSetup\Generator\FileGenerator;
use NowoTech\ClaudePhpSetup\Question\ProjectConfig;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function in_array;
use function strlen;

use const STDIN;
use const STDOUT;

/**
 * Integration tests: full generation pipeline with a realistic project config.
 */
/**
 * Represents the SetupIntegrationTest class.
 */
final class SetupIntegrationTest extends TestCase
{
    private string $tmpDir;
    private FileGenerator $generator;

    /**
     * Handles the setUp operation.
     */
    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/claude-php-setup-integration-' . uniqid();
        mkdir($this->tmpDir, 0755, true);

        $console         = new Console(STDIN, fopen('/dev/null', 'w') ?: STDOUT);
        $this->generator = new FileGenerator($console);
    }

    /**
     * Handles the tearDown operation.
     */
    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
    }

    #[Test]
    /**
     * Handles the itGeneratesFullSymfonySetup operation.
     */
    public function itGeneratesFullSymfonySetup(): void
    {
        $config = $this->symfonyConfig();

        $this->generator->generate($config);

        // CLAUDE.md exists and has all expected sections
        $claudeMd = file_get_contents($this->tmpDir . '/CLAUDE.md') ?: '';
        self::assertStringContainsString('Symfony', $claudeMd);
        self::assertStringContainsString('Rector', $claudeMd);
        self::assertStringContainsString('PHPStan', $claudeMd);
        self::assertStringContainsString('PHPUnit', $claudeMd);
        self::assertStringContainsString('Twig', $claudeMd);
        self::assertStringContainsString('Doctrine', $claudeMd);
        self::assertStringContainsString('constructor injection', $claudeMd);

        // Commands generated
        self::assertFileExists($this->tmpDir . '/.claude/commands/code-review.md');
        self::assertFileExists($this->tmpDir . '/.claude/commands/rector-dry.md');
        self::assertFileExists($this->tmpDir . '/.claude/commands/phpstan.md');
        self::assertFileExists($this->tmpDir . '/.claude/commands/cs-fix.md');
        self::assertFileExists($this->tmpDir . '/.claude/commands/test-run.md');
        self::assertFileExists($this->tmpDir . '/.claude/commands/make-service.md');
        self::assertFileExists($this->tmpDir . '/.claude/commands/make-entity.md');

        // Agents generated
        self::assertFileExists($this->tmpDir . '/.claude/agents/php-architect.md');
        self::assertFileExists($this->tmpDir . '/.claude/agents/test-writer.md');
    }

    #[Test]
    /**
     * Handles the itGeneratesUpgradeContentWhenUpgrading operation.
     */
    public function itGeneratesUpgradeContentWhenUpgrading(): void
    {
        $config                     = $this->symfonyConfig();
        $config->isUpgrading        = true;
        $config->upgradeFromVersion = '6.4';
        $config->selectedCommands[] = 'symfony-upgrade';
        $config->selectedAgents[]   = 'symfony-upgrader';

        $this->generator->generate($config);

        $claudeMd = file_get_contents($this->tmpDir . '/CLAUDE.md') ?: '';
        self::assertStringContainsString('6.4', $claudeMd);
        self::assertStringContainsString('Upgrade Strategy', $claudeMd);

        self::assertFileExists($this->tmpDir . '/.claude/commands/symfony-upgrade.md');
        self::assertFileExists($this->tmpDir . '/.claude/agents/symfony-upgrader.md');
    }

    #[Test]
    /**
     * Handles the itGeneratesNoFrameworkSetup operation.
     */
    public function itGeneratesNoFrameworkSetup(): void
    {
        $config                   = new ProjectConfig();
        $config->projectDir       = $this->tmpDir;
        $config->projectName      = 'My Library';
        $config->phpVersion       = '8.2';
        $config->framework        = 'none';
        $config->hasPhpStan       = true;
        $config->phpStanLevel     = '6';
        $config->hasPhpCsFixer    = true;
        $config->testingFramework = 'phpunit';
        $config->generateClaudeMd = true;
        $config->generateCommands = true;
        $config->selectedCommands = ['code-review', 'phpstan', 'cs-fix', 'test-run'];
        $config->generateAgents   = false;

        $this->generator->generate($config);

        $claudeMd = file_get_contents($this->tmpDir . '/CLAUDE.md') ?: '';
        self::assertStringContainsString('My Library', $claudeMd);
        self::assertStringContainsString('PHPStan', $claudeMd);
        self::assertStringNotContainsString('Symfony', $claudeMd);
        self::assertStringNotContainsString('Laravel', $claudeMd);
    }

    #[Test]
    /**
     * Handles the itProducesCleanlyDedentedOutput operation.
     */
    public function itProducesCleanlyDedentedOutput(): void
    {
        $config = $this->symfonyConfig();
        $this->generator->generate($config);

        $claudeMd = file_get_contents($this->tmpDir . '/CLAUDE.md') ?: '';

        // No line outside fenced code blocks should have 8+ leading spaces (heredoc indent leaking)
        $lines       = explode("\n", $claudeMd);
        $inCodeBlock = false;
        foreach ($lines as $lineNumber => $line) {
            if (preg_match('/^\s*```/', $line) === 1) {
                $inCodeBlock = !$inCodeBlock;
                continue;
            }
            if ($inCodeBlock) {
                continue;
            }
            if (trim($line) === '') {
                continue;
            }
            $indent = strlen($line) - strlen(ltrim($line));
            self::assertLessThan(
                8,
                $indent,
                "Line {$lineNumber} has {$indent} leading spaces (heredoc indent leaking?): {$line}",
            );
        }
    }

    /**
     * Handles the symfonyConfig operation.
     */
    private function symfonyConfig(): ProjectConfig
    {
        $config                   = new ProjectConfig();
        $config->projectDir       = $this->tmpDir;
        $config->projectName      = 'My Symfony App';
        $config->phpVersion       = '8.3';
        $config->framework        = 'symfony';
        $config->frameworkVersion = '7.2';
        $config->hasRector        = true;
        $config->rectorVersion    = '2';
        $config->hasPhpStan       = true;
        $config->phpStanLevel     = '8';
        $config->hasPhpCsFixer    = true;
        $config->hasGrumPhp       = false;
        $config->testingFramework = 'phpunit';
        $config->hasTwig          = true;
        $config->hasDoctrine      = true;
        $config->commandRunner    = 'composer';
        $config->generateClaudeMd = true;
        $config->generateCommands = true;
        $config->selectedCommands = [
            'code-review',
            'rector-dry',
            'rector-run',
            'phpstan',
            'cs-fix',
            'test-run',
            'test-write',
            'twig-review',
            'make-entity',
            'make-repository',
            'make-service',
            'make-command',
        ];
        $config->generateAgents = true;
        $config->selectedAgents = ['php-architect', 'test-writer'];

        return $config;
    }

    /**
     * Handles the removeDir operation.
     */
    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $item) {
            if (in_array($item, ['.', '..'], true)) {
                continue;
            }
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->removeDir($path) : unlink($path);
        }
        rmdir($dir);
    }
}
