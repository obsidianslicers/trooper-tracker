<?php

declare(strict_types=1);

namespace Tests\Unit\Views\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ActionLinkTest extends TestCase
{
    public function test_it_renders_with_required_props(): void
    {
        $subject = Blade::render('<x-action-link label="View Details" url="/events/1" />');

        $this->assertStringContainsString('href="/events/1"', $subject);
        $this->assertStringContainsString('View Details', $subject);
        $this->assertStringContainsString('dropdown-item', $subject);
        $this->assertStringContainsString('<li>', $subject);
    }

    public function test_it_uses_default_icon(): void
    {
        $subject = Blade::render('<x-action-link label="View" url="/events/1" />');

        $this->assertStringContainsString('fa-rectangle-list', $subject);
    }

    public function test_it_renders_with_custom_icon(): void
    {
        $subject = Blade::render('<x-action-link label="Edit" url="/events/1" icon="fa-pencil" />');

        $this->assertStringContainsString('fa-pencil', $subject);
        $this->assertStringNotContainsString('fa-rectangle-list', $subject);
    }

    public function test_it_uses_slot_instead_of_label(): void
    {
        $subject = Blade::render('<x-action-link url="/events/1">Slot Content</x-action-link>');

        $this->assertStringContainsString('Slot Content', $subject);
    }
}
