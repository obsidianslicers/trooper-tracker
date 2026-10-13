<?php

declare(strict_types=1);

namespace Tests\Feature\Mail\Admin\Troopers;

use App\Enums\MembershipRole;
use App\Mail\Admin\Troopers\PermissionRequestSubmitted;
use App\Models\Organization;
use App\Models\PermissionRequest;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionRequestSubmittedTest extends TestCase
{
    use RefreshDatabase;

    public function test_body_lists_requested_organizations_and_groups(): void
    {
        $organization = Organization::factory()->asOrganization()->withName('CFL')->create();

        $permission_request = PermissionRequest::factory()
            ->requestedBy(Trooper::factory()->asModerator()->create())
            ->withRole(MembershipRole::MODERATOR, [$organization->id])
            ->create([
                PermissionRequest::FORUM_GROUPS_TO_ADD => [['id' => 20, 'title' => 'Recruiters']],
            ]);

        $subject = new PermissionRequestSubmitted($permission_request);
        $html = $subject->render();

        $this->assertStringContainsString('Role: Moderator', $html);
        $this->assertStringContainsString('Now moderates: CFL', $html);
        $this->assertStringContainsString('Forum groups added: Recruiters', $html);
    }
}
