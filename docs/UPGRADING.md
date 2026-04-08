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

### 1.0.x → 1.1.x

- **No breaking changes** to the Composer plugin API or CLI flags.
- New optional outputs: **`.claude/skills/`**, **`examples/`**, extra **slash commands** and **sub-agents** (see [CHANGELOG.md](CHANGELOG.md)).
- After `composer update`, run `vendor/bin/claude-php-setup` again and enable the new options if you want those files. Use `--overwrite` only after reviewing diffs in version control.

### General

When upgrading across major versions, follow the changelog and re-run the wizard with `--overwrite` after reviewing generated file diffs.
