<?php

declare(strict_types=1);

namespace NowoTech\ClaudePhpSetup\Cli;

use function in_array;
use function sprintf;

use const PHP_EOL;
use const STDIN;
use const STDOUT;

/**
 * Simple CLI console for interactive Q&A.
 * Works without any external dependency — just stdin/stdout.
 */
final class Console
{
    private readonly bool $isInteractive;

    public function __construct(
        /** @var resource */
        private $input = STDIN,
        /** @var resource */
        private $output = STDOUT,
    ) {
        $this->isInteractive = stream_isatty($this->output);
    }

    public function writeln(string $text = ''): void
    {
        fwrite($this->output, $text . PHP_EOL);
    }

    public function write(string $text): void
    {
        fwrite($this->output, $text);
    }

    public function section(string $title): void
    {
        $this->writeln();
        $this->writeln($this->cyan('  ── ' . $title . ' ──'));
        $this->writeln();
    }

    public function success(string $message): void
    {
        $this->writeln($this->green('  ✓ ' . $message));
    }

    public function info(string $message): void
    {
        $this->writeln($this->cyan('  ℹ ' . $message));
    }

    public function warning(string $message): void
    {
        $this->writeln($this->yellow('  ⚠ ' . $message));
    }

    public function error(string $message): void
    {
        $this->writeln($this->red('  ✗ ' . $message));
    }

    /**
     * Single-choice question with numbered options.
     *
     * @param string[] $choices
     */
    public function choice(string $question, array $choices, string $default = ''): string
    {
        $choices        = array_values($choices);
        $defaultIndex   = array_search($default, $choices, true);
        $defaultDisplay = $defaultIndex !== false ? (string) ($defaultIndex + 1) : '1';

        $this->writeln($this->bold('  ? ' . $question . ':'));
        foreach ($choices as $index => $choice) {
            $marker = ($choice === $default) ? $this->green('▸') : ' ';
            $this->writeln(sprintf('    %s [%d] %s', $marker, $index + 1, $choice));
        }
        $this->write(sprintf('  Enter number [%s]: ', $this->yellow($defaultDisplay)));

        $answer  = $this->readLine();
        $trimmed = trim($answer);

        if ($trimmed === '') {
            return $default !== '' ? $default : $choices[0];
        }

        if (is_numeric($trimmed)) {
            $index = (int) $trimmed - 1;
            if (isset($choices[$index])) {
                return $choices[$index];
            }
        }

        // Allow typing the value directly
        if (in_array($trimmed, $choices, true)) {
            return $trimmed;
        }

        return $default !== '' ? $default : $choices[0];
    }

    public function confirm(string $question, bool $default = true): bool
    {
        $hint = $default ? $this->green('Y') . '/n' : 'y/' . $this->green('N');
        $this->write($this->bold('  ? ' . $question . ':') . ' [' . $hint . '] ');

        $answer = strtolower(trim($this->readLine()));

        if ($answer === '') {
            return $default;
        }

        return in_array($answer, ['y', 'yes', '1', 'true'], true);
    }

    /**
     * Multi-select question.
     *
     * @param array<string, string> $choices key => display label
     * @param string[] $defaults Default selected keys
     *
     * @return string[] Selected keys
     */
    public function multiselect(string $question, array $choices, array $defaults = []): array
    {
        $keys   = array_keys($choices);
        $labels = array_values($choices);

        $this->writeln($this->bold('  ? ' . $question . ':'));
        $this->writeln($this->dim('    (comma-separated numbers, e.g. 1,3,5 — or "all" — default selects all)'));

        foreach ($keys as $index => $key) {
            $isDefault = in_array($key, $defaults, true);
            $marker    = $isDefault ? $this->green('▸') : ' ';
            $check     = $isDefault ? $this->green('✓') : '○';
            $this->writeln(sprintf('    %s [%d] %s %s', $marker, $index + 1, $check, $labels[$index]));
        }

        $defaultNums = array_map(
            static fn (string $k): string => (string) (array_search($k, $keys, true) + 1),
            $defaults,
        );
        $defaultDisplay = $defaultNums === [] ? 'all' : implode(',', $defaultNums);

        $this->write(sprintf('  Enter numbers [%s]: ', $this->yellow($defaultDisplay)));
        $answer = trim($this->readLine());

        if ($answer === '' || strtolower($answer) === 'all') {
            return $defaults ?: $keys;
        }

        $selected = [];
        foreach (explode(',', $answer) as $part) {
            $num = (int) trim($part) - 1;
            if (isset($keys[$num])) {
                $selected[] = $keys[$num];
            }
        }

        return $selected ?: $defaults;
    }

    public function ask(string $question, string $default = ''): string
    {
        $hint = $default !== '' ? ' [' . $this->yellow($default) . ']' : '';
        $this->write($this->bold('  ? ' . $question . ':') . $hint . ' ');
        $answer = trim($this->readLine());

        return $answer === '' ? $default : $answer;
    }

    private function readLine(): string
    {
        $line = fgets($this->input);

        return $line === false ? '' : rtrim($line, "\r\n");
    }

    private function bold(string $text): string
    {
        return $this->isInteractive ? "\033[1m{$text}\033[0m" : $text;
    }

    private function dim(string $text): string
    {
        return $this->isInteractive ? "\033[2m{$text}\033[0m" : $text;
    }

    private function green(string $text): string
    {
        return $this->isInteractive ? "\033[32m{$text}\033[0m" : $text;
    }

    private function cyan(string $text): string
    {
        return $this->isInteractive ? "\033[36m{$text}\033[0m" : $text;
    }

    private function yellow(string $text): string
    {
        return $this->isInteractive ? "\033[33m{$text}\033[0m" : $text;
    }

    private function red(string $text): string
    {
        return $this->isInteractive ? "\033[31m{$text}\033[0m" : $text;
    }
}
