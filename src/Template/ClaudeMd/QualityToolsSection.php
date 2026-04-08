<?php

declare(strict_types=1);

namespace NowoTech\ClaudePhpSetup\Template\ClaudeMd;

use NowoTech\ClaudePhpSetup\Question\ProjectConfig;

use function in_array;

final class QualityToolsSection
{
    public static function render(ProjectConfig $config): string
    {
        $sections = [];

        if ($config->hasRector) {
            $sections[] = self::rector($config);
        }

        if ($config->hasPhpStan) {
            $sections[] = self::phpStan($config);
        }

        if ($config->hasPhpCsFixer) {
            $sections[] = self::phpCsFixer($config);
        }

        if ($config->hasGrumPhp) {
            $sections[] = self::grumPhp($config);
        }

        if ($sections === []) {
            return '';
        }

        return "## Quality Tools\n\n" . implode("\n\n", $sections);
    }

    private static function rector(ProjectConfig $config): string
    {
        $runner     = in_array($config->commandRunner, ['make', 'both'], true) ? 'make' : 'composer';
        $configFile = $config->rectorVersion === '2' ? 'rector.php' : 'rector.php';
        $importNote = $config->rectorVersion === '2'
            ? 'In Rector 2.x, sets are imported from `Rector\Set\ValueObject\SetList` and `Rector\Set\ValueObject\LevelSetList`.'
            : 'In Rector 1.x, sets are imported from `Rector\Set\ValueObject\SetList`.';

        $frameworkSets = '';
        if ($config->framework === 'symfony' && $config->frameworkVersion) {
            $major         = (int) explode('.', $config->frameworkVersion)[0];
            $minor         = (int) (explode('.', $config->frameworkVersion)[1] ?? 0);
            $setName       = 'SYMFONY_' . $major . '_' . $minor;
            $frameworkSets = "\n- Symfony set: `SymfonySetList::{$setName}` — Symfony-specific upgrades";
        }

        return <<<MD
        ### Rector ({$config->rectorVersion}.x)
        Configuration file: `{$configFile}`
        {$importNote}

        **Available rule sets:**
        - `SetList::PHP_{$config->phpVersion}` — PHP {$config->phpVersion} compatibility fixes (replace dots with underscore in version)
        - `SetList::CODE_QUALITY` — code quality improvements
        - `SetList::DEAD_CODE` — remove unreachable/unused code
        - `SetList::TYPE_DECLARATION` — add missing type declarations
        - `SetList::NAMING` — rename to consistent naming conventions{$frameworkSets}

        **Workflow:**
        1. Run `{$runner} rector-dry` — review proposed changes
        2. Check each change in the diff carefully
        3. Run `{$runner} rector` — apply safe changes
        4. Run `{$runner} test` — verify nothing broke
        5. Commit in small, focused commits per rule set

        **Never run Rector on:**
        - Generated code (migrations, generated entities)
        - Vendor directory
        - Files with intentional "legacy" patterns you want to keep
        MD;
    }

    private static function phpStan(ProjectConfig $config): string
    {
        $runner = in_array($config->commandRunner, ['make', 'both'], true) ? 'make' : 'composer';
        $level  = $config->phpStanLevel;

        $levelDesc = match ($level) {
            '0'     => 'basic checks — dead code, always-true conditions',
            '1'     => 'possibly-undefined variables, unknown magic methods',
            '2'     => 'unknown methods on `$this`',
            '3'     => 'return types, types in `@var` annotations',
            '4'     => 'dead code, never-thrown exceptions',
            '5'     => 'check argument types in method/function calls',
            '6'     => 'check returned types from function calls',
            '7'     => 'report always-true type checks',
            '8'     => 'report union types in method calls',
            '9'     => 'strict method calls, property types',
            'max'   => 'strictest — same as level 9 plus bleeding-edge checks',
            default => 'standard checks',
        };

        return <<<MD
        ### PHPStan (level {$level})
        Configuration: `phpstan.neon` (or `phpstan.neon.dist`)
        Level {$level}: {$levelDesc}

        **Run:** `{$runner} phpstan`

        **Fixing PHPStan errors:**
        - **Never use `@phpstan-ignore` without a comment explaining why**
        - Add missing return types rather than suppressing
        - Use `@phpstan-param`, `@phpstan-return` for complex generics
        - For third-party code issues, add stubs or use `phpstan/extension-installer`
        - Baseline (`phpstan-baseline.neon`) only for legacy code that cannot be fixed immediately

        **Common issues:**
        - `Parameter \$x of method expects Y, Z given` → fix the type or add a cast with validation
        - `Call to method on nullable type` → add null check or use null-safe operator `?->`
        - `Property has no type` → add type declaration
        - `Return type of method has nothing to return` → add `@return never` or fix logic
        MD;
    }

    private static function phpCsFixer(ProjectConfig $config): string
    {
        $runner = in_array($config->commandRunner, ['make', 'both'], true) ? 'make' : 'composer';

        $frameworkRules = '';
        if ($config->framework === 'symfony') {
            $frameworkRules = "\n\n**Symfony rules applied** (via `@Symfony` ruleset):\n"
                . "- `phpdoc_align`, `phpdoc_order`, `phpdoc_trim`\n"
                . "- `ordered_imports`, `no_unused_imports`\n"
                . '- `single_quote`, `trailing_comma_in_multiline`';
        }

        return <<<MD
        ### PHP-CS-Fixer
        Configuration: `.php-cs-fixer.php` (or `.php-cs-fixer.dist.php`)

        **Commands:**
        - `{$runner} cs-check` — check without fixing (use in CI)
        - `{$runner} cs-fix` — fix automatically{$frameworkRules}

        **Important rules in this project:**
        - `declare_strict_types` — always add `declare(strict_types=1)`
        - `no_unused_imports` — remove unused `use` statements
        - `ordered_imports` — sort imports alphabetically
        - `trailing_comma_in_multiline` — trailing commas in arrays/parameters

        **CI:** Always run `cs-check` (not `cs-fix`) in CI — fixes are applied locally before commit.
        MD;
    }

    private static function grumPhp(ProjectConfig $config): string
    {
        $tools = [];
        if ($config->hasPhpCsFixer) {
            $tools[] = '- `php_cs_fixer` — runs PHP-CS-Fixer on staged files';
        }
        if ($config->hasPhpStan) {
            $tools[] = '- `phpstan` — runs PHPStan analysis';
        }
        if ($config->testingFramework !== 'none') {
            $tools[] = '- `phpunit` — runs test suite on commit';
        }
        $toolsList = $tools === [] ? '- Configured in `grumphp.yml`' : implode("\n", $tools);

        return <<<MD
        ### GrumPHP
        Configuration: `grumphp.yml`

        GrumPHP runs automatically on `git commit`. Configured tasks:
        {$toolsList}

        **To bypass in emergencies (use rarely):**
        ```bash
        git commit --no-verify -m "WIP: ..."
        ```
        Always fix GrumPHP failures before opening a PR — never bypass on final commits.
        MD;
    }
}
