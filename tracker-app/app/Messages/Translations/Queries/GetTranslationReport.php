<?php

declare(strict_types=1);

namespace App\Messages\Translations\Queries;

use Hyperdrive\Message;
use Illuminate\Support\Arr;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Compares the translation keys code uses with the lang files.
 *
 * - undefined: keys code uses that the English files don't define, so the page
 *   would show the raw key.
 * - locales: per language, English keys not translated yet (they fall back to
 *   English) and orphaned keys English no longer has (usually reworded text).
 *
 * Keys are "group.Line", or "slug::group.Line" for a plugin. Svelte and JS t('Line')
 * calls have no group and count as defined when any English group has the line.
 *
 * @method static array call(array $lang_paths, array $code_paths)
 */
final class GetTranslationReport extends Message
{
    private const string FALLBACK = 'en';

    private const string PHP_CALL = '/(?:\b__|\btrans|\btrans_choice|@lang|Lang::get|Lang::choice)\(\s*'
        ."(['\"])((?:\\\\.|(?!\\1).)*)\\1/s";

    private const string JS_CALL = '/(?<![\w.$])t\(\s*([\'"`])((?:\\\\.|(?!\1).)*)\1/s';

    private const string GROUPED_KEY = '/^(?:([a-z0-9_-]+)::)?([a-z0-9_-]+)\.(.+)$/s';

    /**
     * @param  array<string, string>  $lang_paths  namespace ('' for core) => lang folder
     * @param  array<int, string>  $code_paths  folders to scan for translation calls
     */
    public function __construct(
        private readonly array $lang_paths,
        private readonly array $code_paths,
    ) {}

    /**
     * @return array{
     *     undefined: array<string, array<int, string>>,
     *     total: int,
     *     locales: array<string, array{
     *         translated: int,
     *         missing: array<int, string>,
     *         orphaned: array<int, string>
     *     }>
     * }
     */
    public function handle(): array
    {
        $lines = $this->loadLines();
        $english = $lines[self::FALLBACK] ?? [];

        $locales = [];

        foreach (Arr::except($lines, self::FALLBACK) as $locale => $translated)
        {
            $locales[$locale] = [
                'translated' => count(array_intersect_key($translated, $english)),
                'missing' => array_keys(array_diff_key($english, $translated)),
                'orphaned' => array_keys(array_diff_key($translated, $english)),
            ];
        }

        ksort($locales);

        return [
            'undefined' => $this->findUndefined($english),
            'total' => count($english),
            'locales' => $locales,
        ];
    }

    /**
     * @return array<string, array<string, string>> locale => full key => line
     */
    private function loadLines(): array
    {
        $lines = [];

        foreach ($this->lang_paths as $namespace => $path)
        {
            foreach (glob(rtrim($path, '/').'/*/*.php') ?: [] as $file)
            {
                $locale = basename(dirname($file));
                $group = basename($file, '.php');
                $prefix = ($namespace === '' ? '' : "{$namespace}::")."{$group}.";
                $lines[$locale] ??= [];

                foreach ($this->readGroup($file) as $key => $line)
                {
                    $lines[$locale][$prefix.$key] = $line;
                }
            }
        }

        return $lines;
    }

    /**
     * @return array<string, string>
     */
    private function readGroup(string $file): array
    {
        $group = require $file;

        if (!is_array($group))
        {
            return [];
        }

        return array_filter(
            $this->flatten($group),
            fn (mixed $line): bool => is_string($line)
        );
    }

    /**
     * Like Arr::dot, but keeps keys that contain periods ("Saved. Thanks!") whole.
     *
     * @param  array<array-key, mixed>  $group
     * @return array<string, mixed>
     */
    private function flatten(array $group, string $prefix = ''): array
    {
        $flat = [];

        foreach ($group as $key => $value)
        {
            if (is_array($value))
            {
                $flat += $this->flatten($value, $prefix.$key.'.');

                continue;
            }

            $flat[$prefix.$key] = $value;
        }

        return $flat;
    }

    /**
     * @param  array<string, string>  $english
     * @return array<string, array<int, string>> key => files using it
     */
    private function findUndefined(array $english): array
    {
        $english_lines = [];

        foreach (array_keys($english) as $key)
        {
            $english_lines[preg_replace(self::GROUPED_KEY, '$3', $key)] = true;
        }

        $undefined = [];

        foreach ($this->findUsages() as [$key, $is_grouped, $file])
        {
            $is_defined = $is_grouped
                ? isset($english[$key])
                : isset($english_lines[$key]);

            if (!$is_defined)
            {
                $undefined[$key][] = $file;
            }
        }

        ksort($undefined);

        return array_map(fn (array $files): array => array_values(array_unique($files)), $undefined);
    }

    /**
     * @return array<int, array{0: string, 1: bool, 2: string}> [key, is grouped, relative file]
     */
    private function findUsages(): array
    {
        $usages = [];

        foreach ($this->codeFiles() as $file)
        {
            $is_php = str_ends_with($file, '.php');
            $source = (string) file_get_contents($file);
            $relative = str_replace(base_path().'/', '', $file);

            foreach ($this->extractKeys($source, $is_php ? self::PHP_CALL : self::JS_CALL) as $key)
            {
                $is_grouped = $is_php && preg_match(self::GROUPED_KEY, $key) === 1;

                if ($is_php && !$is_grouped)
                {
                    continue;
                }

                $usages[] = [$key, $is_grouped, $relative];
            }
        }

        return $usages;
    }

    /**
     * Literal keys only: a key built from variables can't be checked here.
     *
     * @return array<int, string>
     */
    private function extractKeys(string $source, string $pattern): array
    {
        preg_match_all($pattern, $source, $matches, PREG_SET_ORDER);

        $keys = [];

        foreach ($matches as $match)
        {
            $is_dynamic = $match[1] !== "'" && (str_contains($match[2], '$') || $match[1] === '`');

            if (!$is_dynamic)
            {
                $keys[] = stripcslashes($match[2]);
            }
        }

        return $keys;
    }

    /**
     * @return array<int, string>
     */
    private function codeFiles(): array
    {
        $files = [];

        foreach (array_filter($this->code_paths, 'is_dir') as $path)
        {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
                $path,
                RecursiveDirectoryIterator::SKIP_DOTS
            ));

            foreach ($iterator as $file)
            {
                if (preg_match('/\.(php|svelte|ts|js)$/', $file->getFilename()) === 1)
                {
                    $files[] = $file->getPathname();
                }
            }
        }

        sort($files);

        return $files;
    }
}
