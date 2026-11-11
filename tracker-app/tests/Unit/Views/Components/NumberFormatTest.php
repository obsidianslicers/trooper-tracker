<?php

declare(strict_types=1);

namespace Tests\Unit\Views\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class NumberFormatTest extends TestCase
{
    public function test_it_renders_number_with_default_decimals(): void
    {
        $subject = Blade::render('<x-number-format :value="1000" />');

        $this->assertStringContainsString('1,000', $subject);
    }

    public function test_it_renders_number_with_custom_decimals(): void
    {
        $subject = Blade::render('<x-number-format :value="1234.567" :decimals="2" />');

        $this->assertStringContainsString('1,234.57', $subject);
    }

    public function test_it_shows_cents_only_when_the_amount_has_them(): void
    {
        $this->assertStringContainsString('1,234.50', Blade::render('<x-number-format :value="1234.5" decimals="cents" />'));
        $this->assertStringContainsString('1,234', Blade::render('<x-number-format :value="1234.0" decimals="cents" />'));
        $this->assertStringNotContainsString('1,234.00', Blade::render('<x-number-format :value="1234" decimals="cents" />'));
        $this->assertStringContainsString('12.05', Blade::render('<x-number-format value="12.05" decimals="cents" />'));
    }

    public function test_it_renders_dash_when_value_is_zero(): void
    {
        $subject = Blade::render('<x-number-format :value="0" />');

        $this->assertStringContainsString('-', $subject);
    }

    public function test_it_renders_with_prefix(): void
    {
        $subject = Blade::render('<x-number-format :value="100" prefix="$" />');

        $this->assertStringContainsString('$', $subject);
        $this->assertStringContainsString('100', $subject);
    }

    public function test_it_wraps_content_in_span(): void
    {
        $subject = Blade::render('<x-number-format :value="100" />');

        $this->assertStringStartsWith('<span>', trim($subject));
        $this->assertStringEndsWith('</span>', trim($subject));
    }
}
