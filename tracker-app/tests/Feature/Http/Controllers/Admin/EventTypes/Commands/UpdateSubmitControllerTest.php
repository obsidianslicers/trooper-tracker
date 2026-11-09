<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\EventTypes\Commands;

use App\Models\EventType;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_saves_settings_and_keeps_the_slug(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $armor_party = EventType::where(EventType::SLUG, 'armorparty')->firstOrFail();

        $response = $this->actingAs($trooper)
            ->post(route('admin.event-types.update', ['event_type' => $armor_party]), [
                'name' => 'Armor Build Party',
                'description' => 'Workshops. No troop credit.',
                'is_counted' => false,
                'is_active' => true,
            ]);

        $response->assertRedirect(route('admin.event-types.index'));
        $this->assertDatabaseHas('tt_event_types', [
            EventType::ID => $armor_party->id,
            EventType::SLUG => 'armorparty',
            EventType::NAME => 'Armor Build Party',
            EventType::DESCRIPTION => 'Workshops. No troop credit.',
            EventType::IS_COUNTED => false,
        ]);
    }

    public function test_invoke_allows_keeping_its_own_name(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $charity = EventType::where(EventType::SLUG, 'charity')->firstOrFail();

        $response = $this->actingAs($trooper)
            ->post(route('admin.event-types.update', ['event_type' => $charity]), [
                'name' => 'Charity',
                'is_counted' => true,
                'is_active' => false,
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertFalse($charity->fresh()->is_active);
    }
}
