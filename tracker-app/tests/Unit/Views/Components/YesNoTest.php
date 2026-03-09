<?php

declare(strict_types=1);

namespace Tests\Unit\Views\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class YesNoTest extends TestCase
{
    public function test_it_renders_checkmark_when_value_is_true(): void
    {
        $subject = Blade::render('<x-yes-no :value="true" />');

        $this->assertStringContainsString('fa-check text-success', $subject);
        $this->assertStringNotContainsString('fa-times text-danger', $subject);
    }

    public function test_it_renders_x_when_value_is_false(): void
    {
        $subject = Blade::render('<x-yes-no :value="false" />');

        $this->assertStringContainsString('fa-times text-danger', $subject);
        $this->assertStringNotContainsString('fa-check text-success', $subject);
    }

    public function test_it_renders_nothing_when_value_is_false_and_blank_is_true(): void
    {
        $subject = Blade::render('<x-yes-no :value="false" :blank="true" />');

        $this->assertStringNotContainsString('fa-times', $subject);
        $this->assertStringNotContainsString('fa-check', $subject);
    }

    public function test_it_accepts_additional_classes(): void
    {
        $subject = Blade::render('<x-yes-no :value="true" class="custom-class" />');

        $this->assertStringContainsString('custom-class', $subject);
    }
}
