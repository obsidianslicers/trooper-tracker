<?php

declare(strict_types=1);

namespace Tests\Feature\Mail\Auth;

use App\Mail\Auth\VerifyTrooperEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerifyTrooperEmailTest extends TestCase
{
    use RefreshDatabase;

    private const VERIFY_URL = 'https://example.test/verify-email/token-123';

    public function test_mailable_implements_should_queue(): void
    {
        $url = self::VERIFY_URL;
        $mail = new VerifyTrooperEmail($url);

        $this->assertInstanceOf(ShouldQueue::class, $mail);
    }

    public function test_envelope_contains_expected_subject(): void
    {
        config(['mail.prefix' => '[TEST]']);

        $url = self::VERIFY_URL;
        $mail = new VerifyTrooperEmail($url);

        $this->assertSame('[TEST] Verify Your Email Address', $mail->envelope()->subject);
    }

    public function test_content_uses_verify_email_view(): void
    {
        $url = self::VERIFY_URL;
        $mail = new VerifyTrooperEmail($url);
        $content = $mail->content();

        $this->assertSame('emails.auth.verify-email', $content->view);
        $this->assertSame(['url' => $url], $content->with);
    }

    public function test_attachments_are_empty(): void
    {
        $url = self::VERIFY_URL;
        $mail = new VerifyTrooperEmail($url);

        $this->assertSame([], $mail->attachments());
    }
}
