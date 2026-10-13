<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands\Permissions;

use App\Enums\PermissionRequestStatus;
use App\Messages\Troopers\Commands\Permissions\SendPermissionRequestReviewedNotifications;
use App\Models\PermissionRequest;
use App\Models\Trooper;
use App\Notifications\Admin\PermissionRequestReviewedNotification;
use App\Notifications\Troopers\TrooperPermissionsChangedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendPermissionRequestReviewedNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_notifies_requester_and_trooper_when_approved(): void
    {
        Notification::fake();

        $moderator = Trooper::factory()->asModerator()->create();
        $trooper = Trooper::factory()->asMember()->create();
        $request = $this->reviewed($moderator, $trooper, PermissionRequestStatus::APPROVED);

        $subject = new SendPermissionRequestReviewedNotifications($request);
        $subject->handle();

        Notification::assertSentTo($moderator, PermissionRequestReviewedNotification::class);
        Notification::assertSentTo($trooper, TrooperPermissionsChangedNotification::class);
    }

    public function test_handle_only_notifies_requester_when_denied(): void
    {
        Notification::fake();

        $moderator = Trooper::factory()->asModerator()->create();
        $trooper = Trooper::factory()->asMember()->create();
        $request = $this->reviewed($moderator, $trooper, PermissionRequestStatus::DENIED);

        $subject = new SendPermissionRequestReviewedNotifications($request);
        $subject->handle();

        Notification::assertSentTo($moderator, PermissionRequestReviewedNotification::class);
        Notification::assertNotSentTo($trooper, TrooperPermissionsChangedNotification::class);
    }

    public function test_handle_skips_requester_who_reviewed_their_own_request(): void
    {
        Notification::fake();

        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asMember()->create();
        $request = $this->reviewed($admin, $trooper, PermissionRequestStatus::APPROVED, $admin);

        $subject = new SendPermissionRequestReviewedNotifications($request);
        $subject->handle();

        Notification::assertNotSentTo($admin, PermissionRequestReviewedNotification::class);
        Notification::assertSentTo($trooper, TrooperPermissionsChangedNotification::class);
    }

    private function reviewed(
        Trooper $requester,
        Trooper $trooper,
        PermissionRequestStatus $status,
        ?Trooper $reviewer = null,
    ): PermissionRequest {
        $reviewer ??= Trooper::factory()->asAdministrator()->create();

        return PermissionRequest::factory()
            ->requestedBy($requester)
            ->forTrooper($trooper)
            ->create([
                PermissionRequest::STATUS => $status,
                PermissionRequest::REVIEWER_TROOPER_ID => $reviewer->id,
            ]);
    }
}
