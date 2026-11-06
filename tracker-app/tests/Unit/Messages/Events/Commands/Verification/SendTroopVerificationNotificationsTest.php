<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\Commands\Verification;

use App\Messages\Events\Commands\Verification\SendTroopVerificationNotifications;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use App\Notifications\Admin\TroopVerificationRequestedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\BuildsCostumeReviews;
use Tests\TestCase;

class SendTroopVerificationNotificationsTest extends TestCase
{
    use BuildsCostumeReviews;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpClubs();
        Notification::fake();
    }

    public function test_handle_notifies_administrators_and_moderators_in_scope(): void
    {
        $event_trooper = $this->awaitingTroop([$this->club->id]);
        $admin = Trooper::factory()->asAdministrator()->asActive()->create();
        $garrison_moderator = $this->moderatorOf($this->garrison);
        $other_moderator = $this->moderatorOf($this->other_club);

        $subject = new SendTroopVerificationNotifications($event_trooper);
        $subject->handle();

        Notification::assertSentTo(
            $admin,
            TroopVerificationRequestedNotification::class,
            fn (TroopVerificationRequestedNotification $notification): bool => $notification->club_names === ['501st Legion']
                && $notification->event_name === 'Comic Con'
                && $notification->costume_name === 'Emperor Palpatine'
        );
        Notification::assertSentTo($garrison_moderator, TroopVerificationRequestedNotification::class);
        Notification::assertNotSentTo($other_moderator, TroopVerificationRequestedNotification::class);
    }

    public function test_handle_lists_only_the_clubs_each_officer_can_verify(): void
    {
        $event_trooper = $this->awaitingTroop([$this->club->id, $this->other_club->id]);
        $this->memberOf($this->other_club, $event_trooper->trooper);
        $other_moderator = $this->moderatorOf($this->other_club);

        $subject = new SendTroopVerificationNotifications($event_trooper);
        $subject->handle();

        Notification::assertSentTo(
            $other_moderator,
            TroopVerificationRequestedNotification::class,
            fn (TroopVerificationRequestedNotification $notification): bool => $notification->club_names === ['Rebel Legion']
        );
    }

    public function test_handle_skips_a_troop_that_is_no_longer_waiting(): void
    {
        $event_trooper = $this->awaitingTroop([$this->club->id]);
        $event_trooper->rejectRequestedOrganization($this->club->id);
        $event_trooper->save();
        $admin = Trooper::factory()->asAdministrator()->asActive()->create();

        $subject = new SendTroopVerificationNotifications($event_trooper);
        $subject->handle();

        Notification::assertNotSentTo($admin, TroopVerificationRequestedNotification::class);
    }

    public function test_notification_renders_mail_and_database_payload(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();

        $subject = new TroopVerificationRequestedNotification(
            trooper_name: 'TK-1480',
            event_name: 'Comic Con',
            costume_name: 'Emperor Palpatine',
            club_names: ['501st Legion'],
        );

        $html = $subject->toMail($admin)->render();
        $payload = $subject->toArray($admin);

        $this->assertStringContainsString('TK-1480', $html);
        $this->assertStringContainsString('Comic Con', $html);
        $this->assertStringContainsString('Emperor Palpatine', $html);
        $this->assertStringContainsString('Troop Verifications', $html);
        $this->assertSame('Troop Verification Request', $payload['title']);
        $this->assertSame('TK-1480 confirmed Comic Con for 501st Legion.', $payload['body']);
        $this->assertSame('/admin/troopers/troop-verifications', $payload['url']);
    }

    /**
     * @param  array<int, int>  $organization_ids
     */
    private function awaitingTroop(array $organization_ids): EventTrooper
    {
        $trooper = $this->memberOf($this->garrison, Trooper::factory()->asMember()->asUntrusted()->create());
        $event = Event::factory()->withOrganization($this->club)->create([Event::NAME => 'Comic Con']);
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $costume = Costume::factory()->create([Costume::NAME => 'Emperor Palpatine']);

        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->withCostume($costume)
            ->asGoing()
            ->create();
        $event_trooper->requestVerification($organization_ids);
        $event_trooper->save();

        return $event_trooper;
    }
}
