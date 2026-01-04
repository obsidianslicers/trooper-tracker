<?php

declare(strict_types=1);

namespace Tests\Unit\Views\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class CardTest extends TestCase
{
    public function test_it_renders_basic_card_with_slot(): void
    {
        $subject = Blade::render('<x-card>Card Content</x-card>');

        $this->assertStringContainsString('card mb-3', $subject);
        $this->assertStringContainsString('card-body', $subject);
        $this->assertStringContainsString('Card Content', $subject);
    }

    public function test_it_renders_with_label_header(): void
    {
        $subject = Blade::render('<x-card label="Card Title">Content</x-card>');

        $this->assertStringContainsString('card-header', $subject);
        $this->assertStringContainsString('Card Title', $subject);
    }

    public function test_it_renders_without_header_when_label_is_empty(): void
    {
        $subject = Blade::render('<x-card>Content</x-card>');

        $this->assertStringNotContainsString('card-header', $subject);
    }

    public function test_it_includes_danger_border_when_danger_is_true(): void
    {
        $subject = Blade::render('<x-card :danger="true">Content</x-card>');

        $this->assertStringContainsString('border-danger', $subject);
    }

    public function test_it_does_not_include_danger_border_by_default(): void
    {
        $subject = Blade::render('<x-card>Content</x-card>');

        $this->assertStringNotContainsString('border-danger', $subject);
    }
}
