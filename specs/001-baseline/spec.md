# Feature Specification: ClaudePhpSetup baseline (100% code coverage)

**Feature Branch**: `001-baseline`  
**Status**: Active  

**Package**: `nowo-tech/claude-php-setup`  
**Code inventory**: [`code-inventory.md`](code-inventory.md)

---

## Summary

Composer **plugin** that scaffolds **Claude Code** project assets (`CLAUDE.md`, agents, commands, skills, examples) for PHP/Symfony repos via interactive CLI after `composer install`.

---

## User Scenarios

### US-01 — Post-install scaffold (P1)

**Given** plugin registered in `composer.json`, **When** `post-install-cmd`/`post-update-cmd` runs, **Then** `InteractiveSetup` prompts or skips based on existing files.

### US-02 — Project detection (P1)

**Given** a target directory, **When** setup starts, **Then** `ProjectDetector` identifies framework, testing stack, and quality tools.

### US-03 — CLAUDE.md generation (P1)

**Given** answered `QuestionTree`, **When** generation runs, **Then** `ClaudeMdGenerator` composes sections from `Template/ClaudeMd/*`.

### US-04 — Asset file generation (P2)

**Given** selected templates, **When** `FileGenerator` runs, **Then** agents/commands/skills/examples are written without overwriting user edits where configured.

---

## Requirements

### Plugin entry

- **FR-PLUGIN-001**: `Plugin` — activate/deactivate, subscribe to Composer script events, delegate to CLI.

### CLI

- **FR-CLI-001**: `Console` — argument parsing and IO wrapper.
- **FR-CLI-002**: `InteractiveSetup` — wizard orchestration.

### Detection & config

- **FR-DET-001**: `ProjectDetector` — inspect composer.lock, config files, directory layout.
- **FR-CFG-001**: `ProjectConfig`, `QuestionTree` — structured answers driving templates.

### Generators

- **FR-GEN-001**: `ClaudeMdGenerator` — assemble markdown from sections.
- **FR-GEN-002**: `FileGenerator` — write template files to project tree.

### Templates — CLAUDE.md sections

- **FR-TPL-MD-001**: Base, Framework, Doctrine, Twig, Testing, QualityTools, Operational, Upgrade sections.

### Templates — assets

- **FR-TPL-AGT-001**: `AgentTemplates`.
- **FR-TPL-CMD-001**: `CommandTemplates`.
- **FR-TPL-SKL-001**: `SkillTemplates`.
- **FR-TPL-EX-001**: `ExampleTemplates`.

---

## Success Criteria

- **SC-001**: **20/20** `src/` files mapped.
- **SC-002**: PHPUnit covers plugin and generators.

---

## Explicit non-goals

- Runtime Symfony bundle integration.
- Modifying application business logic.

---

## Validation

PHPUnit, PHPStan, inventory audit.
