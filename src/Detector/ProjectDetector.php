<?php

declare(strict_types=1);

namespace NowoTech\ClaudePhpSetup\Detector;

use NowoTech\ClaudePhpSetup\Question\ProjectConfig;

use function count;
use function in_array;
use function is_array;

use const PREG_SET_ORDER;

/**
 * Auto-detects project configuration from composer.json and filesystem.
 */
/**
 * Represents the ProjectDetector class.
 */
final class ProjectDetector
{
    private const FRAMEWORK_MAP = [
        'symfony/framework-bundle' => ['framework' => 'symfony'],
        'laravel/framework'        => ['framework' => 'laravel'],
        'slim/slim'                => ['framework' => 'slim'],
        'yiisoft/yii2'             => ['framework' => 'yii'],
    ];

    private const TOOL_MAP = [
        'rector/rector'                => 'rector',
        'phpstan/phpstan'              => 'phpstan',
        'friendsofphp/php-cs-fixer'    => 'phpCsFixer',
        'phpro/grumphp'                => 'grumPhp',
        'vincentlanglet/twig-cs-fixer' => 'twigCsFixer',
    ];

    /**
     * Handles the detect operation.
     */
    public function detect(string $projectDir): ProjectConfig
    {
        $config             = new ProjectConfig();
        $config->projectDir = $projectDir;

        $composerJson = $this->readComposerJson($projectDir);
        if ($composerJson === null) {
            return $config;
        }

        $config->projectName        = $composerJson['name'] ?? null;
        $config->projectDescription = $composerJson['description'] ?? null;

        $allPackages = array_merge(
            $composerJson['require'] ?? [],
            $composerJson['require-dev'] ?? [],
        );

        $this->detectPhpVersion($config, $composerJson);
        $this->detectFramework($config, $allPackages);
        $this->detectQualityTools($config, $allPackages);
        $this->detectTesting($config, $allPackages);
        $this->detectTemplating($config, $allPackages);
        $this->detectDoctrine($config, $allPackages);
        $this->detectApi($config, $allPackages);
        $this->detectCommandRunner($config, $projectDir);
        $this->detectDocker($config, $projectDir);
        $this->detectCi($config, $projectDir);
        $this->detectRectorVersion($config, $allPackages);
        $this->detectPhpStanLevel($config, $projectDir);

        return $config;
    }

    /** @param array<string, mixed> $composerJson */
    private function detectPhpVersion(ProjectConfig $config, array $composerJson): void
    {
        $phpConstraint = $composerJson['require']['php'] ?? null;
        if ($phpConstraint === null) {
            return;
        }

        // Extract minimum version from constraint like ">=8.2", "^8.2", ">=8.2 <8.6"
        if (preg_match('/(\d+\.\d+)/', (string) $phpConstraint, $matches)) {
            $detected = $matches[1];
            $highest  = ProjectConfig::PHP_VERSIONS[count(ProjectConfig::PHP_VERSIONS) - 1];

            if (in_array($detected, ProjectConfig::PHP_VERSIONS, true)) {
                $config->phpVersion = $detected;
            } elseif (version_compare($detected, $highest, '>')) {
                $config->phpVersion = $highest;
            } else {
                $config->phpVersion = ProjectConfig::PHP_VERSIONS[0];
            }
        }
    }

    /** @param array<string, string> $allPackages */
    private function detectFramework(ProjectConfig $config, array $allPackages): void
    {
        foreach (self::FRAMEWORK_MAP as $package => $frameworkConfig) {
            if (isset($allPackages[$package])) {
                $config->framework        = $frameworkConfig['framework'];
                $constraint               = $allPackages[$package];
                $config->frameworkVersion = $this->extractMajorMinorVersion($constraint);
                break;
            }
        }
    }

    /** @param array<string, string> $allPackages */
    private function detectQualityTools(ProjectConfig $config, array $allPackages): void
    {
        foreach (self::TOOL_MAP as $package => $property) {
            if (isset($allPackages[$package])) {
                switch ($property) {
                    case 'rector':
                        $config->hasRector = true;
                        break;
                    case 'phpstan':
                        $config->hasPhpStan = true;
                        break;
                    case 'phpCsFixer':
                        $config->hasPhpCsFixer = true;
                        break;
                    case 'grumPhp':
                        $config->hasGrumPhp = true;
                        break;
                    case 'twigCsFixer':
                        $config->hasTwigCsFixer = true;
                        break;
                }
            }
        }
    }

    /** @param array<string, string> $allPackages */
    private function detectTesting(ProjectConfig $config, array $allPackages): void
    {
        $hasPHPUnit = isset($allPackages['phpunit/phpunit']);
        $hasPest    = isset($allPackages['pestphp/pest']);

        if ($hasPHPUnit && $hasPest) {
            $config->testingFramework = 'both';
        } elseif ($hasPest) {
            $config->testingFramework = 'pest';
        } elseif ($hasPHPUnit) {
            $config->testingFramework = 'phpunit';
        } else {
            $config->testingFramework = 'none';
        }
    }

    /** @param array<string, string> $allPackages */
    private function detectTemplating(ProjectConfig $config, array $allPackages): void
    {
        $config->hasTwig = isset($allPackages['twig/twig'])
            || isset($allPackages['symfony/twig-bundle']);
    }

    /** @param array<string, string> $allPackages */
    private function detectDoctrine(ProjectConfig $config, array $allPackages): void
    {
        $config->hasDoctrine = isset($allPackages['doctrine/orm'])
            || isset($allPackages['doctrine/doctrine-bundle'])
            || isset($allPackages['doctrine/doctrine-migrations-bundle']);
    }

    /** @param array<string, string> $allPackages */
    private function detectApi(ProjectConfig $config, array $allPackages): void
    {
        if (isset($allPackages['api-platform/core']) || isset($allPackages['api-platform/symfony'])) {
            $config->hasApi   = true;
            $config->apiStyle = 'api-platform';
        } elseif (isset($allPackages['symfony/api-platform-core'])) {
            $config->hasApi   = true;
            $config->apiStyle = 'api-platform';
        }
    }

    /**
     * Handles the detectCommandRunner operation.
     */
    private function detectCommandRunner(ProjectConfig $config, string $projectDir): void
    {
        $hasMakefile = file_exists($projectDir . '/Makefile')
            || file_exists($projectDir . '/makefile');

        $config->commandRunner = $hasMakefile ? 'both' : 'composer';
    }

    /**
     * Handles the detectDocker operation.
     */
    private function detectDocker(ProjectConfig $config, string $projectDir): void
    {
        $root              = rtrim($projectDir, '/');
        $config->hasDocker = file_exists($root . '/docker-compose.yml')
            || file_exists($root . '/docker-compose.yaml')
            || file_exists($root . '/compose.yml')
            || file_exists($root . '/compose.yaml')
            || file_exists($root . '/Dockerfile');
    }

    /**
     * Handles the detectCi operation.
     */
    private function detectCi(ProjectConfig $config, string $projectDir): void
    {
        $workflows = rtrim($projectDir, '/') . '/.github/workflows';
        if (!is_dir($workflows)) {
            return;
        }

        $ymlFiles      = glob($workflows . '/*.yml') ?: [];
        $yamlFiles     = glob($workflows . '/*.yaml') ?: [];
        $config->hasCi = $ymlFiles !== [] || $yamlFiles !== [];
    }

    /** @param array<string, string> $allPackages */
    private function detectRectorVersion(ProjectConfig $config, array $allPackages): void
    {
        if (!$config->hasRector) {
            return;
        }

        $config->rectorVersion = $this->rectorMajorFromConstraint($allPackages['rector/rector'] ?? '');
    }

    /**
     * Highest Rector major allowed by a Composer constraint ('1' for 0.x/1.x, '2' otherwise).
     *
     * Upper bounds (`<2.0`) and exclusions (`!=1.0`) are ignored, so `^2.1.0` → 2, `^1.0 || ^2.0` → 2,
     * `>=1.0 <2.0` → 1. Unparseable constraints (`*`, `dev-main`) default to the current major (2).
     */
    private function rectorMajorFromConstraint(string $constraint): string
    {
        preg_match_all('/(<=?|>=?|!=|\^|~|=)?\s*v?(\d+)(?:\.(?:\d+|\*))*/', $constraint, $matches, PREG_SET_ORDER);

        $majors = [];
        foreach ($matches as $match) {
            if (str_starts_with($match[1], '<') || $match[1] === '!=') {
                continue;
            }
            $majors[] = (int) $match[2];
        }

        if ($majors === []) {
            return '2';
        }

        return max($majors) <= 1 ? '1' : '2';
    }

    /**
     * Handles the detectPhpStanLevel operation.
     */
    private function detectPhpStanLevel(ProjectConfig $config, string $projectDir): void
    {
        if (!$config->hasPhpStan) {
            return;
        }

        $configFiles = [
            $projectDir . '/phpstan.neon',
            $projectDir . '/phpstan.neon.dist',
            $projectDir . '/phpstan.dist.neon',
        ];

        foreach ($configFiles as $file) {
            if (!file_exists($file)) {
                continue;
            }

            $content = (string) file_get_contents($file);
            if (preg_match('/level:\s*(\d+|max)/i', $content, $matches)) {
                $config->phpStanLevel = $matches[1];

                return;
            }
        }
    }

    /** @return array<string, mixed>|null */
    private function readComposerJson(string $projectDir): ?array
    {
        $composerJsonPath = rtrim($projectDir, '/') . '/composer.json';
        if (!file_exists($composerJsonPath)) {
            return null;
        }

        $content = file_get_contents($composerJsonPath);
        if ($content === false) {
            return null; // @codeCoverageIgnore
        }

        $data = json_decode($content, true);

        return is_array($data) ? $data : null;
    }

    /**
     * Handles the extractMajorMinorVersion operation.
     */
    private function extractMajorMinorVersion(string $constraint): ?string
    {
        // Handles: ^6.4, >=6.4, ~6.4, 6.4.*, 6.4
        if (preg_match('/(\d+)\.(\d+)/', $constraint, $matches)) {
            return $matches[1] . '.' . $matches[2];
        }
        if (preg_match('/(\d+)/', $constraint, $matches)) {
            return $matches[1] . '.0';
        }

        return null;
    }
}
