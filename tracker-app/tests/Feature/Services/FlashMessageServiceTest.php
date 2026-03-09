<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\Event;
use App\Services\FlashMessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlashMessageServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_success_warning_and_danger_store_messages_by_type(): void
    {
        $subject = new FlashMessageService;

        $subject->success('ok');
        $subject->warning('warn');
        $subject->danger('bad');

        $messages = $subject->getMessages();

        $this->assertSame(['ok'], $messages['success']);
        $this->assertSame(['warn'], $messages['warning']);
        $this->assertSame(['bad'], $messages['danger']);
    }

    public function test_created_builds_model_name_message_with_name_attribute(): void
    {
        $subject = new FlashMessageService;
        $event = Event::factory()->create([Event::NAME => 'Founders Troop']);

        $subject->created($event);

        $messages = $subject->getMessages();

        $this->assertCount(1, $messages['success']);
        $this->assertStringContainsString('Event "Founders Troop" was created successfully.', $messages['success'][0]);
    }

    public function test_get_messages_clears_flash_bucket_after_read(): void
    {
        $subject = new FlashMessageService;

        $subject->success('first');
        $this->assertSame(['first'], $subject->getMessages()['success']);

        $this->assertSame([], $subject->getMessages());
    }
}
