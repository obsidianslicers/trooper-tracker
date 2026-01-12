<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Jobs\SendExceptionNotificationJob;
use App\Mail\ExceptionOccurred;
use App\Models\Trooper;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Feature tests for SendExceptionNotificationJob.
 *
 * Verifies:
 * - Job sends exception emails to all administrator troopers
 * - Uses GetTrooperAdministratorsQuery to retrieve administrators
 * - Queues ExceptionOccurred mailable for each administrator
 * - Passes exception and context to mailable
 * - Handles empty administrator list gracefully
 * - Orchestrates service calls correctly
 */
class SendExceptionNotificationJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_sends_email_to_all_administrators(): void
    {
        // Arrange
        Mail::fake();
        $admin1 = Trooper::factory()->asAdministrator()->create();
        $admin2 = Trooper::factory()->asAdministrator()->create();
        $admin3 = Trooper::factory()->asAdministrator()->create();

        $exception = new Exception('Critical system error');
        $subject = new SendExceptionNotificationJob($exception);

        // Act
        $subject->handle(new \App\Services\Troopers\GetTrooperAdministratorsQuery());

        // Assert
        Mail::assertQueued(ExceptionOccurred::class, 3);
        Mail::assertQueued(ExceptionOccurred::class, fn($mail) =>
            $mail->hasTo($admin1->email)
        );
        Mail::assertQueued(ExceptionOccurred::class, fn($mail) =>
            $mail->hasTo($admin2->email)
        );
        Mail::assertQueued(ExceptionOccurred::class, fn($mail) =>
            $mail->hasTo($admin3->email)
        );
    }

    public function test_handle_does_not_send_to_non_administrators(): void
    {
        // Arrange
        Mail::fake();
        $admin = Trooper::factory()->asAdministrator()->create();
        $moderator = Trooper::factory()->asModerator()->create();
        $regular = Trooper::factory()->asActive()->create();

        $exception = new Exception('Test exception');
        $subject = new SendExceptionNotificationJob($exception);

        // Act
        $subject->handle(new \App\Services\Troopers\GetTrooperAdministratorsQuery());

        // Assert
        Mail::assertQueued(ExceptionOccurred::class, 1);
        Mail::assertQueued(ExceptionOccurred::class, fn($mail) =>
            $mail->hasTo($admin->email)
        );
        Mail::assertNotQueued(ExceptionOccurred::class, fn($mail) =>
            $mail->hasTo($moderator->email)
        );
        Mail::assertNotQueued(ExceptionOccurred::class, fn($mail) =>
            $mail->hasTo($regular->email)
        );
    }

    public function test_handle_sends_no_emails_when_no_administrators(): void
    {
        // Arrange
        Mail::fake();
        Trooper::factory()->asActive()->create();
        Trooper::factory()->asModerator()->create();

        $exception = new Exception('Test exception');
        $subject = new SendExceptionNotificationJob($exception);

        // Act
        $subject->handle(new \App\Services\Troopers\GetTrooperAdministratorsQuery());

        // Assert
        Mail::assertNothingQueued();
    }

    public function test_handle_passes_exception_to_mailable(): void
    {
        // Arrange
        Mail::fake();
        $admin = Trooper::factory()->asAdministrator()->create();
        $exception = new Exception('Database connection failed');
        $subject = new SendExceptionNotificationJob($exception);

        // Act
        $subject->handle(new \App\Services\Troopers\GetTrooperAdministratorsQuery());

        // Assert - verify email was queued to admin
        Mail::assertQueued(ExceptionOccurred::class, 1);
        Mail::assertQueued(ExceptionOccurred::class, fn($mail) =>
            $mail->hasTo($admin->email)
        );
    }

    public function test_handle_passes_context_to_mailable(): void
    {
        // Arrange
        Mail::fake();
        $admin = Trooper::factory()->asAdministrator()->create();
        $exception = new Exception('Test exception');
        $context = [
            'request_url' => '/events/create',
            'user_id' => 42,
            'environment' => 'production',
        ];
        $subject = new SendExceptionNotificationJob($exception, $context);

        // Act
        $subject->handle(new \App\Services\Troopers\GetTrooperAdministratorsQuery());

        // Assert - verify email was queued to admin
        Mail::assertQueued(ExceptionOccurred::class, 1);
        Mail::assertQueued(ExceptionOccurred::class, fn($mail) =>
            $mail->hasTo($admin->email)
        );
    }

    public function test_handle_passes_empty_context_when_not_provided(): void
    {
        // Arrange
        Mail::fake();
        $admin = Trooper::factory()->asAdministrator()->create();
        $exception = new Exception('Test exception');
        $subject = new SendExceptionNotificationJob($exception);

        // Act
        $subject->handle(new \App\Services\Troopers\GetTrooperAdministratorsQuery());

        // Assert - verify email was queued to admin
        Mail::assertQueued(ExceptionOccurred::class, 1);
        Mail::assertQueued(ExceptionOccurred::class, fn($mail) =>
            $mail->hasTo($admin->email)
        );
    }

    public function test_handle_queues_emails_asynchronously(): void
    {
        // Arrange
        Mail::fake();
        $admin = Trooper::factory()->asAdministrator()->create();
        $exception = new Exception('Test exception');
        $subject = new SendExceptionNotificationJob($exception);

        // Act
        $subject->handle(new \App\Services\Troopers\GetTrooperAdministratorsQuery());

        // Assert - Mail::queue() was used, not Mail::send()
        Mail::assertQueued(ExceptionOccurred::class);
    }

    public function test_handle_passes_correct_trooper_to_each_mailable(): void
    {
        // Arrange
        Mail::fake();
        $admin1 = Trooper::factory()->asAdministrator()->create([
            Trooper::EMAIL => 'admin1@501st.com',
        ]);
        $admin2 = Trooper::factory()->asAdministrator()->create([
            Trooper::EMAIL => 'admin2@501st.com',
        ]);

        $exception = new Exception('Test exception');
        $subject = new SendExceptionNotificationJob($exception);

        // Act
        $subject->handle(new \App\Services\Troopers\GetTrooperAdministratorsQuery());

        // Assert - verify emails were queued to both admins
        Mail::assertQueued(ExceptionOccurred::class, 2);
        Mail::assertQueued(ExceptionOccurred::class, fn($mail) =>
            $mail->hasTo($admin1->email)
        );
        Mail::assertQueued(ExceptionOccurred::class, fn($mail) =>
            $mail->hasTo($admin2->email)
        );
    }

    public function test_handle_works_with_different_exception_types(): void
    {
        // Arrange
        Mail::fake();
        $admin = Trooper::factory()->asAdministrator()->create();
        $exception = new \RuntimeException('Runtime error occurred');
        $subject = new SendExceptionNotificationJob($exception);

        // Act
        $subject->handle(new \App\Services\Troopers\GetTrooperAdministratorsQuery());

        // Assert - verify email was queued to admin
        Mail::assertQueued(ExceptionOccurred::class, 1);
        Mail::assertQueued(ExceptionOccurred::class, fn($mail) =>
            $mail->hasTo($admin->email)
        );
    }

    public function test_handle_sends_to_all_administrators_regardless_of_status(): void
    {
        // Arrange
        Mail::fake();
        $active_admin = Trooper::factory()->asAdministrator()->create();
        $pending_admin = Trooper::factory()->asPending()->create([
            Trooper::MEMBERSHIP_ROLE => \App\Enums\MembershipRole::ADMINISTRATOR,
        ]);
        $retired_admin = Trooper::factory()->asRetired()->create([
            Trooper::MEMBERSHIP_ROLE => \App\Enums\MembershipRole::ADMINISTRATOR,
        ]);

        $exception = new Exception('Test exception');
        $subject = new SendExceptionNotificationJob($exception);

        // Act
        $subject->handle(new \App\Services\Troopers\GetTrooperAdministratorsQuery());

        // Assert
        Mail::assertQueued(ExceptionOccurred::class, 3);
        Mail::assertQueued(ExceptionOccurred::class, fn($mail) =>
            $mail->hasTo($active_admin->email)
        );
        Mail::assertQueued(ExceptionOccurred::class, fn($mail) =>
            $mail->hasTo($pending_admin->email)
        );
        Mail::assertQueued(ExceptionOccurred::class, fn($mail) =>
            $mail->hasTo($retired_admin->email)
        );
    }

    public function test_handle_uses_get_trooper_administrators_query_service(): void
    {
        // Arrange
        Mail::fake();
        $admin = Trooper::factory()->asAdministrator()->create();
        $exception = new Exception('Test exception');
        $subject = new SendExceptionNotificationJob($exception);

        $query_service = new \App\Services\Troopers\GetTrooperAdministratorsQuery();

        // Act
        $subject->handle($query_service);

        // Assert - service was called and email was sent
        Mail::assertQueued(ExceptionOccurred::class, 1);
    }

    public function test_handle_sends_separate_email_to_each_administrator(): void
    {
        // Arrange
        Mail::fake();
        $admin1 = Trooper::factory()->asAdministrator()->create();
        $admin2 = Trooper::factory()->asAdministrator()->create();
        $admin3 = Trooper::factory()->asAdministrator()->create();

        $exception = new Exception('Test exception');
        $subject = new SendExceptionNotificationJob($exception);

        // Act
        $subject->handle(new \App\Services\Troopers\GetTrooperAdministratorsQuery());

        // Assert - each email is individualized, not one email with multiple recipients
        Mail::assertQueued(ExceptionOccurred::class, function ($mail) use ($admin1)
        {
            $recipients = collect($mail->to)->pluck('address');
            return $recipients->count() === 1 && $recipients->contains($admin1->email);
        });
    }

    public function test_job_can_handle_many_administrators(): void
    {
        // Arrange
        Mail::fake();

        // Create 10 administrators
        for ($i = 0; $i < 10; $i++)
        {
            Trooper::factory()->asAdministrator()->create();
        }

        $exception = new Exception('Test exception');
        $subject = new SendExceptionNotificationJob($exception);

        // Act
        $subject->handle(new \App\Services\Troopers\GetTrooperAdministratorsQuery());

        // Assert
        Mail::assertQueued(ExceptionOccurred::class, 10);
    }

    public function test_job_implements_should_queue(): void
    {
        // Arrange
        $exception = new Exception('Test exception');
        $subject = new SendExceptionNotificationJob($exception);

        // Assert
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $subject);
    }
}
