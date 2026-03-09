<?php

declare(strict_types=1);

namespace Tests\Unit\Views\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class SectionTitleTest extends TestCase
{
    public function test_it_renders_heading(): void
    {
        $subject = Blade::render('<x-section-title>Event Details</x-section-title>');

        $this->assertStringContainsString('<h6', $subject);
        $this->assertStringContainsString('Event Details', $subject);
        $this->assertStringContainsString('text-uppercase text-muted', $subject);
    }
}
