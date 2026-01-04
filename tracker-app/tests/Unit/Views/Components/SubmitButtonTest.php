<?php

declare(strict_types=1);

namespace Tests\Unit\Views\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class SubmitButtonTest extends TestCase
{
    public function test_it_renders_as_submit_button_by_default(): void
    {
        $subject = Blade::render('<x-submit-button>Save</x-submit-button>');

        $this->assertStringContainsString('<button', $subject);
        $this->assertStringContainsString('type="submit"', $subject);
        $this->assertStringContainsString('btn btn-primary', $subject);
        $this->assertStringContainsString('Save', $subject);
    }

    public function test_it_includes_htmx_attributes(): void
    {
        $subject = Blade::render('<x-submit-button>Submit</x-submit-button>');

        $this->assertStringContainsString('data-action="htmx-disable"', $subject);
        $this->assertStringContainsString('hx-headers', $subject);
        $this->assertStringContainsString('X-Dispatch-ID', $subject);
    }

    public function test_it_generates_unique_id(): void
    {
        $subject = Blade::render('<x-submit-button>Submit</x-submit-button>');

        $this->assertMatchesRegularExpression('/button-[a-z0-9]+/', $subject);
        $this->assertMatchesRegularExpression('/data-id="button-[a-z0-9]+"/', $subject);
    }

    public function test_it_accepts_additional_attributes(): void
    {
        $subject = Blade::render('<x-submit-button class="custom-class" disabled>Submit</x-submit-button>');

        $this->assertStringContainsString('custom-class', $subject);
        $this->assertStringContainsString('disabled', $subject);
    }

    public function test_type_can_be_overridden(): void
    {
        $subject = Blade::render('<x-submit-button type="button">Cancel</x-submit-button>');

        $this->assertStringContainsString('type="button"', $subject);
    }
}
