<?php

declare(strict_types=1);

namespace Tests\Unit\Views\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ActionMenuTest extends TestCase
{
    public function test_it_renders_dropdown_menu(): void
    {
        $subject = Blade::render('<x-action-menu>Menu Content</x-action-menu>');

        $this->assertStringContainsString('dropdown', $subject);
        $this->assertStringContainsString('dropdown-menu', $subject);
        $this->assertStringContainsString('Menu Content', $subject);
    }

    public function test_it_includes_gear_icon_button(): void
    {
        $subject = Blade::render('<x-action-menu></x-action-menu>');

        $this->assertStringContainsString('btn btn-outline-secondary dropdown-toggle', $subject);
        $this->assertStringContainsString('fa-gear', $subject);
        $this->assertStringContainsString('data-bs-toggle="dropdown"', $subject);
    }

    public function test_it_includes_float_end_class(): void
    {
        $subject = Blade::render('<x-action-menu></x-action-menu>');

        $this->assertStringContainsString('float-end', $subject);
    }
}
