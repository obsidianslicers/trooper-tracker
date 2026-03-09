<?php

declare(strict_types=1);

namespace Tests\Unit\Views\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ButtonCreateTest extends TestCase
{
    public function test_it_renders_as_button_with_correct_classes(): void
    {
        $subject = Blade::render('<x-button-create>Add Event</x-button-create>');

        $this->assertStringContainsString('<button', $subject);
        $this->assertStringContainsString('type="button"', $subject);
        $this->assertStringContainsString('btn btn-outline-success', $subject);
        $this->assertStringContainsString('Add Event', $subject);
        $this->assertStringContainsString('fa-add', $subject);
    }

    public function test_it_accepts_additional_attributes(): void
    {
        $subject = Blade::render('<x-button-create class="custom-class" data-test="value">Add</x-button-create>');

        $this->assertStringContainsString('custom-class', $subject);
        $this->assertStringContainsString('data-test="value"', $subject);
    }
}
