<?php

declare(strict_types=1);

namespace Tests\Unit\Views\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class LabelTest extends TestCase
{
    public function test_it_renders_with_value_prop(): void
    {
        $subject = Blade::render('<x-label value="Event Name" />');

        $this->assertStringContainsString('<label', $subject);
        $this->assertStringContainsString('form-label', $subject);
        $this->assertStringContainsString('Event Name', $subject);
    }

    public function test_it_renders_with_slot_content(): void
    {
        $subject = Blade::render('<x-label>Slot Content</x-label>');

        $this->assertStringContainsString('Slot Content', $subject);
    }

    public function test_slot_takes_precedence_over_value(): void
    {
        $subject = Blade::render('<x-label value="Value Text">Slot Text</x-label>');

        $this->assertStringContainsString('Value Text', $subject);
    }

    public function test_it_accepts_additional_attributes(): void
    {
        $subject = Blade::render('<x-label value="Name" for="event_name" class="custom-label" />');

        $this->assertStringContainsString('for="event_name"', $subject);
        $this->assertStringContainsString('custom-label', $subject);
    }
}
