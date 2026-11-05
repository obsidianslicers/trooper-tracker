<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\PageData\Permissions;

use App\Messages\Troopers\PageData\Permissions\RequestPermissionsPageData;
use App\Messages\Troopers\Queries\Permissions\GetRequestableOrganizations;
use App\Messages\Troopers\Queries\Permissions\GetRequestedPermissionRequests;
use App\Messages\Troopers\Resources\PermissionRequestsCollection;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class RequestPermissionsPageDataTest extends TestCase
{
    use FakesMessages;
    use RefreshDatabase;

    public function test_handle_returns_requestable_organizations_and_requests(): void
    {
        $organization = Organization::factory()->create();
        $this->fakeMessages([
            GetRequestableOrganizations::class => collect([$organization]),
            GetRequestedPermissionRequests::class => collect(),
        ]);

        $subject = new RequestPermissionsPageData(Trooper::factory()->asAdministrator()->create());
        $result = $subject->handle();

        $this->assertSame($organization->id, $result['organizations'][0]['id']);
        $this->assertSame($organization->name, $result['organizations'][0]['name']);
        $this->assertInstanceOf(PermissionRequestsCollection::class, $result['permission_requests']);
        $this->assertArrayNotHasKey('forum_groups', $result);
    }
}
