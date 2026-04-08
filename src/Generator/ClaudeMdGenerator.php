<?php

declare(strict_types=1);

namespace NowoTech\ClaudePhpSetup\Generator;

use NowoTech\ClaudePhpSetup\Question\ProjectConfig;
use NowoTech\ClaudePhpSetup\Template\ClaudeMd\BaseSection;
use NowoTech\ClaudePhpSetup\Template\ClaudeMd\DoctrineSection;
use NowoTech\ClaudePhpSetup\Template\ClaudeMd\FrameworkSection;
use NowoTech\ClaudePhpSetup\Template\ClaudeMd\OperationalSection;
use NowoTech\ClaudePhpSetup\Template\ClaudeMd\QualityToolsSection;
use NowoTech\ClaudePhpSetup\Template\ClaudeMd\TestingSection;
use NowoTech\ClaudePhpSetup\Template\ClaudeMd\TwigSection;
use NowoTech\ClaudePhpSetup\Template\ClaudeMd\UpgradeSection;

/**
 * Represents the ClaudeMdGenerator class.
 */
final class ClaudeMdGenerator
{
    /**
     * Handles the generate operation.
     */
    public function generate(ProjectConfig $config): string
    {
        $sections = [];

        // Header
        $sections[] = BaseSection::header($config);

        // Stack overview
        $sections[] = BaseSection::stack($config);

        // What lives under .claude/ (when generating those assets)
        $generatedAssets = BaseSection::generatedClaudeResources($config);
        if ($generatedAssets !== '') {
            $sections[] = $generatedAssets;
        }

        // Key commands table
        $commands = BaseSection::commands($config);
        if ($commands !== '') {
            $sections[] = $commands;
        }

        // Framework-specific best practices
        $frameworkSection = FrameworkSection::render($config);
        if ($frameworkSection !== '') {
            $sections[] = $frameworkSection;
        }

        // Quality tools
        $qualitySection = QualityToolsSection::render($config);
        if ($qualitySection !== '') {
            $sections[] = $qualitySection;
        }

        // Testing
        $testingSection = TestingSection::render($config);
        if ($testingSection !== '') {
            $sections[] = $testingSection;
        }

        // Doctrine
        $doctrineSection = DoctrineSection::render($config);
        if ($doctrineSection !== '') {
            $sections[] = $doctrineSection;
        }

        // Twig
        $twigSection = TwigSection::render($config);
        if ($twigSection !== '') {
            $sections[] = $twigSection;
        }

        // Docker, CI, API security, observability, MCP (optional)
        $operationalSection = OperationalSection::render($config);
        if ($operationalSection !== '') {
            $sections[] = $operationalSection;
        }

        // PHP general best practices
        $sections[] = BaseSection::phpBestPractices();

        // Code review guidelines
        $sections[] = BaseSection::codeReviewGuidelines($config);

        // Symfony upgrade guide (if upgrading)
        $upgradeSection = UpgradeSection::render($config);
        if ($upgradeSection !== '') {
            $sections[] = $upgradeSection;
        }

        return implode("\n\n", array_filter($sections, static fn (string $s): bool => trim($s) !== ''));
    }
}
