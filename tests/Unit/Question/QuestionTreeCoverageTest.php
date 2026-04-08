<?php

declare(strict_types=1);

namespace NowoTech\ClaudePhpSetup\Tests\Unit\Question;

use NowoTech\ClaudePhpSetup\Cli\Console;
use NowoTech\ClaudePhpSetup\Detector\ProjectDetector;
use NowoTech\ClaudePhpSetup\Question\QuestionTree;
use NowoTech\ClaudePhpSetup\Tests\Support\MemoryStreamTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use const JSON_THROW_ON_ERROR;

/**
 * Exercises QuestionTree branches that need specific stdin sequences (Symfony/Laravel paths, agents, API style).
 */
/**
 * Represents the QuestionTreeCoverageTest class.
 */
final class QuestionTreeCoverageTest extends TestCase
{
    use MemoryStreamTrait;

    #[Test]
    /**
     * Handles the itRunsWithSymfonyUpgradeFromBranch operation.
     */
    public function itRunsWithSymfonyUpgradeFromBranch(): void
    {
        $tmp = sys_get_temp_dir() . '/claude-qt-sf-up-' . uniqid();
        mkdir($tmp, 0755, true);
        try {
            file_put_contents(
                $tmp . '/composer.json',
                json_encode([
                    'require' => [
                        'php'                      => '>=8.2',
                        'symfony/framework-bundle' => '^7.2',
                    ],
                ], JSON_THROW_ON_ERROR),
            );

            $payload = "\n\n\ny\n\n"
                . str_repeat("\n", 5)
                . "\n"
                . str_repeat("\n", 5)
                . str_repeat("\n", 4)
                . str_repeat("\n", 7);

            $stdin = $this->memoryStream('r+');
            fwrite($stdin, $payload);
            rewind($stdin);

            $console = new Console($stdin, $this->memoryStream('w'));
            $tree    = new QuestionTree($console);

            $detected = (new ProjectDetector())->detect($tmp);

            $config = $tree->run($detected);

            self::assertTrue($config->isUpgrading);
            self::assertNotNull($config->upgradeFromVersion);
        } finally {
            $this->removeTree($tmp);
        }
    }

    #[Test]
    /**
     * Handles the itRunsWithSymfonyComposerDefaults operation.
     */
    public function itRunsWithSymfonyComposerDefaults(): void
    {
        $tmp = sys_get_temp_dir() . '/claude-qt-sf-' . uniqid();
        mkdir($tmp, 0755, true);
        try {
            file_put_contents(
                $tmp . '/composer.json',
                json_encode([
                    'name'    => 'acme/sf',
                    'require' => [
                        'php'                      => '>=8.2',
                        'symfony/framework-bundle' => '^7.2',
                    ],
                ], JSON_THROW_ON_ERROR),
            );

            $stdin = $this->memoryStream('r+');
            fwrite($stdin, str_repeat("\n", 31));
            rewind($stdin);

            $console = new Console($stdin, $this->memoryStream('w'));
            $tree    = new QuestionTree($console);

            $detected = (new ProjectDetector())->detect($tmp);

            $config = $tree->run($detected);

            self::assertSame('symfony', $config->framework);
        } finally {
            $this->removeTree($tmp);
        }
    }

    #[Test]
    /**
     * Handles the itRunsWithLaravelComposerDefaults operation.
     */
    public function itRunsWithLaravelComposerDefaults(): void
    {
        $tmp = sys_get_temp_dir() . '/claude-qt-lv-' . uniqid();
        mkdir($tmp, 0755, true);
        try {
            file_put_contents(
                $tmp . '/composer.json',
                json_encode([
                    'require' => [
                        'php'               => '^8.3',
                        'laravel/framework' => '^11.0',
                    ],
                ], JSON_THROW_ON_ERROR),
            );

            $stdin = $this->memoryStream('r+');
            fwrite($stdin, str_repeat("\n", 29));
            rewind($stdin);

            $console = new Console($stdin, $this->memoryStream('w'));
            $tree    = new QuestionTree($console);

            $detected = (new ProjectDetector())->detect($tmp);

            $config = $tree->run($detected);

            self::assertSame('laravel', $config->framework);
        } finally {
            $this->removeTree($tmp);
        }
    }

    #[Test]
    /**
     * Handles the itRunsWithApiStyleChoiceWhenApiEnabled operation.
     */
    public function itRunsWithApiStyleChoiceWhenApiEnabled(): void
    {
        $tmp = sys_get_temp_dir() . '/claude-qt-api-' . uniqid();
        mkdir($tmp, 0755, true);
        try {
            file_put_contents(
                $tmp . '/composer.json',
                json_encode(['require' => ['php' => '>=8.1']], JSON_THROW_ON_ERROR),
            );

            $payload = str_repeat("\n", 10) . "y\n\n" . str_repeat("\n", 10);

            $stdin = $this->memoryStream('r+');
            fwrite($stdin, $payload);
            rewind($stdin);

            $console = new Console($stdin, $this->memoryStream('w'));
            $tree    = new QuestionTree($console);

            $detected = (new ProjectDetector())->detect($tmp);

            $config = $tree->run($detected);

            self::assertTrue($config->hasApi);
            self::assertNotSame('api-platform', $config->apiStyle);
        } finally {
            $this->removeTree($tmp);
        }
    }

    #[Test]
    #[DataProvider('agentsStdinProvider')]
    /**
     * Handles the itRunsWithAgentsSelection operation.
     */
    public function itRunsWithAgentsSelection(string $stdinPayload): void
    {
        $tmp = sys_get_temp_dir() . '/claude-qt-ag-' . uniqid();
        mkdir($tmp, 0755, true);
        try {
            file_put_contents(
                $tmp . '/composer.json',
                json_encode(['require' => ['php' => '>=8.1']], JSON_THROW_ON_ERROR),
            );

            $stdin = $this->memoryStream('r+');
            fwrite($stdin, $stdinPayload);
            rewind($stdin);

            $console = new Console($stdin, $this->memoryStream('w'));
            $tree    = new QuestionTree($console);

            $detected = (new ProjectDetector())->detect($tmp);

            $config = $tree->run($detected);

            self::assertTrue($config->generateAgents);
            self::assertNotSame([], $config->selectedAgents);
        } finally {
            $this->removeTree($tmp);
        }
    }

    /** @return array<string, array{string}> */
    public static function agentsStdinProvider(): array
    {
        // Defaults until generation, disable commands, enable agents, then skip skills/examples/manual.
        $payload = str_repeat("\n", 17) . "n\ny\n\nn\nn\n\n";

        return ['default' => [$payload]];
    }

    /**
     * Handles the removeTree operation.
     */
    private function removeTree(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->removeTree($path) : unlink($path);
        }
        rmdir($dir);
    }
}
