<?php

declare(strict_types=1);

namespace NowoTech\ClaudePhpSetup\Cli;

use function count;
use function in_array;
use function is_string;
use function sprintf;

use const PHP_EOL;
use const STDIN;
use const STDOUT;

/**
 * Simple CLI console for interactive Q&A.
 * Works without any external dependency — just stdin/stdout.
 */
/**
 * Represents the Console class.
 */
final class Console
{
    private readonly bool $isInteractive;

    /**
     * Handles the __construct operation.
     */
    public function __construct(
        /** @var resource */
        private $input = STDIN,
        /** @var resource */
        private $output = STDOUT,
    ) {
        $this->isInteractive = stream_isatty($this->output);
    }

    /**
     * Handles the writeln operation.
     */
    public function writeln(string $text = ''): void
    {
        fwrite($this->output, $text . PHP_EOL);
    }

    /**
     * Handles the write operation.
     */
    public function write(string $text): void
    {
        fwrite($this->output, $text);
    }

    /**
     * Handles the section operation.
     */
    public function section(string $title): void
    {
        $this->writeln();
        $this->writeln($this->cyan('  ── ' . $title . ' ──'));
        $this->writeln();
    }

    /**
     * Handles the success operation.
     */
    public function success(string $message): void
    {
        $this->writeln($this->green('  ✓ ' . $message));
    }

    /**
     * Handles the info operation.
     */
    public function info(string $message): void
    {
        $this->writeln($this->cyan('  ℹ ' . $message));
    }

    /**
     * Handles the warning operation.
     */
    public function warning(string $message): void
    {
        $this->writeln($this->yellow('  ⚠ ' . $message));
    }

    /**
     * Handles the error operation.
     */
    public function error(string $message): void
    {
        $this->writeln($this->red('  ✗ ' . $message));
    }

    /**
     * Handles single-choice questions.
     *
     * @param string[] $choices
     */
    public function choice(string $question, array $choices, string $default = ''): string
    {
        $choices        = array_values($choices);
        $defaultIndex   = array_search($default, $choices, true);
        $defaultDisplay = $defaultIndex !== false ? (string) ($defaultIndex + 1) : '1';

        // @codeCoverageIgnoreStart
        if ($this->isInteractive && stream_isatty($this->input)) {
            return $this->choiceInteractive($question, $choices, $default, $defaultIndex !== false ? $defaultIndex : 0);
        }
        // @codeCoverageIgnoreEnd

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

    /**
     * Handles interactive single-choice questions with arrow key support.
     *
     * @param string[] $choices
     *
     * @codeCoverageIgnore
     */
    private function choiceInteractive(string $question, array $choices, string $default, int $selectedIndex): string
    {
        $selectedIndex = max(0, min($selectedIndex, count($choices) - 1));
        $typedNumber   = '';

        $this->writeln($this->bold('  ? ' . $question . ':'));
        $this->writeln($this->dim('    Use ↑/↓ and Enter (or type a number).'));

        $render = function (string $typedInput) use ($choices, &$selectedIndex): void {
            $this->write("\033[" . (count($choices) + 1) . 'A');
            for ($i = 0, $max = count($choices); $i < $max; ++$i) {
                $marker = $i === $selectedIndex ? $this->green('▸') : ' ';
                $this->write("\033[2K\r");
                $this->writeln(sprintf('    %s [%d] %s', $marker, $i + 1, $choices[$i]));
            }

            $hint = $typedInput !== '' ? $typedInput : (string) ($selectedIndex + 1);
            $this->write("\033[2K\r");
            $this->write(sprintf('  Enter number [%s]: ', $this->yellow($hint)));
        };

        // Initial paint.
        foreach ($choices as $i => $choice) {
            $marker = $i === $selectedIndex ? $this->green('▸') : ' ';
            $this->writeln(sprintf('    %s [%d] %s', $marker, $i + 1, $choice));
        }
        $this->write(sprintf('  Enter number [%s]: ', $this->yellow((string) ($selectedIndex + 1))));

        $sttyMode = $this->enableRawMode();
        try {
            while (true) {
                $key = $this->readKey();
                if ($key === '') {
                    continue;
                }

                if ($key === "\033[A") {
                    $selectedIndex = ($selectedIndex - 1 + count($choices)) % count($choices);
                    $typedNumber   = '';
                    $render($typedNumber);
                    continue;
                }

                if ($key === "\033[B") {
                    $selectedIndex = ($selectedIndex + 1) % count($choices);
                    $typedNumber   = '';
                    $render($typedNumber);
                    continue;
                }

                if ($key === "\n" || $key === "\r") {
                    if ($typedNumber !== '' && is_numeric($typedNumber)) {
                        $index = (int) $typedNumber - 1;
                        $this->writeln();

                        return $choices[$index] ?? ($default !== '' ? $default : $choices[0]);
                    }

                    $this->writeln();

                    return $choices[$selectedIndex];
                }

                if ($key === "\177" || $key === "\010") {
                    $typedNumber = substr($typedNumber, 0, -1);
                    $render($typedNumber);
                    continue;
                }

                if (ctype_digit($key)) {
                    $typedNumber .= $key;
                    $render($typedNumber);
                }
            }
        } finally {
            $this->restoreRawMode($sttyMode);
        }
    }

    /**
     * Handles the confirm operation.
     */
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
     * Handles multi-select questions.
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

    /**
     * Handles the ask operation.
     */
    public function ask(string $question, string $default = ''): string
    {
        $hint = $default !== '' ? ' [' . $this->yellow($default) . ']' : '';
        $this->write($this->bold('  ? ' . $question . ':') . $hint . ' ');
        $answer = trim($this->readLine());

        return $answer === '' ? $default : $answer;
    }

    /**
     * Handles the readLine operation.
     */
    private function readLine(): string
    {
        $line = fgets($this->input);

        return $line === false ? '' : rtrim($line, "\r\n");
    }

    /**
     * Handles the readKey operation.
     */
    /**
     * @codeCoverageIgnore
     */
    private function readKey(): string
    {
        $char = fread($this->input, 1);
        if ($char === false || $char === '') {
            return '';
        }

        if ($char !== "\033") {
            return $char;
        }

        $next = fread($this->input, 1);
        if ($next === false || $next === '') {
            return $char;
        }

        if ($next !== '[') {
            return $char . $next;
        }

        $last = fread($this->input, 1);
        if ($last === false || $last === '') {
            return $char . $next;
        }

        return $char . $next . $last;
    }

    /**
     * Handles the enableRawMode operation.
     */
    /**
     * @codeCoverageIgnore
     */
    private function enableRawMode(): ?string
    {
        $current = shell_exec('stty -g 2>/dev/null');
        if (!is_string($current) || trim($current) === '') {
            return null;
        }

        shell_exec('stty -icanon -echo min 1 time 0 2>/dev/null');

        return trim($current);
    }

    /**
     * Handles the restoreRawMode operation.
     */
    /**
     * @codeCoverageIgnore
     */
    private function restoreRawMode(?string $mode): void
    {
        if ($mode === null || $mode === '') {
            return;
        }

        shell_exec('stty ' . escapeshellarg($mode) . ' 2>/dev/null');
    }

    /**
     * Handles the bold operation.
     */
    private function bold(string $text): string
    {
        return $this->isInteractive ? "\033[1m{$text}\033[0m" : $text;
    }

    /**
     * Handles the dim operation.
     */
    private function dim(string $text): string
    {
        return $this->isInteractive ? "\033[2m{$text}\033[0m" : $text;
    }

    /**
     * Handles the green operation.
     */
    private function green(string $text): string
    {
        return $this->isInteractive ? "\033[32m{$text}\033[0m" : $text;
    }

    /**
     * Handles the cyan operation.
     */
    private function cyan(string $text): string
    {
        return $this->isInteractive ? "\033[36m{$text}\033[0m" : $text;
    }

    /**
     * Handles the yellow operation.
     */
    private function yellow(string $text): string
    {
        return $this->isInteractive ? "\033[33m{$text}\033[0m" : $text;
    }

    /**
     * Handles the red operation.
     */
    private function red(string $text): string
    {
        return $this->isInteractive ? "\033[31m{$text}\033[0m" : $text;
    }
}
