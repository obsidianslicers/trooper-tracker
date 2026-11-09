<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\EventTypes\Commands;

use App\Models\EventType;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_adds_the_type_at_the_end_with_a_slug(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $last = EventType::max(EventType::SORT_ORDER);

        $response = $this->actingAs($trooper)->post(route('admin.event-types.create'), [
            'name' => 'Squad Meeting',
            'description' => 'Business meetings, out of costume.',
            'is_counted' => false,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('admin.event-types.index'));
        $this->assertDatabaseHas('tt_event_types', [
            EventType::SLUG => 'squadmeeting',
            EventType::NAME => 'Squad Meeting',
            EventType::IS_COUNTED => false,
            EventType::IS_ACTIVE => true,
            EventType::SORT_ORDER => $last + 1,
            EventType::CREATED_ID => $trooper->id,
        ]);
    }

    public function test_invoke_rejects_a_name_already_in_use(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();

        $response = $this->actingAs($trooper)->post(route('admin.event-types.create'), [
            'name' => 'Charity',
            'is_counted' => true,
            'is_active' => true,
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_invoke_forbids_moderators(): void
    {
        $trooper = Trooper::factory()->asModerator()->create();

        $response = $this->actingAs($trooper)->post(route('admin.event-types.create'), [
            'name' => 'Squad Meeting',
            'is_counted' => true,
            'is_active' => true,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('tt_event_types', [EventType::NAME => 'Squad Meeting']);
    }
}
