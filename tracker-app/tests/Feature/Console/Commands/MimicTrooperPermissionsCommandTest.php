<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Enums\MembershipRole;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MimicTrooperPermissionsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_mimics_permissions_and_stores_snapshot(): void
    {
        Storage::fake('local');

        $target = Trooper::factory()->asMember()->create();
        $source = Trooper::factory()->asAdministrator()->create();

        $target_org = Organization::factory()->create();
        $source_org = Organization::factory()->create();

        TrooperAssignment::factory()
            ->forTrooper($target)
            ->forOrganization($target_org)
            ->asMember()
            ->create();

        TrooperAssignment::factory()
            ->forTrooper($source)
            ->forOrganization($source_org)
            ->asMember()
            ->asModerator()
            ->withShouldNotify(true)
            ->create();

        $this->artisan("tracker:mimic-trooper-permissions {$target->id} {$source->id}")
            ->assertExitCode(0);

        $target->refresh();
        $this->assertSame(MembershipRole::ADMINISTRATOR, $target->membership_role);

        $this->assertDatabaseMissing('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $target->id,
            TrooperAssignment::ORGANIZATION_ID => $target_org->id,
            TrooperAssignment::DELETED_AT => null,
        ]);

        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $target->id,
            TrooperAssignment::ORGANIZATION_ID => $source_org->id,
            TrooperAssignment::IS_MEMBER => true,
            TrooperAssignment::IS_MODERATOR => true,
            TrooperAssignment::SHOULD_NOTIFY => true,
            TrooperAssignment::DELETED_AT => null,
        ]);

        Storage::disk('local')->assertExists("permission-mimics/trooper-{$target->id}.json");
    }

    public function test_it_can_revert_back_to_original_permissions(): void
    {
        Storage::fake('local');

        $target = Trooper::factory()->asMember()->create();
        $source = Trooper::factory()->asModerator()->create();

        $original_org = Organization::factory()->create();
        $source_org = Organization::factory()->create();

        TrooperAssignment::factory()
            ->forTrooper($target)
            ->forOrganization($original_org)
            ->asMember()
            ->withShouldNotify(true)
            ->create();

        TrooperAssignment::factory()
            ->forTrooper($source)
            ->forOrganization($source_org)
            ->asMember()
            ->asModerator()
            ->create();

        $this->artisan("tracker:mimic-trooper-permissions {$target->id} {$source->id}")
            ->assertExitCode(0);

        $this->artisan("tracker:mimic-trooper-permissions {$target->id} --revert")
            ->assertExitCode(0);

        $target->refresh();
        $this->assertSame(MembershipRole::MEMBER, $target->membership_role);

        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $target->id,
            TrooperAssignment::ORGANIZATION_ID => $original_org->id,
            TrooperAssignment::IS_MEMBER => true,
            TrooperAssignment::IS_MODERATOR => false,
            TrooperAssignment::SHOULD_NOTIFY => true,
            TrooperAssignment::DELETED_AT => null,
        ]);

        $this->assertDatabaseMissing('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $target->id,
            TrooperAssignment::ORGANIZATION_ID => $source_org->id,
            TrooperAssignment::DELETED_AT => null,
        ]);

        Storage::disk('local')->assertMissing("permission-mimics/trooper-{$target->id}.json");
    }
}
