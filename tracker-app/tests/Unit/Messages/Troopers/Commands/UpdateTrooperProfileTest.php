<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands;

use App\Enums\TrooperTheme;
use App\Messages\Troopers\Commands\UpdateTrooperProfile;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTrooperProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_and_persists_trooper_profile_fields(): void
    {
        $initial_display_costume = TrooperCostume::factory()->create();
        $updated_display_costume = TrooperCostume::factory()->create();

        $trooper = Trooper::factory()->asMember()->create([
            Trooper::LEGAL_NAME => 'Old Legal',
            Trooper::DISPLAY_NAME => 'Old Display',
            Trooper::THEME => TrooperTheme::STORMTROOPER,
            Trooper::PHONE => '111-1111',
            Trooper::DISPLAY_COSTUME_ID => $initial_display_costume->id,
        ]);

        $subject = new UpdateTrooperProfile(
            trooper: $trooper,
            legal_name: 'New Legal',
            display_name: 'New Display',
            theme: TrooperTheme::REBEL,
            phone: '222-2222',
            display_costume_id: $updated_display_costume->id,
        );

        $subject->handle();

        $trooper->refresh();

        $this->assertSame('New Legal', $trooper->legal_name);
        $this->assertSame('New Display', $trooper->display_name);
        $this->assertSame(TrooperTheme::REBEL, $trooper->theme);
        $this->assertSame('222-2222', $trooper->phone);
        $this->assertSame($updated_display_costume->id, $trooper->display_costume_id);

        $this->assertDatabaseHas('tt_troopers', [
            Trooper::ID => $trooper->id,
            Trooper::LEGAL_NAME => 'New Legal',
            Trooper::DISPLAY_NAME => 'New Display',
            Trooper::THEME => TrooperTheme::REBEL->value,
            Trooper::PHONE => '222-2222',
            Trooper::DISPLAY_COSTUME_ID => $updated_display_costume->id,
        ]);
    }

    public function test_invoke_can_clear_nullable_profile_fields(): void
    {
        $existing_display_costume = TrooperCostume::factory()->create();

        $trooper = Trooper::factory()->asMember()->create([
            Trooper::PHONE => '333-3333',
            Trooper::DISPLAY_COSTUME_ID => $existing_display_costume->id,
        ]);

        $subject = new UpdateTrooperProfile(
            trooper: $trooper,
            legal_name: 'Legal Name',
            display_name: 'Display Name',
            theme: TrooperTheme::CLONE ,
            phone: null,
            display_costume_id: null,
        );

        $subject->handle();

        $this->assertDatabaseHas('tt_troopers', [
            Trooper::ID => $trooper->id,
            Trooper::LEGAL_NAME => 'Legal Name',
            Trooper::DISPLAY_NAME => 'Display Name',
            Trooper::THEME => TrooperTheme::CLONE ->value,
            Trooper::PHONE => null,
            Trooper::DISPLAY_COSTUME_ID => null,
        ]);
    }
}