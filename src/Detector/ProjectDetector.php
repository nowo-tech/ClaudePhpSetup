<?php

declare(strict_types=1);

namespace NowoTech\ClaudePhpSetup\Detector;

use NowoTech\ClaudePhpSetup\Question\ProjectConfig;

use function in_array;
use function is_array;

/**
 * Auto-detects project configuration from composer.json and filesystem.
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
            $detected      = $matches[1];
            $knownVersions = ['8.1', '8.2', '8.3', '8.4'];
            if (in_array($detected, $knownVersions, true)) {
                $config->phpVersion = $detected;
            // @codeCoverageIgnoreStart
            } elseif (version_compare($detected, '8.4', '>=')) {
                $config->phpVersion = '8.4';
            } elseif (version_compare($detected, '8.3', '>=')) {
                $config->phpVersion = '8.3';
            } elseif (version_compare($detected, '8.2', '>=')) {
                $config->phpVersion = '8.2';
            } else {
                $config->phpVersion = '8.1';
            }
            // @codeCoverageIgnoreEnd
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

    private function detectCommandRunner(ProjectConfig $config, string $projectDir): void
    {
        $hasMakefile = file_exists($projectDir . '/Makefile')
            || file_exists($projectDir . '/makefile');

        $config->commandRunner = $hasMakefile ? 'both' : 'composer';
    }

    private function detectDocker(ProjectConfig $config, string $projectDir): void
    {
        $root              = rtrim($projectDir, '/');
        $config->hasDocker = file_exists($root . '/docker-compose.yml')
            || file_exists($root . '/docker-compose.yaml')
            || file_exists($root . '/compose.yml')
            || file_exists($root . '/compose.yaml')
            || file_exists($root . '/Dockerfile');
    }

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

        $rectorConstraint      = $allPackages['rector/rector'] ?? '';
        $config->rectorVersion = str_contains($rectorConstraint, '^1') || str_contains($rectorConstraint, '1.') ? '1' : '2';
    }

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
