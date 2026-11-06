<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands\Costumes;

use App\Jobs\SendCostumeRequestNotificationsJob;
use App\Messages\Troopers\Commands\Costumes\AddCostumeToTrooper;
use App\Models\Costume;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AddCostumeToTrooperTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_creates_missing_trooper_costume_for_active_organization_membership(): void
    {
        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->asMember()->create();

        $costume = Costume::factory()->create();
        $organization_costume = OrganizationCostume::factory()
            ->forOrganization($organization)
            ->forCostume($costume)
            ->create();

        $subject = new AddCostumeToTrooper($trooper, $costume->id, [$organization->id]);

        $subject->handle();

        $this->assertDatabaseCount('tt_trooper_costumes', 1);
        $this->assertDatabaseHas('tt_trooper_costumes', [
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $organization_costume->id,
            TrooperCostume::DELETED_AT => null,
            TrooperCostume::APPROVED_AT => null,
        ]);
    }

    public function test_handle_restores_soft_deleted_trooper_costume_for_matching_costume(): void
    {
        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->asMember()->create();

        $costume = Costume::factory()->create();
        $organization_costume = OrganizationCostume::factory()
            ->forOrganization($organization)
            ->forCostume($costume)
            ->create();

        $trooper_costume = TrooperCostume::factory()
            ->forTrooper($trooper)
            ->forOrganizationCostume($organization_costume)
            ->create();
        $trooper_costume->delete();

        $subject = new AddCostumeToTrooper($trooper, $costume->id, [$organization->id]);

        $subject->handle();

        $this->assertDatabaseCount('tt_trooper_costumes', 1);
        $this->assertDatabaseHas('tt_trooper_costumes', [
            TrooperCostume::ID => $trooper_costume->id,
            TrooperCostume::DELETED_AT => null,
        ]);
    }

    public function test_handle_adding_a_club_to_an_approved_costume_only_leaves_the_new_club_pending(): void
    {
        $trooper = Trooper::factory()->create();
        $first_club = Organization::factory()->create();
        $second_club = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($first_club)->asMember()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($second_club)->asMember()->create();

        $costume = Costume::factory()->create();
        $first = OrganizationCostume::factory()->forOrganization($first_club)->forCostume($costume)->create();
        $second = OrganizationCostume::factory()->forOrganization($second_club)->forCostume($costume)->create();

        $approved = TrooperCostume::factory()->forTrooper($trooper)->forOrganizationCostume($first)->create();

        $subject = new AddCostumeToTrooper($trooper, $costume->id, [$first_club->id, $second_club->id]);

        $subject->handle();

        $this->assertFalse($approved->refresh()->isPending());
        $added = TrooperCostume::onlyPending()->where(TrooperCostume::ORGANIZATION_COSTUME_ID, $second->id)->first();
        $this->assertNotNull($added);
        $this->assertSame($trooper->id, $added->trooper_id);
    }

    public function test_handle_keeps_a_revoked_costume_pending_when_added_again(): void
    {
        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->asMember()->create();

        $costume = Costume::factory()->create();
        $organization_costume = OrganizationCostume::factory()
            ->forOrganization($organization)
            ->forCostume($costume)
            ->create();

        $revoked = TrooperCostume::factory()
            ->forTrooper($trooper)
            ->forOrganizationCostume($organization_costume)
            ->pending()
            ->create();
        $revoked->delete();

        $subject = new AddCostumeToTrooper($trooper, $costume->id, [$organization->id]);

        $subject->handle();

        $revoked = TrooperCostume::withPending()->find($revoked->id);
        $this->assertNotNull($revoked);
        $this->assertTrue($revoked->isPending());
        $this->assertFalse($revoked->trashed());
    }

    public function test_handle_queues_officer_notifications_for_new_pending_costumes(): void
    {
        Queue::fake();
        [$trooper, $organization, $costume, $organization_costume] = $this->singleClubCostume();

        $subject = new AddCostumeToTrooper($trooper, $costume->id, [$organization->id]);
        $subject->handle();

        $added = TrooperCostume::withPending()
            ->where(TrooperCostume::ORGANIZATION_COSTUME_ID, $organization_costume->id)
            ->firstOrFail();

        Queue::assertPushed(
            SendCostumeRequestNotificationsJob::class,
            fn (SendCostumeRequestNotificationsJob $job): bool => $this->jobIds($job) === [$added->id]
        );
    }

    public function test_handle_does_not_notify_again_for_a_costume_already_listed(): void
    {
        Queue::fake();
        [$trooper, $organization, $costume, $organization_costume] = $this->singleClubCostume();
        TrooperCostume::factory()
            ->forTrooper($trooper)
            ->forOrganizationCostume($organization_costume)
            ->pending()
            ->create();

        $subject = new AddCostumeToTrooper($trooper, $costume->id, [$organization->id]);
        $subject->handle();

        Queue::assertNotPushed(SendCostumeRequestNotificationsJob::class);
    }

    public function test_handle_does_not_notify_when_an_approved_costume_comes_back(): void
    {
        Queue::fake();
        [$trooper, $organization, $costume, $organization_costume] = $this->singleClubCostume();
        $approved = TrooperCostume::factory()
            ->forTrooper($trooper)
            ->forOrganizationCostume($organization_costume)
            ->create();
        $approved->delete();

        $subject = new AddCostumeToTrooper($trooper, $costume->id, [$organization->id]);
        $subject->handle();

        Queue::assertNotPushed(SendCostumeRequestNotificationsJob::class);
    }

    /**
     * @return array{0: Trooper, 1: Organization, 2: Costume, 3: OrganizationCostume}
     */
    private function singleClubCostume(): array
    {
        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->asMember()->create();

        $costume = Costume::factory()->create();
        $organization_costume = OrganizationCostume::factory()
            ->forOrganization($organization)
            ->forCostume($costume)
            ->create();

        return [$trooper, $organization, $costume, $organization_costume];
    }

    /**
     * @return array<int, int>
     */
    private function jobIds(SendCostumeRequestNotificationsJob $job): array
    {
        return (fn (): array => $this->trooper_costume_ids)->call($job);
    }
}
