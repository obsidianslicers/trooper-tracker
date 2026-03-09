<?php

declare(strict_types=1);

namespace Tests\Unit\Views\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class MessageTest extends TestCase
{
    public function test_it_renders_with_default_type(): void
    {
        $subject = Blade::render('<x-message>This is a message</x-message>');

        $this->assertStringContainsString('alert alert-info', $subject);
        $this->assertStringContainsString('border-info', $subject);
        $this->assertStringContainsString('This is a message', $subject);
    }

    public function test_it_renders_with_custom_type(): void
    {
        $subject = Blade::render('<x-message type="warning">Warning message</x-message>');

        $this->assertStringContainsString('alert alert-warning', $subject);
        $this->assertStringContainsString('border-warning', $subject);
        $this->assertStringContainsString('Warning message', $subject);
    }

    public function test_it_renders_with_default_icon(): void
    {
        $subject = Blade::render('<x-message>Message</x-message>');

        $this->assertStringContainsString('fa-exclamation-circle', $subject);
    }

    public function test_it_renders_with_custom_icon(): void
    {
        $subject = Blade::render('<x-message icon="fa-warning">Message</x-message>');

        $this->assertStringContainsString('fa-warning', $subject);
        $this->assertStringNotContainsString('fa-exclamation-circle', $subject);
    }
}
