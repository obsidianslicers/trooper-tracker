<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Messages\Translations\Queries\GetTranslationReport;
use App\Messages\Translations\Queries\GetTranslationSources;
use Illuminate\Console\Command;

/**
 * Reports, per language, how many lines are translated and which keys are missing.
 * CI writes the --markdown form to the job summary. Missing translations are not
 * errors (they fall back to English); --strict fails on undefined or orphaned keys.
 */
class ReportTranslationsCommand extends Command
{
    protected $signature = 'tracker:report-translations
        {--markdown : Write a Markdown summary (for a GitHub Actions job summary)}
        {--strict : Exit with an error when keys are undefined or orphaned}';

    protected $description = 'Report missing, undefined and orphaned translation keys per language.';

    public function handle(): int
    {
        $sources = GetTranslationSources::call();

        $report = GetTranslationReport::call(
            lang_paths: $sources['lang_paths'],
            code_paths: $sources['code_paths'],
        );

        $this->option('markdown')
            ? $this->writeMarkdown($report)
            : $this->writeConsole($report);

        return $this->option('strict') && $this->hasErrors($report)
            ? self::FAILURE
            : self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function hasErrors(array $report): bool
    {
        $orphaned = array_filter($report['locales'], fn (array $locale): bool => $locale['orphaned'] !== []);

        return $report['undefined'] !== [] || $orphaned !== [];
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function writeConsole(array $report): void
    {
        $this->info("English: {$report['total']} lines");

        foreach ($report['undefined'] as $key => $files)
        {
            $this->error("Undefined in English: {$key} (".implode(', ', $files).')');
        }

        $rows = [];

        foreach ($report['locales'] as $locale => $result)
        {
            $rows[] = [$locale, $this->percent($result['translated'], $report['total']),
                count($result['missing']), count($result['orphaned'])];
        }

        $this->table(['Language', 'Translated', 'Missing', 'Orphaned'], $rows);
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function writeMarkdown(array $report): void
    {
        $this->line('## Translations');
        $this->line('');
        $this->line("English has {$report['total']} lines.");
        $this->line('');
        $this->writeMarkdownList('Used in code but not in the English files', array_keys($report['undefined']));

        if ($report['locales'] === [])
        {
            $this->line('No other languages yet.');

            return;
        }

        $this->line('| Language | Translated | Missing | Orphaned |');
        $this->line('| --- | --- | --- | --- |');

        foreach ($report['locales'] as $locale => $result)
        {
            $percent = $this->percent($result['translated'], $report['total']);
            $this->line("| {$locale} | {$percent} | ".count($result['missing']).' | '.count($result['orphaned']).' |');
        }

        $this->line('');

        foreach ($report['locales'] as $locale => $result)
        {
            $this->writeMarkdownList("Missing in {$locale}", $result['missing']);
            $this->writeMarkdownList("Orphaned in {$locale}", $result['orphaned']);
        }
    }

    /**
     * @param  array<int, string>  $keys
     */
    private function writeMarkdownList(string $title, array $keys): void
    {
        if ($keys === [])
        {
            return;
        }

        $this->line('<details><summary>'.e($title).' ('.count($keys).')</summary>');
        $this->line('');

        foreach ($keys as $key)
        {
            $this->line('- `'.str_replace('`', "'", $key).'`');
        }

        $this->line('');
        $this->line('</details>');
        $this->line('');
    }

    private function percent(int $translated, int $total): string
    {
        return $total === 0 ? '100%' : (string) floor($translated / $total * 100).'%';
    }
}
