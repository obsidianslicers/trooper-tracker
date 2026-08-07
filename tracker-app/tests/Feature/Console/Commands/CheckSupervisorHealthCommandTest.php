<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Mail\Admin\System\SupervisorDown;
use App\Mail\Admin\System\SupervisorRecovered;
use App\Models\Trooper;
use App\Services\QueueWorkerHeartbeatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CheckSupervisorHealthCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Tests default QUEUE_CONNECTION to "sync"; these tests exercise the
        // async-worker path, so force a driver that requires a real worker.
        config(['queue.default' => 'database']);
    }

    public function test_sends_no_email_when_queue_driver_is_sync(): void
    {
        config([
            'tracker.supervisor_check.email_enabled' => true,
            'queue.default' => 'sync',
        ]);
        Trooper::factory()->asAdministrator()->create();
        Mail::fake();

        $this->artisan('tracker:check-supervisor-health')->assertExitCode(0);

        Mail::assertNothingSent();
    }

    public function test_sends_no_email_when_feature_disabled(): void
    {
        config(['tracker.supervisor_check.email_enabled' => false]);
        Trooper::factory()->asAdministrator()->create();
        Mail::fake();

        $this->artisan('tracker:check-supervisor-health')->assertExitCode(0);

        Mail::assertNothingSent();
    }

    public function test_sends_no_email_when_heartbeat_is_fresh(): void
    {
        config(['tracker.supervisor_check.email_enabled' => true]);
        Trooper::factory()->asAdministrator()->create();
        app(QueueWorkerHeartbeatService::class)->recordHeartbeat();
        Mail::fake();

        $this->artisan('tracker:check-supervisor-health')->assertExitCode(0);

        Mail::assertNothingSent();
    }

    public function test_sends_down_alert_to_administrators_when_heartbeat_is_stale(): void
    {
        config([
            'tracker.supervisor_check.email_enabled' => true,
            'tracker.supervisor_check.heartbeat_down_minutes' => 10,
        ]);
        $admin = Trooper::factory()->asAdministrator()->withEmail('admin@example.com')->create();
        Mail::fake();

        $this->artisan('tracker:check-supervisor-health')->assertExitCode(0);

        Mail::assertSent(SupervisorDown::class, function (SupervisorDown $mail) use ($admin): bool {
            return $mail->hasTo($admin->email);
        });
    }

    public function test_does_not_resend_down_alert_within_renotify_window(): void
    {
        config([
            'tracker.supervisor_check.email_enabled' => true,
            'tracker.supervisor_check.heartbeat_down_minutes' => 10,
            'tracker.supervisor_check.renotify_after_minutes' => 60,
        ]);
        Trooper::factory()->asAdministrator()->create();
        app(QueueWorkerHeartbeatService::class)->markDownNotified();
        Mail::fake();

        $this->artisan('tracker:check-supervisor-health')->assertExitCode(0);

        Mail::assertNothingSent();
    }

    public function test_sends_recovery_email_after_prior_down_alert(): void
    {
        config(['tracker.supervisor_check.email_enabled' => true]);
        $admin = Trooper::factory()->asAdministrator()->withEmail('admin@example.com')->create();
        $heartbeat = app(QueueWorkerHeartbeatService::class);
        $heartbeat->markDownNotified();
        $heartbeat->recordHeartbeat();
        Mail::fake();

        $this->artisan('tracker:check-supervisor-health')->assertExitCode(0);

        Mail::assertSent(SupervisorRecovered::class, function (SupervisorRecovered $mail) use ($admin): bool {
            return $mail->hasTo($admin->email);
        });
        $this->assertNull($heartbeat->downNotifiedAt());
    }
}
