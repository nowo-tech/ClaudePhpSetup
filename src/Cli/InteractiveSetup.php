<?php

declare(strict_types=1);

namespace NowoTech\ClaudePhpSetup\Cli;

use NowoTech\ClaudePhpSetup\Detector\ProjectDetector;
use NowoTech\ClaudePhpSetup\Generator\FileGenerator;
use NowoTech\ClaudePhpSetup\Question\ProjectConfig;
use NowoTech\ClaudePhpSetup\Question\QuestionTree;

/**
 * Entry point for the interactive CLI setup wizard.
 */
/**
 * Represents the InteractiveSetup class.
 */
final class InteractiveSetup
{
    /**
     * Handles the __construct operation.
     */
    public function __construct(
        private readonly Console $console,
        private readonly ProjectDetector $detector,
        private readonly QuestionTree $questionTree,
        private readonly FileGenerator $generator,
    ) {
    }

    /**
     * Handles the create operation.
     */
    public static function create(): self
    {
        $console = new Console();

        return new self(
            console: $console,
            detector: new ProjectDetector(),
            questionTree: new QuestionTree($console),
            generator: new FileGenerator($console),
        );
    }

    /**
     * Handles the run operation.
     */
    public function run(string $projectDir, bool $forceOverwrite = false): int
    {
        $this->printBanner();

        // Auto-detect project configuration
        $this->console->info('Scanning project...');
        $detected = $this->detector->detect($projectDir);

        if ($detected->projectName !== null) {
            $this->console->info('Project detected: ' . $detected->projectName);
        }

        $this->console->writeln();
        $this->console->writeln('  Answer the following questions to customise the generated files.');
        $this->console->writeln('  Press Enter to accept the detected/default value.');

        // Run interactive question tree
        $config = $this->questionTree->run($detected);

        if ($forceOverwrite) {
            $config->overwriteExisting = true;
        }

        // Preview what will be generated
        $this->console->section('Summary');
        $this->previewFiles($config);

        $this->console->writeln();
        $confirmed = $this->console->confirm('Generate these files now?', default: true);

        if (!$confirmed) {
            $this->console->warning('Aborted. No files were created.');

            return 0;
        }

        $this->console->section('Generating Files');

        // Generate files
        $this->generator->generate($config);

        $this->printNextSteps($config);

        return 0;
    }

    /**
     * Handles the printBanner operation.
     */
    private function printBanner(): void
    {
        $this->console->writeln();
        $this->console->writeln('  ╔═══════════════════════════════════════════════════════╗');
        $this->console->writeln('  ║          Claude PHP Setup  ·  nowo-tech               ║');
        $this->console->writeln('  ║  Generate Claude Code markdown for your PHP project   ║');
        $this->console->writeln('  ╚═══════════════════════════════════════════════════════╝');
        $this->console->writeln();
    }

    /**
     * Handles the previewFiles operation.
     */
    private function previewFiles(ProjectConfig $config): void
    {
        $this->console->writeln('  Files to generate:');
        $this->console->writeln();

        if ($config->generateClaudeMd) {
            $exists = file_exists($config->projectDir . '/CLAUDE.md') ? ' (exists)' : '';
            $this->console->writeln("    CLAUDE.md{$exists}");
        }

        if ($config->generateCommands && $config->selectedCommands !== []) {
            foreach ($config->selectedCommands as $cmd) {
                $path   = $config->projectDir . '/.claude/commands/' . $cmd . '.md';
                $exists = file_exists($path) ? ' (exists)' : '';
                $this->console->writeln("    .claude/commands/{$cmd}.md{$exists}");
            }
        }

        if ($config->generateAgents && $config->selectedAgents !== []) {
            foreach ($config->selectedAgents as $agent) {
                $path   = $config->projectDir . '/.claude/agents/' . $agent . '.md';
                $exists = file_exists($path) ? ' (exists)' : '';
                $this->console->writeln("    .claude/agents/{$agent}.md{$exists}");
            }
        }

        if ($config->generateSkills && $config->selectedSkills !== []) {
            foreach ($config->selectedSkills as $skill) {
                $path   = $config->projectDir . '/.claude/skills/' . $skill . '/SKILL.md';
                $exists = file_exists($path) ? ' (exists)' : '';
                $this->console->writeln("    .claude/skills/{$skill}/SKILL.md{$exists}");
            }
        }

        if ($config->generateExamples) {
            $exists = file_exists($config->projectDir . '/examples') ? ' (exists)' : '';
            $this->console->writeln("    examples/{$exists}");
        }

        if (!$config->overwriteExisting) {
            $this->console->writeln();
            $this->console->info('Existing files will be skipped. Use --overwrite to replace them.');
        }
    }

    /**
     * Handles the printNextSteps operation.
     */
    private function printNextSteps(ProjectConfig $config): void
    {
        $this->console->writeln();
        $this->console->writeln('  ╔═══════════════════════════════════════════════════════╗');
        $this->console->writeln('  ║  Next steps                                           ║');
        $this->console->writeln('  ╚═══════════════════════════════════════════════════════╝');
        $this->console->writeln();

        if ($config->generateClaudeMd) {
            $this->console->writeln('  1. Review CLAUDE.md and customise to your project specifics');
        }

        if ($config->generateCommands) {
            $this->console->writeln('  2. Use slash commands in Claude Code: /rector-dry, /phpstan, etc.');
        }

        if ($config->generateAgents) {
            $this->console->writeln('  3. Use sub-agents in Claude Code: @php-architect, @test-writer, etc.');
        }

        if ($config->generateSkills) {
            $this->console->writeln('  4. Skills live under .claude/skills/<name>/SKILL.md — enable them in your Claude Code / tooling config as needed.');
        }

        if ($config->generateExamples) {
            $this->console->writeln('  5. Start from examples/ to bootstrap prompts and repeatable workflows.');
        }

        $this->console->writeln();
        $this->console->writeln('  To re-run and overwrite existing files:');
        $this->console->writeln('    vendor/bin/claude-php-setup --overwrite');
        $this->console->writeln();
        $this->console->writeln('  Documentation: https://github.com/nowo-tech/ClaudePhpSetup');
        $this->console->writeln();
    }
}
