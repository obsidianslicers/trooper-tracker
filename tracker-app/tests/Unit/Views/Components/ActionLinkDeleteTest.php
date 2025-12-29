<?php

declare(strict_types=1);

namespace Tests\Unit\Views\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ActionLinkDeleteTest extends TestCase
{
    public function test_it_renders_with_default_label(): void
    {
        $subject = Blade::render('<x-action-link-delete url="/events/1/delete" />');

        $this->assertStringContainsString('href="/events/1/delete"', $subject);
        $this->assertStringContainsString('Delete', $subject);
        $this->assertStringContainsString('fa-times text-danger', $subject);
    }

    public function test_it_renders_with_custom_label(): void
    {
        $subject = Blade::render('<x-action-link-delete url="/events/1/delete" label="Remove" />');

        $this->assertStringContainsString('Remove', $subject);
        $this->assertStringNotContainsString('Delete', $subject);
    }
}
