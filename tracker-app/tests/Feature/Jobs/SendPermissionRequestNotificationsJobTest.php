<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Jobs\SendPermissionRequestNotificationsJob;
use App\Models\PermissionRequest;
use App\Models\Trooper;
use App\Notifications\Admin\PermissionRequestSubmittedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendPermissionRequestNotificationsJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_notifies_admins_only(): void
    {
        Notification::fake();

        $admin = Trooper::factory()->asAdministrator()->create();
        $moderator = Trooper::factory()->asModerator()->create();
        $permission_request = PermissionRequest::factory()->requestedBy($moderator)->create();

        $subject = new SendPermissionRequestNotificationsJob($permission_request);
        $subject->handle();

        Notification::assertSentTo($admin, PermissionRequestSubmittedNotification::class);
        Notification::assertNotSentTo($moderator, PermissionRequestSubmittedNotification::class);
    }

    public function test_notification_renders_mail_and_database_payload(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $permission_request = PermissionRequest::factory()
            ->withForumGroups(add: [['id' => 20, 'title' => 'Event Coordinators']])
            ->create(['note' => 'Runs the Tampa events']);

        $subject = new PermissionRequestSubmittedNotification($permission_request);

        $html = $subject->toMail($admin)->render();
        $payload = $subject->toArray($admin);

        $this->assertStringContainsString('Event Coordinators', $html);
        $this->assertStringContainsString('Runs the Tampa events', $html);
        $this->assertSame('Permission Request', $payload['title']);
        $this->assertSame('/admin/troopers/permissions/approvals', $payload['url']);
    }
}
