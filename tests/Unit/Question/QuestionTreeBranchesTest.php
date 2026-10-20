<?php

declare(strict_types=1);

namespace NowoTech\ClaudePhpSetup\Tests\Unit\Question;

use NowoTech\ClaudePhpSetup\Cli\Console;
use NowoTech\ClaudePhpSetup\Question\ProjectConfig;
use NowoTech\ClaudePhpSetup\Question\QuestionTree;
use NowoTech\ClaudePhpSetup\Tests\Support\MemoryStreamTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Drives QuestionTree with detected defaults so that Enter accepts them, covering the
 * optional branches (Rector/PHPStan sub-questions, upgrade edge case, agents, skills,
 * per-feature commands and the overwrite prompt).
 */
final class QuestionTreeBranchesTest extends TestCase
{
    use MemoryStreamTrait;

    private string $tmpDir;

    /**
     * Creates an empty project directory per test.
     */
    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/claude-qt-branches-' . uniqid();
        mkdir($this->tmpDir, 0755, true);
    }

    /**
     * Removes the project directory.
     */
    protected function tearDown(): void
    {
        foreach (['CLAUDE.md'] as $file) {
            if (is_file($this->tmpDir . '/' . $file)) {
                unlink($this->tmpDir . '/' . $file);
            }
        }
        rmdir($this->tmpDir);
    }

    #[Test]
    /**
     * Upgrading from the newest supported Symfony version disables the upgrade flow.
     */
    public function itDisablesUpgradeWhenNoNewerSymfonyVersionExists(): void
    {
        $detected                   = $this->config();
        $detected->framework        = 'symfony';
        $detected->frameworkVersion = '8.0';

        // PHP, framework, Symfony version (8.0) accepted; "y" to "Are you upgrading?"
        $config = $this->runTree($detected, "\n\n\ny\n");

        self::assertFalse($config->isUpgrading);
        self::assertSame('8.0', $config->frameworkVersion);
        self::assertNull($config->upgradeFromVersion);
    }

    #[Test]
    /**
     * With every feature detected, the tree offers and selects every optional command, agent and skill.
     */
    public function itOffersEveryOptionalCommandAgentAndSkill(): void
    {
        file_put_contents($this->tmpDir . '/CLAUDE.md', 'existing');

        $detected                            = $this->config();
        $detected->framework                 = 'symfony';
        $detected->frameworkVersion          = '7.2';
        $detected->hasRector                 = true;
        $detected->rectorVersion             = '1';
        $detected->hasPhpStan                = true;
        $detected->phpStanLevel              = 'max';
        $detected->hasPhpCsFixer             = true;
        $detected->hasGrumPhp                = true;
        $detected->hasTwig                   = true;
        $detected->hasTwigCsFixer            = true;
        $detected->testingFramework          = 'phpunit';
        $detected->hasDoctrine               = true;
        $detected->hasApi                    = true;
        $detected->hasDocker                 = true;
        $detected->hasCi                     = true;
        $detected->includeObservabilityNotes = true;
        $detected->includeMcpNotes           = true;

        // 1-3 PHP/framework/Symfony version, 4 "y" upgrading, 5-26 defaults (upgrade target, quality,
        // testing, architecture, API style, runner, operational, CLAUDE.md, commands + selection),
        // 27 "y" agents, 28 all agents, 29 "y" skills, 30 all skills, 31-32 defaults, 33 "y" overwrite.
        $payload = "\n\n\ny\n" . str_repeat("\n", 22) . "y\n\ny\n\n\n\ny\n";
        $config  = $this->runTree($detected, $payload);

        self::assertTrue($config->isUpgrading);
        self::assertSame('7.2', $config->upgradeFromVersion);
        self::assertSame('7.3', $config->frameworkVersion);
        self::assertSame('1', $config->rectorVersion);
        self::assertSame('max', $config->phpStanLevel);
        self::assertSame('rest', $config->apiStyle);
        self::assertTrue($config->overwriteExisting);

        foreach (['rector-dry', 'rector-run', 'phpstan', 'cs-fix', 'test-run', 'test-write', 'twig-review', 'make-entity', 'make-repository', 'migration-review', 'api-security-review', 'docker-exec', 'make-service', 'make-command', 'symfony-upgrade', 'grumphp-check'] as $command) {
            self::assertContains($command, $config->selectedCommands);
        }
        foreach (['test-writer', 'refactor-agent', 'symfony-upgrader', 'doctrine-expert'] as $agent) {
            self::assertContains($agent, $config->selectedAgents);
        }
        self::assertSame(
            ['php-quality', 'php-testing', 'rector-workflow', 'api-security', 'doctrine-data', 'docker-dev', 'ci-pipeline', 'observability', 'mcp-tools'],
            $config->selectedSkills,
        );
    }

    #[Test]
    /**
     * PHPStan defaults to "level 8" when the detected level is empty and Rector 2 maps back to "2".
     */
    public function itMapsNumericPhpStanLevelAndRectorTwo(): void
    {
        $detected               = $this->config();
        $detected->hasRector    = true;
        $detected->hasPhpStan   = true;
        $detected->phpStanLevel = '';

        // PHP, framework, Rector (yes), Rector version, PHPStan (yes), PHPStan level: all defaults.
        $config = $this->runTree($detected, str_repeat("\n", 6));

        self::assertSame('2', $config->rectorVersion);
        self::assertSame('8', $config->phpStanLevel);
    }

    #[Test]
    /**
     * Laravel projects get the Laravel agent.
     */
    public function itOffersTheLaravelAgent(): void
    {
        $detected                   = $this->config();
        $detected->framework        = 'laravel';
        $detected->frameworkVersion = '11';

        // 20 defaults up to the commands selection, then "y" agents and the default selection.
        $config = $this->runTree($detected, str_repeat("\n", 20) . "y\n");

        self::assertSame('11', $config->frameworkVersion);
        self::assertTrue($config->generateAgents);
        self::assertContains('laravel-expert', $config->selectedAgents);
        self::assertNotContains('symfony-upgrader', $config->selectedAgents);
    }

    /**
     * Returns a detected configuration rooted at the temporary project directory.
     */
    private function config(): ProjectConfig
    {
        $config             = new ProjectConfig();
        $config->projectDir = $this->tmpDir;

        return $config;
    }

    /**
     * Runs the question tree with the given stdin payload (missing answers read as Enter).
     */
    private function runTree(ProjectConfig $detected, string $payload): ProjectConfig
    {
        $stdin = $this->memoryStream('r+');
        fwrite($stdin, $payload);
        rewind($stdin);

        return (new QuestionTree(new Console($stdin, $this->memoryStream('w'))))->run($detected);
    }
}
