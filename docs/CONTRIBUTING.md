# Contributing

## Getting started

```bash
git clone https://github.com/nowo-tech/ClaudePhpSetup.git
cd ClaudePhpSetup
make build
make install
make setup-hooks
```

## Running QA

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

## Commit messages

```
feat: add Pest architecture testing section to CLAUDE.md
fix: correct Symfony 7.x deprecation list in upgrade section
docs: improve make-entity command template
test: add missing tests for DoctrineSection
```
