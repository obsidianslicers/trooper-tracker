<?php

declare(strict_types=1);

namespace Tests\Feature\Mail\Auth;

use App\Mail\Auth\TrooperRegistered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrooperRegisteredTest extends TestCase
{
    use RefreshDatabase;

    public function test_envelope_contains_expected_subject(): void
    {
        config(['mail.prefix' => '[TEST]']);

        $mail = new TrooperRegistered;

        $this->assertSame('[TEST] Thanks for Registering!', $mail->envelope()->subject);
    }

    public function test_content_uses_registered_view(): void
    {
        $mail = new TrooperRegistered;

        $this->assertSame('emails.auth.registered', $mail->content()->view);
        $this->assertSame([], $mail->attachments());
    }
}
