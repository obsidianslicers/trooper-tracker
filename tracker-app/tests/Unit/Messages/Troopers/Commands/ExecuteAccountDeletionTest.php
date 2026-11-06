<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands;

use App\Messages\Troopers\Commands\ExecuteAccountDeletion;
use App\Models\EventTrooper;
use App\Models\MobileDevice;
use App\Models\OauthLogin;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExecuteAccountDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_anonymizes_pii_fields(): void
    {
        $trooper = Trooper::factory()->asActive()->create([
            Trooper::DISPLAY_NAME => 'Jane Trooper Display',
            Trooper::LEGAL_NAME => 'Jane Trooper',
            Trooper::PHONE => '555-1234',
        ]);

        (new ExecuteAccountDeletion($trooper))->handle();

        $fresh = Trooper::withTrashed()->find($trooper->id);
        $this->assertNotEquals('Jane Trooper Display', $fresh->display_name);
        $this->assertEquals('Deleted Member', $fresh->legal_name);
        $this->assertNull($fresh->phone);
        $this->assertNotEquals('', $fresh->password);
        $this->assertStringContainsString('deleted.invalid', $fresh->email);
    }

    public function test_handle_soft_deletes_trooper(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        (new ExecuteAccountDeletion($trooper))->handle();

        $this->assertSoftDeleted('tt_troopers', [Trooper::ID => $trooper->id]);
    }

    public function test_handle_hard_deletes_oauth_logins(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        OauthLogin::factory()->create([OauthLogin::TROOPER_ID => $trooper->id]);

        (new ExecuteAccountDeletion($trooper))->handle();

        $this->assertDatabaseMissing('tt_oauth_logins', [OauthLogin::TROOPER_ID => $trooper->id]);
    }

    public function test_handle_hard_deletes_mobile_devices(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        MobileDevice::factory()->create([MobileDevice::TROOPER_ID => $trooper->id]);

        (new ExecuteAccountDeletion($trooper))->handle();

        $this->assertDatabaseMissing('tt_mobile_devices', [MobileDevice::TROOPER_ID => $trooper->id]);
    }

    public function test_handle_soft_deletes_costumes(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        TrooperCostume::factory()->create([TrooperCostume::TROOPER_ID => $trooper->id]);

        (new ExecuteAccountDeletion($trooper))->handle();

        $this->assertSoftDeleted('tt_trooper_costumes', [TrooperCostume::TROOPER_ID => $trooper->id]);
    }

    public function test_handle_retains_event_participation_records(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $event_trooper = EventTrooper::factory()->create([EventTrooper::TROOPER_ID => $trooper->id]);

        (new ExecuteAccountDeletion($trooper))->handle();

        $this->assertDatabaseHas('tt_event_troopers', [EventTrooper::ID => $event_trooper->id]);
    }
}
