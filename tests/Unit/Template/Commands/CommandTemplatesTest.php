<?php

declare(strict_types=1);

namespace NowoTech\ClaudePhpSetup\Tests\Unit\Template\Commands;

use NowoTech\ClaudePhpSetup\Question\ProjectConfig;
use NowoTech\ClaudePhpSetup\Template\Commands\CommandTemplates;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function strlen;

final class CommandTemplatesTest extends TestCase
{
    #[Test]
    public function itRendersAllCommandTemplates(): void
    {
        $config                   = new ProjectConfig();
        $config->commandRunner    = 'both';
        $config->hasRector        = true;
        $config->rectorVersion    = '2';
        $config->hasPhpStan       = true;
        $config->phpStanLevel     = '8';
        $config->hasPhpCsFixer    = true;
        $config->testingFramework = 'phpunit';
        $config->hasTwig          = true;
        $config->hasDoctrine      = true;
        $config->hasApi           = true;
        $config->hasDocker        = true;
        $config->framework        = 'symfony';
        $config->isUpgrading      = true;
        $config->hasGrumPhp       = true;

        $all = CommandTemplates::all($config);

        foreach (array_keys($all) as $key) {
            self::assertNotSame('', $all[$key], "empty template: {$key}");
            self::assertGreaterThan(20, strlen($all[$key]), $key);
        }

        self::assertStringContainsString('grumphp.yml', $all['grumphp-check']);
        self::assertStringContainsString('UPGRADE-7.2', $all['symfony-upgrade']);
        self::assertStringContainsString('qa', $all['qa-gate']);
        self::assertStringContainsString('docker compose exec', $all['docker-exec']);
        self::assertStringContainsString('Doctrine migration', $all['migration-review']);
        self::assertStringContainsString('Authentication', $all['api-security-review']);
    }

    #[Test]
    public function itCoversCodeReviewWithoutOptionalTools(): void
    {
        $minimal                = new ProjectConfig();
        $minimal->hasRector     = false;
        $minimal->hasPhpStan    = false;
        $minimal->hasPhpCsFixer = false;

        $all = CommandTemplates::all($minimal);
        self::assertStringContainsString('declare(strict_types=1)', $all['code-review']);
    }

    #[Test]
    public function itCoversRectorDryForRector1x(): void
    {
        $config                = new ProjectConfig();
        $config->hasRector     = true;
        $config->rectorVersion = '1';
        $config->commandRunner = 'composer';

        $all = CommandTemplates::all($config);
        self::assertStringContainsString('sets([...])', $all['rector-dry']);
    }

    #[Test]
    public function itCoversQaGateRunnerHints(): void
    {
        $make                = new ProjectConfig();
        $make->commandRunner = 'make';
        self::assertStringContainsString('`make qa`', CommandTemplates::all($make)['qa-gate']);

        $composer                = new ProjectConfig();
        $composer->commandRunner = 'composer';
        self::assertStringContainsString('`composer qa`', CommandTemplates::all($composer)['qa-gate']);
    }
}
