<?php

declare(strict_types=1);

namespace NowoTech\ClaudePhpSetup\Question;

/**
 * Represents the ProjectConfig class.
 */
final class ProjectConfig
{
    // PHP
    public string $phpVersion = '8.3';

    // Framework
    public string $framework         = 'none'; // none | symfony | laravel | slim | yii
    public ?string $frameworkVersion = null;

    // Symfony upgrade
    public bool $isUpgrading           = false;
    public ?string $upgradeFromVersion = null;

    // Quality tools
    public bool $hasRector       = false;
    public string $rectorVersion = '2'; // '1' | '2'
    public bool $hasPhpStan      = false;
    public string $phpStanLevel  = '8'; // '0'..'9' | 'max'
    public bool $hasPhpCsFixer   = false;
    public bool $hasGrumPhp      = false;
    public bool $hasTwigCsFixer  = false;

    // Testing
    public string $testingFramework = 'phpunit'; // none | phpunit | pest | both
    public bool $hasDataProviders   = true;

    // Templates
    public bool $hasTwig = false;

    // Doctrine
    public bool $hasDoctrine = false;

    // Architecture
    public string $architectureStyle = 'standard'; // standard | ddd | hexagonal
    public bool $hasApi              = false;
    public string $apiStyle          = 'none'; // none | rest | graphql | api-platform

    // Command runner
    public string $commandRunner = 'composer'; // composer | make | both

    // Generation options
    public bool $generateClaudeMd    = true;
    public bool $generateCommands    = true;
    public bool $generateAgents      = false;
    public bool $generateSkills      = false;
    public bool $generateExamples    = true;
    public bool $generateUsageManual = false;

    // Commands to generate
    /** @var string[] */
    public array $selectedCommands = [];

    // Agents to generate
    /** @var string[] */
    public array $selectedAgents = [];

    // Skills to generate (each becomes `.claude/skills/<name>/SKILL.md`)
    /** @var string[] */
    public array $selectedSkills = [];

    // Operational / delivery (enrich CLAUDE.md)
    public bool $hasDocker = false;
    public bool $hasCi     = false;

    /** Observability: logs, metrics, tracing guidance in CLAUDE.md */
    public bool $includeObservabilityNotes = false;

    /** MCP servers / tool usage guidance in CLAUDE.md */
    public bool $includeMcpNotes = false;

    // Project info
    public string $projectDir          = '.';
    public ?string $projectName        = null;
    public ?string $projectDescription = null;

    // Output control
    public bool $overwriteExisting = false;
}
