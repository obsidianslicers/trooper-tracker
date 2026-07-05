<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

use App\Models\Event;
use App\Models\EventUpload;
use App\Models\EventUploadTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ToggleUploadTypeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_moves_mission_review_upload_to_admin_uploads(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();
        $upload = EventUpload::factory()->for($event)->create([
            EventUpload::IS_ADMINISTRATIVE => false,
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.events.uploads.toggle-type', compact('event') + ['event_upload' => $upload]));

        $response->assertOk();
        $response->assertViewIs('pages.admin.events.mission-review');
        $this->assertTrue($upload->refresh()->is_administrative);
    }

    public function test_invoke_moves_admin_upload_to_mission_review(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();
        $upload = EventUpload::factory()->for($event)->create([
            EventUpload::IS_ADMINISTRATIVE => true,
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.events.uploads.toggle-type', compact('event') + ['event_upload' => $upload]));

        $response->assertOk();
        $response->assertViewIs('pages.admin.events.uploads');
        $this->assertFalse($upload->refresh()->is_administrative);
    }

    public function test_invoke_preserves_tag_rows_when_moving_to_admin_uploads(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();
        $upload = EventUpload::factory()->for($event)->create([
            EventUpload::IS_ADMINISTRATIVE => false,
        ]);
        $trooper = Trooper::factory()->create();
        $pivot = EventUploadTrooper::factory()->forEventUpload($upload)->forTrooper($trooper)->create();

        $this->actingAs($admin)
            ->post(route('admin.events.uploads.toggle-type', compact('event') + ['event_upload' => $upload]));

        $this->assertDatabaseHas('tt_event_upload_troopers', [
            EventUploadTrooper::ID => $pivot->id,
        ]);
    }

    public function test_invoke_returns_403_when_upload_belongs_to_different_event(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();
        $other_event = Event::factory()->create();
        $upload = EventUpload::factory()->for($other_event)->create();

        $response = $this->actingAs($admin)
            ->post(route('admin.events.uploads.toggle-type', compact('event') + ['event_upload' => $upload]));

        $response->assertForbidden();
    }

    public function test_invoke_requires_authentication(): void
    {
        $event = Event::factory()->create();
        $upload = EventUpload::factory()->for($event)->create();

        $response = $this->post(route('admin.events.uploads.toggle-type', compact('event') + ['event_upload' => $upload]));

        $response->assertRedirect(route('auth.login'));
    }
}
