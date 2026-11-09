<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use App\Notifications\Admin\StaleVerificationsNotification;
use DateTimeInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RemindStaleVerificationsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifies_admins_about_verifications_older_than_30_days(): void
    {
        Notification::fake();
        $admin = Trooper::factory()->asSystemOwner()->asActive()->create();
        $this->awaiting(now()->subDays(31));
        $this->awaiting(now()->subDays(2));

        $this->artisan('tracker:remind-stale-verifications')->assertSuccessful();

        Notification::assertSentTo($admin, StaleVerificationsNotification::class);
    }

    public function test_does_nothing_when_nothing_is_stale(): void
    {
        Notification::fake();
        Trooper::factory()->asSystemOwner()->asActive()->create();
        $this->awaiting(now()->subDays(2));

        $this->artisan('tracker:remind-stale-verifications')->assertSuccessful();

        Notification::assertNothingSent();
    }

    private function awaiting(DateTimeInterface $requested_at): void
    {
        $organization = Organization::factory()->create();
        $event = Event::factory()->withOrganization($organization)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $event_trooper = EventTrooper::factory()->forEventShift($event_shift)
            ->forTrooper(Trooper::factory()->asUntrusted()->create())->asGoing()->create();

        $event_trooper->requestVerification([$organization->id]);
        $event_trooper->verification_requested_at = $requested_at;
        $event_trooper->save();
    }
}
