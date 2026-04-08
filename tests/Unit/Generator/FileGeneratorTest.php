<?php

declare(strict_types=1);

namespace NowoTech\ClaudePhpSetup\Tests\Unit\Generator;

use NowoTech\ClaudePhpSetup\Cli\Console;
use NowoTech\ClaudePhpSetup\Generator\FileGenerator;
use NowoTech\ClaudePhpSetup\Question\ProjectConfig;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

use function in_array;

use const STDIN;
use const STDOUT;

/**
 * Represents the FileGeneratorTest class.
 */
final class FileGeneratorTest extends TestCase
{
    private string $tmpDir;
    private FileGenerator $generator;
    private Console $console;

    /**
     * Handles the setUp operation.
     */
    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/claude-php-setup-gen-test-' . uniqid();
        mkdir($this->tmpDir, 0755, true);

        // Console with no output (discard stdout)
        $this->console   = new Console(STDIN, fopen('/dev/null', 'w') ?: STDOUT);
        $this->generator = new FileGenerator($this->console);
    }

    /**
     * Handles the tearDown operation.
     */
    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
    }

    #[Test]
    /**
     * Handles the itGeneratesClaudeMd operation.
     */
    public function itGeneratesClaudeMd(): void
    {
        $config                   = $this->makeConfig();
        $config->generateClaudeMd = true;
        $config->generateCommands = false;
        $config->generateAgents   = false;

        $this->generator->generate($config);

        self::assertFileExists($this->tmpDir . '/CLAUDE.md');
        $content = file_get_contents($this->tmpDir . '/CLAUDE.md');
        self::assertStringContainsString('Test Project', $content ?: '');
    }

    #[Test]
    /**
     * Handles the itGeneratesCommandFiles operation.
     */
    public function itGeneratesCommandFiles(): void
    {
        $config                   = $this->makeConfig();
        $config->generateClaudeMd = false;
        $config->generateCommands = true;
        $config->selectedCommands = ['code-review', 'rector-dry'];
        $config->hasRector        = true;
        $config->generateAgents   = false;

        $this->generator->generate($config);

        self::assertFileExists($this->tmpDir . '/.claude/commands/code-review.md');
        self::assertFileExists($this->tmpDir . '/.claude/commands/rector-dry.md');
    }

    #[Test]
    /**
     * Handles the itGeneratesAgentFiles operation.
     */
    public function itGeneratesAgentFiles(): void
    {
        $config                   = $this->makeConfig();
        $config->generateClaudeMd = false;
        $config->generateCommands = false;
        $config->generateAgents   = true;
        $config->selectedAgents   = ['php-architect'];

        $this->generator->generate($config);

        self::assertFileExists($this->tmpDir . '/.claude/agents/php-architect.md');
        $content = file_get_contents($this->tmpDir . '/.claude/agents/php-architect.md');
        self::assertStringContainsString('php-architect', $content ?: '');
    }

    #[Test]
    /**
     * Handles the itGeneratesSkillFiles operation.
     */
    public function itGeneratesSkillFiles(): void
    {
        $config                   = $this->makeConfig();
        $config->generateClaudeMd = false;
        $config->generateCommands = false;
        $config->generateAgents   = false;
        $config->generateSkills   = true;
        $config->selectedSkills   = ['php-quality'];

        $this->generator->generate($config);

        self::assertFileExists($this->tmpDir . '/.claude/skills/php-quality/SKILL.md');
        $content = file_get_contents($this->tmpDir . '/.claude/skills/php-quality/SKILL.md');
        self::assertStringContainsString('php-quality', $content ?: '');
    }

    #[Test]
    /**
     * Handles the itGeneratesExamplesFolder operation.
     */
    public function itGeneratesExamplesFolder(): void
    {
        $config                   = $this->makeConfig();
        $config->generateClaudeMd = false;
        $config->generateCommands = false;
        $config->generateAgents   = false;
        $config->generateSkills   = false;
        $config->generateExamples = true;

        $this->generator->generate($config);

        self::assertFileExists($this->tmpDir . '/examples/README.md');
        self::assertFileExists($this->tmpDir . '/examples/workflows/feature.md');
        self::assertFileExists($this->tmpDir . '/examples/prompts/review-pr.md');
    }

    #[Test]
    /**
     * Handles the itGeneratesUsageManual operation.
     */
    public function itGeneratesUsageManual(): void
    {
        $config                      = $this->makeConfig();
        $config->generateClaudeMd    = false;
        $config->generateCommands    = true;
        $config->selectedCommands    = ['code-review'];
        $config->generateAgents      = true;
        $config->selectedAgents      = ['php-architect'];
        $config->generateSkills      = true;
        $config->selectedSkills      = ['php-quality'];
        $config->generateExamples    = false;
        $config->generateUsageManual = true;

        $this->generator->generate($config);

        self::assertFileExists($this->tmpDir . '/CLAUDE-USAGE.md');
        $content = file_get_contents($this->tmpDir . '/CLAUDE-USAGE.md') ?: '';
        self::assertStringContainsString('/code-review', $content);
        self::assertStringContainsString('@php-architect', $content);
        self::assertStringContainsString('.claude/skills/php-quality/SKILL.md', $content);
    }

    #[Test]
    /**
     * Handles the itGeneratesUsageManualWithNoSelections operation.
     */
    public function itGeneratesUsageManualWithNoSelections(): void
    {
        $config                      = $this->makeConfig();
        $config->generateClaudeMd    = false;
        $config->generateCommands    = false;
        $config->generateAgents      = false;
        $config->generateSkills      = false;
        $config->generateExamples    = false;
        $config->generateUsageManual = true;

        $this->generator->generate($config);

        $content = file_get_contents($this->tmpDir . '/CLAUDE-USAGE.md') ?: '';
        self::assertStringContainsString('No generated slash commands selected.', $content);
        self::assertStringContainsString('No generated agents selected.', $content);
        self::assertStringContainsString('No generated skills selected.', $content);
    }

    #[Test]
    /**
     * Handles the itRunsExamplesGenerationWhenDirectoriesAlreadyExist operation.
     */
    public function itRunsExamplesGenerationWhenDirectoriesAlreadyExist(): void
    {
        $config                    = $this->makeConfig();
        $config->generateClaudeMd  = false;
        $config->generateCommands  = false;
        $config->generateAgents    = false;
        $config->generateSkills    = false;
        $config->generateExamples  = true;
        $config->overwriteExisting = false;

        $this->generator->generate($config);
        $this->generator->generate($config);

        self::assertFileExists($this->tmpDir . '/examples/README.md');
    }

    #[Test]
    /**
     * Handles the itUsesAbsolutePathsInLogWhenProjectDirNotUnderCwd operation.
     */
    public function itUsesAbsolutePathsInLogWhenProjectDirNotUnderCwd(): void
    {
        $cwd = getcwd();
        if ($cwd === false) {
            self::markTestSkipped('No cwd');
        }

        mkdir($this->tmpDir . '/nested', 0755, true);
        chdir($this->tmpDir . '/nested');

        try {
            $config                   = $this->makeConfig();
            $config->generateClaudeMd = true;
            $config->generateCommands = false;
            $config->generateAgents   = false;

            $this->generator->generate($config);
            self::assertFileExists($this->tmpDir . '/CLAUDE.md');
        } finally {
            chdir($cwd);
        }
    }

    #[Test]
    /**
     * Handles the itSkipsExistingFilesWhenOverwriteIsFalse operation.
     */
    public function itSkipsExistingFilesWhenOverwriteIsFalse(): void
    {
        $claudeMdPath = $this->tmpDir . '/CLAUDE.md';
        file_put_contents($claudeMdPath, 'ORIGINAL CONTENT');

        $config                    = $this->makeConfig();
        $config->generateClaudeMd  = true;
        $config->generateCommands  = false;
        $config->generateAgents    = false;
        $config->overwriteExisting = false;

        $this->generator->generate($config);

        self::assertSame('ORIGINAL CONTENT', file_get_contents($claudeMdPath));
    }

    #[Test]
    /**
     * Handles the itOverwritesExistingFilesWhenOverwriteIsTrue operation.
     */
    public function itOverwritesExistingFilesWhenOverwriteIsTrue(): void
    {
        $claudeMdPath = $this->tmpDir . '/CLAUDE.md';
        file_put_contents($claudeMdPath, 'ORIGINAL CONTENT');

        $config                    = $this->makeConfig();
        $config->generateClaudeMd  = true;
        $config->generateCommands  = false;
        $config->generateAgents    = false;
        $config->overwriteExisting = true;

        $this->generator->generate($config);

        $content = file_get_contents($claudeMdPath);
        self::assertStringNotContainsString('ORIGINAL CONTENT', $content ?: '');
        self::assertStringContainsString('Test Project', $content ?: '');
    }

    #[Test]
    /**
     * Handles the itCreatesCommandsDirectoryIfMissing operation.
     */
    public function itCreatesCommandsDirectoryIfMissing(): void
    {
        $config                   = $this->makeConfig();
        $config->generateClaudeMd = false;
        $config->generateCommands = true;
        $config->selectedCommands = ['code-review'];
        $config->generateAgents   = false;

        self::assertDirectoryDoesNotExist($this->tmpDir . '/.claude/commands');

        $this->generator->generate($config);

        self::assertDirectoryExists($this->tmpDir . '/.claude/commands');
    }

    #[Test]
    /**
     * Handles the itSkipsAgentWhenTemplateIsEmpty operation.
     */
    public function itSkipsAgentWhenTemplateIsEmpty(): void
    {
        $config                   = $this->makeConfig();
        $config->generateClaudeMd = false;
        $config->generateCommands = false;
        $config->generateAgents   = true;
        $config->selectedAgents   = ['symfony-upgrader'];
        $config->isUpgrading      = false;

        $this->generator->generate($config);

        self::assertSame([], glob($this->tmpDir . '/.claude/agents/*.md') ?: []);
    }

    #[Test]
    /**
     * Handles the itSkipsSkillWhenTemplateIsEmpty operation.
     */
    public function itSkipsSkillWhenTemplateIsEmpty(): void
    {
        $config                   = $this->makeConfig();
        $config->generateClaudeMd = false;
        $config->generateCommands = false;
        $config->generateAgents   = false;
        $config->generateSkills   = true;
        $config->selectedSkills   = ['php-testing'];
        $config->testingFramework = 'none';

        $this->generator->generate($config);

        self::assertDirectoryExists($this->tmpDir . '/.claude/skills');
        self::assertSame([], glob($this->tmpDir . '/.claude/skills/*/SKILL.md') ?: []);
    }

    #[Test]
    /**
     * Handles the itDedentNormalisesIndentedBlocks operation.
     */
    public function itDedentNormalisesIndentedBlocks(): void
    {
        $console   = new Console(STDIN, fopen('/dev/null', 'w') ?: STDOUT);
        $generator = new FileGenerator($console);
        $dedent    = new ReflectionMethod(FileGenerator::class, 'dedent');

        self::assertSame(
            "a\nb",
            $dedent->invoke($generator, "    a\n    b"),
        );
        self::assertSame(
            "\n\n\n",
            $dedent->invoke($generator, "\n\n\n"),
        );
        self::assertSame(
            "a\nb",
            $dedent->invoke($generator, "a\nb"),
        );
    }

    #[Test]
    /**
     * Handles the itSkipsUnknownCommandKeys operation.
     */
    public function itSkipsUnknownCommandKeys(): void
    {
        $config                   = $this->makeConfig();
        $config->generateClaudeMd = false;
        $config->generateCommands = true;
        $config->selectedCommands = ['does-not-exist'];
        $config->generateAgents   = false;

        $this->generator->generate($config);

        self::assertDirectoryExists($this->tmpDir . '/.claude/commands');
        self::assertSame([], glob($this->tmpDir . '/.claude/commands/*.md') ?: []);
    }

    #[Test]
    /**
     * Handles the itUsesRelativePathsWhenUnderCurrentWorkingDirectory operation.
     */
    public function itUsesRelativePathsWhenUnderCurrentWorkingDirectory(): void
    {
        $cwd = getcwd();
        if ($cwd === false) {
            self::markTestSkipped('No cwd');
        }

        chdir($this->tmpDir);

        try {
            $config                   = $this->makeConfig();
            $config->generateClaudeMd = true;
            $config->generateCommands = false;
            $config->generateAgents   = false;

            $this->generator->generate($config);

            self::assertFileExists($this->tmpDir . '/CLAUDE.md');
        } finally {
            chdir($cwd);
        }
    }

    #[Test]
    /**
     * Handles the itDedentsHeredocContentInGeneratedFiles operation.
     */
    public function itDedentsHeredocContentInGeneratedFiles(): void
    {
        $config                   = $this->makeConfig();
        $config->generateClaudeMd = true;
        $config->generateCommands = false;
        $config->generateAgents   = false;

        $this->generator->generate($config);

        $content = file_get_contents($this->tmpDir . '/CLAUDE.md') ?: '';
        // No line should start with 8 spaces (heredoc indentation from source)
        foreach (explode("\n", $content) as $line) {
            self::assertFalse(str_starts_with($line, '        '));
        }
    }

    /**
     * Handles the makeConfig operation.
     */
    private function makeConfig(): ProjectConfig
    {
        $config                   = new ProjectConfig();
        $config->projectDir       = $this->tmpDir;
        $config->projectName      = 'Test Project';
        $config->phpVersion       = '8.3';
        $config->framework        = 'none';
        $config->testingFramework = 'phpunit';

        return $config;
    }

    /**
     * Handles the removeDir operation.
     */
    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $item) {
            if (in_array($item, ['.', '..'], true)) {
                continue;
            }
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->removeDir($path) : unlink($path);
        }
        rmdir($dir);
    }
}
