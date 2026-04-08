<?php

declare(strict_types=1);

namespace NowoTech\ClaudePhpSetup\Tests\Unit\Template\Skills;

use NowoTech\ClaudePhpSetup\Question\ProjectConfig;
use NowoTech\ClaudePhpSetup\Template\Skills\SkillTemplates;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SkillTemplatesTest extends TestCase
{
    #[Test]
    public function itRendersCoreSkills(): void
    {
        $config                            = new ProjectConfig();
        $config->testingFramework          = 'phpunit';
        $config->hasRector                 = true;
        $config->hasApi                    = true;
        $config->hasDoctrine               = true;
        $config->hasDocker                 = true;
        $config->hasCi                     = true;
        $config->includeObservabilityNotes = true;
        $config->includeMcpNotes           = true;

        $all = SkillTemplates::all($config);

        foreach ($all as $key => $body) {
            self::assertNotSame('', $body, $key);
            self::assertStringContainsString('---', $body);
            self::assertStringContainsString('name:', $body);
        }

        self::assertArrayHasKey('php-quality', $all);
        self::assertArrayHasKey('observability', $all);
        self::assertArrayHasKey('mcp-tools', $all);
    }

    #[Test]
    public function itOmitsTestingSkillWhenNoTests(): void
    {
        $config                   = new ProjectConfig();
        $config->testingFramework = 'none';

        self::assertArrayNotHasKey('php-testing', SkillTemplates::all($config));
    }

    #[Test]
    public function itCoversPhpQualityRunnerBranches(): void
    {
        $composerOnly                = new ProjectConfig();
        $composerOnly->commandRunner = 'composer';
        self::assertStringContainsString('`composer`', SkillTemplates::all($composerOnly)['php-quality']);

        $makeRunner                = new ProjectConfig();
        $makeRunner->commandRunner = 'both';
        self::assertStringContainsString('`make`', SkillTemplates::all($makeRunner)['php-quality']);
    }

    #[Test]
    public function itCoversPhpTestingFrameworkVariants(): void
    {
        $pest                   = new ProjectConfig();
        $pest->testingFramework = 'pest';
        self::assertStringContainsString('Pest', SkillTemplates::all($pest)['php-testing']);

        $both                   = new ProjectConfig();
        $both->testingFramework = 'both';
        self::assertStringContainsString('PHPUnit and Pest', SkillTemplates::all($both)['php-testing']);
    }
}
