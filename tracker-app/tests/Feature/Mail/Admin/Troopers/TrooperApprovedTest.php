<?php

declare(strict_types=1);

namespace Tests\Feature\Mail\Admin\Troopers;

use App\Mail\Admin\Troopers\TrooperApproved;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrooperApprovedTest extends TestCase
{
    use RefreshDatabase;

    public function test_envelope_contains_expected_subject(): void
    {
        config(['mail.prefix' => '[TEST]']);

        $trooper = Trooper::factory()->create();
        $mail = new TrooperApproved($trooper);

        $this->assertSame('[TEST] Welcome, Trooper! You Passed Inspection!', $mail->envelope()->subject);
    }

    public function test_content_contains_view_and_trooper(): void
    {
        $trooper = Trooper::factory()->create();

        $mail = new TrooperApproved($trooper);
        $content = $mail->content();

        $this->assertSame('emails.admin.troopers.trooper-approved', $content->view);
        $this->assertSame($trooper, $content->with['trooper']);
        $this->assertSame([], $mail->attachments());
    }
}
