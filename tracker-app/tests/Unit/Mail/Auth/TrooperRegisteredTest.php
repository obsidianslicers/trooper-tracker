<?php

declare(strict_types=1);

namespace Tests\Unit\Mail\Auth;

use App\Mail\Auth\TrooperRegistered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrooperRegisteredTest extends TestCase
{
    use RefreshDatabase;
    public function test_it_has_correct_subject(): void
    {
        $subject = new TrooperRegistered();

        $envelope = $subject->envelope();

        $this->assertEquals('Troop Tracker - Thanks for Registering!', $envelope->subject);
    }

    public function test_it_uses_correct_view(): void
    {
        $subject = new TrooperRegistered();

        $content = $subject->content();

        $this->assertEquals('emails.auth.registered', $content->view);
    }

    public function test_it_renders_without_error(): void
    {
        $subject = new TrooperRegistered();

        $this->assertInstanceOf(TrooperRegistered::class, $subject);

        $content = $subject->content();

        $this->assertNotNull($content->view);
    }

    public function test_it_has_no_attachments(): void
    {
        $subject = new TrooperRegistered();

        $attachments = $subject->attachments();

        $this->assertIsArray($attachments);
        $this->assertEmpty($attachments);
    }
}
