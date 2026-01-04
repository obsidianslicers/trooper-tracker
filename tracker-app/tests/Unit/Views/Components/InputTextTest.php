<?php

declare(strict_types=1);

namespace Tests\Unit\Views\Components;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class InputTextTest extends TestCase
{
    public function test_it_renders_text_input_with_required_property(): void
    {
        $errors = new ViewErrorBag();
        view()->share('errors', $errors);

        $subject = Blade::render('<x-input-text property="event_name" />');

        $this->assertStringContainsString('type="text"', $subject);
        $this->assertStringContainsString('name="event_name"', $subject);
        $this->assertStringContainsString('id="event_name"', $subject);
        $this->assertStringContainsString('form-control', $subject);
    }

    public function test_it_renders_with_value(): void
    {
        $errors = new ViewErrorBag();
        view()->share('errors', $errors);

        $subject = Blade::render('<x-input-text property="event_name" value="Test Event" />');

        $this->assertStringContainsString('value="Test Event"', $subject);
    }

    public function test_it_renders_as_textarea_when_multiline(): void
    {
        $errors = new ViewErrorBag();
        view()->share('errors', $errors);

        $subject = Blade::render('<x-input-text property="description" :multiline="true" />');

        $this->assertStringContainsString('<textarea', $subject);
        $this->assertStringContainsString('name="description"', $subject);
        $this->assertStringNotContainsString('type="text"', $subject);
    }

    public function test_textarea_renders_with_custom_rows(): void
    {
        $errors = new ViewErrorBag();
        view()->share('errors', $errors);

        $subject = Blade::render('<x-input-text property="description" :multiline="true" :rows="10" />');

        $this->assertStringContainsString('rows="10"', $subject);
    }

    public function test_it_handles_disabled_attribute(): void
    {
        $errors = new ViewErrorBag();
        view()->share('errors', $errors);

        $subject = Blade::render('<x-input-text property="event_name" :disabled="true" />');

        $this->assertStringContainsString('disabled', $subject);
    }

    public function test_it_converts_dotted_property_to_bracket_notation(): void
    {
        $errors = new ViewErrorBag();
        view()->share('errors', $errors);

        $subject = Blade::render('<x-input-text property="event.name" />');

        $this->assertStringContainsString('name="event[name]"', $subject);
    }
}
