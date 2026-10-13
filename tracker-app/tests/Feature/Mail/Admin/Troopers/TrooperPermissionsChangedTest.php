<?php

declare(strict_types=1);

namespace Tests\Feature\Mail\Admin\Troopers;

use App\Enums\MembershipRole;
use App\Enums\PermissionRequestStatus;
use App\Mail\Admin\Troopers\TrooperPermissionsChanged;
use App\Models\Organization;
use App\Models\PermissionRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrooperPermissionsChangedTest extends TestCase
{
    use RefreshDatabase;

    public function test_body_lists_moderation_changes(): void
    {
        $added = Organization::factory()->asOrganization()->withName('CFL')->create();
        $removed = Organization::factory()->asOrganization()->withName('Rebel Legion')->create();

        $permission_request = PermissionRequest::factory()
            ->withRole(MembershipRole::MODERATOR, [$added->id])
            ->create([
                PermissionRequest::STATUS => PermissionRequestStatus::APPROVED,
                PermissionRequest::UNMODERATED_ORGANIZATION_IDS => [$removed->id],
            ]);

        $subject = new TrooperPermissionsChanged($permission_request);
        $html = $subject->render();

        $this->assertStringContainsString('Role: Moderator', $html);
        $this->assertStringContainsString('Now moderates: CFL', $html);
        $this->assertStringContainsString('No longer moderates: Rebel Legion', $html);
    }
}
