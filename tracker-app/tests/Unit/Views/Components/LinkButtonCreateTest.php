<?php

declare(strict_types=1);

namespace Tests\Unit\Views\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class LinkButtonCreateTest extends TestCase
{
    public function test_it_renders_with_required_url(): void
    {
        $subject = Blade::render('<x-link-button-create url="/events/create">Add Event</x-link-button-create>');

        $this->assertStringContainsString('href="/events/create"', $subject);
        $this->assertStringContainsString('btn btn-sm btn-outline-success', $subject);
        $this->assertStringContainsString('Add Event', $subject);
        $this->assertStringContainsString('fa-add', $subject);
    }

    public function test_it_includes_float_end_class(): void
    {
        $subject = Blade::render('<x-link-button-create url="/events/create">Add</x-link-button-create>');

        $this->assertStringContainsString('float-end', $subject);
    }

    public function test_it_renders_as_anchor_tag(): void
    {
        $subject = Blade::render('<x-link-button-create url="/events/create">Add</x-link-button-create>');

        $this->assertStringStartsWith('<a', trim($subject));
        $this->assertStringEndsWith('</a>', trim($subject));
    }
}
