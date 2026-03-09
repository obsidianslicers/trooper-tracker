<?php

declare(strict_types=1);

namespace Tests\Unit\Views\Components;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class InputCheckboxTest extends TestCase
{
    public function test_it_renders_checkbox_input_with_required_property(): void
    {
        $errors = new ViewErrorBag();
        view()->share('errors', $errors);

        $subject = Blade::render('<x-input-checkbox property="is_active" />');

        $this->assertStringContainsString('type="checkbox"', $subject);
        $this->assertStringContainsString('name="is_active"', $subject);
        $this->assertStringContainsString('id="is_active"', $subject);
        $this->assertStringContainsString('form-check-input', $subject);
    }

    public function test_it_renders_with_default_value(): void
    {
        $errors = new ViewErrorBag();
        view()->share('errors', $errors);

        $subject = Blade::render('<x-input-checkbox property="is_active" />');

        $this->assertStringContainsString('value="1"', $subject);
    }

    public function test_it_renders_with_custom_value(): void
    {
        $errors = new ViewErrorBag();
        view()->share('errors', $errors);

        $subject = Blade::render('<x-input-checkbox property="terms" value="accepted" />');

        $this->assertStringContainsString('value="accepted"', $subject);
    }

    public function test_it_renders_with_label(): void
    {
        $errors = new ViewErrorBag();
        view()->share('errors', $errors);

        $subject = Blade::render('<x-input-checkbox property="is_active" label="Active Status" />');

        $this->assertStringContainsString('Active Status', $subject);
        $this->assertStringContainsString('form-check-label', $subject);
        $this->assertStringContainsString('for="is_active"', $subject);
    }

    public function test_it_can_be_checked(): void
    {
        $errors = new ViewErrorBag();
        view()->share('errors', $errors);

        $subject = Blade::render('<x-input-checkbox property="is_active" :checked="true" />');

        $this->assertStringContainsString('checked', $subject);
    }

    public function test_it_can_be_disabled(): void
    {
        $errors = new ViewErrorBag();
        view()->share('errors', $errors);

        $subject = Blade::render('<x-input-checkbox property="is_active" :disabled="true" />');

        $this->assertStringContainsString('disabled', $subject);
    }
}
