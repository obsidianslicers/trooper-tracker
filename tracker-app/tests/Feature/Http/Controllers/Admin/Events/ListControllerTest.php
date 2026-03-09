<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

use App\Models\Event;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_events_list_for_admin(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        Event::factory()->count(2)->create();

        $response = $this->actingAs($trooper)->get(route('admin.events.list'));

        $response->assertOk();
        $response->assertViewIs('pages.admin.events.list');
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('admin.events.list'));

        $response->assertRedirect(route('auth.login'));
    }
}
