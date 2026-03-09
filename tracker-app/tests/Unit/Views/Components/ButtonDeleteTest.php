<?php

declare(strict_types=1);

namespace Tests\Unit\Views\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ButtonDeleteTest extends TestCase
{
    public function test_it_renders_as_button_with_correct_classes(): void
    {
        $subject = Blade::render('<x-button-delete>Remove Event</x-button-delete>');

        $this->assertStringContainsString('<button', $subject);
        $this->assertStringContainsString('type="button"', $subject);
        $this->assertStringContainsString('btn btn-outline-danger', $subject);
        $this->assertStringContainsString('Remove Event', $subject);
        $this->assertStringContainsString('fa-times', $subject);
    }

    public function test_it_accepts_additional_attributes(): void
    {
        $subject = Blade::render('<x-button-delete class="custom-class">Delete</x-button-delete>');

        $this->assertStringContainsString('custom-class', $subject);
    }
}
