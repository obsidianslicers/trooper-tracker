<?php

declare(strict_types=1);

namespace Tests\Unit\Views\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class LoadingTest extends TestCase
{
    public function test_it_renders_loading_spinner(): void
    {
        $subject = Blade::render('<x-loading />');

        $this->assertStringContainsString('fa-spinner fa-spin fa-3x', $subject);
        $this->assertStringContainsString('Loading ... stand by, trooper', $subject);
        $this->assertStringContainsString('text-center py-4', $subject);
    }
}
