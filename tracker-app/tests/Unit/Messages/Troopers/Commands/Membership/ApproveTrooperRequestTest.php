<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands\Membership;

use App\Enums\MembershipStatus;
use App\Enums\TrooperRequestStatus;
use App\Messages\Troopers\Commands\Membership\ApproveTrooperRequest;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperOrganization;
use App\Models\TrooperRequest;
use App\Notifications\Troopers\TrooperRequestApprovedNotification;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

final class ApproveTrooperRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_approves_request_and_creates_membership(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $trooper_request = $this->createRequest($trooper, $organization, $organization, 'TK-12345');

        $subject = new ApproveTrooperRequest($trooper_request, true);
        $subject->handle();

        $this->assertDatabaseHas('tt_trooper_requests', [
            TrooperRequest::ID => $trooper_request->id,
            TrooperRequest::STATUS => TrooperRequestStatus::APPROVED->value,
        ]);
        $this->assertDatabaseHas('tt_trooper_organizations', [
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $organization->id,
            TrooperOrganization::IDENTIFIER => 'TK-12345',
            TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE->value,
        ]);
        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::IS_MEMBER => true,
            TrooperAssignment::SHOULD_NOTIFY => true,
        ]);
    }

    public function test_handle_enables_notifications_for_the_requested_organization_lineage(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $primary_organization = Organization::factory()
            ->asOrganization()
            ->withNodePath('100:')
            ->create();
        $region = Organization::factory()
            ->asRegion()
            ->withParent($primary_organization)
            ->withNodePath('100:200:')
            ->create();
        $unit = Organization::factory()
            ->asUnit()
            ->withParent($region)
            ->withNodePath('100:200:300:')
            ->create();
        $trooper_request = $this->createRequest($trooper, $primary_organization, $unit);

        (new ApproveTrooperRequest($trooper_request, true))->handle();

        foreach ([$primary_organization, $region, $unit] as $organization)
        {
            $this->assertDatabaseHas('tt_trooper_assignments', [
                TrooperAssignment::TROOPER_ID => $trooper->id,
                TrooperAssignment::ORGANIZATION_ID => $organization->id,
                TrooperAssignment::SHOULD_NOTIFY => true,
            ]);
        }
    }

    public function test_handle_sends_approval_notification_by_default(): void
    {
        Notification::fake();

        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $trooper_request = $this->createRequest($trooper, $organization, $organization);

        (new ApproveTrooperRequest($trooper_request))->handle();

        Notification::assertSentTo($trooper, TrooperRequestApprovedNotification::class);
    }

    public function test_handle_suppresses_approval_notification_when_requested(): void
    {
        Notification::fake();

        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $trooper_request = $this->createRequest($trooper, $organization, $organization);

        (new ApproveTrooperRequest($trooper_request, true))->handle();

        Notification::assertNothingSent();
    }

    public function test_handle_rejects_a_duplicate_identifier_before_approving(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $existing_trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()
            ->asOrganization()
            ->withNodePath('100:')
            ->withIdentifierDisplay('TK ID')
            ->create();

        TrooperOrganization::factory()
            ->forTrooper($existing_trooper)
            ->forOrganization($organization)
            ->withIdentifier('TK-12345')
            ->create();

        $trooper_request = $this->createRequest($trooper, $organization, $organization);
        DB::table('tt_trooper_requests')
            ->where(TrooperRequest::ID, $trooper_request->id)
            ->update([TrooperRequest::IDENTIFIER => 'TK-12345']);
        $trooper_request->refresh();

        try
        {
            (new ApproveTrooperRequest($trooper_request, true))->handle();
            $this->fail('A duplicate organization identifier should be rejected.');
        }
        catch (Exception $exception)
        {
            $this->assertStringContainsString('TK ID TK-12345 is already assigned', $exception->getMessage());
        }

        $this->assertDatabaseHas('tt_trooper_requests', [
            TrooperRequest::ID => $trooper_request->id,
            TrooperRequest::STATUS => TrooperRequestStatus::PENDING->value,
        ]);
    }

    private function createRequest(
        Trooper $trooper,
        Organization $primary_organization,
        Organization $organization,
        ?string $identifier = null,
    ): TrooperRequest {
        $factory = TrooperRequest::factory()
            ->forTrooper($trooper)
            ->forPrimaryOrganization($primary_organization)
            ->forOrganization($organization);

        if ($identifier !== null)
        {
            $factory = $factory->withIdentifier($identifier);
        }

        return $factory->create();
    }
}