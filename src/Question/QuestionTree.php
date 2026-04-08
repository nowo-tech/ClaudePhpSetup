<?php

declare(strict_types=1);

namespace NowoTech\ClaudePhpSetup\Question;

use NowoTech\ClaudePhpSetup\Cli\Console;

/**
 * Interactive question tree that fills in a ProjectConfig.
 */
/**
 * Represents the QuestionTree class.
 */
final class QuestionTree
{
    /**
     * Handles the __construct operation.
     */
    public function __construct(
        private readonly Console $console,
    ) {
    }

    /**
     * Handles the run operation.
     */
    public function run(ProjectConfig $detected): ProjectConfig
    {
        $config = clone $detected;

        $this->console->section('Project Settings');
        $this->askPhpVersion($config);
        $this->askFramework($config);

        $this->console->section('Quality Tools');
        $this->askQualityTools($config);

        $this->console->section('Testing');
        $this->askTesting($config);

        $this->console->section('Architecture & Stack');
        $this->askArchitecture($config);

        $this->console->section('Operational & delivery');
        $this->askOperational($config);

        $this->console->section('Files to Generate');
        $this->askGeneration($config);

        return $config;
    }

    /**
     * Handles the askPhpVersion operation.
     */
    private function askPhpVersion(ProjectConfig $config): void
    {
        $config->phpVersion = $this->console->choice(
            question: 'PHP version',
            choices: ['8.1', '8.2', '8.3', '8.4'],
            default: $config->phpVersion,
        );
    }

    /**
     * Handles the askFramework operation.
     */
    private function askFramework(ProjectConfig $config): void
    {
        $config->framework = $this->console->choice(
            question: 'Framework',
            choices: ['none', 'symfony', 'laravel', 'slim', 'other'],
            default: $config->framework,
        );

        if ($config->framework === 'symfony') {
            $symfonyVersions = ['5.4', '6.4', '7.0', '7.1', '7.2', '7.3', '7.4', '8.0'];
            $currentVersion  = $this->console->choice(
                question: 'Current Symfony version',
                choices: $symfonyVersions,
                default: $config->frameworkVersion ?? '8.0',
            );

            $config->isUpgrading = $this->console->confirm(
                question: 'Are you upgrading Symfony to a newer version?',
                default: false,
            );

            if ($config->isUpgrading) {
                $upgradeToChoices = array_values(array_filter(
                    $symfonyVersions,
                    static fn (string $v): bool => version_compare($v, $currentVersion, '>'),
                ));

                if ($upgradeToChoices === []) {
                    $this->console->warning('No newer Symfony version available in the supported list; upgrade flow disabled.');
                    $config->isUpgrading      = false;
                    $config->frameworkVersion = $currentVersion;
                } else {
                    $config->frameworkVersion = $this->console->choice(
                        question: 'Upgrading to which Symfony version?',
                        choices: $upgradeToChoices,
                        default: $upgradeToChoices[array_key_first($upgradeToChoices)] ?? $currentVersion,
                    );
                    $config->upgradeFromVersion = $currentVersion;
                }
            } else {
                $config->frameworkVersion   = $currentVersion;
                $config->upgradeFromVersion = null;
            }
        } elseif ($config->framework === 'laravel') {
            $config->frameworkVersion = $this->console->choice(
                question: 'Laravel version',
                choices: ['10', '11', '12'],
                default: $config->frameworkVersion ?? '11',
            );
        } else {
            $config->frameworkVersion   = null;
            $config->upgradeFromVersion = null;
            $config->isUpgrading        = false;
        }
    }

    /**
     * Handles the askQualityTools operation.
     */
    private function askQualityTools(ProjectConfig $config): void
    {
        $config->hasRector = $this->console->confirm(
            question: 'Rector (automated refactoring)',
            default: $config->hasRector,
        );

        if ($config->hasRector) {
            $rectorVersionLabel = $this->console->choice(
                question: '  Rector version',
                choices: ['version 1', 'version 2'],
                default: $config->rectorVersion === '1' ? 'version 1' : 'version 2',
            );
            $config->rectorVersion = $rectorVersionLabel === 'version 1' ? '1' : '2';
        }

        $config->hasPhpStan = $this->console->confirm(
            question: 'PHPStan (static analysis)',
            default: $config->hasPhpStan,
        );

        if ($config->hasPhpStan) {
            $phpStanLevelLabels = [
                'level 0',
                'level 1',
                'level 2',
                'level 3',
                'level 4',
                'level 5',
                'level 6',
                'level 7',
                'level 8',
                'level 9',
                'level max',
            ];
            $defaultPhpStanLabel = $config->phpStanLevel === 'max'
                ? 'level max'
                : 'level ' . ($config->phpStanLevel !== '' ? $config->phpStanLevel : '8');

            $phpStanLevelLabel = $this->console->choice(
                question: '  PHPStan level',
                choices: $phpStanLevelLabels,
                default: $defaultPhpStanLabel,
            );
            $config->phpStanLevel = $phpStanLevelLabel === 'level max'
                ? 'max'
                : trim(str_replace('level', '', $phpStanLevelLabel));
        }

        $config->hasPhpCsFixer = $this->console->confirm(
            question: 'PHP-CS-Fixer (code style)',
            default: $config->hasPhpCsFixer,
        );

        $config->hasGrumPhp = $this->console->confirm(
            question: 'GrumPHP (git hooks / pre-commit quality)',
            default: $config->hasGrumPhp,
        );

        if ($config->hasTwig || $config->framework === 'symfony') {
            $config->hasTwigCsFixer = $this->console->confirm(
                question: 'Twig-CS-Fixer (Twig template style)',
                default: $config->hasTwigCsFixer,
            );
        }
    }

    /**
     * Handles the askTesting operation.
     */
    private function askTesting(ProjectConfig $config): void
    {
        $config->testingFramework = $this->console->choice(
            question: 'Testing framework',
            choices: ['none', 'phpunit', 'pest', 'both'],
            default: $config->testingFramework,
        );
    }

    /**
     * Handles the askArchitecture operation.
     */
    private function askArchitecture(ProjectConfig $config): void
    {
        $config->hasTwig = $this->console->confirm(
            question: 'Twig templates',
            default: $config->hasTwig,
        );

        $config->hasDoctrine = $this->console->confirm(
            question: 'Doctrine ORM',
            default: $config->hasDoctrine,
        );

        $config->architectureStyle = $this->console->choice(
            question: 'Architecture style',
            choices: ['standard', 'ddd', 'hexagonal', 'layered'],
            default: $config->architectureStyle,
        );

        $config->hasApi = $this->console->confirm(
            question: 'Has REST / GraphQL API?',
            default: $config->hasApi,
        );

        if ($config->hasApi && $config->apiStyle !== 'api-platform') {
            $config->apiStyle = $this->console->choice(
                question: '  API style',
                choices: ['rest', 'graphql', 'api-platform'],
                default: 'rest',
            );
        }

        $config->commandRunner = $this->console->choice(
            question: 'Command runner for QA',
            choices: ['composer', 'make', 'both'],
            default: $config->commandRunner,
        );
    }

    /**
     * Handles the askOperational operation.
     */
    private function askOperational(ProjectConfig $config): void
    {
        $config->hasDocker = $this->console->confirm(
            question: 'Docker / Compose (document commands & paths in CLAUDE.md)',
            default: $config->hasDocker,
        );

        $config->hasCi = $this->console->confirm(
            question: 'GitHub Actions CI (document workflow expectations in CLAUDE.md)',
            default: $config->hasCi,
        );

        $config->includeObservabilityNotes = $this->console->confirm(
            question: 'Include observability notes (logs, metrics, tracing) in CLAUDE.md',
            default: $config->includeObservabilityNotes,
        );

        $config->includeMcpNotes = $this->console->confirm(
            question: 'Include MCP / external tools guidance in CLAUDE.md',
            default: $config->includeMcpNotes,
        );
    }

    /**
     * Handles the askGeneration operation.
     */
    private function askGeneration(ProjectConfig $config): void
    {
        $config->generateClaudeMd = $this->console->confirm(
            question: 'Generate CLAUDE.md (main project instructions)',
            default: true,
        );

        $config->generateCommands = $this->console->confirm(
            question: 'Generate .claude/commands/ (slash commands)',
            default: true,
        );

        if ($config->generateCommands) {
            $availableCommands        = $this->getAvailableCommands($config);
            $config->selectedCommands = $this->console->multiselect(
                question: '  Select commands to generate',
                choices: $availableCommands,
                defaults: array_keys($availableCommands),
            );
        }

        $config->generateAgents = $this->console->confirm(
            question: 'Generate .claude/agents/ (sub-agents)',
            default: false,
        );

        if ($config->generateAgents) {
            $availableAgents        = $this->getAvailableAgents($config);
            $config->selectedAgents = $this->console->multiselect(
                question: '  Select agents to generate',
                choices: $availableAgents,
                defaults: array_keys($availableAgents),
            );
        }

        $config->generateSkills = $this->console->confirm(
            question: 'Generate .claude/skills/ (SKILL.md per skill folder)',
            default: false,
        );

        if ($config->generateSkills) {
            $availableSkills        = $this->getAvailableSkills($config);
            $config->selectedSkills = $this->console->multiselect(
                question: '  Select skills to generate',
                choices: $availableSkills,
                defaults: array_keys($availableSkills),
            );
        }

        $config->generateExamples = $this->console->confirm(
            question: 'Generate examples/ folder with practical sample prompts/workflows',
            default: true,
        );

        $config->generateUsageManual = $this->console->confirm(
            question: 'Generate CLAUDE-USAGE.md manual (requirements, init, commands/agents/skills usage)',
            default: false,
        );

        if ($this->hasExistingFiles($config)) {
            $config->overwriteExisting = $this->console->confirm(
                question: 'Overwrite existing files?',
                default: false,
            );
        }
    }

    /** @return array<string, string> */
    private function getAvailableCommands(ProjectConfig $config): array
    {
        $commands = [
            'code-review' => 'code-review — Review code changes for issues',
            'qa-gate'     => 'qa-gate — Run full QA pipeline (composer/make qa)',
        ];

        if ($config->hasRector) {
            $commands['rector-dry'] = 'rector-dry — Preview Rector changes';
            $commands['rector-run'] = 'rector-run — Apply Rector refactoring';
        }

        if ($config->hasPhpStan) {
            $commands['phpstan'] = 'phpstan — Run PHPStan static analysis';
        }

        if ($config->hasPhpCsFixer) {
            $commands['cs-fix'] = 'cs-fix — Fix PHP code style';
        }

        if ($config->testingFramework !== 'none') {
            $commands['test-run']   = 'test-run — Run test suite';
            $commands['test-write'] = 'test-write — Write tests for a class or method';
        }

        if ($config->hasTwig) {
            $commands['twig-review'] = 'twig-review — Review Twig template';
        }

        if ($config->hasDoctrine) {
            $commands['make-entity']      = 'make-entity — Scaffold a Doctrine entity';
            $commands['make-repository']  = 'make-repository — Scaffold a repository with DQL';
            $commands['migration-review'] = 'migration-review — Review a Doctrine migration for safety';
        }

        if ($config->hasApi) {
            $commands['api-security-review'] = 'api-security-review — Hardening checklist for HTTP APIs';
        }

        if ($config->hasDocker) {
            $commands['docker-exec'] = 'docker-exec — Run work inside the correct container';
        }

        if ($config->framework === 'symfony') {
            $commands['make-service'] = 'make-service — Create a Symfony service with DI';
            $commands['make-command'] = 'make-command — Create a Symfony console command';
        }

        if ($config->isUpgrading) {
            $commands['symfony-upgrade'] = 'symfony-upgrade — Step-by-step Symfony upgrade guide';
        }

        if ($config->hasGrumPhp) {
            $commands['grumphp-check'] = 'grumphp-check — Run GrumPHP pre-commit checks';
        }

        return $commands;
    }

    /** @return array<string, string> */
    private function getAvailableAgents(ProjectConfig $config): array
    {
        $agents = [
            'php-architect'    => 'php-architect — Architecture & design decisions',
            'security-auditor' => 'security-auditor — Security review for PHP & HTTP',
            'performance-php'  => 'performance-php — Performance profiling & optimization',
        ];

        if ($config->testingFramework !== 'none') {
            $agents['test-writer'] = 'test-writer — Write comprehensive tests';
        }

        if ($config->framework === 'laravel') {
            $agents['laravel-expert'] = 'laravel-expert — Laravel conventions & ecosystem';
        }

        if ($config->hasRector) {
            $agents['refactor-agent'] = 'refactor-agent — Automated code refactoring';
        }

        if ($config->isUpgrading) {
            $agents['symfony-upgrader'] = 'symfony-upgrader — Guide Symfony upgrades step by step';
        }

        if ($config->hasDoctrine) {
            $agents['doctrine-expert'] = 'doctrine-expert — Doctrine ORM, DQL, migrations';
        }

        return $agents;
    }

    /** @return array<string, string> */
    private function getAvailableSkills(ProjectConfig $config): array
    {
        $skills = [
            'php-quality' => 'php-quality — Quality checks workflow',
        ];

        if ($config->testingFramework !== 'none') {
            $skills['php-testing'] = 'php-testing — PHPUnit/Pest workflow';
        }

        if ($config->hasRector) {
            $skills['rector-workflow'] = 'rector-workflow — Rector dry-run and apply';
        }

        if ($config->hasApi) {
            $skills['api-security'] = 'api-security — API hardening checklist';
        }

        if ($config->hasDoctrine) {
            $skills['doctrine-data'] = 'doctrine-data — Migrations & queries';
        }

        if ($config->hasDocker) {
            $skills['docker-dev'] = 'docker-dev — Docker development workflow';
        }

        if ($config->hasCi) {
            $skills['ci-pipeline'] = 'ci-pipeline — Align with GitHub Actions';
        }

        if ($config->includeObservabilityNotes) {
            $skills['observability'] = 'observability — Logs, metrics, tracing';
        }

        if ($config->includeMcpNotes) {
            $skills['mcp-tools'] = 'mcp-tools — MCP usage safely';
        }

        return $skills;
    }

    /**
     * Handles the hasExistingFiles operation.
     */
    private function hasExistingFiles(ProjectConfig $config): bool
    {
        $checks = [
            $config->projectDir . '/CLAUDE.md',
            $config->projectDir . '/.claude/commands',
            $config->projectDir . '/.claude/agents',
            $config->projectDir . '/.claude/skills',
            $config->projectDir . '/examples',
            $config->projectDir . '/CLAUDE-USAGE.md',
        ];

        foreach ($checks as $path) {
            if (file_exists($path)) {
                return true;
            }
        }

        return false;
    }
}
