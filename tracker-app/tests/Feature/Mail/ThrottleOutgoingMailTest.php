<?php

declare(strict_types=1);

namespace Tests\Feature\Mail;

use App\Mail\Auth\TrooperRegistered;
use App\Mail\OutgoingMailBudget;
use App\Mail\ThrottleOutgoingMail;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Notifications\SendQueuedNotifications;
use Mockery;
use Tests\TestCase;

class ThrottleOutgoingMailTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array', 'mail.recipients_per_hour' => 3]);
    }

    public function test_sends_while_there_is_room(): void
    {
        $job = $this->mailJob('one@example.com');
        $job->job->shouldNotReceive('release');

        $sent = (new ThrottleOutgoingMail)->handle($job, fn () => 'sent');

        $this->assertSame('sent', $sent);
    }

    public function test_holds_the_job_until_there_is_room(): void
    {
        app(OutgoingMailBudget::class)->record(3);

        $job = $this->mailJob('one@example.com');
        $job->job->shouldReceive('release')->once()->with(Mockery::on(fn (int $delay): bool => $delay >= 3600 && $delay <= 4200));

        $sent = (new ThrottleOutgoingMail)->handle($job, fn () => 'sent');

        $this->assertNull($sent);
    }

    public function test_counts_every_recipient_of_a_queued_mailable(): void
    {
        app(OutgoingMailBudget::class)->record(1);

        $job = $this->mailJob('one@example.com', 'two@example.com', 'three@example.com');
        $job->job->shouldReceive('release')->once();

        (new ThrottleOutgoingMail)->handle($job, fn () => 'sent');
    }

    public function test_a_notification_job_needs_room_for_one(): void
    {
        app(OutgoingMailBudget::class)->record(2);

        $job = Mockery::mock(SendQueuedNotifications::class);
        $job->shouldNotReceive('release');

        $this->assertSame('sent', (new ThrottleOutgoingMail)->handle($job, fn () => 'sent'));
    }

    public function test_queued_mailables_carry_the_throttle(): void
    {
        $this->assertEquals([new ThrottleOutgoingMail], (new TrooperRegistered)->middleware());
    }

    private function mailJob(string $to, string ...$cc): SendQueuedMailable
    {
        $job = new SendQueuedMailable((new TrooperRegistered)->to($to)->cc($cc));
        $job->setJob(Mockery::mock(Job::class));

        return $job;
    }
}
