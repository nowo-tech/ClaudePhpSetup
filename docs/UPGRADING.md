# Upgrading

## General process

1. Update the package:
   ```bash
   composer update nowo-tech/claude-php-setup
   ```

2. Read [CHANGELOG.md](CHANGELOG.md) for breaking changes and new templates.

3. Re-run the wizard if you want refreshed files:
   ```bash
   vendor/bin/claude-php-setup --overwrite
   ```

## Version-specific notes

### 1.1.2 → 1.1.3

- No breaking changes.
- Interactive single-choice redraw in TTY was fixed to avoid duplicated prompt lines while using arrow keys.
- Confirm prompts (`[y/N]`) now accept single-key `y`/`n` reliably in interactive mode.
- Wizard labels are clearer: Rector shows `version 1/2`, PHPStan shows `level 0..9/max`.

### 1.1.1 → 1.1.2

- No breaking changes.
- Symfony choices now include `7.3`, `7.4`, and `8.0`.
- Symfony upgrade prompt flow changed to:
  1. `Current Symfony version`
  2. `Are you upgrading Symfony to a newer version?`
  3. `Upgrading to which Symfony version?` (only higher versions are offered)
- In interactive terminals, single-choice prompts now support arrow navigation (`↑`/`↓` + Enter) in addition to numeric input.

### 1.1.0 → 1.1.1

- No breaking changes.
- This patch ensures `vendor/bin/claude-php-setup` is available after install/update in consumer projects.
- If the binary was missing before, run:
  ```bash
  composer update nowo-tech/claude-php-setup
  ```

### 1.0.x → 1.1.x

- **No breaking changes** to the Composer plugin API or CLI flags.
- New optional outputs: **`.claude/skills/`**, **`examples/`**, extra **slash commands** and **sub-agents** (see [CHANGELOG.md](CHANGELOG.md)).
- After `composer update`, run `vendor/bin/claude-php-setup` again and enable the new options if you want those files. Use `--overwrite` only after reviewing diffs in version control.

### General

When upgrading across major versions, follow the changelog and re-run the wizard with `--overwrite` after reviewing generated file diffs.
