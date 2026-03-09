<?php

declare(strict_types=1);

namespace Tests\Unit\Views\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ActionSeparatorTest extends TestCase
{
    public function test_it_renders_dropdown_divider(): void
    {
        $subject = Blade::render('<x-action-separator />');

        $this->assertStringContainsString('<li>', $subject);
        $this->assertStringContainsString('dropdown-divider', $subject);
        $this->assertStringContainsString('<hr', $subject);
    }
}
