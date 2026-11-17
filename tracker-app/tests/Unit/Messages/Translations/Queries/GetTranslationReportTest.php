<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Translations\Queries;

use App\Messages\Translations\Queries\GetTranslationReport;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class GetTranslationReportTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = sys_get_temp_dir().'/tt-translations-'.uniqid();
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->root);

        parent::tearDown();
    }

    private function write(string $path, string $contents): void
    {
        File::ensureDirectoryExists(dirname($this->root.'/'.$path));
        File::put($this->root.'/'.$path, $contents);
    }

    /**
     * @param  array<string, string>  $lines
     */
    private function writeGroup(string $path, array $lines): void
    {
        $this->write($path, '<?php return '.var_export($lines, true).';');
    }

    private function report(): array
    {
        $subject = new GetTranslationReport(
            lang_paths: ['' => $this->root.'/lang', 'demo' => $this->root.'/plugin/lang'],
            code_paths: [$this->root.'/code'],
        );

        return $subject->handle();
    }

    public function test_handle_reports_missing_and_orphaned_keys_per_language(): void
    {
        $this->writeGroup('lang/en/common.php', ['Save' => 'Save', 'Saved. Thanks!' => 'Saved. Thanks!']);
        $this->writeGroup('lang/de/common.php', ['Save' => 'Speichern', 'Old text' => 'Alter Text']);
        $this->writeGroup('lang/es/common.php', ['Save' => 'Guardar', 'Saved. Thanks!' => 'Guardado.']);

        $result = $this->report();

        $this->assertSame(2, $result['total']);
        $this->assertSame([
            'de' => ['translated' => 1, 'missing' => ['common.Saved. Thanks!'], 'orphaned' => ['common.Old text']],
            'es' => ['translated' => 2, 'missing' => [], 'orphaned' => []],
        ], $result['locales']);
    }

    public function test_handle_reads_plugin_lang_files_under_their_namespace(): void
    {
        $this->writeGroup('plugin/lang/en/settings.php', ['Webhook URL' => 'Webhook URL']);
        $this->writeGroup('plugin/lang/de/settings.php', []);

        $result = $this->report();

        $this->assertSame(['demo::settings.Webhook URL'], $result['locales']['de']['missing']);
    }

    public function test_handle_flags_keys_code_uses_that_english_lacks(): void
    {
        $this->writeGroup('lang/en/common.php', ['Save' => 'Save', 'Hello, :name' => 'Hello, :name']);
        $this->writeGroup('plugin/lang/en/settings.php', ['Webhook URL' => 'Webhook URL']);
        $this->write('code/page.blade.php', "{{ __('common.Save') }} {{ __('common.Delete') }} @lang('demo::settings.Webhook URL')");
        $this->write('code/Thing.php', "<?php trans_choice('common.:count troop|:count troops', 2); __(\"common.{\$label}\"); __('Ungrouped text');");
        $this->write('code/Page.svelte', "<h1>{t('Hello, :name', { name })}</h1><p>{t('Not defined')}</p><p>{format('x')}</p>");

        $result = $this->report();

        $this->assertSame([
            'Not defined' => ['code/Page.svelte'],
            'common.:count troop|:count troops' => ['code/Thing.php'],
            'common.Delete' => ['code/page.blade.php'],
        ], $this->relative($result['undefined']));
    }

    public function test_handle_checks_local_format_patterns_against_the_formats_group(): void
    {
        $this->writeGroup('lang/en/formats.php', ['M j, Y' => 'M j, Y']);
        $this->write('code/view.blade.php', '{{ $a->localFormat("M j, Y") }} {{ $b?->localFormat("g:ia") }}');

        $this->assertSame(['formats.g:ia'], array_keys($this->report()['undefined']));
    }

    public function test_handle_reads_escaped_quotes_in_keys(): void
    {
        $this->writeGroup('lang/en/common.php', ["Don't panic" => "Don't panic"]);
        $this->write('code/view.blade.php', "{{ __('common.Don\\'t panic') }}");
        $this->write('code/view.ts', "t('Don\\'t panic')");

        $this->assertSame([], $this->report()['undefined']);
    }

    /**
     * @param  array<string, array<int, string>>  $undefined
     * @return array<string, array<int, string>>
     */
    private function relative(array $undefined): array
    {
        return array_map(
            fn (array $files): array => array_map(fn (string $file): string => substr($file, strpos($file, 'code/')), $files),
            $undefined
        );
    }
}
