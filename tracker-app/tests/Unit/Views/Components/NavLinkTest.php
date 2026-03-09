<?php

declare(strict_types=1);

namespace Tests\Unit\Views\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class NavLinkTest extends TestCase
{
    public function test_it_renders_nav_item_with_url(): void
    {
        $subject = Blade::render('<x-nav-link url="/events">Events</x-nav-link>');

        $this->assertStringContainsString('nav-item', $subject);
        $this->assertStringContainsString('nav-link', $subject);
        $this->assertStringContainsString('href="/events"', $subject);
        $this->assertStringContainsString('Events', $subject);
    }

    public function test_it_uses_hash_as_default_url(): void
    {
        $subject = Blade::render('<x-nav-link>Link</x-nav-link>');

        $this->assertStringContainsString('href="#"', $subject);
    }

    public function test_it_adds_active_class_when_active_is_true(): void
    {
        $subject = Blade::render('<x-nav-link url="/events" :active="true">Events</x-nav-link>');

        $this->assertStringContainsString('active', $subject);
    }

    public function test_it_does_not_add_active_class_by_default(): void
    {
        $subject = Blade::render('<x-nav-link url="/events">Events</x-nav-link>');

        $this->assertMatchesRegularExpression('/nav-link(?!\s+active)/', $subject);
    }

    public function test_it_accepts_additional_attributes(): void
    {
        $subject = Blade::render('<x-nav-link url="/events" class="custom-class">Events</x-nav-link>');

        $this->assertStringContainsString('custom-class', $subject);
    }
}
