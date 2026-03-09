<?php

declare(strict_types=1);

namespace Tests\Unit\Views\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class LinkButtonCancelTest extends TestCase
{
    public function test_it_renders_with_required_url(): void
    {
        $subject = Blade::render('<x-link-button-cancel url="/events" />');

        $this->assertStringContainsString('href="/events"', $subject);
        $this->assertStringContainsString('btn btn-secondary ms-3 px-4', $subject);
        $this->assertStringContainsString('Cancel', $subject);
    }

    public function test_it_renders_with_custom_slot_content(): void
    {
        $subject = Blade::render('<x-link-button-cancel url="/events">Go Back</x-link-button-cancel>');

        $this->assertStringContainsString('href="/events"', $subject);
        $this->assertStringContainsString('Go Back', $subject);
        $this->assertStringNotContainsString('Cancel', $subject);
    }

    public function test_it_accepts_additional_classes_via_attributes(): void
    {
        $subject = Blade::render('<x-link-button-cancel url="/events" class="custom-class" />');

        $this->assertStringContainsString('btn btn-secondary ms-3 px-4', $subject);
        $this->assertStringContainsString('custom-class', $subject);
    }

    public function test_it_accepts_additional_html_attributes(): void
    {
        $subject = Blade::render('<x-link-button-cancel url="/events" id="cancel-btn" data-test="value" />');

        $this->assertStringContainsString('id="cancel-btn"', $subject);
        $this->assertStringContainsString('data-test="value"', $subject);
    }

    public function test_it_renders_as_anchor_tag(): void
    {
        $subject = Blade::render('<x-link-button-cancel url="/events" />');

        $this->assertStringStartsWith('<a', trim($subject));
        $this->assertStringEndsWith('</a>', trim($subject));
    }
}
