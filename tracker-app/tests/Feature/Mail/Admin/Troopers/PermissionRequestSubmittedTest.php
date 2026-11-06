<?php

declare(strict_types=1);

namespace Tests\Feature\Mail\Admin\Troopers;

use App\Enums\MembershipRole;
use App\Mail\Admin\Troopers\PermissionRequestSubmitted;
use App\Models\Organization;
use App\Models\PermissionRequest;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsesBadgeRequests;
use Tests\Fixtures\PermissionRequests\BadgeRequests;
use Tests\TestCase;

class PermissionRequestSubmittedTest extends TestCase
{
    use RefreshDatabase;
    use UsesBadgeRequests;

    public function test_body_lists_requested_organizations_and_extension_changes(): void
    {
        $this->addBadgeRequests();
        $organization = Organization::factory()->asOrganization()->withName('CFL')->create();

        $permission_request = PermissionRequest::factory()
            ->requestedBy(Trooper::factory()->asModerator()->create())
            ->withRole(MembershipRole::MODERATOR, [$organization->id])
            ->create();
        BadgeRequests::$stored[$permission_request->id] = ['gold', 'silver'];

        $subject = new PermissionRequestSubmitted($permission_request);
        $html = $subject->render();

        $this->assertStringContainsString('Role: Moderator', $html);
        $this->assertStringContainsString('Now moderates: CFL', $html);
        $this->assertStringContainsString('Badges: gold, silver', $html);
    }
}
