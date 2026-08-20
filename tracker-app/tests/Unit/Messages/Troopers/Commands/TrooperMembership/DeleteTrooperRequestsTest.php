<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands\TrooperMemberships;

use App\Enums\TrooperRequestStatus;
use App\Messages\Troopers\Commands\TrooperMemberships\DeleteTrooperRequests;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteTrooperRequestsTest extends TestCase
{
    use RefreshDatabase;

    public function test_call_accepts_named_parameters(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $primary_organization = Organization::factory()->asOrganization()->create();

        DeleteTrooperRequests::call(
            trooper: $trooper,
            primary_organization: $primary_organization,
        );

        $this->assertTrue(true);
    }

    public function test_handle_soft_deletes_pending_requests_in_same_primary_club_family(): void
    {
        $trooper = Trooper::factory()->asMember()->create();

        $root = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $region = Organization::factory()->asRegion()->withParent($root)->withNodePath('100:200:')->create();

        $request = TrooperRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($region)
            ->forPrimaryOrganization($root)
            ->asPending()
            ->create();

        (new DeleteTrooperRequests($trooper, $root))->handle();

        $this->assertSoftDeleted('tt_trooper_requests', [
            TrooperRequest::ID => $request->id,
        ]);
    }

    public function test_handle_does_not_delete_non_matching_requests(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $other_trooper = Trooper::factory()->asMember()->create();

        $root = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $region = Organization::factory()->asRegion()->withParent($root)->withNodePath('100:200:')->create();

        $other_root = Organization::factory()->asOrganization()->withNodePath('300:')->create();
        $other_region = Organization::factory()
            ->asRegion()
            ->withParent($other_root)
            ->withNodePath('300:400:')
            ->create();

        $approved_request = TrooperRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($region)
            ->forPrimaryOrganization($root)
            ->asApproved()
            ->create();

        $different_primary_request = TrooperRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($other_region)
            ->forPrimaryOrganization($other_root)
            ->asPending()
            ->create();

        $different_trooper_request = TrooperRequest::factory()
            ->forTrooper($other_trooper)
            ->forOrganization($region)
            ->forPrimaryOrganization($root)
            ->asPending()
            ->create();

        (new DeleteTrooperRequests($trooper, $root))->handle();

        $this->assertDatabaseHas('tt_trooper_requests', [
            TrooperRequest::ID => $approved_request->id,
            TrooperRequest::STATUS => TrooperRequestStatus::APPROVED,
        ]);

        $this->assertDatabaseHas('tt_trooper_requests', [
            TrooperRequest::ID => $different_primary_request->id,
            TrooperRequest::STATUS => TrooperRequestStatus::PENDING,
        ]);

        $this->assertDatabaseHas('tt_trooper_requests', [
            TrooperRequest::ID => $different_trooper_request->id,
            TrooperRequest::STATUS => TrooperRequestStatus::PENDING,
        ]);
    }
}