<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Jobs\SendPermissionRequestNotificationsJob;
use App\Models\PermissionRequest;
use App\Models\Trooper;
use App\Notifications\Admin\PermissionRequestSubmittedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\UsesBadgeRequests;
use Tests\Fixtures\PermissionRequests\BadgeRequests;
use Tests\TestCase;

class SendPermissionRequestNotificationsJobTest extends TestCase
{
    use RefreshDatabase;
    use UsesBadgeRequests;

    public function test_handle_notifies_administrators(): void
    {
        Notification::fake();

        $admin = Trooper::factory()->asSystemOwner()->create();
        $permission_request = PermissionRequest::factory()->create();

        $subject = new SendPermissionRequestNotificationsJob($permission_request);
        $subject->handle();

        Notification::assertSentTo($admin, PermissionRequestSubmittedNotification::class);
    }

    public function test_notification_renders_mail_and_database_payload(): void
    {
        $this->addBadgeRequests();
        $admin = Trooper::factory()->asSystemOwner()->create();
        $permission_request = PermissionRequest::factory()->create(['note' => 'Runs the Tampa events']);
        BadgeRequests::$stored[$permission_request->id] = ['gold'];

        $subject = new PermissionRequestSubmittedNotification($permission_request);

        $html = $subject->toMail($admin)->render();
        $payload = $subject->toArray($admin);

        $this->assertStringContainsString('Badges: gold', $html);
        $this->assertStringContainsString('Runs the Tampa events', $html);
        $this->assertSame('Permission Request', $payload['title']);
        $this->assertSame('/admin/troopers/permissions/approvals', $payload['url']);
    }
}
