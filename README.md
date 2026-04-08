# Claude PHP Setup

[![CI](https://github.com/nowo-tech/ClaudePhpSetup/actions/workflows/ci.yml/badge.svg)](https://github.com/nowo-tech/ClaudePhpSetup/actions/workflows/ci.yml) [![Packagist Version](https://img.shields.io/packagist/v/nowo-tech/claude-php-setup.svg?style=flat)](https://packagist.org/packages/nowo-tech/claude-php-setup) [![Packagist Downloads](https://img.shields.io/packagist/dt/nowo-tech/claude-php-setup.svg)](https://packagist.org/packages/nowo-tech/claude-php-setup) [![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE) [![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php)](https://php.net) [![Symfony](https://img.shields.io/badge/Symfony-6%20%7C%207%20%7C%208-000000?logo=symfony)](https://symfony.com) [![GitHub stars](https://img.shields.io/github/stars/nowo-tech/claude-php-setup.svg?style=social&label=Star)](https://github.com/nowo-tech/ClaudePhpSetup) [![Coverage](https://img.shields.io/badge/Coverage-100%25-brightgreen)](#tests-and-coverage)

> ⭐ **Found this useful?** Install from [Packagist](https://packagist.org/packages/nowo-tech/claude-php-setup) and give the repo a [star on GitHub](https://github.com/nowo-tech/ClaudePhpSetup) if it helps you.

An interactive CLI wizard that generates **customised Claude Code markdown files** for PHP projects.

Answer a series of questions about your stack and the tool generates a `CLAUDE.md`, slash commands, sub-agents, optional **skills** (`.claude/skills/`) and **examples** (`examples/`) tailored to your exact setup — Symfony, Laravel, Rector, PHPStan, PHP-CS-Fixer, GrumPHP, PHPUnit, Pest, Twig, Doctrine, and more.

## Installation

```bash
composer require --dev nowo-tech/claude-php-setup
```

## Usage

```bash
vendor/bin/claude-php-setup
```

Re-run and overwrite existing files:

```bash
vendor/bin/claude-php-setup --overwrite
```

## What it generates

### `CLAUDE.md`

A comprehensive project-level instruction file for Claude Code, with sections tailored to your stack:

| Section | Included when |
|---------|---------------|
| Stack overview | always |
| Key commands table | any QA tool configured |
| Symfony best practices | Symfony detected |
| Laravel best practices | Laravel detected |
| Rector workflow | Rector installed |
| PHPStan guide | PHPStan installed |
| PHP-CS-Fixer guide | PHP-CS-Fixer installed |
| GrumPHP guide | GrumPHP installed |
| PHPUnit testing | PHPUnit installed |
| Pest testing | Pest installed |
| Doctrine ORM | Doctrine installed |
| Twig templates | Twig installed |
| PHP best practices | always |
| Code review guidelines | always |
| Symfony upgrade guide | upgrading Symfony |
| Operational (API, Docker, CI, observability, MCP) | when the wizard enables the matching options |

### `.claude/commands/` (slash commands)

| Command | Description | Requires |
|---------|-------------|----------|
| `/code-review` | Review code for issues | — |
| `/qa-gate` | Run full QA pipeline (style, analysis, tests) | — |
| `/rector-dry` | Preview Rector changes | Rector |
| `/rector-run` | Apply Rector refactoring | Rector |
| `/phpstan` | Run PHPStan analysis | PHPStan |
| `/cs-fix` | Fix code style | PHP-CS-Fixer |
| `/test-run` | Run test suite | PHPUnit/Pest |
| `/test-write` | Write tests for a class | PHPUnit/Pest |
| `/twig-review` | Review a Twig template | Twig |
| `/make-entity` | Scaffold a Doctrine entity | Doctrine |
| `/make-repository` | Scaffold a repository | Doctrine |
| `/make-service` | Create a Symfony service | Symfony |
| `/make-command` | Create a Symfony command | Symfony |
| `/symfony-upgrade` | Step-by-step upgrade guide | Upgrading |
| `/grumphp-check` | Run GrumPHP manually | GrumPHP |
| `/docker-exec` | Run commands in Docker/Compose | Docker (wizard) |
| `/migration-review` | Review Doctrine migrations | Doctrine |
| `/api-security-review` | Review API security | HTTP API (wizard) |

### `.claude/agents/` (sub-agents)

| Agent | Description |
|-------|-------------|
| `php-architect` | Architecture & design decisions |
| `test-writer` | Write comprehensive tests |
| `refactor-agent` | Automated code refactoring with Rector |
| `symfony-upgrader` | Guide Symfony upgrades step by step |
| `doctrine-expert` | Doctrine ORM, DQL, migrations |
| `laravel-expert` | Laravel conventions and structure |
| `security-auditor` | Security review checklist (OWASP-minded) |
| `performance-php` | Performance profiling and optimisation |

### `.claude/skills/` (optional)

YAML-frontmatter skills used by Claude Code workflows — generated only if selected in the wizard (e.g. `php-quality`, `php-testing`, `rector-workflow`, `api-security`, `doctrine-data`, `docker-dev`, `ci-pipeline`, `observability`, `mcp-tools`).

### `examples/` (optional)

Sample workflows and prompts under `examples/` (README, workflows, prompts) when that generation step is enabled.

## Question Tree

The wizard auto-detects your stack from `composer.json` and pre-fills answers. You confirm or adjust each one:

```
── Project Settings ──
  ? PHP version: [8.3]
  ? Framework: [symfony]
  ? Current Symfony version: [8.0]
  ? Are you upgrading Symfony? [N]

── Quality Tools ──
  ? Rector: [Y]
    ? Rector version: [2]
  ? PHPStan: [Y]
    ? PHPStan level: [8]
  ? PHP-CS-Fixer: [Y]
  ? GrumPHP: [N]

── Testing ──
  ? Testing framework: [phpunit]

── Architecture & Stack ──
  ? Twig templates: [Y]
  ? Doctrine ORM: [Y]
  ? Architecture style: [standard]
  ? Has REST / GraphQL API?: [N]
  ? Command runner for QA: [composer]

── Files to Generate ──
  ? Generate CLAUDE.md: [Y]
  ? Generate .claude/commands/: [Y]
    ? Select commands: (all)
  ? Generate .claude/agents/: [N]
```

## Auto-detection

The wizard reads your `composer.json` and detects:

- **PHP version** from the `require.php` constraint
- **Framework** (Symfony, Laravel, Slim, Yii) from installed packages
- **Framework version** from the version constraint
- **Quality tools**: Rector, PHPStan, PHP-CS-Fixer, GrumPHP, Twig-CS-Fixer
- **Testing**: PHPUnit, Pest
- **Doctrine, Twig, API Platform** from installed packages
- **Rector version** (1.x vs 2.x)
- **PHPStan level** from `phpstan.neon`
- **Makefile** presence for command runner detection

## Prompt navigation

- Single-choice prompts support both:
  - arrow keys (`↑` / `↓`) + `Enter` in interactive terminals
  - numeric input (`1`, `2`, `3`, ...)

## Related packages

- [nowo-tech/php-quality-tools](https://packagist.org/packages/nowo-tech/php-quality-tools) — Pre-configured Rector, PHP-CS-Fixer and Twig-CS-Fixer
- [nowo-tech/code-review-guardian](https://packagist.org/packages/nowo-tech/code-review-guardian) — AI-powered automated code review

## Documentation

- [Installation](docs/INSTALLATION.md)
- [Configuration](docs/CONFIGURATION.md)
- [Usage](docs/USAGE.md)
- [Contributing](docs/CONTRIBUTING.md)
- [Changelog](docs/CHANGELOG.md)
- [Upgrading](docs/UPGRADING.md)
- [Release](docs/RELEASE.md)
- [Security](docs/SECURITY.md)
- [Engram](docs/ENGRAM.md)

## Tests and coverage

- **Tests:** PHPUnit (`tests/Unit`, `tests/Integration`).
- **PHP (lines):** **100%** on covered `src/` paths (refresh with `make test-coverage`; the Makefile prints **Global PHP coverage (Lines)** at the end).
- **TS/JS:** N/A
- **Python:** N/A

Coverage configuration excludes interactive wizard orchestration files listed in `phpunit.xml.dist` (see file comments there).

## License

MIT — see [LICENSE](LICENSE).
