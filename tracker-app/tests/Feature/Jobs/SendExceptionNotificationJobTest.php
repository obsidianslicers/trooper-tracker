<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Bus\MagicBus;
use App\Enums\MembershipRole;
use App\Features\Troopers\Queries\GetTroopersByRoleQuery;
use App\Jobs\SendExceptionNotificationJob;
use App\Mail\ExceptionOccurred;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class SendExceptionNotificationJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_queues_exception_email_for_each_administrator(): void
    {
        Mail::fake();

        $admin_one = Trooper::factory()->asAdministrator()->withEmail('first-admin@example.com')->create();
        $admin_two = Trooper::factory()->asAdministrator()->withEmail('second-admin@example.com')->create();

        $bus = Mockery::mock(MagicBus::class);
        $bus->shouldReceive('send')
            ->once()
            ->withArgs(function (object $query): bool
            {
                return $query instanceof GetTroopersByRoleQuery
                    && $query->membership_role === MembershipRole::ADMINISTRATOR;
            })
            ->andReturn(collect([$admin_one, $admin_two]));

        $exception = new RuntimeException('Unhandled failure');
        $subject = new SendExceptionNotificationJob($exception, ['path' => '/jobs']);
        $subject->handle($bus);

        Mail::assertQueued(ExceptionOccurred::class, 2);
        Mail::assertQueued(ExceptionOccurred::class, function (ExceptionOccurred $mail) use ($admin_one): bool
        {
            return $mail->hasTo($admin_one->email);
        });
        Mail::assertQueued(ExceptionOccurred::class, function (ExceptionOccurred $mail) use ($admin_two): bool
        {
            return $mail->hasTo($admin_two->email);
        });
    }
}
