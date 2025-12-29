<?php

declare(strict_types=1);

namespace Tests\Unit\Views\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ActionLinkCopyTest extends TestCase
{
    public function test_it_renders_with_default_label(): void
    {
        $subject = Blade::render('<x-action-link-copy url="/events/1/copy" />');

        $this->assertStringContainsString('href="/events/1/copy"', $subject);
        $this->assertStringContainsString('Copy', $subject);
        $this->assertStringContainsString('fa-copy', $subject);
    }

    public function test_it_renders_with_custom_label(): void
    {
        $subject = Blade::render('<x-action-link-copy url="/events/1/copy" label="Duplicate" />');

        $this->assertStringContainsString('Duplicate', $subject);
        $this->assertStringNotContainsString('Copy', $subject);
    }
}
