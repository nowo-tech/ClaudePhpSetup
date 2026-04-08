<?php

declare(strict_types=1);

namespace NowoTech\ClaudePhpSetup\Template\ClaudeMd;

use NowoTech\ClaudePhpSetup\Question\ProjectConfig;

/**
 * Represents the FrameworkSection class.
 */
final class FrameworkSection
{
    /**
     * Handles the render operation.
     */
    public static function render(ProjectConfig $config): string
    {
        return match ($config->framework) {
            'symfony' => self::symfony($config),
            'laravel' => self::laravel($config),
            'slim'    => self::slim(),
            default   => '',
        };
    }

    /**
     * Handles the symfony operation.
     */
    private static function symfony(ProjectConfig $config): string
    {
        $version      = $config->frameworkVersion ?? '7.x';
        $majorVersion = (int) explode('.', $version)[0];
        $isModern     = $majorVersion >= 6;

        $attributesNote = $isModern
            ? '- Use **PHP attributes** for routing, validation, serialization — no YAML/XML annotation files'
            : '- PHP attributes available from Symfony 5.2+; YAML config still common in older versions';

        $asMapNote = $majorVersion >= 7
            ? '- Use `#[AsEventListener]`, `#[AsMessageHandler]`, `#[AsCommand]` attributes instead of YAML service tags'
            : '';

        $asMapBlock = $asMapNote !== '' ? "\n{$asMapNote}" : '';

        return <<<MD
        ## Symfony Best Practices

        ### Dependency Injection
        - Use **constructor injection** exclusively — no setter injection or property injection
        - Services are **private by default** — never fetch from the container directly
        - Use `#[Autowire]` attribute for non-autowireable arguments (scalars, tagged iterators)
        - Bind parameters in `services.yaml` for project-wide scalar injection
        {$attributesNote}{$asMapBlock}

        ### Controllers
        - Controllers must be **thin** — no business logic, only orchestration
        - Use **AbstractController** for convenience methods (`render`, `redirectToRoute`, etc.)
        - Prefer **`#[MapRequestPayload]`** and **`#[MapQueryParameter]`** (Symfony 6.3+) over manual request parsing
        - Return typed responses — avoid raw `Response` when `JsonResponse` or `RedirectResponse` is more specific

        ### Services & Domain Logic
        - One class = one responsibility
        - Domain services go in `src/Service/` or `src/Domain/`
        - Never inject `Request` into a service — pass only the data the service needs
        - Use **Events** (`EventDispatcherInterface`) for cross-cutting concerns

        ### Routing
        - Define routes with `#[Route]` attribute on controllers
        - Group routes by resource with `#[Route('/prefix')]` on the controller class
        - Use **route names** everywhere — never hardcode URLs

        ### Configuration
        - Use `config/services.yaml` for service configuration
        - Use environment variables (`.env`) for infrastructure settings
        - Never put secrets in `config/` — use `secrets/` or vault

        ### Console Commands
        - Extend `Command` or use `#[AsCommand]` with `Invokable` style (Symfony 6.1+)
        - Commands should delegate to services — no business logic in `execute()`

        ### Events & Messaging
        - Use **Symfony Messenger** for async operations
        - Use **Event Dispatcher** for synchronous domain events
        - Message handlers: one handler per message class
        MD;
    }

    /**
     * Handles the laravel operation.
     */
    private static function laravel(ProjectConfig $config): string
    {
        $version = $config->frameworkVersion ?? '11';

        return <<<MD
        ## Laravel {$version} Best Practices

        ### Architecture
        - Use **Service classes** for business logic — keep controllers thin
        - Use **Form Requests** for validation (`php artisan make:request`)
        - Use **Resources** for API responses (`php artisan make:resource`)
        - Use **Policies** for authorization (`php artisan make:policy`)

        ### Eloquent
        - Use **Query Scopes** for reusable query logic
        - Use **Observers** for model lifecycle hooks
        - Avoid `all()` and `get()` on large tables — always paginate or chunk
        - Use **eager loading** (`with()`) to prevent N+1 queries

        ### Dependency Injection
        - Bind interfaces in `AppServiceProvider` — depend on abstractions not implementations
        - Use **constructor injection** in services and controllers
        - Avoid `app()` helper in production code — use DI instead

        ### Routing
        - Group routes logically with `Route::group()` or `Route::prefix()`
        - Use **named routes** — never hardcode URLs
        - Apply **middleware** at the route group level

        ### Configuration
        - All env values go through `config/` — never call `env()` outside config files
        - Use **typed config** where possible

        ### Queues & Jobs
        - Use **Jobs** for async work (`php artisan make:job`)
        - Define `\$tries` and `\$timeout` on every job
        - Use **Batches** for parallel job groups

        ### Database & migrations
        - Prefer **migrations** (`php artisan make:migration`) over manual SQL; review `down()` for reversibility
        - Use **factories & seeders** for tests and local data — never rely on production dumps in dev by default
        - Add **indexes** in migrations for foreign keys and columns used in `WHERE` / `ORDER BY`

        ### HTTP & APIs
        - Version routes (`/api/v1/...`) when breaking changes are expected
        - Use **Form Requests** and **API Resources** for consistent validation and JSON shapes
        - Apply **throttle** middleware to brute-forceable routes (login, password reset)

        ### Testing
        - Use `RefreshDatabase` or `DatabaseTransactions` traits appropriately in feature tests
        - Prefer **HTTP tests** for endpoints (`\$this->postJson(...)`) over testing controllers in isolation when routing matters
        MD;
    }

    /**
     * Handles the slim operation.
     */
    private static function slim(): string
    {
        return <<<'MD'
        ## Slim Framework Best Practices

        ### Architecture
        - Use **PSR-7** request/response objects — never access superglobals directly
        - Register routes in dedicated route files, not `index.php`
        - Use **Dependency Injection Container** (PHP-DI or Pimple) for all services
        - Keep **bootstrap** (`public/index.php`) minimal — wiring only

        ### Routing & middleware
        - Register routes in dedicated files; group by prefix and middleware (e.g. `/api` vs `/admin`)
        - Apply **PSR-15** middleware for cross-cutting concerns (auth, JSON body parsing, CORS)
        - Order matters: last registered = first executed for middleware stacks

        ### Controllers / actions
        - Prefer **single-action** invokable classes or small closures that delegate to services
        - Controllers return **Response** with correct `Content-Type` and status codes

        ### Error Handling
        - Register a custom `ErrorMiddleware` handler
        - Return proper HTTP status codes with JSON error bodies for APIs — **no stack traces** to clients in production

        ### Security
        - Validate input with **respect/validation** or Symfony Validator via bridge — reject unknown fields when appropriate
        - Use **HTTPS** termination at the edge; forward proto headers only when trusted

        ### Testing
        - Use **PHPUnit** with a real `App` instance or `Request`/`Response` cycle tests for HTTP behaviour
        - Mock external HTTP at boundaries — not domain value objects
        MD;
    }
}
