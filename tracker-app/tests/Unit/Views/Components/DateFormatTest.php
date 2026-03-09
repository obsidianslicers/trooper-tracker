<?php

declare(strict_types=1);

namespace Tests\Unit\Views\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class DateFormatTest extends TestCase
{
    public function test_it_renders_formatted_date_with_carbon_instance(): void
    {
        $date = \Carbon\Carbon::parse('2025-12-25');
        $subject = Blade::render('<x-date-format :value="$date" />', ['date' => $date]);

        $this->assertStringContainsString('2025-12-25', $subject);
    }

    public function test_it_renders_formatted_date_with_string(): void
    {
        $subject = Blade::render('<x-date-format value="2025-12-25" />');

        $this->assertStringContainsString('2025-12-25', $subject);
    }

    public function test_it_renders_with_custom_format(): void
    {
        $date = \Carbon\Carbon::parse('2025-12-25');
        $subject = Blade::render('<x-date-format :value="$date" format="m/d/Y" />', ['date' => $date]);

        $this->assertStringContainsString('12/25/2025', $subject);
    }

    public function test_it_renders_dash_when_value_is_null(): void
    {
        $subject = Blade::render('<x-date-format :value="null" />');

        $this->assertStringContainsString('-', $subject);
        $this->assertStringNotContainsString('2025', $subject);
    }

    public function test_it_wraps_content_in_span(): void
    {
        $date = \Carbon\Carbon::parse('2025-12-25');
        $subject = Blade::render('<x-date-format :value="$date" />', ['date' => $date]);

        $this->assertStringStartsWith('<span>', trim($subject));
        $this->assertStringEndsWith('</span>', trim($subject));
    }
}
