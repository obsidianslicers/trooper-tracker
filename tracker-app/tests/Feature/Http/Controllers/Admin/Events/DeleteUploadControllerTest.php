<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

use App\Models\Event;
use App\Models\EventUpload;
use App\Models\EventUploadTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DeleteUploadControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_deletes_upload_and_returns_view(): void
    {
        Storage::fake('public');

        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();
        $upload = EventUpload::factory()->for($event)->create([EventUpload::IS_ADMINISTRATIVE => false]);

        $response = $this->actingAs($admin)
            ->post(route('admin.events.uploads.delete', ['event' => $event, 'event_upload' => $upload]));

        $response->assertOk();
        $response->assertViewIs('pages.admin.events.mission-review');
        $this->assertDatabaseMissing('tt_event_uploads', [EventUpload::ID => $upload->id]);
    }

    public function test_invoke_returns_empty_state_when_last_upload_is_deleted(): void
    {
        Storage::fake('public');

        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();
        $upload = EventUpload::factory()->for($event)->create([EventUpload::IS_ADMINISTRATIVE => false]);

        $response = $this->actingAs($admin)
            ->post(route('admin.events.uploads.delete', ['event' => $event, 'event_upload' => $upload]));

        $response->assertOk();
        $uploads = $response->viewData('member_uploads');
        $this->assertCount(0, $uploads);
    }

    public function test_invoke_deletes_admin_upload_and_returns_uploads_view(): void
    {
        Storage::fake('public');

        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();
        $upload = EventUpload::factory()->for($event)->create([EventUpload::IS_ADMINISTRATIVE => true]);

        $response = $this->actingAs($admin)
            ->post(route('admin.events.uploads.delete', ['event' => $event, 'event_upload' => $upload]));

        $response->assertOk();
        $response->assertViewIs('pages.admin.events.uploads');
        $this->assertDatabaseMissing('tt_event_uploads', [EventUpload::ID => $upload->id]);
    }

    public function test_invoke_deletes_tagged_pivot_rows(): void
    {
        Storage::fake('public');

        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();
        $upload = EventUpload::factory()->for($event)->create();
        $trooper = Trooper::factory()->create();
        $pivot = EventUploadTrooper::factory()->forEventUpload($upload)->forTrooper($trooper)->create();

        $this->actingAs($admin)
            ->post(route('admin.events.uploads.delete', ['event' => $event, 'event_upload' => $upload]));

        $this->assertDatabaseMissing('tt_event_upload_troopers', [EventUploadTrooper::ID => $pivot->id]);
    }

    public function test_invoke_returns_403_when_upload_belongs_to_different_event(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();
        $other_event = Event::factory()->create();
        $upload = EventUpload::factory()->for($other_event)->create();

        $response = $this->actingAs($admin)
            ->post(route('admin.events.uploads.delete', ['event' => $event, 'event_upload' => $upload]));

        $response->assertForbidden();
    }

    public function test_invoke_requires_authentication(): void
    {
        $event = Event::factory()->create();
        $upload = EventUpload::factory()->for($event)->create();

        $response = $this->post(route('admin.events.uploads.delete', compact('event') + ['event_upload' => $upload]));

        $response->assertRedirect(route('auth.login'));
    }
}
