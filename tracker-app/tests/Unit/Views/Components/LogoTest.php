<?php

declare(strict_types=1);

namespace Tests\Unit\Views\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class LogoTest extends TestCase
{
    public function test_it_renders_with_required_paths(): void
    {
        $subject = Blade::render('<x-logo storage_path="logos/org.png" default_path="/img/default-logo.png" />');

        $this->assertStringContainsString('<img', $subject);
        $this->assertStringContainsString('alt="Logo"', $subject);
    }

    public function test_it_renders_with_width_attribute(): void
    {
        $subject = Blade::render('<x-logo storage_path="logo.png" default_path="default.png" :width="100" />');

        $this->assertStringContainsString('width=100', $subject);
    }

    public function test_it_renders_with_height_attribute(): void
    {
        $subject = Blade::render('<x-logo storage_path="logo.png" default_path="default.png" :height="50" />');

        $this->assertStringContainsString('height=50', $subject);
    }

    public function test_it_renders_with_fluid_class(): void
    {
        $subject = Blade::render('<x-logo storage_path="logo.png" default_path="default.png" :fluid="true" />');

        $this->assertStringContainsString('class=img-fluid', $subject);
    }
}
