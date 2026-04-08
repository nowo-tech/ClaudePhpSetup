<?php

declare(strict_types=1);

namespace NowoTech\ClaudePhpSetup\Template\Skills;

use NowoTech\ClaudePhpSetup\Question\ProjectConfig;

/**
 * Skill content for `.claude/skills/<name>/SKILL.md` (YAML frontmatter + body).
 */
/**
 * Represents the SkillTemplates class.
 */
final class SkillTemplates
{
    /** @return array<string, string> key => SKILL.md body */
    public static function all(ProjectConfig $config): array
    {
        $skills = [
            'php-quality'     => self::phpQuality($config),
            'php-testing'     => self::phpTesting($config),
            'rector-workflow' => self::rectorWorkflow($config),
            'api-security'    => self::apiSecurity($config),
            'doctrine-data'   => self::doctrineData($config),
            'docker-dev'      => self::dockerDev($config),
            'ci-pipeline'     => self::ciPipeline($config),
            'observability'   => self::observability($config),
            'mcp-tools'       => self::mcpTools($config),
        ];

        return array_filter($skills, static fn (string $s): bool => $s !== '');
    }

    /**
     * Handles the phpQuality operation.
     */
    private static function phpQuality(ProjectConfig $config): string
    {
        $runner = $config->commandRunner === 'composer' ? 'composer' : 'make';

        return <<<MD
        ---
        name: php-quality
        description: Run PHP quality checks (style, static analysis, optional Rector dry-run) in the same order as this project.
        ---

        # PHP quality workflow

        ## Context

        - PHP **{$config->phpVersion}**
        - Preferred runner: **`{$runner}`** scripts

        ## Steps

        1. If the project documents a single QA command (e.g. `{$runner} qa`), run that first.
        2. Otherwise run, in order: code style check, PHPStan (if configured), tests, then Rector dry-run (if configured).
        3. Fix issues from the **first failing tool** before re-running the next.

        ## Rules

        - Do not silence warnings with broad ignores; **narrow** suppressions or fix root causes.
        - Prefer **small, reviewable** commits when fixing CS or Rector output.
        MD;
    }

    /**
     * Handles the phpTesting operation.
     */
    private static function phpTesting(ProjectConfig $config): string
    {
        if ($config->testingFramework === 'none') {
            return '';
        }

        $tf = match ($config->testingFramework) {
            'pest'  => 'Pest',
            'both'  => 'PHPUnit and Pest',
            default => 'PHPUnit',
        };

        return <<<MD
        ---
        name: php-testing
        description: Write and run {$tf} tests following this project's conventions.
        ---

        # PHP testing workflow

        ## Stack

        - **{$tf}** on PHP **{$config->phpVersion}**

        ## Workflow

        1. Run the test suite with the documented command (`composer test`, `make test`, or `vendor/bin/phpunit`).
        2. For new code, add tests that fail first or assert observable behaviour — not implementation details.
        3. Use **data providers** or datasets when covering multiple scenarios of the same rule.

        ## Rules

        - Keep tests **fast** — mock I/O boundaries; avoid real network, mail, filesystem.
        - One logical scenario per test; name tests after **behaviour**.
        MD;
    }

    /**
     * Handles the rectorWorkflow operation.
     */
    private static function rectorWorkflow(ProjectConfig $config): string
    {
        if (!$config->hasRector) {
            return '';
        }

        $v = $config->rectorVersion;

        return <<<MD
        ---
        name: rector-workflow
        description: Rector {$v}.x dry-run and apply workflow for this repository.
        ---

        # Rector workflow

        ## Config

        - Rector **{$v}.x** — see `rector.php` in the project root.

        ## Steps

        1. Run **dry-run** and read the diff preview carefully.
        2. Apply changes only when they match project intent; skip or narrow rules in config if needed.
        3. Run tests and static analysis after applying.

        ## Rules

        - Never use Rector to **hide** type errors — fix PHPStan issues explicitly when they appear.
        MD;
    }

    /**
     * Handles the apiSecurity operation.
     */
    private static function apiSecurity(ProjectConfig $config): string
    {
        if (!$config->hasApi) {
            return '';
        }

        return <<<'MD'
        ---
        name: api-security
        description: Review and harden HTTP APIs — authn/z, validation, headers, and safe error responses.
        ---

        # API security skill

        ## Checklist

        1. Authentication required for non-public endpoints; authorization checked per resource.
        2. Input validated at the boundary; reject unexpected fields for public APIs when policy requires it.
        3. Errors: generic client message, detailed server-side log without secrets.
        4. Rate limiting considered for sensitive routes (login, reset password, token issuance).

        ## Rules

        - Never log bearer tokens or passwords.
        - Prefer framework guards (firewalls, policies, middleware) over ad-hoc checks scattered in controllers.
        MD;
    }

    /**
     * Handles the doctrineData operation.
     */
    private static function doctrineData(ProjectConfig $config): string
    {
        if (!$config->hasDoctrine) {
            return '';
        }

        return <<<'MD'
        ---
        name: doctrine-data
        description: Doctrine entities, migrations, migrations safety, and query performance.
        ---

        # Doctrine & data layer

        ## Workflow

        1. Schema changes go through **migrations** — review `up`/`down` for data safety.
        2. Prefer explicit indexes for columns used in `WHERE`, `ORDER BY`, and `JOIN`.
        3. Avoid `findAll()` on large tables; use pagination or chunking.

        ## Rules

        - Keep migrations **reversible** when possible; document irreversible steps.
        - Watch for **N+1** queries — use eager loading or DTO projections.
        MD;
    }

    /**
     * Handles the dockerDev operation.
     */
    private static function dockerDev(ProjectConfig $config): string
    {
        if (!$config->hasDocker) {
            return '';
        }

        return <<<'MD'
        ---
        name: docker-dev
        description: Work inside Docker/Compose for this PHP project — run commands in the right container.
        ---

        # Docker development

        ## Workflow

        1. Find the documented entrypoint (`docker compose`, `make up`, etc.).
        2. Run Composer, PHP, and PHPUnit **inside** the application container unless README says otherwise.
        3. After changing images or compose files, rebuild when required.

        ## Rules

        - Do not commit `.env` secrets; use env files or CI secrets.
        - Respect **volume paths** when editing configs or generated files.
        MD;
    }

    /**
     * Handles the ciPipeline operation.
     */
    private static function ciPipeline(ProjectConfig $config): string
    {
        if (!$config->hasCi) {
            return '';
        }

        return <<<'MD'
        ---
        name: ci-pipeline
        description: Align local checks with GitHub Actions workflows in this repository.
        ---

        # CI pipeline alignment

        ## Workflow

        1. Open `.github/workflows` and note PHP version, extensions, and env vars.
        2. Reproduce failing jobs locally with the **same** commands (copy from YAML).
        3. When changing workflow names, update branch protection if applicable.

        ## Rules

        - Prefer **deterministic** installs (`composer install --no-interaction` with lockfile when used).
        - Fix CI failures before merging; avoid disabling failing checks without team agreement.
        MD;
    }

    /**
     * Handles the observability operation.
     */
    private static function observability(ProjectConfig $config): string
    {
        if (!$config->includeObservabilityNotes) {
            return '';
        }

        return <<<'MD'
        ---
        name: observability
        description: Structured logging, metrics, and tracing conventions for this codebase.
        ---

        # Observability

        ## Workflow

        1. Use **structured** logs with stable keys; avoid raw string concatenation of user input in logs.
        2. Propagate a **request/correlation ID** across services when debugging multi-step flows.
        3. Use metrics for SLOs (latency, error rate) — not only log volume.

        ## Rules

        - Do not log PII or tokens; redact or hash when required.
        MD;
    }

    /**
     * Handles the mcpTools operation.
     */
    private static function mcpTools(ProjectConfig $config): string
    {
        if (!$config->includeMcpNotes) {
            return '';
        }

        return <<<'MD'
        ---
        name: mcp-tools
        description: Use MCP tools safely — read schemas, avoid secrets in prompts, handle failures.
        ---

        # MCP tools

        ## Workflow

        1. Prefer **project-local** MCP config when the team should share tools.
        2. Read tool **schemas** before invocation; pass required parameters.
        3. On failure, surface the error and retry with a narrower scope.

        ## Rules

        - Never paste API keys or tokens into chat; use host-provided auth.
        - Keep a short list of **expected** MCP servers for this repo in docs or CLAUDE.md.
        MD;
    }
}
