<?php

declare(strict_types=1);

namespace Tests\Unit\Views\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class InputHelpTest extends TestCase
{
    public function test_it_renders_help_text(): void
    {
        $subject = Blade::render('<x-input-help>This is help text</x-input-help>');

        $this->assertStringContainsString('form-text text-muted', $subject);
        $this->assertStringContainsString('This is help text', $subject);
    }

    public function test_it_wraps_content_in_div(): void
    {
        $subject = Blade::render('<x-input-help>Help text</x-input-help>');

        $this->assertStringStartsWith('<div', trim($subject));
        $this->assertStringEndsWith('</div>', trim($subject));
    }
}
