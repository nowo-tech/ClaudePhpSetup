# Installation

## Requirements

- PHP 8.1 or higher
- Composer 2.x

## Install

```bash
composer require --dev nowo-tech/claude-php-setup
```

This registers a `vendor/bin/claude-php-setup` executable and a `composer setup` script alias.

## Run the wizard

```bash
vendor/bin/claude-php-setup
```

Or via Composer:

```bash
composer setup
```

## Options

| Option | Description |
|--------|-------------|
| `--overwrite` / `-f` | Overwrite files that already exist |
| `--dir=<path>` / `-d <path>` | Target a different project directory |

## Generated file locations

All files are written to the **project root** (directory containing `composer.json`):

```
your-project/
├── CLAUDE.md                       ← main Claude instructions
└── .claude/
    ├── commands/
    │   ├── code-review.md
    │   ├── rector-dry.md
    │   ├── phpstan.md
    │   └── ...
    └── agents/
        ├── php-architect.md
        ├── test-writer.md
        └── ...
```

## Re-running

Running the wizard again without `--overwrite` skips existing files, so you can safely add new commands or agents without touching files you've already customised.

To regenerate everything from scratch:

```bash
vendor/bin/claude-php-setup --overwrite
```
