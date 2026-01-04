<?php

declare(strict_types=1);

namespace Tests\Unit\Views\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class InputHiddenTest extends TestCase
{
    public function test_it_renders_hidden_input_with_required_property(): void
    {
        $subject = Blade::render('<x-input-hidden property="event_id" />');

        $this->assertStringContainsString('type="hidden"', $subject);
        $this->assertStringContainsString('name="event_id"', $subject);
        $this->assertStringContainsString('id="event_id"', $subject);
    }

    public function test_it_renders_with_value(): void
    {
        $subject = Blade::render('<x-input-hidden property="event_id" value="123" />');

        $this->assertStringContainsString('value="123"', $subject);
    }

    public function test_it_converts_dotted_property_to_bracket_notation(): void
    {
        $subject = Blade::render('<x-input-hidden property="event.id" />');

        $this->assertStringContainsString('name="event[id]"', $subject);
    }
}
