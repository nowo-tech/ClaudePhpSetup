<?php

declare(strict_types=1);

namespace NowoTech\ClaudePhpSetup\Tests\Unit\Template;

use NowoTech\ClaudePhpSetup\Question\ProjectConfig;
use NowoTech\ClaudePhpSetup\Template\ClaudeMd\BaseSection;
use NowoTech\ClaudePhpSetup\Template\ClaudeMd\DoctrineSection;
use NowoTech\ClaudePhpSetup\Template\ClaudeMd\FrameworkSection;
use NowoTech\ClaudePhpSetup\Template\ClaudeMd\OperationalSection;
use NowoTech\ClaudePhpSetup\Template\ClaudeMd\QualityToolsSection;
use NowoTech\ClaudePhpSetup\Template\ClaudeMd\TestingSection;
use NowoTech\ClaudePhpSetup\Template\ClaudeMd\TwigSection;
use NowoTech\ClaudePhpSetup\Template\ClaudeMd\UpgradeSection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MarkdownTemplatesTest extends TestCase
{
    #[Test]
    public function itCoversFrameworkSectionVariants(): void
    {
        $symfonyOld                   = new ProjectConfig();
        $symfonyOld->framework        = 'symfony';
        $symfonyOld->frameworkVersion = '5.4';
        self::assertStringContainsString('YAML config still common', FrameworkSection::render($symfonyOld));

        $symfony7                   = new ProjectConfig();
        $symfony7->framework        = 'symfony';
        $symfony7->frameworkVersion = '7.0';
        self::assertStringContainsString('AsEventListener', FrameworkSection::render($symfony7));

        $laravel                   = new ProjectConfig();
        $laravel->framework        = 'laravel';
        $laravel->frameworkVersion = '11';
        self::assertStringContainsString('Laravel', FrameworkSection::render($laravel));

        $slim            = new ProjectConfig();
        $slim->framework = 'slim';
        self::assertStringContainsString('PSR-15', FrameworkSection::render($slim));

        $none            = new ProjectConfig();
        $none->framework = 'none';
        self::assertSame('', FrameworkSection::render($none));
    }

    #[Test]
    public function itCoversQualityToolsSection(): void
    {
        $full                   = new ProjectConfig();
        $full->hasRector        = true;
        $full->rectorVersion    = '1';
        $full->hasPhpStan       = true;
        $full->phpStanLevel     = 'unknown';
        $full->hasPhpCsFixer    = true;
        $full->hasGrumPhp       = true;
        $full->testingFramework = 'phpunit';
        $full->framework        = 'symfony';
        $full->frameworkVersion = '7.2';
        $full->phpVersion       = '8.3';
        $full->commandRunner    = 'make';

        $out = QualityToolsSection::render($full);
        self::assertStringContainsString('Rector', $out);
        self::assertStringContainsString('standard checks', $out);
        self::assertStringContainsString('GrumPHP', $out);

        $empty = new ProjectConfig();
        self::assertSame('', QualityToolsSection::render($empty));

        $grumOnly                   = new ProjectConfig();
        $grumOnly->hasGrumPhp       = true;
        $grumOnly->testingFramework = 'none';
        $grumOnly->hasPhpCsFixer    = false;
        $grumOnly->hasPhpStan       = false;
        self::assertStringContainsString('Configured in `grumphp.yml`', QualityToolsSection::render($grumOnly));
    }

    #[Test]
    #[DataProvider('phpStanLevelProvider')]
    public function itCoversPhpStanLevelDescriptions(string $level): void
    {
        $config                = new ProjectConfig();
        $config->hasPhpStan    = true;
        $config->phpStanLevel  = $level;
        $config->commandRunner = 'composer';

        self::assertStringContainsString('### PHPStan', QualityToolsSection::render($config));
    }

    /** @return array<string, array{string}> */
    public static function phpStanLevelProvider(): array
    {
        // @phpstan-ignore-next-line return.type (PHPStan infers numeric keys from the literal array shape)
        return [
            '0'   => ['0'],
            '1'   => ['1'],
            '2'   => ['2'],
            '3'   => ['3'],
            '4'   => ['4'],
            '5'   => ['5'],
            '6'   => ['6'],
            '7'   => ['7'],
            '8'   => ['8'],
            '9'   => ['9'],
            'max' => ['max'],
        ];
    }

    #[Test]
    public function itCoversTestingSection(): void
    {
        $none                   = new ProjectConfig();
        $none->testingFramework = 'none';
        self::assertSame('', TestingSection::render($none));

        $both                   = new ProjectConfig();
        $both->testingFramework = 'both';
        $both->commandRunner    = 'both';
        $both->hasDoctrine      = true;
        self::assertStringContainsString('Pest', TestingSection::render($both));
        self::assertStringContainsString('Database Tests', TestingSection::render($both));

        $symfonyPest                   = new ProjectConfig();
        $symfonyPest->framework        = 'symfony';
        $symfonyPest->testingFramework = 'pest';
        $symfonyPest->commandRunner    = 'composer';
        self::assertStringContainsString('Architecture tests', TestingSection::render($symfonyPest));

        $symfonyPhpunit                   = new ProjectConfig();
        $symfonyPhpunit->framework        = 'symfony';
        $symfonyPhpunit->testingFramework = 'phpunit';
        self::assertStringContainsString('WebTestCase', TestingSection::render($symfonyPhpunit));
    }

    #[Test]
    public function itCoversTwigSection(): void
    {
        $twig                 = new ProjectConfig();
        $twig->hasTwig        = true;
        $twig->hasTwigCsFixer = true;
        $twig->commandRunner  = 'make';
        $twig->framework      = 'symfony';

        $out = TwigSection::render($twig);
        self::assertStringContainsString('Twig-CS-Fixer', $out);
        self::assertStringContainsString('Symfony + Twig', $out);

        $laravelTwig            = new ProjectConfig();
        $laravelTwig->hasTwig   = true;
        $laravelTwig->framework = 'laravel';
        self::assertStringContainsString('resources/views', TwigSection::render($laravelTwig));

        $noTwig          = new ProjectConfig();
        $noTwig->hasTwig = false;
        self::assertSame('', TwigSection::render($noTwig));
    }

    #[Test]
    public function itCoversUpgradeSectionBreakingChangeBranches(): void
    {
        $sixToSeven                     = new ProjectConfig();
        $sixToSeven->isUpgrading        = true;
        $sixToSeven->framework          = 'symfony';
        $sixToSeven->upgradeFromVersion = '6.4';
        $sixToSeven->frameworkVersion   = '7.2';
        self::assertStringContainsString('AbstractController::json', UpgradeSection::render($sixToSeven));

        $fiveToSix                     = new ProjectConfig();
        $fiveToSix->isUpgrading        = true;
        $fiveToSix->framework          = 'symfony';
        $fiveToSix->upgradeFromVersion = '5.4';
        $fiveToSix->frameworkVersion   = '6.4';
        self::assertStringContainsString('PHP 8.0+ required', UpgradeSection::render($fiveToSix));

        $minor                     = new ProjectConfig();
        $minor->isUpgrading        = true;
        $minor->framework          = 'symfony';
        $minor->upgradeFromVersion = '7.1';
        $minor->frameworkVersion   = '7.2';
        self::assertStringContainsString('UPGRADE-7.2.md', UpgradeSection::render($minor));

        $notSymfony              = new ProjectConfig();
        $notSymfony->isUpgrading = true;
        $notSymfony->framework   = 'laravel';
        self::assertSame('', UpgradeSection::render($notSymfony));
    }

    #[Test]
    public function itCoversBaseSection(): void
    {
        $header                     = new ProjectConfig();
        $header->projectName        = 'P';
        $header->projectDescription = 'Desc';
        self::assertStringContainsString('# P', BaseSection::header($header));
        self::assertStringContainsString('Desc', BaseSection::header($header));

        $stack                   = new ProjectConfig();
        $stack->phpVersion       = '8.4';
        $stack->framework        = 'symfony';
        $stack->frameworkVersion = '7.2';
        $stack->hasDoctrine      = true;
        $stack->hasTwig          = true;
        $stack->hasApi           = true;
        $stack->apiStyle         = 'api-platform';
        $stack->testingFramework = 'pest';
        $stack->hasRector        = true;
        $stack->rectorVersion    = '2';
        $stack->hasPhpStan       = true;
        $stack->phpStanLevel     = '8';
        $stack->hasPhpCsFixer    = true;
        $stack->hasGrumPhp       = true;
        $out                     = BaseSection::stack($stack);
        self::assertStringContainsString('Symfony', $out);
        self::assertStringContainsString('API Platform', $out);
        self::assertStringContainsString('Pest', $out);

        $noCommands                   = new ProjectConfig();
        $noCommands->testingFramework = 'none';
        self::assertSame('', BaseSection::commands($noCommands));

        $grumCmd                   = new ProjectConfig();
        $grumCmd->hasGrumPhp       = true;
        $grumCmd->testingFramework = 'phpunit';
        self::assertStringContainsString('grumphp', BaseSection::commands($grumCmd));

        $yii                   = new ProjectConfig();
        $yii->framework        = 'yii';
        $yii->frameworkVersion = '2.0';
        self::assertStringContainsString('Yii', BaseSection::stack($yii));

        $slim                   = new ProjectConfig();
        $slim->framework        = 'slim';
        $slim->frameworkVersion = '4.0';
        self::assertStringContainsString('Slim', BaseSection::stack($slim));

        $graphqlApi           = new ProjectConfig();
        $graphqlApi->hasApi   = true;
        $graphqlApi->apiStyle = 'graphql';
        self::assertStringContainsString('GraphQL', BaseSection::stack($graphqlApi));

        $restApi           = new ProjectConfig();
        $restApi->hasApi   = true;
        $restApi->apiStyle = 'rest';
        self::assertStringContainsString('REST API', BaseSection::stack($restApi));

        $pestOnly                   = new ProjectConfig();
        $pestOnly->testingFramework = 'pest';
        self::assertStringContainsString('Pest', BaseSection::stack($pestOnly));

        $bothTesting                   = new ProjectConfig();
        $bothTesting->testingFramework = 'both';
        self::assertStringContainsString('PHPUnit + Pest', BaseSection::stack($bothTesting));

        $laravelOnly                   = new ProjectConfig();
        $laravelOnly->framework        = 'laravel';
        $laravelOnly->frameworkVersion = '11';
        self::assertStringContainsString('Laravel', BaseSection::stack($laravelOnly));

        $dockerCi            = new ProjectConfig();
        $dockerCi->hasDocker = true;
        $dockerCi->hasCi     = true;
        self::assertStringContainsString('Docker', BaseSection::stack($dockerCi));
        self::assertStringContainsString('GitHub Actions', BaseSection::stack($dockerCi));

        $customFw                   = new ProjectConfig();
        $customFw->framework        = 'customcms';
        $customFw->frameworkVersion = '2';
        self::assertStringContainsString('Customcms', BaseSection::stack($customFw));

        self::assertStringContainsString('PHP Best Practices', BaseSection::phpBestPractices());

        self::assertStringContainsString('Code Review', BaseSection::codeReviewGuidelines(new ProjectConfig()));
    }

    #[Test]
    public function itCoversOperationalSection(): void
    {
        $full                            = new ProjectConfig();
        $full->hasDocker                 = true;
        $full->hasCi                     = true;
        $full->hasApi                    = true;
        $full->apiStyle                  = 'rest';
        $full->includeObservabilityNotes = true;
        $full->includeMcpNotes           = true;

        $out = OperationalSection::render($full);
        self::assertStringContainsString('Docker', $out);
        self::assertStringContainsString('GitHub Actions', $out);
        self::assertStringContainsString('API security', $out);
        self::assertStringContainsString('Observability', $out);
        self::assertStringContainsString('MCP', $out);

        $empty = new ProjectConfig();
        self::assertSame('', OperationalSection::render($empty));

        $graphql           = new ProjectConfig();
        $graphql->hasApi   = true;
        $graphql->apiStyle = 'graphql';
        self::assertStringContainsString('GraphQL', OperationalSection::render($graphql));

        $apiPlatform           = new ProjectConfig();
        $apiPlatform->hasApi   = true;
        $apiPlatform->apiStyle = 'api-platform';
        self::assertStringContainsString('API Platform', OperationalSection::render($apiPlatform));
    }

    #[Test]
    public function itCoversGeneratedClaudeResourcesSection(): void
    {
        $cfg                   = new ProjectConfig();
        $cfg->generateClaudeMd = true;
        $cfg->generateCommands = true;
        $cfg->selectedCommands = ['code-review'];
        $cfg->generateAgents   = true;
        $cfg->selectedAgents   = ['php-architect'];
        $cfg->generateSkills   = true;
        $cfg->selectedSkills   = ['php-quality'];

        $out = BaseSection::generatedClaudeResources($cfg);
        self::assertStringContainsString('Claude resources', $out);
        self::assertStringContainsString('.claude/skills/php-quality/SKILL.md', $out);
        self::assertStringContainsString('examples/', $out);

        $none                   = new ProjectConfig();
        $none->generateClaudeMd = false;
        $none->generateCommands = false;
        $none->generateAgents   = false;
        $none->generateSkills   = false;
        $none->generateExamples = false;
        self::assertSame('', BaseSection::generatedClaudeResources($none));
    }

    #[Test]
    public function itCoversDoctrineSection(): void
    {
        $on              = new ProjectConfig();
        $on->hasDoctrine = true;
        self::assertStringContainsString('Doctrine', DoctrineSection::render($on));

        $off              = new ProjectConfig();
        $off->hasDoctrine = false;
        self::assertSame('', DoctrineSection::render($off));
    }
}
