<?php

declare(strict_types=1);

namespace Tests\Feature\Mail;

use App\Mail\OutgoingMailBudget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OutgoingMailBudgetTest extends TestCase
{
    private OutgoingMailBudget $budget;

    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array', 'mail.recipients_per_hour' => 3]);
        Carbon::setTestNow(Carbon::parse('2026-11-03 12:00:00'));

        $this->budget = new OutgoingMailBudget;
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_without_a_limit_nothing_waits_or_is_counted(): void
    {
        config(['mail.recipients_per_hour' => null]);

        $this->budget->record(50);

        $this->assertFalse($this->budget->enabled());
        $this->assertSame(0, $this->budget->secondsUntilAvailable(50));
        $this->assertNull($this->budget->retryUntil());
    }

    public function test_sends_until_the_hour_is_full(): void
    {
        $this->budget->record(2);

        $this->assertSame(0, $this->budget->secondsUntilAvailable(1));

        $this->budget->record(1);

        $this->assertSame(3600, $this->budget->secondsUntilAvailable(1));
    }

    public function test_the_hour_rolls_instead_of_resetting(): void
    {
        $this->budget->record(2);
        Carbon::setTestNow(now()->addMinutes(50));
        $this->budget->record(1);

        // a fixed hourly window would reset at 13:00 and allow 3 more right away
        Carbon::setTestNow(now()->addMinutes(15));

        $this->assertSame(0, $this->budget->secondsUntilAvailable(2));
        $this->assertSame(45 * 60, $this->budget->secondsUntilAvailable(3));
    }

    public function test_waits_for_as_many_old_sends_as_it_needs_to_age_out(): void
    {
        $this->budget->record(1);
        Carbon::setTestNow(now()->addMinutes(10));
        $this->budget->record(1);
        Carbon::setTestNow(now()->addMinutes(10));
        $this->budget->record(1);

        $this->assertSame(40 * 60, $this->budget->secondsUntilAvailable(1));
        $this->assertSame(50 * 60, $this->budget->secondsUntilAvailable(2));
    }

    public function test_a_message_bigger_than_the_budget_is_not_held_forever(): void
    {
        $this->budget->record(3);

        $this->assertSame(0, $this->budget->secondsUntilAvailable(4));
    }

    public function test_retries_by_time_while_enabled(): void
    {
        $this->assertEquals(now()->addDay(), $this->budget->retryUntil());
    }

    public function test_every_sent_recipient_is_counted(): void
    {
        config(['mail.default' => 'array']);

        Mail::raw('Hello', fn ($message) => $message->to('one@example.com')->cc('two@example.com'));

        $this->assertSame(0, $this->budget->secondsUntilAvailable(1));
        $this->assertSame(3600, $this->budget->secondsUntilAvailable(2));
    }
}
