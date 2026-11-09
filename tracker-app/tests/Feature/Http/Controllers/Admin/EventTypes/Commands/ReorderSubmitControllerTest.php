<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\EventTypes\Commands;

use App\Models\EventType;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReorderSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_saves_the_new_order(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $ids = EventType::query()->ordered()->pluck(EventType::ID)->reverse()->values()->all();

        $response = $this->actingAs($trooper)
            ->post(route('admin.event-types.reorder'), ['ids' => $ids]);

        $response->assertRedirect();
        $this->assertSame($ids, EventType::query()->ordered()->pluck(EventType::ID)->all());
    }

    public function test_invoke_forbids_moderators(): void
    {
        $trooper = Trooper::factory()->asModerator()->create();

        $response = $this->actingAs($trooper)
            ->post(route('admin.event-types.reorder'), ['ids' => [1]]);

        $response->assertForbidden();
    }
}
