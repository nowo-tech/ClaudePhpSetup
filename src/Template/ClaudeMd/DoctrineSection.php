<?php

declare(strict_types=1);

namespace NowoTech\ClaudePhpSetup\Template\ClaudeMd;

use NowoTech\ClaudePhpSetup\Question\ProjectConfig;

/**
 * Represents the DoctrineSection class.
 */
final class DoctrineSection
{
    /**
     * Handles the render operation.
     */
    public static function render(ProjectConfig $config): string
    {
        if (!$config->hasDoctrine) {
            return '';
        }

        return <<<'MD'
        ## Doctrine ORM

        ### Entity Design

        - Use `#[ORM\Entity]` and `#[ORM\Table]` attributes
        - Every entity must have a typed `id` property (prefer `int` or `Uuid`)
        - Use `#[ORM\Column]` with explicit `type`, `nullable`, `length`
        - Avoid `cascade: ['all']` — be explicit about what cascades
        - Use `#[ORM\Index]` for frequently queried columns

        **Preferred entity structure:**
        ```php
        #[ORM\Entity(repositoryClass: UserRepository::class)]
        #[ORM\Table(name: 'users')]
        /**
         * Represents the User class.
         */
        class User
        {
            #[ORM\Id]
            #[ORM\GeneratedValue]
            #[ORM\Column(type: Types::INTEGER)]
            private ?int $id = null;

            #[ORM\Column(type: Types::STRING, length: 180, unique: true)]
            private string $email;

            // Named constructor — entities should never be constructed with `new`
            /**
             * Handles the create operation.
             */
            public static function create(string $email): self
            {
                $user = new self();
                $user->email = $email;
                return $user;
            }

            // Getters only — no setters for business properties
            /**
             * Handles the getId operation.
             */
            public function getId(): ?int { return $this->id; }
            /**
             * Handles the getEmail operation.
             */
            public function getEmail(): string { return $this->email; }
        }
        ```

        ### Repository Pattern

        - Repositories extend `ServiceEntityRepository` or implement a custom interface
        - All queries go in repositories — never in controllers or services
        - Use **QueryBuilder** or **DQL** for complex queries — never raw SQL unless absolutely necessary
        - Methods should return typed values — never return raw `array` for entity collections
        - Use `findOneBy()`, `findBy()` for simple queries; QueryBuilder for filtering/sorting/pagination

        **Repository method naming:**
        - `findByEmail(string $email): ?User`
        - `findActiveUsers(): UserCollection`
        - `countByStatus(Status $status): int`
        - `paginatedList(int $page, int $limit): Paginator`

        ### Migrations

        - **Never edit existing migrations** — always create new ones
        - Always review generated migrations before running — check for data loss warnings
        - Test migrations in both directions (up/down)
        - Commands:
          ```bash
          php bin/console doctrine:migrations:diff     # Generate migration
          php bin/console doctrine:migrations:migrate  # Apply pending
          php bin/console doctrine:migrations:status   # Check status
          ```

        ### Performance

        - Use `PARTIAL` selects for large entities when you only need a few fields
        - Use `getArrayResult()` for read-only data (avoids hydrating entities)
        - Enable second-level cache for rarely-changing data
        - Monitor queries with Symfony Profiler or Doctrine DBAL logger
        - Avoid `findAll()` on large tables — always paginate
        - Use `EXTRA_LAZY` for collections that may be large and are rarely fully loaded
        MD;
    }
}
