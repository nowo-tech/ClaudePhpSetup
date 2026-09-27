# FrankenPHP worker mode audit (kernel not reset between requests)

| Field | Value |
|-------|-------|
| Package | `nowo-tech/claude-php-setup` (`composer-plugin`) |
| Audited revision | `v1.1.11` / `eecc849` |
| Audit date | 2026-09-23 |
| Method | Manual review of every file under `src/` (Composer plugin, CLI wizard, detector, generators, question tree) and `bin/claude-php-setup`; template classes skimmed for static state |
| **Verdict** | — **Not applicable** — Composer plugin plus an interactive CLI wizard that writes Markdown files; no Symfony bundle, no container services, nothing is loaded by the HTTP kernel |

## Execution model assumed

FrankenPHP worker mode boots the Symfony kernel once per worker and serves many requests with the same container. This audit assumes the **strict** variant: the kernel is **not** rebooted between requests, so every shared service, static property and PHP global survives from one request to the next. Two scenarios are evaluated:

- **A — kernel not rebooted, `services_resetter` still runs:** services tagged `kernel.reset` (or implementing `ResetInterface`) are reset between requests.
- **B — no reset at all:** nothing is reset; any per-request state kept in a service leaks into the next request.

A bundle that is safe under **B** is safe under **A** and under classic mode / PHP-FPM.

## Why it does not run in the worker

- `composer.json` declares `"type": "composer-plugin"` with `extra.class = NowoTech\ClaudePhpSetup\Plugin` and only requires `php` and `composer-plugin-api`. There is no `Bundle` class, DI extension, service configuration, route, listener or Twig extension.
- `src/Plugin.php` is instantiated by Composer (not by Symfony) and only prints a hint on `post-install-cmd` / `post-update-cmd` (`src/Plugin.php:46-93`).
- The wizard is started only from the CLI entry point `bin/claude-php-setup` (`InteractiveSetup::create()->run()`, `bin/claude-php-setup:70-71`), which reads `$argv` and writes to `STDIN`/`STDOUT`.
- Autoloaded classes under `NowoTech\ClaudePhpSetup\` are never referenced by a Symfony application unless someone calls them explicitly.

## Summary

| Area | Status | Notes |
|------|--------|-------|
| Mutable state in shared services | ✅ N/A | No container services. `FileGenerator` has counters (`src/Generator/FileGenerator.php:31-33`) that are reset at the start of every `generate()` call (`:50-52`); it only lives for one CLI run |
| Static properties / `static` locals | ✅ | None. Template classes (`src/Template/**`) only have pure `static` methods returning strings |
| `ResetInterface` / `kernel.reset` coverage | ✅ N/A | Nothing to reset |
| Request / user / locale captured in services | ✅ N/A | No HTTP code |
| Superglobals, `$_ENV`, `putenv`, `ini_set`, `setlocale`, timezone | ✅ N/A | Only `$argv` in the CLI script; `getcwd()` in `FileGenerator::relativePath()` (`:297`) |
| Doctrine / EntityManager | ✅ N/A | No persistence |
| Output, headers, `exit`, shutdown functions | ✅ N/A | `exit()` and `fwrite(STDERR, …)` only in `bin/claude-php-setup`; `Console` writes to `STDOUT` (`src/Cli/Console.php:32-35`) |
| Resources (files, sockets, cURL) held open | ✅ N/A | One-shot `file_get_contents` / `file_put_contents` during the CLI run |
| Memory growth across requests | ✅ N/A | Short-lived CLI process |
| Blocking I/O and timeouts | ✅ N/A | Interactive stdin reads and `shell_exec('stty …')` (`src/Cli/Console.php:414-436`), CLI only |
| Third-party static state | ✅ N/A | No runtime dependencies besides Composer's plugin API |
| PHPStan FrankenPHP rulesets | ✅ | `ruleset-classic.neon` + `ruleset-worker.neon` included in `phpstan.neon.dist` |

Worker demo: none (the package has no `demo/` directory, which is expected for a CLI tool).

## Services reviewed

| Service | Shared | Mutable state | Scenario A | Scenario B |
|---------|--------|---------------|------------|------------|
| — (no Symfony services) | — | — | N/A | N/A |
| `NowoTech\ClaudePhpSetup\Plugin` (Composer plugin, not a Symfony service) | Composer process only | `$composer` set in `activate()` | N/A | N/A |
| `Cli\InteractiveSetup`, `Cli\Console`, `Detector\ProjectDetector`, `Generator\*`, `Question\*` | CLI process only | per-run state only | N/A | N/A |

## Findings

No worker-mode findings: the package never executes inside the HTTP worker.

### W-01 — CLI classes assume the CLI SAPI (Info)

- **Where:** `src/Cli/Console.php:32-35` defaults to the `STDIN` / `STDOUT` constants; `src/Cli/Console.php:414-436` calls `shell_exec('stty …')`.
- **Worker impact:** none in normal use. These constants are only defined by the CLI SAPI, so instantiating `Console` (or `InteractiveSetup::create()`) from a FrankenPHP HTTP request would fail. This is a reminder that the classes must not be called from web code, not a defect.
- **Recommendation:** keep using the tool only through `vendor/bin/claude-php-setup`.

## Usage recommendations in worker mode

- Install it as a development dependency (`composer require --dev nowo-tech/claude-php-setup`) so it is not present in production images; it has no effect on the worker either way.
- Do not call its classes from controllers, listeners or services.
- The generated `CLAUDE.md` / `.claude/*` files are documentation only and are not loaded at runtime.

## Re-audit triggers

Re-run this audit if the package gains a Symfony bundle class, a DI extension, services meant to be used from web code, or any code that is autoloaded and executed during HTTP requests.
