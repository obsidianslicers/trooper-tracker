<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns;

use App\Enums\MembershipStatus;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\ModelChange;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HasAuditTrailTest extends TestCase
{
    use RefreshDatabase;

    public function test_trait_exists(): void
    {
        $this->assertTrue(trait_exists('App\Models\Concerns\HasAuditTrail'));
    }

    public function test_updating_audited_field_creates_model_change_with_authenticated_trooper_id(): void
    {
        $actor = Trooper::factory()->asActive()->create();
        $subject = Trooper::factory()->asActive()->create();

        $this->actingAs($actor);

        $subject->{Trooper::MEMBERSHIP_STATUS} = MembershipStatus::RETIRED->value;
        $subject->save();

        $this->assertDatabaseHas('tt_model_changes', [
            ModelChange::AUDITABLE_TYPE => Trooper::class,
            ModelChange::AUDITABLE_ID => $subject->id,
            ModelChange::TROOPER_ID => $actor->id,
            ModelChange::FIELD_NAME => Trooper::MEMBERSHIP_STATUS,
        ]);
    }

    public function test_updating_audited_field_creates_model_change_with_null_trooper_id_for_guest(): void
    {
        $subject = Trooper::factory()->asActive()->create();

        $subject->{Trooper::MEMBERSHIP_STATUS} = MembershipStatus::RETIRED->value;
        $subject->save();

        $this->assertDatabaseHas('tt_model_changes', [
            ModelChange::AUDITABLE_TYPE => Trooper::class,
            ModelChange::AUDITABLE_ID => $subject->id,
            ModelChange::TROOPER_ID => null,
            ModelChange::FIELD_NAME => Trooper::MEMBERSHIP_STATUS,
        ]);
    }

    public function test_updating_audited_json_cast_field_stores_json_encoded_old_and_new_values(): void
    {
        $org = Organization::factory()->create();
        $event_shift = EventShift::factory()->create();
        $subject = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->create([EventTrooper::COSTUME_ORGANIZATION_IDS => []]);

        $subject->{EventTrooper::COSTUME_ORGANIZATION_IDS} = [$org->id];
        $subject->save();

        $this->assertDatabaseHas('tt_model_changes', [
            ModelChange::AUDITABLE_TYPE => EventTrooper::class,
            ModelChange::AUDITABLE_ID => $subject->id,
            ModelChange::FIELD_NAME => EventTrooper::COSTUME_ORGANIZATION_IDS,
            ModelChange::OLD_VALUE => json_encode([]),
            ModelChange::NEW_VALUE => json_encode([$org->id]),
        ]);
    }
}