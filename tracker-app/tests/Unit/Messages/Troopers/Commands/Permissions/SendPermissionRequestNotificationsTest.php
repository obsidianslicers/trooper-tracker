<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands\Permissions;

use App\Messages\Troopers\Commands\Permissions\SendPermissionRequestNotifications;
use App\Models\PermissionRequest;
use App\Models\Trooper;
use App\Notifications\Admin\PermissionRequestSubmittedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendPermissionRequestNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_notifies_admins_only(): void
    {
        Notification::fake();

        $admin = Trooper::factory()->asAdministrator()->create();
        $moderator = Trooper::factory()->asModerator()->create();
        $permission_request = PermissionRequest::factory()->requestedBy($moderator)->create();

        $subject = new SendPermissionRequestNotifications($permission_request);
        $subject->handle();

        Notification::assertSentTo($admin, PermissionRequestSubmittedNotification::class);
        Notification::assertNotSentTo($moderator, PermissionRequestSubmittedNotification::class);
    }
}
