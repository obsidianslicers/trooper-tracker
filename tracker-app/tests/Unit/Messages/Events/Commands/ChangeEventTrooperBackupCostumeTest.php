<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\Commands;

use App\Messages\Events\Commands\ChangeEventTrooperBackupCostume;
use App\Models\Costume;
use App\Models\EventTrooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChangeEventTrooperBackupCostumeTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_sets_the_backup_costume(): void
    {
        $event_trooper = EventTrooper::factory()->create();
        $costume = Costume::factory()->create();

        $subject = new ChangeEventTrooperBackupCostume($event_trooper, $costume->id);
        $subject->handle();

        $this->assertSame($costume->id, $event_trooper->fresh()->backup_costume_id);
    }

    public function test_handle_clears_the_backup_costume(): void
    {
        $event_trooper = EventTrooper::factory()->withBackupCostume(Costume::factory()->create())->create();

        $subject = new ChangeEventTrooperBackupCostume($event_trooper, null);
        $subject->handle();

        $this->assertNull($event_trooper->fresh()->backup_costume_id);
    }
}
