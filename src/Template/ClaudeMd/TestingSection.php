<?php

declare(strict_types=1);

namespace NowoTech\ClaudePhpSetup\Template\ClaudeMd;

use NowoTech\ClaudePhpSetup\Question\ProjectConfig;

use function in_array;

final class TestingSection
{
    public static function render(ProjectConfig $config): string
    {
        if ($config->testingFramework === 'none') {
            return '';
        }

        $sections = ['## Testing'];

        if (in_array($config->testingFramework, ['phpunit', 'both'], true)) {
            $sections[] = self::phpUnit($config);
        }

        if (in_array($config->testingFramework, ['pest', 'both'], true)) {
            $sections[] = self::pest($config);
        }

        $sections[] = self::generalPrinciples($config);

        if ($config->hasDoctrine) {
            $sections[] = self::doctrine();
        }

        return implode("\n\n", $sections);
    }

    private static function phpUnit(ProjectConfig $config): string
    {
        $runner = in_array($config->commandRunner, ['make', 'both'], true) ? 'make' : 'composer';

        $framework = '';
        if ($config->framework === 'symfony') {
            $framework = <<<'MD'

            **Symfony Testing:**
            - Use `WebTestCase` for functional controller tests (real HTTP cycle)
            - Use `KernelTestCase` for integration tests with container
            - Use `ApiTestCase` (from ApiTestCase bundle) for API endpoint testing
            - Use `MockObject` from PHPUnit or `prophecy-phpspec` for mocking services
            MD;
        }

        return <<<MD
        ### PHPUnit
        Config: `phpunit.xml.dist`

        **Commands:**
        - `{$runner} test` — run full suite
        - `{$runner} test -- --filter=ClassName` — run specific class
        - `{$runner} test -- --filter=ClassName::testMethod` — run specific test
        - `{$runner} test -- --group=unit` — run by group

        **Test types:**
        - `tests/Unit/` — pure unit tests, no I/O, no framework, fast
        - `tests/Integration/` — tests with database, cache, real services
        - `tests/Functional/` — full HTTP request/response cycle{$framework}

        **Writing tests:**
        - Test class name must end in `Test`
        - Test method name must start with `test` or use `#[Test]` attribute
        - One assertion per test (ideally) — or group related assertions with a message
        - Use `#[DataProvider]` for parametrised tests (PHPUnit 10+)
        - Use `setUp()` for shared fixtures, `tearDown()` for cleanup
        MD;
    }

    private static function pest(ProjectConfig $config): string
    {
        $runner = in_array($config->commandRunner, ['make', 'both'], true) ? 'make' : 'composer';

        return <<<MD
        ### Pest
        Config: `phpunit.xml.dist` (Pest runs on top of PHPUnit)

        **Commands:**
        - `{$runner} test` — run full suite
        - `{$runner} test -- --filter="test name"` — run specific test
        - `{$runner} test -- --group=unit` — run by group

        **Test structure:**
        ```php
        describe('UserService', function () {
            it('creates a user with valid data', function () {
                \$user = \$this->userService->create('test@example.com');
                expect(\$user->getEmail())->toBe('test@example.com');
            });

            dataset('invalid emails', [
                'not-an-email',
                '@missing-local.com',
                'missing-at-sign.com',
            ]);

            it('rejects invalid email', function (string \$email) {
                expect(fn () => \$this->userService->create(\$email))
                    ->toThrow(InvalidArgumentException::class);
            })->with('invalid emails');
        });
        ```

        **Pest plugins in use** (check `composer.json`):
        - `pestphp/pest-plugin-arch` — architecture testing
        - `pestphp/pest-plugin-laravel` — Laravel helpers (if applicable)
        MD;
    }

    private static function generalPrinciples(ProjectConfig $config): string
    {
        $architectureTest = '';
        if ($config->framework === 'symfony' && in_array($config->testingFramework, ['pest', 'both'], true)) {
            $architectureTest = <<<'MD'

            **Architecture tests (Pest arch plugin):**
            ```php
            arch('controllers should not use repositories directly')
                ->expect('App\Controller')
                ->not->toUse('App\Repository');

            arch('services should be final')
                ->expect('App\Service')
                ->toBeFinal();
            ```
            MD;
        }

        return <<<MD
        ### Testing Principles

        - **Arrange / Act / Assert** — structure every test in three clear phases
        - **Test behaviour, not implementation** — test what the class does, not how
        - **No `sleep()` in tests** — use mocked time (`ClockInterface`)
        - **No real HTTP calls** — mock HTTP clients (`MockHandler` for Guzzle, etc.)
        - **No real filesystem** — use in-memory streams or temp dirs with cleanup
        - **Deterministic** — tests must always produce the same result
        - **Fast unit tests** — unit tests should run in milliseconds
        - **Isolated** — each test must clean up after itself{$architectureTest}
        MD;
    }

    private static function doctrine(): string
    {
        return <<<'MD'
        ### Database Tests

        - Use **in-memory SQLite** for unit tests when possible
        - Use a **dedicated test database** for integration tests (never use production DB)
        - Use **fixtures** (`doctrine/data-fixtures`) for test data — not raw SQL
        - Use **transactions + rollback** between tests for isolation
        - Reset sequences with `TRUNCATE ... RESTART IDENTITY` when needed
        - Never use `EntityManager::clear()` in production code — only in batch processing
        MD;
    }
}
