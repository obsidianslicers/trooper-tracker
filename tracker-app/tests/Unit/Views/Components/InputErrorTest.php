<?php

declare(strict_types=1);

namespace Tests\Unit\Views\Components;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class InputErrorTest extends TestCase
{
    public function test_it_renders_nothing_when_no_error_exists(): void
    {
        $errors = new ViewErrorBag();
        $subject = Blade::render('<x-input-error property="event_name" />', ['errors' => $errors]);

        $this->assertEmpty(trim($subject));
    }

    public function test_it_renders_error_message_when_error_exists(): void
    {
        $errors = new ViewErrorBag();
        $messageBag = new \Illuminate\Support\MessageBag();
        $messageBag->add('event_name', 'The event name is required.');
        $errors->put('default', $messageBag);

        $subject = Blade::render('<x-input-error property="event_name" />', ['errors' => $errors]);

        $this->assertStringContainsString('The event name is required.', $subject);
        $this->assertStringContainsString('form-text text-danger', $subject);
    }
}
