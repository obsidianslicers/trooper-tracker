<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands;

use App\Mail\Account\AccountDeletionRequestedMail;
use App\Messages\Troopers\Commands\RequestTrooperDeletion;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RequestTrooperDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_queues_confirmation_email_and_marks_deletion_requested(): void
    {
        Mail::fake();

        $trooper = Trooper::factory()->asActive()->create();
        $this->assertNull($trooper->deletion_requested_at);

        $subject = new RequestTrooperDeletion($trooper);

        $subject->handle();

        $trooper->refresh();

        $this->assertNotNull($trooper->deletion_requested_at);

        Mail::assertQueued(AccountDeletionRequestedMail::class, function (AccountDeletionRequestedMail $mail) use ($trooper): bool
        {
            return $mail->trooper->id === $trooper->id;
        });
    }
}
