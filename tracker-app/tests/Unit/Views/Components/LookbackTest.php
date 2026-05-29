<?php

declare(strict_types=1);

namespace Tests\Unit\Views\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class LookbackTest extends TestCase
{
    public function test_all_time_is_active_when_days_is_null(): void
    {
        $subject = Blade::render('<x-lookback :days="null" :all-time="true" />');

        $this->assertStringContainsString('active', $this->anchorFor($subject, 'All Time'));
        $this->assertStringNotContainsString('active', $this->anchorFor($subject, '30D'));
    }

    public function test_selected_interval_is_active(): void
    {
        $subject = Blade::render('<x-lookback :days="30" :all-time="true" />');

        $this->assertStringNotContainsString('active', $this->anchorFor($subject, 'All Time'));
        $this->assertStringContainsString('active', $this->anchorFor($subject, '30D'));
    }

    private function anchorFor(string $html, string $label): string
    {
        preg_match('/<a\b[^>]*>\s*'.preg_quote($label, '/').'\s*<\/a>/s', $html, $matches);

        return $matches[0] ?? '';
    }
}
