<?php

declare(strict_types=1);

namespace Tests\Feature\Mail\Admin\Troopers;

use App\Enums\MembershipRole;
use App\Enums\PermissionRequestStatus;
use App\Mail\Admin\Troopers\PermissionRequestReviewed;
use App\Mail\Admin\Troopers\TrooperPermissionsChanged;
use App\Models\Organization;
use App\Models\PermissionRequest;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionRequestReviewedTest extends TestCase
{
    use RefreshDatabase;

    public function test_reviewed_mail_renders_denial_with_reason(): void
    {
        config(['mail.prefix' => '[TEST]']);

        $permission_request = PermissionRequest::factory()
            ->requestedBy(Trooper::factory()->asModerator()->create())
            ->withRole(MembershipRole::MODERATOR)
            ->create([
                PermissionRequest::STATUS => PermissionRequestStatus::DENIED,
                PermissionRequest::DENIAL_REASON => 'Not yet',
            ]);

        $mail = new PermissionRequestReviewed($permission_request);

        $this->assertSame('[TEST] Permission Request Denied', $mail->envelope()->subject);
        $html = $mail->render();
        $this->assertStringContainsString('denied', $html);
        $this->assertStringContainsString('Reason: Not yet', $html);
    }

    public function test_permissions_changed_mail_lists_moderation_changes(): void
    {
        $added = Organization::factory()->asOrganization()->withName('CFL')->create();
        $removed = Organization::factory()->asOrganization()->withName('Rebel Legion')->create();

        $permission_request = PermissionRequest::factory()
            ->withRole(MembershipRole::MODERATOR, [$added->id])
            ->create([
                PermissionRequest::STATUS => PermissionRequestStatus::APPROVED,
                PermissionRequest::UNMODERATED_ORGANIZATION_IDS => [$removed->id],
            ]);

        $html = (new TrooperPermissionsChanged($permission_request))->render();

        $this->assertStringContainsString('Role: Moderator', $html);
        $this->assertStringContainsString('Now moderates: CFL', $html);
        $this->assertStringContainsString('No longer moderates: Rebel Legion', $html);
    }
}
