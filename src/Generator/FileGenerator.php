<?php

declare(strict_types=1);

namespace NowoTech\ClaudePhpSetup\Generator;

use NowoTech\ClaudePhpSetup\Cli\Console;
use NowoTech\ClaudePhpSetup\Question\ProjectConfig;
use NowoTech\ClaudePhpSetup\Template\Agents\AgentTemplates;
use NowoTech\ClaudePhpSetup\Template\Commands\CommandTemplates;
use NowoTech\ClaudePhpSetup\Template\Examples\ExampleTemplates;
use NowoTech\ClaudePhpSetup\Template\Skills\SkillTemplates;

use function dirname;
use function sprintf;
use function strlen;

use const PHP_EOL;
use const PHP_INT_MAX;

/**
 * Orchestrates file generation based on ProjectConfig.
 */
/**
 * Represents the FileGenerator class.
 */
final class FileGenerator
{
    private int $created     = 0;
    private int $skipped     = 0;
    private int $overwritten = 0;

    /**
     * Handles the __construct operation.
     */
    public function __construct(
        private readonly Console $console,
        private readonly ClaudeMdGenerator $claudeMdGenerator = new ClaudeMdGenerator(),
    ) {
    }

    /**
     * Handles the generate operation.
     */
    public function generate(ProjectConfig $config): void
    {
        $this->created     = 0;
        $this->skipped     = 0;
        $this->overwritten = 0;

        if ($config->generateClaudeMd) {
            $this->generateClaudeMd($config);
        }

        if ($config->generateCommands && $config->selectedCommands !== []) {
            $this->generateCommands($config);
        }

        if ($config->generateAgents && $config->selectedAgents !== []) {
            $this->generateAgents($config);
        }

        if ($config->generateSkills && $config->selectedSkills !== []) {
            $this->generateSkills($config);
        }

        if ($config->generateExamples) {
            $this->generateExamples($config);
        }

        $this->console->writeln();
        $this->console->writeln('  ─────────────────────────────────');
        $this->console->success(sprintf(
            'Done! %d created, %d overwritten, %d skipped.',
            $this->created,
            $this->overwritten,
            $this->skipped,
        ));
    }

    /**
     * Handles the generateClaudeMd operation.
     */
    private function generateClaudeMd(ProjectConfig $config): void
    {
        $path    = rtrim($config->projectDir, '/') . '/CLAUDE.md';
        $content = $this->claudeMdGenerator->generate($config);
        $this->writeFile($path, $content, $config->overwriteExisting);
    }

    /**
     * Handles the generateCommands operation.
     */
    private function generateCommands(ProjectConfig $config): void
    {
        $commandsDir = rtrim($config->projectDir, '/') . '/.claude/commands';
        $this->ensureDirectory($commandsDir);

        $allTemplates = CommandTemplates::all($config);

        foreach ($config->selectedCommands as $commandKey) {
            if (!isset($allTemplates[$commandKey]) || $allTemplates[$commandKey] === '') {
                continue;
            }

            $filename = $commandKey . '.md';
            $path     = $commandsDir . '/' . $filename;
            $this->writeFile($path, $allTemplates[$commandKey], $config->overwriteExisting);
        }
    }

    /**
     * Handles the generateAgents operation.
     */
    private function generateAgents(ProjectConfig $config): void
    {
        $agentsDir = rtrim($config->projectDir, '/') . '/.claude/agents';
        $this->ensureDirectory($agentsDir);

        $allTemplates = AgentTemplates::all($config);

        foreach ($config->selectedAgents as $agentKey) {
            if (!isset($allTemplates[$agentKey]) || $allTemplates[$agentKey] === '') {
                continue;
            }

            $filename = $agentKey . '.md';
            $path     = $agentsDir . '/' . $filename;
            $this->writeFile($path, $allTemplates[$agentKey], $config->overwriteExisting);
        }
    }

    /**
     * Handles the generateSkills operation.
     */
    private function generateSkills(ProjectConfig $config): void
    {
        $skillsRoot = rtrim($config->projectDir, '/') . '/.claude/skills';
        $this->ensureDirectory($skillsRoot);

        $allTemplates = SkillTemplates::all($config);

        foreach ($config->selectedSkills as $skillKey) {
            if (!isset($allTemplates[$skillKey]) || $allTemplates[$skillKey] === '') {
                continue;
            }

            $skillDir = $skillsRoot . '/' . $skillKey;
            $this->ensureDirectory($skillDir);
            $path = $skillDir . '/SKILL.md';
            $this->writeFile($path, $allTemplates[$skillKey], $config->overwriteExisting);
        }
    }

    /**
     * Handles the generateExamples operation.
     */
    private function generateExamples(ProjectConfig $config): void
    {
        $allTemplates = ExampleTemplates::all($config);

        foreach ($allTemplates as $relativePath => $content) {
            $path = rtrim($config->projectDir, '/') . '/' . ltrim($relativePath, '/');
            $this->ensureDirectory(dirname($path));
            $this->writeFile($path, $content, $config->overwriteExisting);
        }
    }

    /**
     * Handles the writeFile operation.
     */
    private function writeFile(string $path, string $content, bool $overwrite): void
    {
        $relativePath = $this->relativePath($path);

        if (file_exists($path)) {
            if (!$overwrite) {
                $this->console->warning("Skipped (exists): {$relativePath}");
                ++$this->skipped;

                return;
            }
            ++$this->overwritten;
            $this->console->info("Overwriting: {$relativePath}");
        } else {
            ++$this->created;
            $this->console->success("Created: {$relativePath}");
        }

        // Dedent heredoc indentation (remove leading whitespace from each line)
        $content = $this->dedent($content);

        file_put_contents($path, $content . PHP_EOL);
    }

    /**
     * Handles the ensureDirectory operation.
     */
    private function ensureDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    /**
     * Handles the relativePath operation.
     */
    private function relativePath(string $absolutePath): string
    {
        $cwd = getcwd();
        if ($cwd && str_starts_with($absolutePath, $cwd)) {
            return ltrim(substr($absolutePath, strlen($cwd)), '/');
        }

        return $absolutePath;
    }

    /**
     * Remove common leading whitespace from heredoc strings.
     * This allows indented heredocs in source code while outputting clean markdown.
     */
    /**
     * Handles the dedent operation.
     */
    private function dedent(string $content): string
    {
        $lines = explode("\n", $content);

        // Find minimum indentation (ignoring empty lines)
        $minIndent = PHP_INT_MAX;
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $indent    = strlen($line) - strlen(ltrim($line));
            $minIndent = min($minIndent, $indent);
        }

        if ($minIndent === PHP_INT_MAX || $minIndent === 0) {
            return $content;
        }

        return implode("\n", array_map(
            static fn (string $line): string => trim($line) === '' ? '' : substr($line, $minIndent),
            $lines,
        ));
    }
}
