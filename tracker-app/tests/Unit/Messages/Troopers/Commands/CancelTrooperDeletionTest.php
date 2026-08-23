<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands;

use App\Mail\Account\AccountDeletionCancelledMail;
use App\Messages\Troopers\Commands\CancelTrooperDeletion;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CancelTrooperDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_clears_deletion_request_and_queues_cancellation_email(): void
    {
        Mail::fake();

        $trooper = Trooper::factory()->asActive()->create([
            Trooper::DELETION_REQUESTED_AT => now(),
        ]);

        $subject = new CancelTrooperDeletion($trooper);

        $subject->handle();

        $trooper->refresh();

        $this->assertNull($trooper->deletion_requested_at);

        Mail::assertQueued(AccountDeletionCancelledMail::class, function (AccountDeletionCancelledMail $mail) use ($trooper): bool
        {
            return $mail->trooper->id === $trooper->id;
        });
    }
}
