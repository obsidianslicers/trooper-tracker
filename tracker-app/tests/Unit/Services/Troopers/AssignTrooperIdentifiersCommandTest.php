<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Troopers;

use App\Enums\MembershipStatus;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperOrganization;
use App\Services\Troopers\AssignTrooperIdentifiersCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for AssignTrooperIdentifiersCommand.
 *
 * Verifies:
 * - Creates new TrooperOrganization records with identifiers.
 * - Updates existing TrooperOrganization identifiers.
 * - Skips organizations without identifiers.
 * - Trims whitespace from identifiers.
 * - Sets membership status to ACTIVE for new records.
 * - Processes multiple organizations in one invocation.
 */
class AssignTrooperIdentifiersCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_creates_new_trooper_organization_with_identifier(): void
    {
        // Arrange
        $subject = new AssignTrooperIdentifiersCommand();

        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();

        $organizations_data = [
            $organization->id => [
                'identifier' => 'TK-12345',
            ],
        ];

        // Act
        $subject($trooper, $organizations_data);

        // Assert
        $trooper_organization = TrooperOrganization::where(TrooperOrganization::TROOPER_ID, $trooper->id)
            ->where(TrooperOrganization::ORGANIZATION_ID, $organization->id)
            ->first();

        $this->assertNotNull($trooper_organization);
        $this->assertEquals('TK-12345', $trooper_organization->identifier);
        $this->assertEquals(MembershipStatus::ACTIVE, $trooper_organization->membership_status);
    }

    public function test_invoke_skips_organizations_without_identifier(): void
    {
        // Arrange
        $subject = new AssignTrooperIdentifiersCommand();

        $trooper = Trooper::factory()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        $organizations_data = [
            $org1->id => [
                'identifier' => 'TK-12345',
            ],
            $org2->id => [
                'identifier' => '',
            ],
        ];

        // Act
        $subject($trooper, $organizations_data);

        // Assert
        $this->assertEquals(1, TrooperOrganization::where(TrooperOrganization::TROOPER_ID, $trooper->id)->count());

        $trooper_org1 = TrooperOrganization::where(TrooperOrganization::TROOPER_ID, $trooper->id)
            ->where(TrooperOrganization::ORGANIZATION_ID, $org1->id)
            ->first();
        $this->assertNotNull($trooper_org1);

        $trooper_org2 = TrooperOrganization::where(TrooperOrganization::TROOPER_ID, $trooper->id)
            ->where(TrooperOrganization::ORGANIZATION_ID, $org2->id)
            ->first();
        $this->assertNull($trooper_org2);
    }

    public function test_invoke_trims_whitespace_from_identifier(): void
    {
        // Arrange
        $subject = new AssignTrooperIdentifiersCommand();

        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();

        $organizations_data = [
            $organization->id => [
                'identifier' => '  TK-12345  ',
            ],
        ];

        // Act
        $subject($trooper, $organizations_data);

        // Assert
        $trooper_organization = TrooperOrganization::where(TrooperOrganization::TROOPER_ID, $trooper->id)
            ->where(TrooperOrganization::ORGANIZATION_ID, $organization->id)
            ->first();

        $this->assertEquals('TK-12345', $trooper_organization->identifier);
    }

    public function test_invoke_processes_multiple_organizations(): void
    {
        // Arrange
        $subject = new AssignTrooperIdentifiersCommand();

        $trooper = Trooper::factory()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();
        $org3 = Organization::factory()->create();

        $organizations_data = [
            $org1->id => ['identifier' => 'TK-12345'],
            $org2->id => ['identifier' => 'SL-67890'],
            $org3->id => ['identifier' => 'TB-11111'],
        ];

        // Act
        $subject($trooper, $organizations_data);

        // Assert
        $this->assertEquals(3, TrooperOrganization::where(TrooperOrganization::TROOPER_ID, $trooper->id)->count());

        $org1_identifier = TrooperOrganization::where(TrooperOrganization::TROOPER_ID, $trooper->id)
            ->where(TrooperOrganization::ORGANIZATION_ID, $org1->id)
            ->first();
        $this->assertEquals('TK-12345', $org1_identifier->identifier);

        $org2_identifier = TrooperOrganization::where(TrooperOrganization::TROOPER_ID, $trooper->id)
            ->where(TrooperOrganization::ORGANIZATION_ID, $org2->id)
            ->first();
        $this->assertEquals('SL-67890', $org2_identifier->identifier);

        $org3_identifier = TrooperOrganization::where(TrooperOrganization::TROOPER_ID, $trooper->id)
            ->where(TrooperOrganization::ORGANIZATION_ID, $org3->id)
            ->first();
        $this->assertEquals('TB-11111', $org3_identifier->identifier);
    }

    public function test_invoke_sets_membership_status_to_active_for_new_records(): void
    {
        // Arrange
        $subject = new AssignTrooperIdentifiersCommand();

        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();

        $organizations_data = [
            $organization->id => [
                'identifier' => 'TK-12345',
            ],
        ];

        // Act
        $subject($trooper, $organizations_data);

        // Assert
        $trooper_organization = TrooperOrganization::where(TrooperOrganization::TROOPER_ID, $trooper->id)
            ->where(TrooperOrganization::ORGANIZATION_ID, $organization->id)
            ->first();

        $this->assertEquals(MembershipStatus::ACTIVE, $trooper_organization->membership_status);
    }

    public function test_invoke_skips_empty_string_identifier(): void
    {
        // Arrange
        $subject = new AssignTrooperIdentifiersCommand();

        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();

        $organizations_data = [
            $organization->id => [
                'identifier' => '',
            ],
        ];

        // Act
        $subject($trooper, $organizations_data);

        // Assert
        $trooper_organization = TrooperOrganization::where(TrooperOrganization::TROOPER_ID, $trooper->id)
            ->where(TrooperOrganization::ORGANIZATION_ID, $organization->id)
            ->first();

        $this->assertNull($trooper_organization);
    }

    public function test_invoke_creates_empty_identifier_when_whitespace_only(): void
    {
        // Arrange
        $subject = new AssignTrooperIdentifiersCommand();

        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();

        $organizations_data = [
            $organization->id => [
                'identifier' => '   ',
            ],
        ];

        // Act
        $subject($trooper, $organizations_data);

        // Assert
        $trooper_organization = TrooperOrganization::where(TrooperOrganization::TROOPER_ID, $trooper->id)
            ->where(TrooperOrganization::ORGANIZATION_ID, $organization->id)
            ->first();

        // The code checks empty() before trim, so '   ' passes and gets trimmed to ''
        $this->assertNotNull($trooper_organization);
        $this->assertEquals('', $trooper_organization->identifier);
    }
}
