# Code inventory — 100% traceability

**Baseline spec**: [`spec.md`](spec.md)  
**Package**: `nowo-tech/claude-php-setup`  
**Last audited**: 2026-07-07

## Plugin entry

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Plugin.php` | Composer plugin | FR-PLUGIN-001 |

## CLI

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Cli/Console.php` | CLI wrapper | FR-CLI-001 |
| `Cli/InteractiveSetup.php` | Setup wizard | FR-CLI-002 |

## Detection & questions

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Detector/ProjectDetector.php` | Project stack detection | FR-DET-001 |
| `Question/ProjectConfig.php` | Answer model | FR-CFG-001 |
| `Question/QuestionTree.php` | Question flow | FR-CFG-001 |

## Generators

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Generator/ClaudeMdGenerator.php` | CLAUDE.md builder | FR-GEN-001 |
| `Generator/FileGenerator.php` | Asset writer | FR-GEN-002 |

## Templates — CLAUDE.md sections

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Template/ClaudeMd/BaseSection.php` | Base section | FR-TPL-MD-001 |
| `Template/ClaudeMd/FrameworkSection.php` | Framework section | FR-TPL-MD-001 |
| `Template/ClaudeMd/DoctrineSection.php` | Doctrine section | FR-TPL-MD-001 |
| `Template/ClaudeMd/TwigSection.php` | Twig section | FR-TPL-MD-001 |
| `Template/ClaudeMd/TestingSection.php` | Testing section | FR-TPL-MD-001 |
| `Template/ClaudeMd/QualityToolsSection.php` | Quality tools | FR-TPL-MD-001 |
| `Template/ClaudeMd/OperationalSection.php` | Operations section | FR-TPL-MD-001 |
| `Template/ClaudeMd/UpgradeSection.php` | Upgrade section | FR-TPL-MD-001 |

## Templates — project assets

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Template/Agents/AgentTemplates.php` | Agent stubs | FR-TPL-AGT-001 |
| `Template/Commands/CommandTemplates.php` | Command stubs | FR-TPL-CMD-001 |
| `Template/Skills/SkillTemplates.php` | Skill stubs | FR-TPL-SKL-001 |
| `Template/Examples/ExampleTemplates.php` | Example stubs | FR-TPL-EX-001 |

## Coverage summary

| Category | Files | Mapped |
| --- | ---: | ---: |
| Plugin entry | 1 | 1 |
| CLI | 2 | 2 |
| Detection & questions | 3 | 3 |
| Generators | 2 | 2 |
| CLAUDE.md sections | 8 | 8 |
| Asset templates | 4 | 4 |
| **Total production sources** | **20** | **20** |
