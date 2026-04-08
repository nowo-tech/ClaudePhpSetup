<?php

declare(strict_types=1);

namespace NowoTech\ClaudePhpSetup\Template\ClaudeMd;

use NowoTech\ClaudePhpSetup\Question\ProjectConfig;

/**
 * Docker, CI, API hardening, observability, and MCP — optional CLAUDE.md sections.
 */
final class OperationalSection
{
    public static function render(ProjectConfig $config): string
    {
        $blocks = [];

        if ($config->hasDocker) {
            $blocks[] = self::dockerBlock();
        }

        if ($config->hasCi) {
            $blocks[] = self::ciBlock();
        }

        if ($config->hasApi) {
            $blocks[] = self::apiSecurityBlock($config);
        }

        if ($config->includeObservabilityNotes) {
            $blocks[] = self::observabilityBlock();
        }

        if ($config->includeMcpNotes) {
            $blocks[] = self::mcpBlock();
        }

        if ($blocks === []) {
            return '';
        }

        return "## Delivery, operations & security\n\n" . implode("\n\n", $blocks);
    }

    private static function dockerBlock(): string
    {
        return <<<'MD'
        ### Docker & local environment

        - Prefer **one documented entrypoint** (e.g. `docker compose up` or `make up`) — do not invent ad-hoc container names.
        - Run Composer and PHP **inside** the app container unless the README says otherwise.
        - Keep **host paths vs container paths** straight (`/app`, `/var/www/html`, etc.) when editing configs or volumes.
        - After changing `Dockerfile` or `compose*.yml`, rebuild images explicitly when required (`docker compose build --no-cache`).
        - Never commit **secrets** — use `.env` / Docker secrets / CI variables.
        MD;
    }

    private static function ciBlock(): string
    {
        return <<<'MD'
        ### Continuous integration (GitHub Actions)

        - Treat **CI as the source of truth** for merge readiness — match its PHP version, extensions, and env vars locally when debugging failures.
        - Prefer **deterministic** installs: `composer install --no-interaction` with a committed lockfile when the project uses one.
        - If workflows cache Composer or tools, **bust caches** when dependencies change in unexpected ways.
        - Keep **job names and required checks** aligned with branch protection; rename jobs with care.
        - When a pipeline fails, reproduce with the **same command sequence** locally (copy from the workflow YAML).
        MD;
    }

    private static function apiSecurityBlock(ProjectConfig $config): string
    {
        $style = match ($config->apiStyle) {
            'graphql'      => 'GraphQL',
            'api-platform' => 'API Platform',
            default        => 'REST',
        };

        return <<<MD
        ### API security ({$style})

        - **Authentication & authorization**: enforce at the framework boundary (firewalls, policies, voters, middleware) — not only in controllers.
        - **Input validation**: validate DTOs / payloads; reject unknown fields for public APIs when appropriate.
        - **Rate limiting & abuse**: consider throttling brute-forceable endpoints (login, token, password reset).
        - **Headers**: `Content-Type`, `Cache-Control`, CORS — configure explicitly; avoid `*` in production CORS.
        - **Errors**: return **generic** messages to clients; log detailed traces server-side only.
        - **PII & logs**: never log tokens, passwords, or full payment data; scrub structured log fields.
        - **Dependencies**: monitor security advisories (`composer audit` / GitHub Dependabot) for HTTP stacks and serializers.
        MD;
    }

    private static function observabilityBlock(): string
    {
        return <<<'MD'
        ### Observability (logs, metrics, tracing)

        - Use **structured logging** (JSON) in production when possible — one event per line, stable field names.
        - Correlate requests with a **request ID** propagated from the web server or middleware.
        - **Metrics**: expose HTTP latency, error rate, and queue depth — alert on SLO breaches, not on every stack trace.
        - **Tracing**: name spans after user-visible operations (e.g. `checkout.payment`) rather than internal class names only.
        - **PII**: never log secrets; sample or hash user identifiers where regulations require minimization.
        MD;
    }

    private static function mcpBlock(): string
    {
        return <<<'MD'
        ### MCP & external tools

        - Prefer **project-scoped** MCP configuration committed to the repo when the whole team should share the same tools.
        - **Read tool schemas** before calling — respect required parameters and rate limits.
        - **Do not** paste secrets into prompts; use environment variables or host-provided auth flows.
        - When an MCP tool fails, **surface the error message** and retry with a smaller scope (e.g. narrower query or path).
        - Keep a short **inventory** in docs or `CLAUDE.md` of which MCP servers are expected for this codebase.
        MD;
    }
}
