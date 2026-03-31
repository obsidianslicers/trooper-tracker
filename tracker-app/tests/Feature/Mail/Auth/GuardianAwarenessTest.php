<?php

declare(strict_types=1);

namespace Tests\Feature\Mail\Auth;

use App\Mail\Auth\GuardianAwareness;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuardianAwarenessTest extends TestCase
{
    use RefreshDatabase;

    public function test_mailable_implements_should_queue(): void
    {
        $mail = new GuardianAwareness;

        $this->assertInstanceOf(ShouldQueue::class, $mail);
    }

    public function test_envelope_contains_expected_subject(): void
    {
        config(['mail.prefix' => '[TEST]']);

        $mail = new GuardianAwareness;

        $this->assertSame('[TEST] Parent/Guardian of Cadet Registration', $mail->envelope()->subject);
    }

    public function test_content_uses_guardian_awareness_view(): void
    {
        $mail = new GuardianAwareness;

        $this->assertSame('emails.auth.guardian-awareness', $mail->content()->view);
    }

    public function test_attachments_are_empty(): void
    {
        $mail = new GuardianAwareness;

        $this->assertSame([], $mail->attachments());
    }
}
