<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

use App\Models\Event;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UploadsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_uploads_page_for_admin(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($trooper)->get(route('admin.events.uploads', ['event' => $event->id]));

        $response->assertOk();
        $response->assertViewIs('pages.admin.events.uploads');
    }

    public function test_invoke_requires_authentication(): void
    {
        $event = Event::factory()->create();

        $response = $this->get(route('admin.events.uploads', ['event' => $event->id]));

        $response->assertRedirect(route('auth.login'));
    }
}
