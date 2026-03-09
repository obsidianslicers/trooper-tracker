<?php

declare(strict_types=1);

namespace Tests\Unit\Views\Components;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class InputPasswordTest extends TestCase
{
    public function test_it_renders_password_input_with_required_property(): void
    {
        $errors = new ViewErrorBag();
        view()->share('errors', $errors);

        $subject = Blade::render('<x-input-password property="password" />');

        $this->assertStringContainsString('type="password"', $subject);
        $this->assertStringContainsString('name="password"', $subject);
        $this->assertStringContainsString('id="password"', $subject);
        $this->assertStringContainsString('form-control', $subject);
    }

    public function test_it_always_has_empty_value(): void
    {
        $errors = new ViewErrorBag();
        view()->share('errors', $errors);

        $subject = Blade::render('<x-input-password property="password" />');

        $this->assertStringContainsString('value=""', $subject);
    }

    public function test_it_handles_disabled_attribute(): void
    {
        $errors = new ViewErrorBag();
        view()->share('errors', $errors);

        $subject = Blade::render('<x-input-password property="password" :disabled="true" />');

        $this->assertStringContainsString('disabled', $subject);
    }

    public function test_it_converts_dotted_property_to_bracket_notation(): void
    {
        $errors = new ViewErrorBag();
        view()->share('errors', $errors);

        $subject = Blade::render('<x-input-password property="user.password" />');

        $this->assertStringContainsString('name="user[password]"', $subject);
    }
}
