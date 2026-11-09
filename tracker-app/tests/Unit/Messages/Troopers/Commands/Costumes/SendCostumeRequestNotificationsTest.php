<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands\Costumes;

use App\Messages\Troopers\Commands\Costumes\SendCostumeRequestNotifications;
use App\Models\Trooper;
use App\Notifications\Admin\CostumeRequestSubmittedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\BuildsCostumeReviews;
use Tests\TestCase;

class SendCostumeRequestNotificationsTest extends TestCase
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
        $trooper = $this->memberOf($this->garrison);
        $trooper_costume = $this->costumeFor($trooper, $this->club);
        $admin = Trooper::factory()->asSystemOwner()->asActive()->create();
        $garrison_moderator = $this->moderatorOf($this->garrison);
        $other_moderator = $this->moderatorOf($this->other_club);

        $subject = new SendCostumeRequestNotifications($trooper, [$trooper_costume->id]);
        $subject->handle();

        Notification::assertSentTo($admin, CostumeRequestSubmittedNotification::class);
        Notification::assertSentTo($garrison_moderator, CostumeRequestSubmittedNotification::class);
        Notification::assertNotSentTo($other_moderator, CostumeRequestSubmittedNotification::class);
    }

    public function test_handle_lists_only_the_clubs_each_officer_can_review(): void
    {
        $trooper = $this->memberOf($this->garrison);
        $this->memberOf($this->other_club, $trooper);
        $ids = [
            $this->costumeFor($trooper, $this->club)->id,
            $this->costumeFor($trooper, $this->other_club)->id,
        ];
        $admin = Trooper::factory()->asSystemOwner()->asActive()->create();
        $other_moderator = $this->moderatorOf($this->other_club);

        $subject = new SendCostumeRequestNotifications($trooper, $ids);
        $subject->handle();

        Notification::assertSentTo(
            $other_moderator,
            CostumeRequestSubmittedNotification::class,
            fn (CostumeRequestSubmittedNotification $notification): bool => $notification->club_names === ['Rebel Legion']
                && $notification->costume_name === 'Emperor Palpatine'
        );
        Notification::assertSentTo(
            $admin,
            CostumeRequestSubmittedNotification::class,
            fn (CostumeRequestSubmittedNotification $notification): bool => count($notification->club_names) === 2
        );
    }

    public function test_handle_skips_rows_already_approved(): void
    {
        $trooper = $this->memberOf($this->garrison);
        $trooper_costume = $this->costumeFor($trooper, $this->club, pending: false);
        $admin = Trooper::factory()->asSystemOwner()->asActive()->create();

        $subject = new SendCostumeRequestNotifications($trooper, [$trooper_costume->id]);
        $subject->handle();

        Notification::assertNotSentTo($admin, CostumeRequestSubmittedNotification::class);
    }

    public function test_handle_never_notifies_the_trooper_about_their_own_costume(): void
    {
        $admin = Trooper::factory()->asSystemOwner()->asActive()->create();
        $this->memberOf($this->garrison, $admin);
        $trooper_costume = $this->costumeFor($admin, $this->club);

        $subject = new SendCostumeRequestNotifications($admin, [$trooper_costume->id]);
        $subject->handle();

        Notification::assertNotSentTo($admin, CostumeRequestSubmittedNotification::class);
    }

    public function test_notification_renders_mail_and_database_payload(): void
    {
        $admin = Trooper::factory()->asSystemOwner()->create();

        $subject = new CostumeRequestSubmittedNotification(
            trooper_name: 'TK-1480',
            costume_name: 'Emperor Palpatine',
            club_names: ['501st Legion'],
        );

        $html = $subject->toMail($admin)->render();
        $payload = $subject->toArray($admin);

        $this->assertStringContainsString('TK-1480', $html);
        $this->assertStringContainsString('Emperor Palpatine', $html);
        $this->assertStringContainsString('Costume Approvals', $html);
        $this->assertSame('Costume Approval Request', $payload['title']);
        $this->assertSame('TK-1480 added Emperor Palpatine for 501st Legion.', $payload['body']);
        $this->assertSame('/admin/troopers/costume-approvals', $payload['url']);
    }
}
