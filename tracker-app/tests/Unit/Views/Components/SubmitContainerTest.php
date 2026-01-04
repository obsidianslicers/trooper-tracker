<?php

declare(strict_types=1);

namespace Tests\Unit\Views\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class SubmitContainerTest extends TestCase
{
    public function test_it_renders_with_text_end_alignment(): void
    {
        $subject = Blade::render('<x-submit-container>Button Content</x-submit-container>');

        $this->assertStringContainsString('text-end', $subject);
        $this->assertStringContainsString('mb-4', $subject);
        $this->assertStringContainsString('Button Content', $subject);
    }

    public function test_it_wraps_content_in_div(): void
    {
        $subject = Blade::render('<x-submit-container>Content</x-submit-container>');

        $this->assertStringStartsWith('<div', trim($subject));
        $this->assertStringEndsWith('</div>', trim($subject));
    }
}
