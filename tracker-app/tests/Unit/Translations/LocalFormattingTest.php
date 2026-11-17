<?php

declare(strict_types=1);

namespace Tests\Unit\Translations;

use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LocalFormattingTest extends TestCase
{
    private function useGerman(): void
    {
        app('translator')->addLines(['formats.M j, Y g:ia' => 'j. M Y H:i'], 'de');
        app()->setLocale('de');
    }

    public function test_local_format_keeps_the_english_pattern_in_english(): void
    {
        $subject = Carbon::create(2026, 11, 5, 14, 3);

        $this->assertSame('Nov 5, 2026 2:03pm', $subject->localFormat('M j, Y g:ia'));
    }

    public function test_local_format_uses_the_languages_pattern_and_names(): void
    {
        $this->useGerman();

        $this->assertSame('5. Nov 2026 14:03', Carbon::create(2026, 11, 5, 14, 3)->localFormat('M j, Y g:ia'));
        $this->assertSame('5. Nov 2026 14:03', CarbonImmutable::create(2026, 11, 5, 14, 3)->localFormat('M j, Y g:ia'));
    }

    public function test_local_format_falls_back_to_the_pattern_it_was_given(): void
    {
        $this->useGerman();

        $this->assertSame('Donnerstag', Carbon::create(2026, 11, 5)->localFormat('l'));
    }

    public function test_format_number_follows_the_language(): void
    {
        $this->assertSame('1,234.50', format_number(1234.5, 2));

        app()->setLocale('de');

        $this->assertSame('1.234,50', format_number(1234.5, 2));
        $this->assertSame('1.234,50', format_funds(1234.5));
        $this->assertSame('1.234', format_funds(1234));
    }

    public function test_format_funds_with_another_separator_stays_machine_readable(): void
    {
        app()->setLocale('de');

        $this->assertSame('1234.50', format_funds(1234.5, ''));
    }
}
