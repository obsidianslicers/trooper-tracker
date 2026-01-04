<?php

declare(strict_types=1);

namespace Tests\Unit\Views\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class FilterChipTest extends TestCase
{
    public function test_it_renders_with_required_props(): void
    {
        $subject = Blade::render('<x-filter-chip label="Active Events" url="/events?filter=active" />');

        $this->assertStringContainsString('Active Events', $subject);
        $this->assertStringContainsString('href="/events?filter=active"', $subject);
        $this->assertStringContainsString('badge rounded-pill bg-primary', $subject);
    }

    public function test_it_includes_close_icon(): void
    {
        $subject = Blade::render('<x-filter-chip label="Filter" url="/events" />');

        $this->assertStringContainsString('fa-times', $subject);
    }

    public function test_it_has_white_text_on_link(): void
    {
        $subject = Blade::render('<x-filter-chip label="Filter" url="/events" />');

        $this->assertStringContainsString('text-white', $subject);
    }
}
