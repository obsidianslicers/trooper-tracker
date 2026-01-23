<?php

declare(strict_types=1);

namespace Tests\Unit\Mail\Admin\Troopers;

use App\Mail\Admin\Troopers\TrooperAwaitingApproval;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrooperAwaitingApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_has_correct_subject(): void
    {
        $subject = new TrooperAwaitingApproval();

        $envelope = $subject->envelope();

        $this->assertEquals('Troop Tracker - Awaiting Approval', $envelope->subject);
    }

    public function test_it_uses_correct_view(): void
    {
        $subject = new TrooperAwaitingApproval();

        $content = $subject->content();

        $this->assertEquals('emails.admin.troopers.awaiting-approval', $content->view);
    }

    public function test_it_renders_without_error(): void
    {
        $subject = new TrooperAwaitingApproval();

        $this->assertInstanceOf(TrooperAwaitingApproval::class, $subject);

        $content = $subject->content();

        $this->assertNotNull($content->view);
    }

    public function test_it_has_no_attachments(): void
    {
        $subject = new TrooperAwaitingApproval();

        $attachments = $subject->attachments();

        $this->assertIsArray($attachments);
        $this->assertEmpty($attachments);
    }
}
