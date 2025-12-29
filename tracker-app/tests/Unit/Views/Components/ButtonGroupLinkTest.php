<?php

declare(strict_types=1);

namespace Tests\Unit\Views\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ButtonGroupLinkTest extends TestCase
{
    public function test_it_renders_with_label_and_url(): void
    {
        $subject = Blade::render('<x-button-group-link label="Events" url="/events" />');

        $this->assertStringContainsString('href="/events"', $subject);
        $this->assertStringContainsString('Events', $subject);
        $this->assertStringContainsString('btn btn-outline-primary', $subject);
    }

    public function test_it_uses_slot_instead_of_label(): void
    {
        $subject = Blade::render('<x-button-group-link url="/events">Slot Content</x-button-group-link>');

        $this->assertStringContainsString('Slot Content', $subject);
    }

    public function test_it_adds_active_class_when_active_is_true(): void
    {
        $subject = Blade::render('<x-button-group-link label="Events" url="/events" :active="true" />');

        $this->assertStringContainsString('active', $subject);
    }

    public function test_it_does_not_add_active_class_by_default(): void
    {
        $subject = Blade::render('<x-button-group-link label="Events" url="/events" />');

        $this->assertMatchesRegularExpression('/btn-outline-primary(?!.*\sactive)/', $subject);
    }
}
