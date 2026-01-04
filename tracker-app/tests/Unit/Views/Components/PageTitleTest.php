<?php

declare(strict_types=1);

namespace Tests\Unit\Views\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class PageTitleTest extends TestCase
{
    public function test_it_renders_heading(): void
    {
        $subject = Blade::render('<x-page-title>Event Management</x-page-title>');

        $this->assertStringContainsString('<h2', $subject);
        $this->assertStringContainsString('Event Management', $subject);
        $this->assertStringContainsString('text-center my-4', $subject);
    }
}
