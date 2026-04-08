# Usage Examples

## Symfony 7 project with full stack

```
── Project Settings ──
  ? PHP version: 8.3
  ? Framework: symfony
  ? Symfony version: 7.2
  ? Are you upgrading Symfony? N

── Quality Tools ──
  ? Rector: Y  →  version: 2
  ? PHPStan: Y  →  level: 8
  ? PHP-CS-Fixer: Y
  ? GrumPHP: Y
  ? Twig-CS-Fixer: Y

── Testing ──
  ? Testing framework: phpunit

── Architecture & Stack ──
  ? Twig templates: Y
  ? Doctrine ORM: Y
  ? Architecture style: ddd
  ? Has API: Y  →  api-platform
  ? Command runner: make

── Files to Generate ──
  ? Generate CLAUDE.md: Y
  ? Generate .claude/commands/: Y  →  all
  ? Generate .claude/agents/: Y  →  php-architect, test-writer, refactor-agent, doctrine-expert
```

**Generated files:**

```
CLAUDE.md
.claude/commands/code-review.md
.claude/commands/rector-dry.md
.claude/commands/rector-run.md
.claude/commands/phpstan.md
.claude/commands/cs-fix.md
.claude/commands/test-run.md
.claude/commands/test-write.md
.claude/commands/twig-review.md
.claude/commands/make-entity.md
.claude/commands/make-repository.md
.claude/commands/make-service.md
.claude/commands/make-command.md
.claude/commands/grumphp-check.md
.claude/agents/php-architect.md
.claude/agents/test-writer.md
.claude/agents/refactor-agent.md
.claude/agents/doctrine-expert.md
```

---

## Symfony upgrade project (6.4 → 7.2)

```
── Project Settings ──
  ? PHP version: 8.3
  ? Framework: symfony  →  version: 7.2
  ? Are you upgrading Symfony? Y
    ? Upgrading from which version? 6.4

── Quality Tools ──
  ? Rector: Y  →  version: 2
  ...
```

Adds to generated files:
- `CLAUDE.md` includes a full **Symfony Upgrade** section with phases and checklist
- `.claude/commands/symfony-upgrade.md` — step-by-step upgrade slash command
- `.claude/agents/symfony-upgrader.md` — dedicated upgrade sub-agent

---

## Laravel project with Pest

```
── Project Settings ──
  ? PHP version: 8.3
  ? Framework: laravel  →  version: 11

── Quality Tools ──
  ? Rector: Y  →  version: 2
  ? PHPStan: Y  →  level: 7
  ? PHP-CS-Fixer: Y

── Testing ──
  ? Testing framework: pest
```

---

## Minimal setup (no framework)

```
  ? PHP version: 8.2
  ? Framework: none
  ? Rector: N
  ? PHPStan: Y  →  level: 5
  ? PHP-CS-Fixer: Y
  ? Testing framework: phpunit
  ? Generate CLAUDE.md: Y
  ? Generate .claude/commands/: Y  →  code-review, phpstan, cs-fix, test-run
  ? Generate .claude/agents/: N
```

---

## Using the generated slash commands

In Claude Code, invoke commands with `/`:

```
/rector-dry
```
→ Claude runs Rector dry-run and explains all proposed changes.

```
/test-write UserService::register
```
→ Claude writes complete PHPUnit tests for the `register` method.

```
/make-entity Product name:string price:decimal active:boolean
```
→ Claude generates a Doctrine entity with proper attributes, named constructor, and typed properties.

```
/symfony-upgrade
```
→ Claude provides the current step in the upgrade checklist, checks the codebase state, and suggests next actions.

## Using sub-agents

In Claude Code, mention agents with `@`:

```
@php-architect should I use a Domain Event or a Symfony Event here?
```

```
@test-writer write tests for the ProductRepository
```

```
@doctrine-expert this query is slow, how do I optimise it?
```
