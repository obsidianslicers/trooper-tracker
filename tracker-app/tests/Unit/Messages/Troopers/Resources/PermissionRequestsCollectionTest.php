<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Resources;

use App\Enums\MembershipRole;
use App\Messages\Troopers\Resources\PermissionRequestsCollection;
use App\Models\Organization;
use App\Models\PermissionRequest;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class PermissionRequestsCollectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_to_array_resolves_moderated_organizations_with_parent_names(): void
    {
        $club = Organization::factory()->asOrganization()->withName('501st')->create();
        $garrison = Organization::factory()->asRegion()->withParent($club)
            ->withName('Florida')->create();
        $trooper = Trooper::factory()->create();
        $request = PermissionRequest::factory()->forTrooper($trooper)->requestedBy($trooper)
            ->withRole(MembershipRole::MODERATOR, [$garrison->id])->create();

        $subject = new PermissionRequestsCollection(collect([$request->fresh()]));
        $result = $subject->toArray(new Request)[0];

        $this->assertSame('moderator', $result['membership_role']);
        $this->assertSame('Moderator', $result['membership_role_label']);
        $this->assertSame(
            [['id' => $garrison->id, 'name' => 'Florida', 'parent_name' => '501st']],
            $result['moderated_organizations_to_add']
        );
        $this->assertSame([], $result['moderated_organizations_to_remove']);
        $this->assertSame($trooper->id, $result['trooper']['id']);
        $this->assertNull($result['reviewer']);
    }

    public function test_role_label_handles_null_and_regular_members(): void
    {
        $this->assertNull(PermissionRequestsCollection::roleLabel(null));
        $this->assertSame('Regular Trooper', PermissionRequestsCollection::roleLabel(MembershipRole::MEMBER));
    }

    public function test_role_label_spells_out_event_coordinator(): void
    {
        $this->assertSame(
            'Event Coordinator',
            PermissionRequestsCollection::roleLabel(MembershipRole::EVENT_COORDINATOR)
        );
    }
}
