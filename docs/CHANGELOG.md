# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.1.3] — 2026-04-08

### Changed

- Interactive CLI prompt UX refined: fixed arrow-key redraw artifacts in single-choice prompts (no duplicated `Enter number` lines).
- Confirm prompts now reliably accept single-key `y`/`n` in interactive terminals after arrow-key navigation mode.
- Quality tool labels in the wizard are more explicit: Rector uses `version 1/2` and PHPStan uses `level 0..9/max`.

## [1.1.2] — 2026-04-08

### Added

- Interactive single-choice navigation now supports `↑`/`↓` + Enter in TTY terminals (numeric selection is still supported).

### Changed

- Symfony wizard versions expanded to include `7.3`, `7.4`, and `8.0`.
- Symfony upgrade flow now asks for **current version** first and then a **higher target version** (`upgrading to`), avoiding confusing downgrade-looking prompts.
- Added English PHPDoc comments across `src/` and `tests/` classes/methods where missing.

## [1.1.1] — 2026-04-08

### Fixed

- Publish Composer binary metadata via `"bin": ["bin/claude-php-setup"]` so `vendor/bin/claude-php-setup` is created correctly in consumer projects.

## [1.1.0] — 2026-04-08

### Added

- **Skills:** `.claude/skills/<name>/SKILL.md` for `php-quality`, `php-testing`, `rector-workflow`, `api-security`, `doctrine-data`, `docker-dev`, `ci-pipeline`, and optional `observability` / `mcp-tools` when enabled in the wizard.
- **Examples:** `examples/` with README, workflow and prompt samples (optional generation step).
- **CLAUDE.md — Operational** section when relevant (API style, Docker, CI, observability, MCP notes).
- Slash commands: `/qa-gate`, `/docker-exec`, `/migration-review`, `/api-security-review`.
- Sub-agents: `laravel-expert`, `security-auditor`, `performance-php`.

### Changed

- Test suite: **100%** line coverage on included `src/` paths (see `phpunit.xml.dist` for exclusions such as interactive CLI orchestration).
- `composer.json` **branch-alias** `dev-main` → `1.1-dev`.

## [1.0.0] — 2026-04-08

### Added

- Interactive CLI wizard (`vendor/bin/claude-php-setup`)
- Auto-detection of framework, tools and testing setup from `composer.json`
- `CLAUDE.md` generation with sections for:
  - Stack overview and key commands table
  - Symfony 5.4 / 6.4 / 7.x best practices
  - Laravel 10 / 11 / 12 best practices
  - Rector 1.x and 2.x workflow guide
  - PHPStan levels 0–max guide
  - PHP-CS-Fixer guide
  - GrumPHP guide
  - PHPUnit testing guide
  - Pest testing guide
  - Doctrine ORM guide
  - Twig template guide
  - PHP 8.1–8.4 best practices
  - Code review guidelines
  - Symfony upgrade guide (any minor → any minor)
- Slash commands: `code-review`, `rector-dry`, `rector-run`, `phpstan`, `cs-fix`, `test-run`, `test-write`, `twig-review`, `make-entity`, `make-repository`, `make-service`, `make-command`, `symfony-upgrade`, `grumphp-check`
- Sub-agents: `php-architect`, `test-writer`, `refactor-agent`, `symfony-upgrader`, `doctrine-expert`
- Composer plugin notification on `post-install-cmd` and `post-update-cmd`
- `--overwrite` flag to regenerate existing files
- `--dir` flag to target a custom project directory
- Heredoc dedentation — source code indented templates produce clean output

[1.1.3]: https://github.com/nowo-tech/ClaudePhpSetup/releases/tag/v1.1.3
[1.1.2]: https://github.com/nowo-tech/ClaudePhpSetup/releases/tag/v1.1.2
[1.1.1]: https://github.com/nowo-tech/ClaudePhpSetup/releases/tag/v1.1.1
[1.1.0]: https://github.com/nowo-tech/ClaudePhpSetup/releases/tag/v1.1.0
