<?php

declare(strict_types=1);

namespace NowoTech\ClaudePhpSetup\Tests\Unit\Template\Agents;

use NowoTech\ClaudePhpSetup\Question\ProjectConfig;
use NowoTech\ClaudePhpSetup\Template\Agents\AgentTemplates;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AgentTemplatesTest extends TestCase
{
    #[Test]
    public function itRendersAllAgentTemplatesWithFullConfig(): void
    {
        $config                     = new ProjectConfig();
        $config->phpVersion         = '8.3';
        $config->framework          = 'symfony';
        $config->frameworkVersion   = '7.2';
        $config->architectureStyle  = 'ddd';
        $config->testingFramework   = 'both';
        $config->hasRector          = true;
        $config->rectorVersion      = '2';
        $config->phpStanLevel       = '8';
        $config->isUpgrading        = true;
        $config->upgradeFromVersion = '6.4';
        $config->hasDoctrine        = true;

        $all = AgentTemplates::all($config);

        self::assertArrayHasKey('php-architect', $all);
        self::assertStringContainsString('Domain-Driven Design', $all['php-architect']);
        self::assertStringContainsString('test-writer', $all['test-writer']);
        self::assertStringContainsString('refactor-agent', $all['refactor-agent']);
        self::assertStringContainsString('symfony-upgrader', $all['symfony-upgrader']);
        self::assertStringContainsString('doctrine-expert', $all['doctrine-expert']);
        self::assertStringContainsString('security-auditor', $all['security-auditor']);
        self::assertStringContainsString('performance-php', $all['performance-php']);
    }

    #[Test]
    public function itCoversArchitectureAndFrameworkBranches(): void
    {
        $hex                    = new ProjectConfig();
        $hex->architectureStyle = 'hexagonal';
        $hex->framework         = 'laravel';
        $hex->frameworkVersion  = '11';
        self::assertStringContainsString('Hexagonal', AgentTemplates::all($hex)['php-architect']);
        self::assertStringContainsString('laravel-expert', AgentTemplates::all($hex)['laravel-expert']);

        $layered                    = new ProjectConfig();
        $layered->architectureStyle = 'layered';
        self::assertStringContainsString('Layered', AgentTemplates::all($layered)['php-architect']);

        $plain            = new ProjectConfig();
        $plain->framework = 'none';
        self::assertStringContainsString('Standard MVC', AgentTemplates::all($plain)['php-architect']);
    }

    #[Test]
    public function itCoversSymfonyUpgraderWhenNotUpgrading(): void
    {
        $config              = new ProjectConfig();
        $config->isUpgrading = false;

        self::assertArrayNotHasKey('symfony-upgrader', AgentTemplates::all($config));
    }

    #[Test]
    public function itCoversDoctrineExpertWhenNoDoctrine(): void
    {
        $config              = new ProjectConfig();
        $config->hasDoctrine = false;

        self::assertArrayNotHasKey('doctrine-expert', AgentTemplates::all($config));
    }

    #[Test]
    public function itOmitsLaravelExpertWhenNotLaravel(): void
    {
        $config            = new ProjectConfig();
        $config->framework = 'symfony';

        self::assertArrayNotHasKey('laravel-expert', AgentTemplates::all($config));
    }

    #[Test]
    public function itCoversTestWriterPestOnly(): void
    {
        $config                   = new ProjectConfig();
        $config->testingFramework = 'pest';
        $config->framework        = 'none';

        self::assertStringContainsString('Pest', AgentTemplates::all($config)['test-writer']);
    }

    #[Test]
    public function itCoversTestWriterLaravelContext(): void
    {
        $config                   = new ProjectConfig();
        $config->testingFramework = 'both';
        $config->framework        = 'laravel';

        self::assertStringContainsString('Laravel testing tools', AgentTemplates::all($config)['test-writer']);
    }

    #[Test]
    public function itCoversSecurityAuditorApiHint(): void
    {
        $config         = new ProjectConfig();
        $config->hasApi = true;

        self::assertStringContainsString('verify authn/z', AgentTemplates::all($config)['security-auditor']);
    }
}
