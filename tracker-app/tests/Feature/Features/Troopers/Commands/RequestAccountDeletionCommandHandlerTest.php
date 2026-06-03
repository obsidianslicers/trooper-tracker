<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Commands;

use App\Features\Troopers\Commands\RequestAccountDeletionCommand;
use App\Features\Troopers\Commands\RequestAccountDeletionCommandHandler;
use App\Mail\Account\AccountDeletionRequestedMail;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * @see RequestAccountDeletionCommandHandler
 */
class RequestAccountDeletionCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_sets_deletion_requested_at(): void
    {
        Mail::fake();

        $trooper = Trooper::factory()->asActive()->create();
        $this->assertNull($trooper->deletion_requested_at);

        $handler = app(RequestAccountDeletionCommandHandler::class);
        $handler(new RequestAccountDeletionCommand($trooper));

        $this->assertNotNull($trooper->fresh()->deletion_requested_at);
    }

    public function test_invoke_queues_confirmation_email(): void
    {
        Mail::fake();

        $trooper = Trooper::factory()->asActive()->create();

        $handler = app(RequestAccountDeletionCommandHandler::class);
        $handler(new RequestAccountDeletionCommand($trooper));

        Mail::assertQueued(AccountDeletionRequestedMail::class, function (AccountDeletionRequestedMail $mail) use ($trooper): bool
        {
            return $mail->trooper->id === $trooper->id;
        });
    }
}
