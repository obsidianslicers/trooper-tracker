<?php

declare(strict_types=1);

namespace Tests\Unit\Views\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ButtonGroupTest extends TestCase
{
    public function test_it_renders_button_group_container(): void
    {
        $subject = Blade::render('<x-button-group>Buttons</x-button-group>');

        $this->assertStringContainsString('btn-group', $subject);
        $this->assertStringContainsString('mb-3', $subject);
        $this->assertStringContainsString('Buttons', $subject);
    }

    public function test_it_wraps_content_in_div(): void
    {
        $subject = Blade::render('<x-button-group>Content</x-button-group>');

        $this->assertStringStartsWith('<div', trim($subject));
        $this->assertStringEndsWith('</div>', trim($subject));
    }
}
