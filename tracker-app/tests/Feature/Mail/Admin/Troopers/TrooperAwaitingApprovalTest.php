<?php

declare(strict_types=1);

namespace Tests\Feature\Mail\Admin\Troopers;

use App\Mail\Admin\Troopers\TrooperAwaitingApproval;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrooperAwaitingApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_envelope_contains_expected_subject(): void
    {
        config(['mail.prefix' => '[TEST]']);

        $mail = new TrooperAwaitingApproval;

        $this->assertSame('[TEST] Awaiting Approval', $mail->envelope()->subject);
    }

    public function test_content_uses_awaiting_approval_view(): void
    {
        $mail = new TrooperAwaitingApproval;

        $this->assertSame('emails.admin.troopers.awaiting-approval', $mail->content()->view);
        $this->assertSame([], $mail->attachments());
    }
}
