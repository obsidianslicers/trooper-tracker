<?php

declare(strict_types=1);

namespace Tests\Unit\Views\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class SpinnerTest extends TestCase
{
    public function test_it_renders_with_default_id(): void
    {
        $subject = Blade::render('<x-spinner />');

        $this->assertStringContainsString('<span', $subject);
        $this->assertStringContainsString('htmx-indicator', $subject);
        $this->assertStringContainsString('fa-spinner fa-spin', $subject);
        $this->assertMatchesRegularExpression('/id="spinner-x-[a-z0-9]+"/', $subject);
    }

    public function test_it_renders_with_custom_id(): void
    {
        $subject = Blade::render('<x-spinner id="custom-spinner" />');

        $this->assertStringContainsString('id="spinner-custom-spinner"', $subject);
    }

    public function test_it_includes_margin_styling(): void
    {
        $subject = Blade::render('<x-spinner />');

        $this->assertStringContainsString('margin-left: 8px', $subject);
    }
}
