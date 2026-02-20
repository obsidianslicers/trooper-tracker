<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Scopes;

use App\Models\Costume;
use App\Models\EventTrooper;
use App\Models\EventShift;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HasEventShiftScopesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Costume::resolveRelationUsing('organization', function (Costume $costume)
        {
            return $costume->belongsToMany(
                Organization::class,
                OrganizationCostume::class,
                OrganizationCostume::COSTUME_ID,
                OrganizationCostume::ORGANIZATION_ID
            );
        });
    }

    public function test_scope_active_includes_open_shifts(): void
    {
        // Arrange
        $costume = Costume::factory()->create();
        $trooper = Trooper::factory()->create();
        $open_shift = $this->createShiftWithAssignment($trooper, $costume, 'open');

        // Act
        $result = EventShift::active()->get();

        // Assert
        $this->assertTrue($result->contains($open_shift));
    }

    public function test_scope_active_includes_draft_shifts(): void
    {
        // Arrange
        $costume = Costume::factory()->create();
        $trooper = Trooper::factory()->create();
        $draft_shift = $this->createShiftWithAssignment($trooper, $costume, 'draft');

        // Act
        $result = EventShift::active()->get();

        // Assert
        $this->assertTrue($result->contains($draft_shift));
    }

    public function test_scope_active_includes_sign_up_locked_shifts(): void
    {
        // Arrange
        $costume = Costume::factory()->create();
        $trooper = Trooper::factory()->create();
        $locked_shift = $this->createShiftWithAssignment($trooper, $costume, 'signUpLocked');

        // Act
        $result = EventShift::active()->get();

        // Assert
        $this->assertTrue($result->contains($locked_shift->id));
    }

    public function test_scope_active_excludes_closed_shifts(): void
    {
        // Arrange
        $costume = Costume::factory()->create();
        $trooper = Trooper::factory()->create();
        $closed_shift = $this->createShiftWithAssignment($trooper, $costume, 'closed');

        // Act
        $result = EventShift::active()->get();

        // Assert
        $this->assertFalse($result->contains($closed_shift));
    }

    public function test_scope_by_trooper_filters_shifts_by_trooper_participation(): void
    {
        // Arrange
        $costume = Costume::factory()->create();
        $trooper = Trooper::factory()->create();
        $other_trooper = Trooper::factory()->create();
        $shift_with_trooper = $this->createShiftWithAssignment($trooper, $costume, 'open');
        $shift_with_other = $this->createShiftWithAssignment($other_trooper, $costume, 'open');

        // Act
        $result = EventShift::byTrooper($trooper->id, false)->get();

        // Assert
        $this->assertCount(1, $result);
        $this->assertTrue($result->contains($shift_with_trooper));
        $this->assertFalse($result->contains($shift_with_other));
    }

    public function test_scope_by_trooper_filters_by_open_status(): void
    {
        // Arrange
        $costume = Costume::factory()->create();
        $trooper = Trooper::factory()->create();
        $open_shift = $this->createShiftWithAssignment($trooper, $costume, 'open');
        $closed_shift = $this->createShiftWithAssignment($trooper, $costume, 'closed');

        // Act
        $result = EventShift::byTrooper($trooper->id, false)->get();

        // Assert
        $this->assertCount(1, $result);
        $this->assertTrue($result->contains($open_shift));
        $this->assertFalse($result->contains($closed_shift));
    }

    public function test_scope_by_trooper_filters_by_closed_status(): void
    {
        // Arrange
        $costume = Costume::factory()->create();
        $trooper = Trooper::factory()->create();
        $open_shift = $this->createShiftWithAssignment($trooper, $costume, 'open');
        $closed_shift = $this->createShiftWithAssignment($trooper, $costume, 'closed');

        // Act
        $result = EventShift::byTrooper($trooper->id, true)->get();

        // Assert
        $this->assertCount(1, $result);
        $this->assertFalse($result->contains($open_shift));
        $this->assertTrue($result->contains($closed_shift));
    }

    public function test_scope_by_trooper_eager_loads_event_troopers(): void
    {
        // Arrange
        $costume = Costume::factory()->create();
        $trooper = Trooper::factory()->create();
        $shift = $this->createShiftWithAssignment($trooper, $costume, 'open');

        // Act
        $result = EventShift::byTrooper($trooper->id, false)->first();

        // Assert
        $this->assertTrue($result->relationLoaded('event_troopers'));
    }

    private function createShiftWithAssignment(
        Trooper $trooper,
        Costume $costume,
        string $state
    ): EventShift {
        $shift = EventShift::factory()->{$state}()->create();

        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::COSTUME_ID => $costume->id,
        ]);

        return $shift;
    }
}
