<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Messages\Translations\Queries\GetTranslationReport;
use App\Messages\Translations\Queries\GetTranslationSources;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class ReportTranslationsCommandTest extends TestCase
{
    use FakesMessages;

    private const array SOURCES = ['lang_paths' => ['' => '/lang'], 'code_paths' => ['/app']];

    private function fakeReport(array $undefined = [], array $orphaned = []): void
    {
        $this->fakeMessages([
            GetTranslationSources::class => self::SOURCES,
            GetTranslationReport::class => [
                'undefined' => $undefined,
                'total' => 4,
                'locales' => [
                    'de' => ['translated' => 3, 'missing' => ['common.Save'], 'orphaned' => $orphaned],
                ],
            ],
        ]);
    }

    public function test_prints_a_table_per_language(): void
    {
        $this->fakeReport();

        $this->artisan('tracker:report-translations')
            ->expectsOutput('English: 4 lines')
            ->expectsTable(['Language', 'Translated', 'Missing', 'Orphaned'], [['de', '75%', 1, 0]])
            ->assertExitCode(0);
    }

    public function test_writes_markdown_with_missing_keys(): void
    {
        $this->fakeReport();

        $this->artisan('tracker:report-translations', ['--markdown' => true])
            ->expectsOutput('| de | 75% | 1 | 0 |')
            ->expectsOutput('<details><summary>Missing in de (1)</summary>')
            ->expectsOutput('- `common.Save`')
            ->assertExitCode(0);
    }

    public function test_strict_fails_on_undefined_or_orphaned_keys(): void
    {
        $this->fakeReport(undefined: ['common.Nope' => ['app/Thing.php']], orphaned: ['common.Old']);

        $this->artisan('tracker:report-translations', ['--strict' => true])
            ->expectsOutput('Undefined in English: common.Nope (app/Thing.php)')
            ->assertExitCode(1);
    }

    public function test_missing_translations_alone_do_not_fail_strict(): void
    {
        $this->fakeReport();

        $this->artisan('tracker:report-translations', ['--strict' => true])->assertExitCode(0);
    }
}
