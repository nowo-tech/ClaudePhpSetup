<?php

declare(strict_types=1);

namespace NowoTech\ClaudePhpSetup\Template\ClaudeMd;

use NowoTech\ClaudePhpSetup\Question\ProjectConfig;

use function count;
use function in_array;

/**
 * Represents the BaseSection class.
 */
final class BaseSection
{
    /**
     * Handles the header operation.
     */
    public static function header(ProjectConfig $config): string
    {
        $name = $config->projectName ?? 'Project';
        $desc = $config->projectDescription ? "\n\n{$config->projectDescription}" : '';

        return <<<MD
        # {$name}{$desc}
        MD;
    }

    /**
     * Handles the stack operation.
     */
    public static function stack(ProjectConfig $config): string
    {
        $lines   = ['## Stack', ''];
        $lines[] = '- **PHP**: ' . $config->phpVersion;

        if ($config->framework !== 'none') {
            $frameworkName = match ($config->framework) {
                'symfony' => 'Symfony',
                'laravel' => 'Laravel',
                'slim'    => 'Slim',
                'yii'     => 'Yii',
                default   => ucfirst($config->framework),
            };
            $version = $config->frameworkVersion ? ' ' . $config->frameworkVersion : '';
            $lines[] = '- **Framework**: ' . $frameworkName . $version;
        }

        if ($config->hasDoctrine) {
            $lines[] = '- **ORM**: Doctrine ORM';
        }

        if ($config->hasTwig) {
            $lines[] = '- **Templates**: Twig';
        }

        if ($config->hasApi) {
            $apiLabel = match ($config->apiStyle) {
                'api-platform' => 'API Platform',
                'graphql'      => 'GraphQL',
                default        => 'REST API',
            };
            $lines[] = '- **API**: ' . $apiLabel;
        }

        if ($config->testingFramework !== 'none') {
            $testLabel = match ($config->testingFramework) {
                'both'  => 'PHPUnit + Pest',
                'pest'  => 'Pest',
                default => 'PHPUnit',
            };
            $lines[] = '- **Testing**: ' . $testLabel;
        }

        $tools = [];
        if ($config->hasRector) {
            $tools[] = 'Rector ' . $config->rectorVersion . '.x';
        }
        if ($config->hasPhpStan) {
            $tools[] = 'PHPStan level ' . $config->phpStanLevel;
        }
        if ($config->hasPhpCsFixer) {
            $tools[] = 'PHP-CS-Fixer';
        }
        if ($config->hasGrumPhp) {
            $tools[] = 'GrumPHP';
        }

        if ($tools !== []) {
            $lines[] = '- **Quality**: ' . implode(', ', $tools);
        }

        if ($config->hasDocker) {
            $lines[] = '- **Containers**: Docker / Compose';
        }

        if ($config->hasCi) {
            $lines[] = '- **CI**: GitHub Actions';
        }

        return implode("\n", $lines);
    }

    /**
     * Lists generated Claude Code assets (paths) so the model knows what exists under `.claude/`.
     */
    /**
     * Handles the generatedClaudeResources operation.
     */
    public static function generatedClaudeResources(ProjectConfig $config): string
    {
        $hasAny = $config->generateClaudeMd
            || ($config->generateCommands && $config->selectedCommands !== [])
            || ($config->generateAgents && $config->selectedAgents !== [])
            || ($config->generateSkills && $config->selectedSkills !== [])
            || $config->generateExamples;

        if (!$hasAny) {
            return '';
        }

        $lines = ['## Claude resources in this repository', ''];

        if ($config->generateClaudeMd) {
            $lines[] = '- `CLAUDE.md` — primary project instructions (this file).';
        }

        if ($config->generateCommands && $config->selectedCommands !== []) {
            $lines[] = '- **Slash commands** (`.claude/commands/`):';
            foreach ($config->selectedCommands as $key) {
                $lines[] = '  - `.claude/commands/' . $key . '.md`';
            }
        }

        if ($config->generateAgents && $config->selectedAgents !== []) {
            $lines[] = '- **Sub-agents** (`.claude/agents/`):';
            foreach ($config->selectedAgents as $key) {
                $lines[] = '  - `.claude/agents/' . $key . '.md`';
            }
        }

        if ($config->generateSkills && $config->selectedSkills !== []) {
            $lines[] = '- **Skills** (`.claude/skills/<name>/SKILL.md`):';
            foreach ($config->selectedSkills as $key) {
                $lines[] = '  - `.claude/skills/' . $key . '/SKILL.md`';
            }
        }

        if ($config->generateExamples) {
            $lines[] = '- **Examples** (`examples/`): practical prompts and repeatable workflows.';
        }

        return implode("\n", $lines);
    }

    /**
     * Handles the commands operation.
     */
    public static function commands(ProjectConfig $config): string
    {
        $isMake = in_array($config->commandRunner, ['make', 'both'], true);
        $runner = $isMake ? 'make' : 'composer';

        $lines = ['## Key Commands', ''];

        if ($config->hasRector) {
            $lines[] = '| `' . $runner . ' rector-dry` | Preview Rector changes without applying |';
            $lines[] = '| `' . $runner . ' rector` | Apply Rector refactoring |';
        }

        if ($config->hasPhpStan) {
            $lines[] = '| `' . $runner . ' phpstan` | Run PHPStan static analysis (level ' . $config->phpStanLevel . ') |';
        }

        if ($config->hasPhpCsFixer) {
            $lines[] = '| `' . $runner . ' cs-fix` | Fix code style automatically |';
            $lines[] = '| `' . $runner . ' cs-check` | Check code style without fixing |';
        }

        if ($config->testingFramework !== 'none') {
            $lines[] = '| `' . $runner . ' test` | Run the full test suite |';
        }

        if ($config->hasGrumPhp) {
            $lines[] = '| `' . $runner . ' grumphp` | Run GrumPHP pre-commit checks manually |';
        }

        if (count($lines) <= 2) {
            return '';
        }

        // Insert table header after "## Key Commands\n"
        array_splice($lines, 2, 0, ['| Command | Description |', '|---------|-------------|']);

        return implode("\n", $lines);
    }

    /**
     * Handles the phpBestPractices operation.
     */
    public static function phpBestPractices(): string
    {
        return <<<'MD'
        ## PHP Best Practices

        - Use **strict types**: every file must begin with `declare(strict_types=1);`
        - Prefer **named constructors** and **value objects** over plain arrays for domain data
        - Use **readonly properties** (PHP 8.1+) for immutable value objects
        - Use **enum** instead of string constants for fixed sets of values
        - Use **named arguments** for clarity when calling functions with many parameters
        - Use **match** instead of switch where possible
        - Avoid `null` — use **nullable types** explicitly and handle them at the boundary
        - Use **first-class callables** (`Closure::fromCallable`, `$fn(...)`) to pass methods as callbacks
        - Every `public` method should have a **return type declaration**
        - Avoid static methods in domain code — they make testing harder
        MD;
    }

    /**
     * Handles the codeReviewGuidelines operation.
     */
    public static function codeReviewGuidelines(ProjectConfig $config): string
    {
        unset($config); // config reserved for future framework-specific guidelines

        return <<<'MD'
        ## Code Review Guidelines

        When reviewing code, always check:
        1. **Strict types** declared at the top of every PHP file
        2. **Type coverage** — all parameters and return types declared
        3. **Single Responsibility** — each class/method does one thing
        4. **No magic values** — use named constants or enums
        5. **No unused variables** or imports
        6. **Error handling** — exceptions are typed and documented
        7. **Test coverage** — new code has corresponding tests
        8. **No direct `$_GET`/`$_POST`** — use framework request abstraction
        9. **No `var_dump`, `print_r`, `die`, `exit`** in production code
        10. **SQL injection safety** — never concatenate user input into queries
        MD;
    }
}
