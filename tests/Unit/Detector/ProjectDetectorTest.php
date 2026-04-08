<?php

declare(strict_types=1);

namespace NowoTech\ClaudePhpSetup\Tests\Unit\Detector;

use NowoTech\ClaudePhpSetup\Detector\ProjectDetector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function in_array;

use const JSON_PRETTY_PRINT;

/**
 * Represents the ProjectDetectorTest class.
 */
final class ProjectDetectorTest extends TestCase
{
    private ProjectDetector $detector;

    /**
     * Handles the setUp operation.
     */
    protected function setUp(): void
    {
        $this->detector = new ProjectDetector();
    }

    #[Test]
    /**
     * Handles the itReturnsDefaultConfigWhenNoComposerJsonExists operation.
     */
    public function itReturnsDefaultConfigWhenNoComposerJsonExists(): void
    {
        $config = $this->detector->detect('/nonexistent-directory');

        self::assertSame('8.3', $config->phpVersion);
        self::assertSame('none', $config->framework);
        self::assertFalse($config->hasRector);
        self::assertFalse($config->hasPhpStan);
        self::assertFalse($config->hasPhpCsFixer);
        self::assertFalse($config->hasGrumPhp);
        self::assertSame('phpunit', $config->testingFramework);
    }

    #[Test]
    /**
     * Handles the itDetectsSymfonyFramework operation.
     */
    public function itDetectsSymfonyFramework(): void
    {
        $dir = $this->createTempProjectWith([
            'require' => [
                'php'                      => '>=8.2',
                'symfony/framework-bundle' => '^7.2',
            ],
        ]);

        $config = $this->detector->detect($dir);

        self::assertSame('symfony', $config->framework);
        self::assertSame('7.2', $config->frameworkVersion);
        self::assertSame('8.2', $config->phpVersion);

        $this->removeTempProject($dir);
    }

    #[Test]
    /**
     * Handles the itDetectsLaravelFramework operation.
     */
    public function itDetectsLaravelFramework(): void
    {
        $dir = $this->createTempProjectWith([
            'require' => [
                'php'               => '^8.3',
                'laravel/framework' => '^11.0',
            ],
        ]);

        $config = $this->detector->detect($dir);

        self::assertSame('laravel', $config->framework);
        self::assertSame('11.0', $config->frameworkVersion);

        $this->removeTempProject($dir);
    }

    #[Test]
    /**
     * Handles the itDetectsQualityTools operation.
     */
    public function itDetectsQualityTools(): void
    {
        $dir = $this->createTempProjectWith([
            'require'     => ['php' => '>=8.1'],
            'require-dev' => [
                'rector/rector'             => '^2.0',
                'phpstan/phpstan'           => '^2.0',
                'friendsofphp/php-cs-fixer' => '^3.0',
                'phpro/grumphp'             => '^2.0',
            ],
        ]);

        $config = $this->detector->detect($dir);

        self::assertTrue($config->hasRector);
        self::assertSame('2', $config->rectorVersion);
        self::assertTrue($config->hasPhpStan);
        self::assertTrue($config->hasPhpCsFixer);
        self::assertTrue($config->hasGrumPhp);

        $this->removeTempProject($dir);
    }

    #[Test]
    /**
     * Handles the itDetectsRector1x operation.
     */
    public function itDetectsRector1x(): void
    {
        $dir = $this->createTempProjectWith([
            'require'     => ['php' => '>=8.1'],
            'require-dev' => ['rector/rector' => '^1.0'],
        ]);

        $config = $this->detector->detect($dir);

        self::assertTrue($config->hasRector);
        self::assertSame('1', $config->rectorVersion);

        $this->removeTempProject($dir);
    }

    #[Test]
    /**
     * Handles the itDetectsPestTestingFramework operation.
     */
    public function itDetectsPestTestingFramework(): void
    {
        $dir = $this->createTempProjectWith([
            'require'     => ['php' => '>=8.1'],
            'require-dev' => ['pestphp/pest' => '^3.0'],
        ]);

        $config = $this->detector->detect($dir);

        self::assertSame('pest', $config->testingFramework);

        $this->removeTempProject($dir);
    }

    #[Test]
    /**
     * Handles the itDetectsBothTestFrameworks operation.
     */
    public function itDetectsBothTestFrameworks(): void
    {
        $dir = $this->createTempProjectWith([
            'require'     => ['php' => '>=8.1'],
            'require-dev' => [
                'phpunit/phpunit' => '^11.0',
                'pestphp/pest'    => '^3.0',
            ],
        ]);

        $config = $this->detector->detect($dir);

        self::assertSame('both', $config->testingFramework);

        $this->removeTempProject($dir);
    }

    #[Test]
    /**
     * Handles the itDetectsTwig operation.
     */
    public function itDetectsTwig(): void
    {
        $dir = $this->createTempProjectWith([
            'require' => [
                'php'                 => '>=8.1',
                'symfony/twig-bundle' => '^7.0',
            ],
        ]);

        $config = $this->detector->detect($dir);

        self::assertTrue($config->hasTwig);

        $this->removeTempProject($dir);
    }

    #[Test]
    /**
     * Handles the itDetectsDoctrine operation.
     */
    public function itDetectsDoctrine(): void
    {
        $dir = $this->createTempProjectWith([
            'require' => [
                'php'                      => '>=8.1',
                'doctrine/doctrine-bundle' => '^2.0',
            ],
        ]);

        $config = $this->detector->detect($dir);

        self::assertTrue($config->hasDoctrine);

        $this->removeTempProject($dir);
    }

    #[Test]
    /**
     * Handles the itDetectsMakefileCommandRunner operation.
     */
    public function itDetectsMakefileCommandRunner(): void
    {
        $dir = $this->createTempProjectWith(['require' => ['php' => '>=8.1']]);
        file_put_contents($dir . '/Makefile', '# test makefile');

        $config = $this->detector->detect($dir);

        self::assertSame('both', $config->commandRunner);

        $this->removeTempProject($dir);
    }

    #[Test]
    #[DataProvider('phpVersionConstraintProvider')]
    /**
     * Handles the itExtractsPhpVersionFromConstraint operation.
     */
    public function itExtractsPhpVersionFromConstraint(string $constraint, string $expectedVersion): void
    {
        $dir = $this->createTempProjectWith(['require' => ['php' => $constraint]]);

        $config = $this->detector->detect($dir);

        self::assertSame($expectedVersion, $config->phpVersion);

        $this->removeTempProject($dir);
    }

    #[Test]
    /**
     * Handles the itDetectsSlimFramework operation.
     */
    public function itDetectsSlimFramework(): void
    {
        $dir = $this->createTempProjectWith([
            'require' => [
                'php'       => '^8.0',
                'slim/slim' => '^4.0',
            ],
        ]);

        $config = $this->detector->detect($dir);

        self::assertSame('slim', $config->framework);
        self::assertSame('4.0', $config->frameworkVersion);

        $this->removeTempProject($dir);
    }

    #[Test]
    /**
     * Handles the itDetectsYiiFramework operation.
     */
    public function itDetectsYiiFramework(): void
    {
        $dir = $this->createTempProjectWith([
            'require' => [
                'php'          => '^8.0',
                'yiisoft/yii2' => '^2.0',
            ],
        ]);

        $config = $this->detector->detect($dir);

        self::assertSame('yii', $config->framework);

        $this->removeTempProject($dir);
    }

    #[Test]
    /**
     * Handles the itDetectsApiPlatform operation.
     */
    public function itDetectsApiPlatform(): void
    {
        $dir = $this->createTempProjectWith([
            'require' => [
                'php'               => '^8.2',
                'api-platform/core' => '^4.0',
            ],
        ]);

        $config = $this->detector->detect($dir);

        self::assertTrue($config->hasApi);
        self::assertSame('api-platform', $config->apiStyle);

        $this->removeTempProject($dir);
    }

    #[Test]
    /**
     * Handles the itDetectsSymfonyApiPlatformCorePackage operation.
     */
    public function itDetectsSymfonyApiPlatformCorePackage(): void
    {
        $dir = $this->createTempProjectWith([
            'require' => [
                'php'                       => '^8.2',
                'symfony/api-platform-core' => '^4.0',
            ],
        ]);

        $config = $this->detector->detect($dir);

        self::assertTrue($config->hasApi);
        self::assertSame('api-platform', $config->apiStyle);

        $this->removeTempProject($dir);
    }

    #[Test]
    /**
     * Handles the itDetectsTwigCsFixer operation.
     */
    public function itDetectsTwigCsFixer(): void
    {
        $dir = $this->createTempProjectWith([
            'require'     => ['php' => '>=8.1'],
            'require-dev' => ['vincentlanglet/twig-cs-fixer' => '^3.0'],
        ]);

        $config = $this->detector->detect($dir);

        self::assertTrue($config->hasTwigCsFixer);

        $this->removeTempProject($dir);
    }

    #[Test]
    /**
     * Handles the itDetectsPhpStanLevelFromPhpstanNeonDist operation.
     */
    public function itDetectsPhpStanLevelFromPhpstanNeonDist(): void
    {
        $dir = $this->createTempProjectWith([
            'require'     => ['php' => '>=8.1'],
            'require-dev' => ['phpstan/phpstan' => '^2.0'],
        ]);
        file_put_contents($dir . '/phpstan.neon.dist', "parameters:\n    level: max\n");

        $config = $this->detector->detect($dir);

        self::assertSame('max', $config->phpStanLevel);

        $this->removeTempProject($dir);
    }

    #[Test]
    /**
     * Handles the itDetectsMakefileCaseInsensitive operation.
     */
    public function itDetectsMakefileCaseInsensitive(): void
    {
        $dir = $this->createTempProjectWith(['require' => ['php' => '>=8.1']]);
        file_put_contents($dir . '/makefile', '#');

        $config = $this->detector->detect($dir);

        self::assertSame('both', $config->commandRunner);

        $this->removeTempProject($dir);
    }

    #[Test]
    /**
     * Handles the itDetectsNoTestingFrameworkWhenNoTestPackages operation.
     */
    public function itDetectsNoTestingFrameworkWhenNoTestPackages(): void
    {
        $dir = $this->createTempProjectWith(['require' => ['php' => '>=8.1']]);

        $config = $this->detector->detect($dir);

        self::assertSame('none', $config->testingFramework);

        $this->removeTempProject($dir);
    }

    #[Test]
    /**
     * Handles the itDetectsPhpVersionBelow81FromConstraint operation.
     */
    public function itDetectsPhpVersionBelow81FromConstraint(): void
    {
        $dir = $this->createTempProjectWith(['require' => ['php' => '^7.4']]);

        $config = $this->detector->detect($dir);

        self::assertSame('8.1', $config->phpVersion);

        $this->removeTempProject($dir);
    }

    #[Test]
    /**
     * Handles the itKeepsDefaultPhpWhenComposerHasNoPhpRequirement operation.
     */
    public function itKeepsDefaultPhpWhenComposerHasNoPhpRequirement(): void
    {
        $dir = $this->createTempProjectWith(['name' => 'vendor/pkg', 'require' => []]);

        $config = $this->detector->detect($dir);

        self::assertSame('8.3', $config->phpVersion);

        $this->removeTempProject($dir);
    }

    #[Test]
    /**
     * Handles the itDetectsPhpunitOnlyTesting operation.
     */
    public function itDetectsPhpunitOnlyTesting(): void
    {
        $dir = $this->createTempProjectWith([
            'require'     => ['php' => '>=8.1'],
            'require-dev' => ['phpunit/phpunit' => '^11.0'],
        ]);

        $config = $this->detector->detect($dir);

        self::assertSame('phpunit', $config->testingFramework);

        $this->removeTempProject($dir);
    }

    #[Test]
    /**
     * Handles the itNormalisesPhpVersion85ConstraintTo84Cap operation.
     */
    public function itNormalisesPhpVersion85ConstraintTo84Cap(): void
    {
        $dir = $this->createTempProjectWith(['require' => ['php' => '>=8.5']]);

        $config = $this->detector->detect($dir);

        self::assertSame('8.4', $config->phpVersion);

        $this->removeTempProject($dir);
    }

    #[Test]
    /**
     * Handles the itAllowsNullFrameworkVersionWhenConstraintHasNoMinor operation.
     */
    public function itAllowsNullFrameworkVersionWhenConstraintHasNoMinor(): void
    {
        $dir = $this->createTempProjectWith([
            'require' => [
                'php'                      => '^8.2',
                'symfony/framework-bundle' => 'dev-main',
            ],
        ]);

        $config = $this->detector->detect($dir);

        self::assertSame('symfony', $config->framework);
        self::assertNull($config->frameworkVersion);

        $this->removeTempProject($dir);
    }

    #[Test]
    /**
     * Handles the itExtractsSymfonyVersionFromMajorOnlyConstraint operation.
     */
    public function itExtractsSymfonyVersionFromMajorOnlyConstraint(): void
    {
        $dir = $this->createTempProjectWith([
            'require' => [
                'php'                      => '^8.2',
                'symfony/framework-bundle' => '^7',
            ],
        ]);

        $config = $this->detector->detect($dir);

        self::assertSame('7.0', $config->frameworkVersion);

        $this->removeTempProject($dir);
    }

    #[Test]
    /**
     * Handles the itReturnsDefaultConfigWhenComposerJsonIsInvalid operation.
     */
    public function itReturnsDefaultConfigWhenComposerJsonIsInvalid(): void
    {
        $dir = sys_get_temp_dir() . '/claude-php-setup-test-' . uniqid();
        mkdir($dir, 0755, true);
        file_put_contents($dir . '/composer.json', '{not json');

        $config = $this->detector->detect($dir);

        self::assertSame('none', $config->framework);

        $this->removeTempProject($dir);
    }

    /** @return array<string, array{string, string}> */
    public static function phpVersionConstraintProvider(): array
    {
        return [
            '>=8.1'      => ['>=8.1', '8.1'],
            '^8.2'       => ['^8.2', '8.2'],
            '^8.3'       => ['^8.3', '8.3'],
            '>=8.2 <8.6' => ['>=8.2 <8.6', '8.2'],
            '8.1.*'      => ['8.1.*', '8.1'],
        ];
    }

    /** @param array<string, mixed> $composerData */
    private function createTempProjectWith(array $composerData): string
    {
        $dir = sys_get_temp_dir() . '/claude-php-setup-test-' . uniqid();
        mkdir($dir, 0755, true);
        file_put_contents($dir . '/composer.json', json_encode($composerData, JSON_PRETTY_PRINT));

        return $dir;
    }

    /**
     * Handles the removeTempProject operation.
     */
    private function removeTempProject(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $file) {
            if (in_array($file, ['.', '..'], true)) {
                continue;
            }
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeTempProject($path) : unlink($path);
        }
        rmdir($dir);
    }

    #[Test]
    /**
     * Handles the itDetectsDockerfileOrCompose operation.
     */
    public function itDetectsDockerfileOrCompose(): void
    {
        $dir = $this->createTempProjectWith(['require' => ['php' => '>=8.1']]);
        file_put_contents($dir . '/Dockerfile', 'FROM php:8.3-cli');

        $config = $this->detector->detect($dir);

        self::assertTrue($config->hasDocker);

        $this->removeTempProject($dir);
    }

    #[Test]
    /**
     * Handles the itDetectsGithubActionsWorkflows operation.
     */
    public function itDetectsGithubActionsWorkflows(): void
    {
        $dir = $this->createTempProjectWith(['require' => ['php' => '>=8.1']]);
        mkdir($dir . '/.github/workflows', 0755, true);
        file_put_contents($dir . '/.github/workflows/ci.yml', "on: push\njobs: {}\n");

        $config = $this->detector->detect($dir);

        self::assertTrue($config->hasCi);

        $this->removeTempProject($dir);
    }
}
