<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Resolves the languages the tracker supports and hands translation groups to
 * the front end. See docs/LOCALIZATION.md.
 */
class TranslationService
{
    /**
     * @return array<string, string> Supported locales as locale => the language's own name
     */
    public function locales(): array
    {
        return config('tracker.locales', ['en' => 'English']);
    }

    public function isSupported(?string $locale): bool
    {
        return $locale !== null && array_key_exists($locale, $this->locales());
    }

    /**
     * The site default, or English when APP_LOCALE names a language we don't ship.
     */
    public function siteLocale(): string
    {
        $locale = (string) config('app.locale');

        return $this->isSupported($locale) ? $locale : 'en';
    }

    /**
     * The groups an Inertia page gets as its `lang` prop: the group named after the
     * first segment of its route name ("account.update-profile" gets lang/<locale>/account.php),
     * when English has that group.
     *
     * @return array<string, string>
     */
    public function forRoute(?string $route_name): array
    {
        $group = $route_name === null ? null : strtok($route_name, '.');

        if (!$group || !is_file(lang_path("en/{$group}.php")))
        {
            return [];
        }

        return $this->groups($group);
    }

    /**
     * Merges translation groups into one flat key => line map for Svelte and JS.
     *
     * Each group is laid over its fallback (English) version, because trans('group')
     * returns only the current locale's lines and would drop untranslated keys.
     *
     * @return array<string, string>
     */
    public function groups(string ...$groups): array
    {
        $messages = [];

        foreach ($groups as $group)
        {
            $messages = array_replace(
                $messages,
                $this->lines($group, config('app.fallback_locale', 'en')),
                $this->lines($group, app()->getLocale()),
            );
        }

        return $messages;
    }

    /**
     * @return array<string, string>
     */
    private function lines(string $group, string $locale): array
    {
        $lines = trans($group, [], $locale);

        if (!is_array($lines))
        {
            return [];
        }

        return array_filter($lines, fn (mixed $line): bool => is_string($line));
    }
}
