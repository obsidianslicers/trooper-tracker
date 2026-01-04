<?php

declare(strict_types=1);

namespace Tests\Unit\Views\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ActionLinkCreateTest extends TestCase
{
    public function test_it_renders_with_default_label(): void
    {
        $subject = Blade::render('<x-action-link-create url="/events/create" />');

        $this->assertStringContainsString('href="/events/create"', $subject);
        $this->assertStringContainsString('Add', $subject);
        $this->assertStringContainsString('fa-add text-success', $subject);
    }

    public function test_it_renders_with_custom_label(): void
    {
        $subject = Blade::render('<x-action-link-create url="/events/create" label="Create New" />');

        $this->assertStringContainsString('Create New', $subject);
        $this->assertStringNotContainsString('Add', $subject);
    }
}
