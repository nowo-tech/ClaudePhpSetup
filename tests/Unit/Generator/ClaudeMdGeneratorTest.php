<?php

declare(strict_types=1);

namespace NowoTech\ClaudePhpSetup\Tests\Unit\Generator;

use NowoTech\ClaudePhpSetup\Generator\ClaudeMdGenerator;
use NowoTech\ClaudePhpSetup\Question\ProjectConfig;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Represents the ClaudeMdGeneratorTest class.
 */
final class ClaudeMdGeneratorTest extends TestCase
{
    private ClaudeMdGenerator $generator;

    /**
     * Handles the setUp operation.
     */
    protected function setUp(): void
    {
        $this->generator = new ClaudeMdGenerator();
    }

    #[Test]
    /**
     * Handles the itGeneratesBasicMarkdownForEmptyConfig operation.
     */
    public function itGeneratesBasicMarkdownForEmptyConfig(): void
    {
        $config              = new ProjectConfig();
        $config->projectName = 'Test Project';

        $output = $this->generator->generate($config);

        self::assertStringContainsString('# Test Project', $output);
        self::assertStringContainsString('## Stack', $output);
        self::assertStringContainsString('PHP', $output);
    }

    #[Test]
    /**
     * Handles the itIncludesSymfonySection operation.
     */
    public function itIncludesSymfonySection(): void
    {
        $config                   = new ProjectConfig();
        $config->framework        = 'symfony';
        $config->frameworkVersion = '7.2';

        $output = $this->generator->generate($config);

        self::assertStringContainsString('Symfony', $output);
        self::assertStringContainsString('constructor injection', $output);
    }

    #[Test]
    /**
     * Handles the itIncludesRectorSection operation.
     */
    public function itIncludesRectorSection(): void
    {
        $config                = new ProjectConfig();
        $config->hasRector     = true;
        $config->rectorVersion = '2';

        $output = $this->generator->generate($config);

        self::assertStringContainsString('Rector', $output);
        self::assertStringContainsString('2.x', $output);
    }

    #[Test]
    /**
     * Handles the itIncludesPhpStanSection operation.
     */
    public function itIncludesPhpStanSection(): void
    {
        $config               = new ProjectConfig();
        $config->hasPhpStan   = true;
        $config->phpStanLevel = '8';

        $output = $this->generator->generate($config);

        self::assertStringContainsString('PHPStan', $output);
        self::assertStringContainsString('level 8', $output);
    }

    #[Test]
    /**
     * Handles the itIncludesTestingSection operation.
     */
    public function itIncludesTestingSection(): void
    {
        $config                   = new ProjectConfig();
        $config->testingFramework = 'phpunit';

        $output = $this->generator->generate($config);

        self::assertStringContainsString('PHPUnit', $output);
        self::assertStringContainsString('Testing', $output);
    }

    #[Test]
    /**
     * Handles the itIncludesTwigSection operation.
     */
    public function itIncludesTwigSection(): void
    {
        $config          = new ProjectConfig();
        $config->hasTwig = true;

        $output = $this->generator->generate($config);

        self::assertStringContainsString('Twig', $output);
        self::assertStringContainsString('|raw', $output);
    }

    #[Test]
    /**
     * Handles the itIncludesDoctrineSection operation.
     */
    public function itIncludesDoctrineSection(): void
    {
        $config              = new ProjectConfig();
        $config->hasDoctrine = true;

        $output = $this->generator->generate($config);

        self::assertStringContainsString('Doctrine', $output);
        self::assertStringContainsString('ORM', $output);
    }

    #[Test]
    /**
     * Handles the itIncludesUpgradeSectionWhenUpgrading operation.
     */
    public function itIncludesUpgradeSectionWhenUpgrading(): void
    {
        $config                     = new ProjectConfig();
        $config->framework          = 'symfony';
        $config->frameworkVersion   = '7.2';
        $config->isUpgrading        = true;
        $config->upgradeFromVersion = '6.4';

        $output = $this->generator->generate($config);

        self::assertStringContainsString('Upgrade', $output);
        self::assertStringContainsString('6.4', $output);
        self::assertStringContainsString('7.2', $output);
    }

    #[Test]
    /**
     * Handles the itDoesNotIncludeUpgradeSectionWhenNotUpgrading operation.
     */
    public function itDoesNotIncludeUpgradeSectionWhenNotUpgrading(): void
    {
        $config                   = new ProjectConfig();
        $config->framework        = 'symfony';
        $config->frameworkVersion = '7.2';
        $config->isUpgrading      = false;

        $output = $this->generator->generate($config);

        self::assertStringNotContainsString('Upgrade Strategy', $output);
    }

    #[Test]
    /**
     * Handles the itGeneratesCommandsTableWhenToolsConfigured operation.
     */
    public function itGeneratesCommandsTableWhenToolsConfigured(): void
    {
        $config                = new ProjectConfig();
        $config->hasRector     = true;
        $config->hasPhpStan    = true;
        $config->commandRunner = 'composer';

        $output = $this->generator->generate($config);

        self::assertStringContainsString('Key Commands', $output);
        self::assertStringContainsString('rector-dry', $output);
        self::assertStringContainsString('phpstan', $output);
    }

    #[Test]
    /**
     * Handles the itUseMakeCommandRunnerInTable operation.
     */
    public function itUseMakeCommandRunnerInTable(): void
    {
        $config                = new ProjectConfig();
        $config->hasRector     = true;
        $config->commandRunner = 'make';

        $output = $this->generator->generate($config);

        self::assertStringContainsString('make rector-dry', $output);
    }

    #[Test]
    /**
     * Handles the itIncludesOperationalSectionWhenFlagsSet operation.
     */
    public function itIncludesOperationalSectionWhenFlagsSet(): void
    {
        $config                            = new ProjectConfig();
        $config->hasDocker                 = true;
        $config->hasCi                     = true;
        $config->hasApi                    = true;
        $config->includeObservabilityNotes = true;
        $config->includeMcpNotes           = true;

        $output = $this->generator->generate($config);

        self::assertStringContainsString('Delivery, operations & security', $output);
        self::assertStringContainsString('Docker', $output);
        self::assertStringContainsString('GitHub Actions', $output);
        self::assertStringContainsString('MCP', $output);
    }

    #[Test]
    /**
     * Handles the itListsGeneratedClaudePathsWhenGenerationFlagsSet operation.
     */
    public function itListsGeneratedClaudePathsWhenGenerationFlagsSet(): void
    {
        $config                   = new ProjectConfig();
        $config->projectName      = 'X';
        $config->generateClaudeMd = true;
        $config->generateCommands = true;
        $config->selectedCommands = ['code-review'];
        $config->generateAgents   = true;
        $config->selectedAgents   = ['php-architect'];
        $config->generateSkills   = true;
        $config->selectedSkills   = ['php-quality'];

        $output = $this->generator->generate($config);

        self::assertStringContainsString('Claude resources in this repository', $output);
        self::assertStringContainsString('.claude/commands/code-review.md', $output);
        self::assertStringContainsString('.claude/agents/php-architect.md', $output);
        self::assertStringContainsString('.claude/skills/php-quality/SKILL.md', $output);
    }
}
