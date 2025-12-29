<?php

declare(strict_types=1);

namespace Tests\Unit\Views\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class LinkButtonUpdateTest extends TestCase
{
    public function test_it_renders_with_required_url(): void
    {
        $subject = Blade::render('<x-link-button-update url="/events/1/edit">Edit Event</x-link-button-update>');

        $this->assertStringContainsString('href="/events/1/edit"', $subject);
        $this->assertStringContainsString('btn btn-sm btn-outline-warning', $subject);
        $this->assertStringContainsString('Edit Event', $subject);
        $this->assertStringContainsString('fa-edit', $subject);
    }

    public function test_it_includes_float_end_class(): void
    {
        $subject = Blade::render('<x-link-button-update url="/events/1/edit">Edit</x-link-button-update>');

        $this->assertStringContainsString('float-end', $subject);
    }

    public function test_it_renders_as_anchor_tag(): void
    {
        $subject = Blade::render('<x-link-button-update url="/events/1/edit">Edit</x-link-button-update>');

        $this->assertStringStartsWith('<a', trim($subject));
        $this->assertStringEndsWith('</a>', trim($subject));
    }
}
