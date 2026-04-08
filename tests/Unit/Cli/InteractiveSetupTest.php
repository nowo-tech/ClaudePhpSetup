<?php

declare(strict_types=1);

namespace NowoTech\ClaudePhpSetup\Tests\Unit\Cli;

use NowoTech\ClaudePhpSetup\Cli\Console;
use NowoTech\ClaudePhpSetup\Cli\InteractiveSetup;
use NowoTech\ClaudePhpSetup\Detector\ProjectDetector;
use NowoTech\ClaudePhpSetup\Generator\FileGenerator;
use NowoTech\ClaudePhpSetup\Question\QuestionTree;
use NowoTech\ClaudePhpSetup\Tests\Support\MemoryStreamTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function in_array;

use const JSON_THROW_ON_ERROR;

final class InteractiveSetupTest extends TestCase
{
    use MemoryStreamTrait;

    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/claude-php-setup-interactive-' . uniqid();
        mkdir($this->tmpDir, 0755, true);
        file_put_contents(
            $this->tmpDir . '/composer.json',
            json_encode(['require' => ['php' => '>=8.1']], JSON_THROW_ON_ERROR),
        );
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
    }

    #[Test]
    public function itRunsWizardAndGeneratesFiles(): void
    {
        $stdin = $this->memoryStream('r+');
        // 22 QuestionTree prompts + "Generate now?" (default yes)
        fwrite($stdin, str_repeat("\n", 23));
        rewind($stdin);

        $stdout  = $this->memoryStream('w+');
        $console = new Console($stdin, $stdout);

        $setup = new InteractiveSetup(
            $console,
            new ProjectDetector(),
            new QuestionTree($console),
            new FileGenerator($console),
        );

        $code = $setup->run($this->tmpDir, false);
        self::assertSame(0, $code);
        self::assertFileExists($this->tmpDir . '/CLAUDE.md');
    }

    #[Test]
    public function itAbortsWhenUserDeclinesGeneration(): void
    {
        $stdin = $this->memoryStream('r+');
        // 22 prompts in QuestionTree, then "n" declines the final "Generate now?" confirm
        fwrite($stdin, str_repeat("\n", 22));
        fwrite($stdin, "n\n");
        rewind($stdin);

        $stdout  = $this->memoryStream('w+');
        $console = new Console($stdin, $stdout);

        $setup = new InteractiveSetup(
            $console,
            new ProjectDetector(),
            new QuestionTree($console),
            new FileGenerator($console),
        );

        $code = $setup->run($this->tmpDir, false);
        self::assertSame(0, $code);
        self::assertFileDoesNotExist($this->tmpDir . '/CLAUDE.md');
    }

    #[Test]
    public function itSetsOverwriteWhenForceFlag(): void
    {
        file_put_contents($this->tmpDir . '/CLAUDE.md', 'old');

        $stdin = $this->memoryStream('r+');
        fwrite($stdin, str_repeat("\n", 23));
        rewind($stdin);

        $console = new Console($stdin, $this->memoryStream('w'));
        $setup   = new InteractiveSetup(
            $console,
            new ProjectDetector(),
            new QuestionTree($console),
            new FileGenerator($console),
        );

        $setup->run($this->tmpDir, true);
        $content = file_get_contents($this->tmpDir . '/CLAUDE.md') ?: '';
        self::assertStringNotContainsString('old', $content);
    }

    #[Test]
    public function itGeneratesAgentsWhenSelectedInWizard(): void
    {
        $stdin = $this->memoryStream('r+');
        fwrite($stdin, str_repeat("\n", 17) . "n\ny\n\nn\nn\n\n");
        rewind($stdin);

        $console = new Console($stdin, $this->memoryStream('w'));
        $setup   = new InteractiveSetup(
            $console,
            new ProjectDetector(),
            new QuestionTree($console),
            new FileGenerator($console),
        );

        $setup->run($this->tmpDir, false);

        self::assertFileExists($this->tmpDir . '/.claude/agents/php-architect.md');
    }

    #[Test]
    public function itShowsPreviewForCommandsAndAgentsWhenPathsExist(): void
    {
        mkdir($this->tmpDir . '/.claude/commands', 0755, true);
        mkdir($this->tmpDir . '/.claude/agents', 0755, true);
        touch($this->tmpDir . '/.claude/commands/x.md');
        touch($this->tmpDir . '/.claude/agents/y.md');

        $stdin = $this->memoryStream('r+');
        fwrite($stdin, str_repeat("\n", 23));
        rewind($stdin);

        $stdout  = $this->memoryStream('w+');
        $console = new Console($stdin, $stdout);

        $setup = new InteractiveSetup(
            $console,
            new ProjectDetector(),
            new QuestionTree($console),
            new FileGenerator($console),
        );

        $setup->run($this->tmpDir, false);

        rewind($stdout);
        $out = (string) stream_get_contents($stdout);
        self::assertStringContainsString('.claude/commands/', $out);
        self::assertStringContainsString('.claude/agents/', $out);
    }

    #[Test]
    public function itCreateFactoryBuildsInstance(): void
    {
        InteractiveSetup::create();
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function itLogsDetectedProjectNameWhenPresent(): void
    {
        file_put_contents(
            $this->tmpDir . '/composer.json',
            json_encode([
                'name'    => 'vendor/pkg',
                'require' => ['php' => '>=8.1'],
            ], JSON_THROW_ON_ERROR),
        );

        $stdin = $this->memoryStream('r+');
        fwrite($stdin, str_repeat("\n", 23));
        rewind($stdin);

        $stdout  = $this->memoryStream('w+');
        $console = new Console($stdin, $stdout);
        $setup   = new InteractiveSetup(
            $console,
            new ProjectDetector(),
            new QuestionTree($console),
            new FileGenerator($console),
        );

        $setup->run($this->tmpDir, false);
        rewind($stdout);
        $out = (string) stream_get_contents($stdout);
        self::assertStringContainsString('vendor/pkg', $out);
    }

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
