<?php

declare(strict_types=1);

namespace NowoTech\ClaudePhpSetup\Template\ClaudeMd;

use NowoTech\ClaudePhpSetup\Question\ProjectConfig;

use function in_array;

/**
 * Represents the TwigSection class.
 */
final class TwigSection
{
    /**
     * Handles the render operation.
     */
    public static function render(ProjectConfig $config): string
    {
        if (!$config->hasTwig) {
            return '';
        }

        $runner      = self::runner($config);
        $csFixerNote = $config->hasTwigCsFixer
            ? "\n\n**Twig-CS-Fixer:** Run `{$runner} twig-cs-fix` to fix template style."
            : '';

        $symfonyExtra = '';
        if ($config->framework === 'symfony') {
            $symfonyExtra = self::symfonyTwig();
        }

        return <<<MD
        ## Twig Templates

        ### Best Practices

        **Structure:**
        - Store templates in `templates/` (Symfony) or `resources/views/` (Laravel)
        - Use **template inheritance**: base layout → section layout → page template
        - Partial templates start with `_` (e.g., `_card.html.twig`)
        - Macros in dedicated `_macros.html.twig` files

        **Logic rules:**
        - **No PHP logic in Twig** — templates are for presentation only
        - Pass **only the data the template needs** — avoid passing entire entities
        - Use **Twig filters and functions** — never write raw PHP helpers
        - Complex display logic belongs in a **Twig Extension** (`AbstractExtension`)

        **Performance:**
        - Enable Twig cache in production (`cache: '%kernel.cache_dir%/twig'`)
        - Avoid querying the database from templates — pass prepared data from controllers
        - Use `{% block %}` for overridable sections
        - Use `{% include %}` for partials, `{% embed %}` for overridable partials

        **Security:**
        - Twig auto-escapes by default — never use `|raw` unless the value is genuinely safe HTML
        - Use `|e` filter explicitly when you need to confirm escaping intent
        - Never render user input with `|raw`{$csFixerNote}{$symfonyExtra}
        MD;
    }

    /**
     * Handles the symfonyTwig operation.
     */
    private static function symfonyTwig(): string
    {
        return <<<'MD'

        ### Symfony + Twig

        **Forms:**
        - Use `form_start()`, `form_end()`, `form_row()` — never render form fields manually
        - Customize form themes with `form_themes` config
        - For complex forms, create a custom form theme template

        **Asset management:**
        - Use `asset()` function for CSS/JS paths — never hardcode paths
        - Use `{{ path('route_name') }}` for URLs — never hardcode
        - Use `{{ url('route_name') }}` only for absolute URLs (emails, etc.)

        **Translations:**
        - Use `{{ 'key'|trans }}` or `{% trans %}key{% endtrans %}`
        - Keep translation keys hierarchical: `user.profile.title`
        - Domain-specific translations: `{{ 'message'|trans({}, 'emails') }}`
        MD;
    }

    /**
     * Handles the runner operation.
     */
    private static function runner(ProjectConfig $config): string
    {
        return in_array($config->commandRunner, ['make', 'both'], true) ? 'make' : 'composer';
    }
}
