<?php

declare(strict_types=1);

namespace Tests\Unit\Views\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class SlimContainerTest extends TestCase
{
    public function test_it_renders_with_default_responsive_classes(): void
    {
        $subject = Blade::render('<x-slim-container>Content</x-slim-container>');

        $this->assertStringContainsString('col-lg-8 col-md-9 col-sm-12', $subject);
        $this->assertStringContainsString('m-auto mb-5 p-0', $subject);
        $this->assertStringContainsString('Content', $subject);
    }

    public function test_it_accepts_additional_classes(): void
    {
        $subject = Blade::render('<x-slim-container class="custom-class">Content</x-slim-container>');

        $this->assertStringContainsString('custom-class', $subject);
        $this->assertStringContainsString('col-lg-8', $subject);
    }

    public function test_it_wraps_content_in_div(): void
    {
        $subject = Blade::render('<x-slim-container>Content</x-slim-container>');

        $this->assertStringStartsWith('<div', trim($subject));
        $this->assertStringEndsWith('</div>', trim($subject));
    }
}
