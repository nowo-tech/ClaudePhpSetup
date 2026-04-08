<?php

declare(strict_types=1);

namespace NowoTech\ClaudePhpSetup\Template\Commands;

use NowoTech\ClaudePhpSetup\Question\ProjectConfig;

use function array_filter;
use function in_array;

/**
 * Returns the markdown content for each slash command.
 */
/**
 * Represents the CommandTemplates class.
 */
final class CommandTemplates
{
    /** @return array<string, string>  key => markdown content */
    public static function all(ProjectConfig $config): array
    {
        $runner = in_array($config->commandRunner, ['make', 'both'], true) ? 'make' : 'composer';

        return array_filter([
            'code-review'         => self::codeReview($config),
            'qa-gate'             => self::qaGate($config),
            'rector-dry'          => self::rectorDry($config, $runner),
            'rector-run'          => self::rectorRun($runner),
            'phpstan'             => self::phpstan($config, $runner),
            'cs-fix'              => self::csFix($runner),
            'test-run'            => self::testRun($config, $runner),
            'test-write'          => self::testWrite($config),
            'twig-review'         => self::twigReview(),
            'make-entity'         => self::makeEntity($config),
            'make-repository'     => self::makeRepository($config),
            'make-service'        => self::makeService($config),
            'make-command'        => self::makeCommand($config),
            'symfony-upgrade'     => self::symfonyUpgrade($config),
            'grumphp-check'       => self::grumphpCheck($runner),
            'docker-exec'         => self::dockerExec($config),
            'migration-review'    => self::migrationReview($config),
            'api-security-review' => self::apiSecurityReview($config),
        ], static fn (string $s): bool => $s !== '');
    }

    /**
     * Handles the codeReview operation.
     */
    private static function codeReview(ProjectConfig $config): string
    {
        $toolChecks = [];

        if ($config->hasPhpStan) {
            $toolChecks[] = '- PHPStan level ' . $config->phpStanLevel . ' compliance';
        }
        if ($config->hasPhpCsFixer) {
            $toolChecks[] = '- PHP-CS-Fixer code style compliance';
        }
        if ($config->hasRector) {
            $toolChecks[] = '- Rector rule compliance (no pending upgrades)';
        }

        $toolChecksList = $toolChecks === [] ? '' : "\n\n**Tool compliance:**\n" . implode("\n", $toolChecks);

        return <<<MD
        Review the code changes in the current branch or the specified files for issues.

        **What to check:**
        1. `declare(strict_types=1)` at the top of every PHP file
        2. All properties, parameters and return types are explicitly declared
        3. No unused variables, imports, or dead code
        4. Single Responsibility — each class/method does one thing
        5. No magic strings or numbers — use constants or enums
        6. Error handling is appropriate and typed exceptions are used
        7. No `var_dump`, `die`, `exit`, `print_r` left in code
        8. Security: no SQL concatenation, no raw user input in HTML, no hardcoded credentials
        9. Tests exist for new functionality{$toolChecksList}

        **Output format:**
        For each issue found, report:
        - File path and line number
        - Issue description
        - Suggested fix

        If no issues found, confirm the code looks good and is ready for review.
        MD;
    }

    /**
     * Handles the qaGate operation.
     */
    private static function qaGate(ProjectConfig $config): string
    {
        $hint = match ($config->commandRunner) {
            'both'  => 'Prefer `make qa` if a Makefile exists; otherwise `composer qa`. If only one is wired, use that.',
            'make'  => 'Run `make qa` (or the documented Makefile target for full QA).',
            default => 'Run `composer qa` (or the documented Composer script for full QA).',
        };

        return <<<MD
        Run the **full QA pipeline** for this repository (style, static analysis, tests — as configured).

        **What to do:**
        1. {$hint}
        2. If there is no `qa` script, run the project's documented sequence (often: `cs-check` → `phpstan` → `test`).
        3. Stop at the **first failing step**, fix the root cause, then re-run from the start.

        **Output:** Summarize which commands ran, pass/fail, and the minimal next fix for any failure.
        MD;
    }

    /**
     * Handles the rectorDry operation.
     */
    private static function rectorDry(ProjectConfig $config, string $runner): string
    {
        $configNote = $config->rectorVersion === '2'
            ? 'Rector 2.x config uses `RectorConfig::configure()` builder pattern.'
            : 'Rector 1.x config uses `$rectorConfig->sets([...])` style.';

        return <<<MD
        Run Rector in dry-run mode to preview what changes would be made without applying them.

        Execute: `{$runner} rector-dry`

        **Interpreting the output:**
        - `[CHANGED]` — file would be modified
        - Lines starting with `-` (red) — code that would be removed
        - Lines starting with `+` (green) — code that would be added

        **After reviewing:**
        - If changes look correct: run `/rector-run` to apply them
        - If a rule causes unwanted changes: add the file to `skip` in `rector.php`
        - If unsure about a change: ask about the specific Rector rule shown

        {$configNote}

        **Config file:** `rector.php`
        MD;
    }

    /**
     * Handles the rectorRun operation.
     */
    private static function rectorRun(string $runner): string
    {
        return <<<MD
        Apply Rector refactoring to the codebase.

        **Pre-flight checklist:**
        1. Run `{$runner} rector-dry` first and review all changes
        2. Ensure all tests pass before running: `{$runner} test`
        3. Commit or stash any work in progress

        Execute: `{$runner} rector`

        **After applying:**
        1. Run `{$runner} test` — verify nothing broke
        2. Run `{$runner} phpstan` — check for new type errors
        3. Review the diff with `git diff`
        4. Commit with a descriptive message: `refactor: apply Rector rule <rule-name>`

        **If something breaks:**
        1. `git diff` — identify the problematic change
        2. Add the file or rule to `skip` in `rector.php`
        3. Revert: `git checkout -- <file>`
        4. Re-run Rector
        MD;
    }

    /**
     * Handles the phpstan operation.
     */
    private static function phpstan(ProjectConfig $config, string $runner): string
    {
        return <<<MD
        Run PHPStan static analysis at level {$config->phpStanLevel}.

        Execute: `{$runner} phpstan`

        **Interpreting results:**
        Each error shows: `FILE:LINE - ERROR_MESSAGE`

        **Fix priorities:**
        1. **Type errors** (missing types, wrong types) — add type declarations
        2. **Undefined variables** — fix variable names or add initialization
        3. **Nullable type violations** — add null checks or use null-safe `?->`
        4. **Dead code** — remove or explain with a comment

        **When you cannot fix the error:**
        - Third-party library issue: add a PHPStan extension or stub
        - Legacy code: add to `phpstan-baseline.neon` (explain in PR why)
        - False positive: use `@phpstan-ignore-next-line` with a comment explaining why

        **Never use `@phpstan-ignore` without a comment.**

        Config: `phpstan.neon` | Level: {$config->phpStanLevel}
        MD;
    }

    /**
     * Handles the csFix operation.
     */
    private static function csFix(string $runner): string
    {
        return <<<MD
        Fix PHP code style using PHP-CS-Fixer.

        **Check first (no changes):** `{$runner} cs-check`
        **Fix automatically:** `{$runner} cs-fix`

        **What gets fixed:**
        - Import ordering and unused imports removal
        - `declare(strict_types=1)` added where missing
        - Trailing commas in multi-line arrays/function calls
        - Single quotes for strings without variables
        - PHPDoc formatting and alignment
        - Blank lines between methods and after opening braces

        **After fixing:**
        1. Review changes with `git diff`
        2. Run `{$runner} test` to ensure nothing broke (style fixes should never break tests)
        3. Commit: `style: apply PHP-CS-Fixer`

        Config: `.php-cs-fixer.php`
        MD;
    }

    /**
     * Handles the testRun operation.
     */
    private static function testRun(ProjectConfig $config, string $runner): string
    {
        $framework = $config->testingFramework;

        $filterExamples = match ($framework) {
            'pest' => <<<'MD'
            - Run specific test: `vendor/bin/pest --filter="creates a user"`
            - Run specific file: `vendor/bin/pest tests/Unit/UserServiceTest.php`
            - Run by group: `vendor/bin/pest --group=unit`
            MD,
            default => <<<'MD'
            - Run specific class: `vendor/bin/phpunit --filter=UserServiceTest`
            - Run specific method: `vendor/bin/phpunit --filter=UserServiceTest::testCreatesUser`
            - Run by group: `vendor/bin/phpunit --group=unit`
            MD,
        };

        return <<<MD
        Run the test suite.

        **Full suite:** `{$runner} test`

        **Targeted runs:**
        {$filterExamples}

        **If tests fail:**
        1. Read the failure message carefully — don't guess
        2. Check which assertion failed and why
        3. Verify the test is testing the right thing (not the implementation)
        4. If the test was correct and the code changed: fix the code
        5. If the code is correct and the test is outdated: update the test

        **Debugging a failing test:**
        - Add `dump(\$variable)` temporarily (Symfony VarDumper) — remove before committing
        - Use `--stop-on-failure` to stop at first failure
        - Use `--verbose` for more output

        Testing framework: {$framework}
        MD;
    }

    /**
     * Handles the testWrite operation.
     */
    private static function testWrite(ProjectConfig $config): string
    {
        $framework = $config->testingFramework;

        $pestExample = <<<'PHP'
        describe('UserService', function () {
            beforeEach(function () {
                $this->service = new UserService(/* mocked deps */);
            });

            it('creates a user with valid email', function () {
                $user = $this->service->create('test@example.com');
                expect($user->getEmail())->toBe('test@example.com');
            });
        });
        PHP;

        $phpunitExample = <<<'PHP'
        /**
         * Represents the UserServiceTest class.
         */
        final class UserServiceTest extends TestCase
        {
            private UserService $service;

            /**
             * Handles the setUp operation.
             */
            protected function setUp(): void
            {
                $this->service = new UserService(/* mocked deps */);
            }

            /**
             * Handles the testCreatesUserWithValidEmail operation.
             */
            public function testCreatesUserWithValidEmail(): void
            {
                $user = $this->service->create('test@example.com');
                self::assertSame('test@example.com', $user->getEmail());
            }
        }
        PHP;

        $example = in_array($framework, ['pest', 'both'], true) ? $pestExample : $phpunitExample;

        return <<<MD
        Write tests for the specified class, method, or feature.

        **Usage:** `/test-write UserService::create` or `/test-write UserController`

        **Test writing guidelines:**
        1. Test **behaviour**, not implementation details
        2. Follow **Arrange / Act / Assert** structure
        3. One assertion per test (or grouped related assertions)
        4. Test the **happy path** first, then edge cases, then error cases
        5. Use descriptive test names: `testCreatesUserWithValidEmail`, `it creates a user...`
        6. Mock only external dependencies (database, HTTP, filesystem, time)
        7. Do not mock the class under test

        **Template ({$framework}):**
        ```php
        {$example}
        ```

        When writing tests, always ask:
        - What are the valid inputs and expected outputs?
        - What are the invalid inputs and expected exceptions?
        - What are the edge cases (empty, null, boundary values)?
        MD;
    }

    /**
     * Handles the twigReview operation.
     */
    private static function twigReview(): string
    {
        return <<<'MD'
        Review a Twig template for issues and best practices.

        **Usage:** `/twig-review templates/user/profile.html.twig`

        **What to check:**
        1. No PHP logic — only display logic in templates
        2. No database queries from templates
        3. `|raw` filter used safely — only for known-safe HTML
        4. Template inheritance used correctly (`{% extends %}`, `{% block %}`)
        5. Variables are typed and documented (if using Twig type hints)
        6. No hardcoded URLs — use `path()` and `url()` functions
        7. Forms rendered with `form_row()` / `form_widget()` — not manually
        8. Assets referenced with `asset()` function
        9. Translations with `|trans` filter — no hardcoded strings
        10. No XSS risk — confirm all user data is escaped

        After review, provide specific line numbers for any issues found.
        MD;
    }

    /**
     * Handles the makeEntity operation.
     */
    private static function makeEntity(ProjectConfig $config): string
    {
        if (!$config->hasDoctrine) {
            return '';
        }

        return <<<'MD'
        Scaffold a Doctrine entity following project conventions.

        **Usage:** `/make-entity User email:string name:string active:boolean`

        **Entity requirements:**
        - `declare(strict_types=1)` at the top
        - `#[ORM\Entity(repositoryClass: XxxRepository::class)]` attribute
        - `#[ORM\Table(name: 'table_name')]` attribute
        - `id` as private `?int` with `#[ORM\Id]`, `#[ORM\GeneratedValue]`, `#[ORM\Column]`
        - All properties typed and private
        - Named constructor (static factory method) — no `new Entity()` in application code
        - Getters only for business-meaningful properties
        - Business methods for state changes (not raw setters)

        **Column type mapping:**
        - `string` → `Types::STRING` with explicit `length`
        - `text` → `Types::TEXT`
        - `int` / `integer` → `Types::INTEGER`
        - `bool` / `boolean` → `Types::BOOLEAN`
        - `datetime` → `Types::DATETIME_IMMUTABLE` (prefer immutable)
        - `date` → `Types::DATE_IMMUTABLE`
        - `decimal` → `Types::DECIMAL` with `precision` and `scale`
        - `uuid` → `Types::GUID` or Symfony UID component
        MD;
    }

    /**
     * Handles the makeRepository operation.
     */
    private static function makeRepository(ProjectConfig $config): string
    {
        if (!$config->hasDoctrine) {
            return '';
        }

        return <<<'MD'
        Scaffold a Doctrine repository with typed query methods.

        **Usage:** `/make-repository UserRepository User`

        **Repository requirements:**
        - Extends `ServiceEntityRepository`
        - `declare(strict_types=1)` at the top
        - Constructor calls `parent::__construct($registry, Entity::class)`
        - Every method has typed parameters and return types
        - No raw SQL — use QueryBuilder or DQL
        - Method names are descriptive business terms

        **Method templates:**
        ```php
        /**
         * Handles the findByEmail operation.
         */
        public function findByEmail(string $email): ?User
        {
            return $this->findOneBy(['email' => $email]);
        }

        /**
         * Handles the findActiveUsers operation.
         */
        public function findActiveUsers(): array
        {
            return $this->createQueryBuilder('u')
                ->andWhere('u.active = :active')
                ->setParameter('active', true)
                ->orderBy('u.createdAt', 'DESC')
                ->getQuery()
                ->getResult();
        }

        /**
         * Handles the countByStatus operation.
         */
        public function countByStatus(Status $status): int
        {
            return (int) $this->createQueryBuilder('u')
                ->select('COUNT(u.id)')
                ->andWhere('u.status = :status')
                ->setParameter('status', $status->value)
                ->getQuery()
                ->getSingleScalarResult();
        }
        ```
        MD;
    }

    /**
     * Handles the makeService operation.
     */
    private static function makeService(ProjectConfig $config): string
    {
        if ($config->framework !== 'symfony') {
            return '';
        }

        return <<<'MD'
        Create a Symfony service following DI and SOLID principles.

        **Usage:** `/make-service UserRegistrationService`

        **Service requirements:**
        - `declare(strict_types=1)` at the top
        - `final class` — services should not be extended
        - All dependencies injected via constructor
        - No `ContainerInterface` injection — inject specific services only
        - One public method per service (for focused services) or grouped related methods
        - No static methods
        - No direct `EntityManager::flush()` in domain services — flush in controller or dedicated handler

        **Template:**
        ```php
        declare(strict_types=1);

        namespace App\Service;

        /**
         * Represents the UserRegistrationService class.
         */
        final class UserRegistrationService
        {
            /**
             * Handles the __construct operation.
             */
            public function __construct(
                private readonly UserRepository $userRepository,
                private readonly PasswordHasherInterface $passwordHasher,
                private readonly EntityManagerInterface $entityManager,
            ) {
            }

            /**
             * Handles the register operation.
             */
            public function register(string $email, string $plainPassword): User
            {
                if ($this->userRepository->findByEmail($email)) {
                    throw new UserAlreadyExistsException($email);
                }

                $user = User::create($email, $this->passwordHasher->hash($plainPassword));
                $this->entityManager->persist($user);
                $this->entityManager->flush();

                return $user;
            }
        }
        ```
        MD;
    }

    /**
     * Handles the makeCommand operation.
     */
    private static function makeCommand(ProjectConfig $config): string
    {
        if ($config->framework !== 'symfony') {
            return '';
        }

        return <<<'MD'
        Create a Symfony console command following best practices.

        **Usage:** `/make-command SendWeeklyReportCommand`

        **Command requirements:**
        - Use `#[AsCommand]` attribute with `name`, `description`
        - Extend `Command`
        - All business logic in injected services — `execute()` only orchestrates
        - Use `$io->success()`, `$io->error()`, `$io->progressBar()` for output
        - Return `Command::SUCCESS` or `Command::FAILURE`
        - Add `--dry-run` option for destructive commands

        **Template:**
        ```php
        #[AsCommand(
            name: 'app:send-weekly-report',
            description: 'Sends the weekly report to all active users',
        )]
        /**
         * Represents the SendWeeklyReportCommand class.
         */
        final class SendWeeklyReportCommand extends Command
        {
            /**
             * Handles the __construct operation.
             */
            public function __construct(
                private readonly WeeklyReportService $reportService,
            ) {
                parent::__construct();
            }

            /**
             * Handles the configure operation.
             */
            protected function configure(): void
            {
                $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Preview without sending');
            }

            /**
             * Handles the execute operation.
             */
            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                $io = new SymfonyStyle($input, $output);
                $dryRun = (bool) $input->getOption('dry-run');

                $count = $this->reportService->send(dryRun: $dryRun);
                $io->success(sprintf('%d reports %s.', $count, $dryRun ? 'would be sent' : 'sent'));

                return Command::SUCCESS;
            }
        }
        ```
        MD;
    }

    /**
     * Handles the symfonyUpgrade operation.
     */
    private static function symfonyUpgrade(ProjectConfig $config): string
    {
        if (!$config->isUpgrading) {
            return '';
        }

        $from = $config->upgradeFromVersion ?? '6.4';
        $to   = $config->frameworkVersion ?? '8.0';

        return <<<MD
        Step-by-step guide for upgrading Symfony from {$from} to {$to}.

        **Current status:** Check pending deprecations
        ```bash
        php bin/console debug:container --deprecations
        grep -r "trigger_deprecation\\|@deprecated" src/
        ```

        **Step 1: Fix all deprecations in {$from}**
        ```bash
        # Run Rector with deprecation rules
        # In rector.php add: SymfonySetList::SYMFONY_{$from}_DEPRECATIONS (replace dots with _)
        composer rector-dry
        composer rector
        composer test  # must be green
        ```

        **Step 2: Update composer.json**
        ```bash
        composer require "symfony/framework-bundle:^{$to}" --no-update
        composer update "symfony/*" --with-all-dependencies
        ```

        **Step 3: Apply {$to} Rector rules**
        ```bash
        # In rector.php add: SymfonySetList::SYMFONY_{$to}
        composer rector-dry  # review carefully
        composer rector
        ```

        **Step 4: Update configuration files**
        - Check: `vendor/symfony/symfony/UPGRADE-{$to}.md`
        - Check: `config/packages/security.yaml` for auth changes
        - Check: `config/packages/*.yaml` for renamed keys

        **Step 5: Verify**
        ```bash
        composer phpstan
        composer test
        php bin/console cache:clear
        ```

        **Resources:**
        - `vendor/symfony/symfony/UPGRADE-{$to}.md`
        - Rector Symfony: https://github.com/rectorphp/rector-symfony
        MD;
    }

    /**
     * Handles the grumphpCheck operation.
     */
    private static function grumphpCheck(string $runner): string
    {
        return <<<MD
        Run GrumPHP pre-commit checks manually without making a commit.

        **Usage:** `{$runner} grumphp` or `vendor/bin/grumphp run`

        GrumPHP runs automatically on `git commit`. Use this command to check manually before committing.

        **If a check fails:**
        1. Read the error output carefully
        2. Fix the reported issue
        3. Re-run to verify the fix
        4. Stage the fix with `git add`
        5. Commit normally

        **Config:** `grumphp.yml`
        MD;
    }

    /**
     * Handles the dockerExec operation.
     */
    private static function dockerExec(ProjectConfig $config): string
    {
        if (!$config->hasDocker) {
            return '';
        }

        return <<<'MD'
        Run commands **inside** the correct Docker/Compose service (PHP, workers, etc.).

        **Before acting:**
        1. Read `README`, `compose.yml` / `docker-compose.yml`, or `Makefile` for the canonical service names.
        2. Prefer `docker compose exec <service> <command>` over guessing container IDs.

        **Typical flows:**
        - Install dependencies: `docker compose exec <php-service> composer install`
        - Tests: `docker compose exec <php-service> composer test`
        - Shell: `docker compose exec <php-service> sh`

        **Rules:** Never bake secrets into images; use env files or CI variables.
        MD;
    }

    /**
     * Handles the migrationReview operation.
     */
    private static function migrationReview(ProjectConfig $config): string
    {
        if (!$config->hasDoctrine) {
            return '';
        }

        return <<<'MD'
        Review a **Doctrine migration** before merge/deploy.

        **Checklist:**
        1. `up()` and `down()` are symmetric when reversibility is required; irreversible steps are documented.
        2. No accidental **data loss** (DROP, TRUNCATE) without backup plan.
        3. Indexes and foreign keys match expected query patterns; avoid blocking migrations on huge tables without `ALGORITHM=INPLACE` / batching strategy when relevant.
        4. Charset/collation consistent with the rest of the schema.

        **Output:** List risks, rollback strategy, and any staging validation steps.
        MD;
    }

    /**
     * Handles the apiSecurityReview operation.
     */
    private static function apiSecurityReview(ProjectConfig $config): string
    {
        if (!$config->hasApi) {
            return '';
        }

        return <<<'MD'
        Review HTTP API changes for **security** issues.

        **Checklist:**
        1. Authentication/authorization on every non-public route; no security by obscurity.
        2. Input validation and output encoding; reject unknown fields when policy requires it.
        3. Rate limiting / abuse resistance on sensitive endpoints (login, tokens, password reset).
        4. Errors: generic to clients, detailed in server logs **without** secrets or tokens.
        5. CORS, cookies, CSRF (if session-based) configured explicitly — no `*` in production CORS by default.

        **Output:** File/line references, severity, and concrete fixes.
        MD;
    }
}
