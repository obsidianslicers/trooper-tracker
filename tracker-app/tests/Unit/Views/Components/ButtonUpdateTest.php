<?php

declare(strict_types=1);

namespace Tests\Unit\Views\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ButtonUpdateTest extends TestCase
{
    public function test_it_renders_as_button_with_correct_classes(): void
    {
        $subject = Blade::render('<x-button-update>Edit Event</x-button-update>');

        $this->assertStringContainsString('<button', $subject);
        $this->assertStringContainsString('type="button"', $subject);
        $this->assertStringContainsString('btn btn-outline-primary', $subject);
        $this->assertStringContainsString('Edit Event', $subject);
        $this->assertStringContainsString('fa-edit', $subject);
    }

    public function test_it_accepts_additional_attributes(): void
    {
        $subject = Blade::render('<x-button-update class="custom-class">Edit</x-button-update>');

        $this->assertStringContainsString('custom-class', $subject);
    }
}
