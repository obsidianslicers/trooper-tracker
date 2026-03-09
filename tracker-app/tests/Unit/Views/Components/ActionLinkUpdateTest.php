<?php

declare(strict_types=1);

namespace Tests\Unit\Views\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ActionLinkUpdateTest extends TestCase
{
    public function test_it_renders_with_default_label(): void
    {
        $subject = Blade::render('<x-action-link-update url="/events/1/edit" />');

        $this->assertStringContainsString('href="/events/1/edit"', $subject);
        $this->assertStringContainsString('Update', $subject);
        $this->assertStringContainsString('fa-pencil', $subject);
    }

    public function test_it_renders_with_custom_label(): void
    {
        $subject = Blade::render('<x-action-link-update url="/events/1/edit" label="Edit Event" />');

        $this->assertStringContainsString('Edit Event', $subject);
        $this->assertStringNotContainsString('Update', $subject);
    }
}
