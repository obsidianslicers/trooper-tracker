<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

use App\Models\Event;
use App\Models\EventUpload;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MissionReviewControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_mission_review_page_for_admin(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();
        EventUpload::factory()->for($event)->create([
            EventUpload::IS_ADMINISTRATIVE => false,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.events.mission-review', compact('event')));

        $response->assertOk();
        $response->assertViewIs('pages.admin.events.mission-review');
        $response->assertSee('Move to Admin Uploads');
    }

    public function test_invoke_passes_only_non_administrative_uploads(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();

        EventUpload::factory()->for($event)->create([EventUpload::IS_ADMINISTRATIVE => false]);
        EventUpload::factory()->for($event)->create([EventUpload::IS_ADMINISTRATIVE => true]);

        $response = $this->actingAs($admin)
            ->get(route('admin.events.mission-review', compact('event')));

        $response->assertOk();
        $uploads = $response->viewData('member_uploads');
        $this->assertCount(1, $uploads);
        $this->assertFalse($uploads->first()->is_administrative);
    }

    public function test_invoke_requires_authentication(): void
    {
        $event = Event::factory()->create();

        $response = $this->get(route('admin.events.mission-review', compact('event')));

        $response->assertRedirect(route('auth.login'));
    }
}
