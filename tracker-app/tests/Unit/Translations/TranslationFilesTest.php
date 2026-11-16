<?php

declare(strict_types=1);

namespace Tests\Unit\Translations;

use App\Enums\HasEnumHelpers;
use App\Messages\Translations\Queries\GetTranslationReport;
use App\Messages\Translations\Queries\GetTranslationSources;
use Tests\TestCase;

/**
 * Guards the real lang files: every key the code uses must be in English (or the
 * page shows the raw key), and no language may keep keys English dropped.
 * Untranslated lines are fine (they fall back to English); CI reports those.
 */
class TranslationFilesTest extends TestCase
{
    private function report(): array
    {
        $sources = GetTranslationSources::call();

        return GetTranslationReport::call(
            lang_paths: $sources['lang_paths'],
            code_paths: $sources['code_paths'],
        );
    }

    public function test_every_key_used_in_code_is_in_the_english_files(): void
    {
        $undefined = $this->report()['undefined'];

        $this->assertSame([], $undefined, 'Add these keys to lang/en (or the plugin\'s lang/en): '
            .implode(', ', array_keys($undefined)));
    }

    public function test_no_language_has_keys_english_lacks(): void
    {
        $orphaned = array_filter(array_map(
            fn (array $result): array => $result['orphaned'],
            $this->report()['locales']
        ));

        $this->assertSame([], $orphaned, 'These languages have keys that are no longer in English. '
            .'Re-key or remove them: '.json_encode($orphaned));
    }

    public function test_every_enum_label_is_in_the_english_files(): void
    {
        $missing = [];

        foreach (glob(app_path('Enums/*.php')) as $file)
        {
            $class = 'App\\Enums\\'.basename($file, '.php');

            if (!enum_exists($class) || !in_array(HasEnumHelpers::class, class_uses($class), true))
            {
                continue;
            }

            foreach ($class::cases() as $case)
            {
                $label = to_title($case->name)->toString();

                if (!array_key_exists($label, trans('enums', [], 'en')))
                {
                    $missing[] = "{$class}::{$case->name} ({$label})";
                }
            }
        }

        $this->assertSame([], $missing, 'Add these labels to lang/en/enums.php');
    }
}
