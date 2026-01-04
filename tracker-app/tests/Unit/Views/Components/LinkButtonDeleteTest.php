<?php

declare(strict_types=1);

namespace Tests\Unit\Views\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class LinkButtonDeleteTest extends TestCase
{
    public function test_it_renders_with_required_url(): void
    {
        $subject = Blade::render('<x-link-button-delete url="/events/1/delete">Delete Event</x-link-button-delete>');

        $this->assertStringContainsString('href="/events/1/delete"', $subject);
        $this->assertStringContainsString('btn btn-sm btn-outline-danger', $subject);
        $this->assertStringContainsString('Delete Event', $subject);
        $this->assertStringContainsString('fa-times', $subject);
    }

    public function test_it_includes_float_end_class(): void
    {
        $subject = Blade::render('<x-link-button-delete url="/events/1/delete">Delete</x-link-button-delete>');

        $this->assertStringContainsString('float-end', $subject);
    }

    public function test_it_renders_as_anchor_tag(): void
    {
        $subject = Blade::render('<x-link-button-delete url="/events/1/delete">Delete</x-link-button-delete>');

        $this->assertStringStartsWith('<a', trim($subject));
        $this->assertStringEndsWith('</a>', trim($subject));
    }
}
