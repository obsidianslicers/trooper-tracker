<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Scopes;

use App\Models\EventShift;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HasEventShiftScopesTest extends TestCase
{
    use RefreshDatabase;

    public function test_scope_active_includes_open_shifts(): void
    {
        // Arrange
        $costume = OrganizationCostume::factory()->create();
        $trooper = Trooper::factory()->create();

        $open_shift = EventShift::factory()
            ->withAssignment($trooper, $costume)
            ->open()
            ->create();

        // Act
        $result = EventShift::active()->get();

        // Assert
        $this->assertTrue($result->contains($open_shift));
    }

    public function test_scope_active_includes_draft_shifts(): void
    {
        // Arrange
        $costume = OrganizationCostume::factory()->create();
        $trooper = Trooper::factory()->create();

        $draft_shift = EventShift::factory()
            ->withAssignment($trooper, $costume)
            ->draft()
            ->create();

        // Act
        $result = EventShift::active()->get();

        // Assert
        $this->assertTrue($result->contains($draft_shift));
    }

    public function test_scope_active_includes_sign_up_locked_shifts(): void
    {
        // Arrange
        $costume = OrganizationCostume::factory()->create();
        $trooper = Trooper::factory()->create();

        $locked_shift = EventShift::factory()
            ->withAssignment($trooper, $costume)
            ->signUpLocked()
            ->create();

        // Act
        $result = EventShift::active()->get();

        // Assert
        $this->assertTrue($result->contains($locked_shift->id));
    }

    public function test_scope_active_excludes_closed_shifts(): void
    {
        // Arrange
        $costume = OrganizationCostume::factory()->create();
        $trooper = Trooper::factory()->create();

        $closed_shift = EventShift::factory()
            ->withAssignment($trooper, $costume)
            ->closed()
            ->create();

        // Act
        $result = EventShift::active()->get();

        // Assert
        $this->assertFalse($result->contains($closed_shift));
    }

    public function test_scope_by_trooper_filters_shifts_by_trooper_participation(): void
    {
        // Arrange
        $costume = OrganizationCostume::factory()->create();
        $trooper = Trooper::factory()->create();
        $other_trooper = Trooper::factory()->create();

        $shift_with_trooper = EventShift::factory()
            ->withAssignment($trooper, $costume)
            ->open()
            ->create();

        $shift_with_other = EventShift::factory()
            ->withAssignment($other_trooper, $costume)
            ->open()
            ->create();

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
        $costume = OrganizationCostume::factory()->create();
        $trooper = Trooper::factory()->create();

        $open_shift = EventShift::factory()
            ->withAssignment($trooper, $costume)
            ->open()
            ->create();

        $closed_shift = EventShift::factory()
            ->withAssignment($trooper, $costume)
            ->closed()
            ->create();

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
        $costume = OrganizationCostume::factory()->create();
        $trooper = Trooper::factory()->create();

        $open_shift = EventShift::factory()
            ->withAssignment($trooper, $costume)
            ->open()
            ->create();

        $closed_shift = EventShift::factory()
            ->withAssignment($trooper, $costume)
            ->closed()
            ->create();

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
        $costume = OrganizationCostume::factory()->create();
        $trooper = Trooper::factory()->create();

        $shift = EventShift::factory()
            ->withAssignment($trooper, $costume)
            ->open()
            ->create();

        // Act
        $result = EventShift::byTrooper($trooper->id, false)->first();

        // Assert
        $this->assertTrue($result->relationLoaded('event_troopers'));
    }

    public function test_scope_roster_eager_loads_relationships(): void
    {
        // Arrange
        $costume = OrganizationCostume::factory()->create();
        $trooper = Trooper::factory()->create();

        $shift = EventShift::factory()
            ->withAssignment($trooper, $costume)
            ->open()
            ->create();

        // Act
        $result = EventShift::roster()->first();

        // Assert
        $this->assertTrue($result->relationLoaded('event_troopers'));
    }

    public function test_scope_roster_orders_by_shift_starts_at(): void
    {
        // Arrange
        $costume = OrganizationCostume::factory()->create();
        $trooper = Trooper::factory()->create();

        $shift1 = EventShift::factory()
            ->withAssignment($trooper, $costume)
            ->open()
            ->create(['shift_starts_at' => now()->addDays(3)]);
        $shift2 = EventShift::factory()
            ->withAssignment($trooper, $costume)
            ->open()
            ->create(['shift_starts_at' => now()->addDay()]);
        $shift3 = EventShift::factory()
            ->withAssignment($trooper, $costume)
            ->open()
            ->create(['shift_starts_at' => now()->addDays(5)]);

        // Act
        $result = EventShift::roster()->get();

        // Assert
        $this->assertEquals($shift2->id, $result[0]->id, 'Earliest shift should be first');
        $this->assertEquals($shift1->id, $result[1]->id);
        $this->assertEquals($shift3->id, $result[2]->id);
    }
}
