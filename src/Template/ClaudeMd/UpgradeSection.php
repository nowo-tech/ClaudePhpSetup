<?php

declare(strict_types=1);

namespace NowoTech\ClaudePhpSetup\Template\ClaudeMd;

use NowoTech\ClaudePhpSetup\Question\ProjectConfig;

/**
 * Represents the UpgradeSection class.
 */
final class UpgradeSection
{
    /**
     * Handles the render operation.
     */
    public static function render(ProjectConfig $config): string
    {
        if (!$config->isUpgrading || $config->framework !== 'symfony') {
            return '';
        }

        $from     = $config->upgradeFromVersion ?? '6.4';
        $to       = $config->frameworkVersion ?? '8.0';
        $fromSet  = self::setName($from);
        $toSet    = self::setName($to);
        $breaking = self::breakingChanges($from, $to);

        return <<<MD
        ## Symfony Upgrade: {$from} → {$to}

        ### Upgrade Strategy

        **Golden rule:** Upgrade one minor version at a time. Never jump multiple minors in one PR.

        **Phase 1 — Fix deprecations in current version ({$from})**
        1. Install `symfony/deprecation-contracts` if missing
        2. Run the application with deprecation notices enabled (check logs)
        3. Use Rector to fix known deprecations automatically:
           ```bash
           # In rector.php, add the deprecation set for your FROM version
           SymfonySetList::SYMFONY_{$fromSet}_DEPRECATIONS
           ```
        4. Fix remaining deprecations manually
        5. All tests green? Proceed to Phase 2

        **Phase 2 — Update composer.json**
        ```bash
        # Update symfony/* constraints
        composer require "symfony/framework-bundle:{$to}.*" --no-update
        # Update all symfony/* packages
        composer update "symfony/*" --with-all-dependencies
        ```

        **Phase 3 — Apply new version rules with Rector**
        ```bash
        # In rector.php, add the new version set
        SymfonySetList::SYMFONY_{$toSet}
        ```

        **Phase 4 — Update configuration**
        - Check `UPGRADE-X.Y.md` in `vendor/symfony/symfony/`
        - Update `config/packages/*.yaml` for renamed/moved config keys
        - Check `config/routes/*.yaml` for route configuration changes
        - Review security configuration changes in `config/packages/security.yaml`

        **Phase 5 — Verify**
        - `composer phpstan` — fix any new type errors
        - `composer test` — all tests must pass
        - Manual smoke test of critical paths

        ### Using Rector for Symfony Upgrades

        Add these sets to `rector.php` progressively:

        ```php
        use Rector\\Symfony\\Set\\SymfonySetList;

        return RectorConfig::configure()
            ->withSets([
                // Deprecation fixes (current version)
                SymfonySetList::SYMFONY_{$fromSet}_DEPRECATIONS,

                // After upgrading to {$to}:
                // SymfonySetList::SYMFONY_{$toSet},
            ]);
        ```

        ### Common Breaking Changes {$from} → {$to}

        {$breaking}

        ### Resources
        - Official upgrade guide: `vendor/symfony/symfony/UPGRADE-{$to}.md`
        - Symfony deprecations list: https://symfony.com/blog
        - Rector Symfony rules: https://github.com/rectorphp/rector-symfony
        MD;
    }

    /**
     * Handles the setName operation.
     */
    private static function setName(string $version): string
    {
        return str_replace('.', '_', $version);
    }

    /**
     * Handles the breakingChanges operation.
     */
    private static function breakingChanges(string $from, string $to): string
    {
        $fromMajor = (int) explode('.', $from)[0];
        $toMajor   = (int) explode('.', $to)[0];

        if ($fromMajor < 7 && $toMajor >= 7) {
            return implode("\n", [
                '- **`#[Route]` attribute changes** — `methods` parameter is now mandatory for non-GET routes',
                '- **`AbstractController::json()`** — returns `JsonResponse` with `Content-Type: application/json`',
                '- **Security system rewrite** — `security.yaml` structure changed; use the new authenticator system',
                '- **Form changes** — `FormTypeExtensionInterface::getExtendedType()` replaced by `getExtendedTypes()`',
                '- **`ContainerAwareTrait` removed** — use constructor injection only',
                '- **`ObjectNormalizer` type enforcement** — stricter type handling in Serializer',
                '- **`HttpFoundation` changes** — `Request::isXmlHttpRequest()` deprecated for `Content-Type` header check',
                '- **Config tree builder** — `addDefaultsIfNotSet()` behaviour changes',
            ]);
        }

        if ($fromMajor === 5 && $toMajor >= 6) {
            return implode("\n", [
                '- **PHP 8.0+ required** for Symfony 6',
                '- **`AbstractController` changes** — `getDoctrine()` removed; inject `ManagerRegistry` instead',
                '- **`RouterInterface::generate()`** — returns relative URLs by default; use `UrlGeneratorInterface::ABSOLUTE_URL` for absolute',
                '- **`EventSubscriberInterface`** — `getSubscribedEvents()` return type changed',
                '- **Security voters** — must implement `supportsAttribute()` method',
                '- **`twig/twig` 3.x required** — removed legacy tags like `{% spaceless %}`',
            ]);
        }

        return '- Check `UPGRADE-' . $to . '.md` in the Symfony repository for the complete list';
    }
}
