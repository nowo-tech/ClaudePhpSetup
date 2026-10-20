# Contributing

## Table of contents

- [Code of Conduct](#code-of-conduct)
- [Getting started](#getting-started)
- [Running QA](#running-qa)
- [Areas where contributions are most welcome](#areas-where-contributions-are-most-welcome)
  - [New templates / sections](#new-templates--sections)
  - [Template files](#template-files)
  - [Adding a new CLAUDE.md section](#adding-a-new-claudemd-section)
  - [Adding a new slash command](#adding-a-new-slash-command)
  - [Adding a new agent](#adding-a-new-agent)
- [Code standards](#code-standards)
- [Cursor Agent](#cursor-agent)
- [Commit messages](#commit-messages)
- [Git hooks (REQ-GIT-001)](#git-hooks-req-git-001)

## Code of Conduct

This project follows the [Contributor Covenant Code of Conduct](../CODE_OF_CONDUCT.md). By participating, you are expected to uphold it. Please report unacceptable behavior to **hectorfranco@nowo.tech**.

## Getting started

```bash
git clone https://github.com/nowo-tech/ClaudePhpSetup.git
cd ClaudePhpSetup
make build
make install
make setup-hooks
```

## Running QA

Run `make igor` for the Igor FrankenPHP worker-state audit (REQ-CS-008).


```bash
make qa          # cs-check + phpstan + tests
make test        # tests only
make rector-dry  # preview Rector changes
```

## Areas where contributions are most welcome

### New templates / sections
The most impactful contributions are improvements to the generated markdown content:

- **New framework support** — add detection and templates for Slim, Yii, CodeIgniter
- **New tool support** — add templates for Psalm, Infection, Behat, PHPArkitect
- **Richer command content** — make slash commands more actionable and specific
- **Agent improvements** — better agent descriptions and responsibility definitions

### Template files

Templates are PHP classes with static methods returning strings:

- `src/Template/ClaudeMd/` — sections for `CLAUDE.md`
- `src/Template/Commands/CommandTemplates.php` — one entry per slash command
- `src/Template/Agents/AgentTemplates.php` — one entry per agent

### Adding a new CLAUDE.md section

1. Create `src/Template/ClaudeMd/MySection.php` with a `render(ProjectConfig $config): string` method
2. Add the call in `src/Generator/ClaudeMdGenerator.php`
3. Add a corresponding `ProjectConfig` property if a new question is needed
4. Add the question to `src/Question/QuestionTree.php`
5. Add detection logic in `src/Detector/ProjectDetector.php` if auto-detectable
6. Write tests in `tests/Unit/Generator/ClaudeMdGeneratorTest.php`

### Adding a new slash command

1. Add the template as a private method in `CommandTemplates::all()` in `src/Template/Commands/CommandTemplates.php`
2. Register it in `QuestionTree::getAvailableCommands()` with an appropriate condition
3. Write a test case

### Adding a new agent

1. Add the template in `AgentTemplates::all()` in `src/Template/Agents/AgentTemplates.php`
2. Register it in `QuestionTree::getAvailableAgents()` with an appropriate condition
3. Ensure the frontmatter has `name` and `description` fields

## Code standards

- `declare(strict_types=1)` in every file
- `final class` for all concrete classes
- All properties and methods fully typed
- PHPStan level 8 compliance
- PHP-CS-Fixer `@Symfony` ruleset

## Cursor Agent

Contributors using Cursor Agent on this repository should enable these user-level settings (REQ-IDE-006):

1. **Submit with Ctrl + Enter** (macOS: **⌘ + Enter**) — Cursor Settings → Agents / Chat → Submit with Ctrl + Enter. Enter inserts a newline; Ctrl/⌘+Enter submits the prompt.
2. **Queue follow-ups while the agent is running** — in your user `settings.json` (Linux `~/.config/Cursor/User/settings.json`, Windows `%APPDATA%\Cursor\User\settings.json`, macOS `~/Library/Application Support/Cursor/User/settings.json`):

```json
{
  "cursor.composer.queueMessageDefaultBehavior": "queue"
}
```

Do not use `"steer"` as the default: it interrupts the running agent turn as soon as the follow-up is sent.

## Commit messages

```
feat: add Pest architecture testing section to CLAUDE.md
fix: correct Symfony 7.x deprecation list in upgrade section
docs: improve make-entity command template
test: add missing tests for DoctrineSection
```
## Git hooks (REQ-GIT-001)

Do **not** add `Co-authored-by: Cursor` or `cursoragent@cursor.com` trailers, nor `Co-Authored-By: Claude …` / `noreply@anthropic.com` ones, to commit messages.

```bash
make setup-hooks
make check-no-cursor-coauthor
```

`make setup-hooks` installs `.githooks/commit-msg` (or sets `core.hooksPath` to `.githooks`). Run it once per clone before your first commit.
If CI fails because trailers are already on the remote, see [GITHUB_CI.md](GITHUB_CI.md) (REQ-GIT-001) and run `make strip-cursor-coauthor-from-history` before `git push --force-with-lease`.
