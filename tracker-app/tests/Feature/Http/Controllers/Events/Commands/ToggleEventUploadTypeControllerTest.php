<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events\Commands;

use App\Models\Event;
use App\Models\EventUpload;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ReadsToasts;
use Tests\TestCase;

class ToggleEventUploadTypeControllerTest extends TestCase
{
    use ReadsToasts;
    use RefreshDatabase;

    public function test_invoke_lets_uploader_move_own_photo_to_instructional(): void
    {
        $trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();
        $upload = EventUpload::factory()->for(Event::factory())->create([
            EventUpload::TROOPER_ID => $trooper->id,
            EventUpload::IS_ADMINISTRATIVE => false,
        ]);

        $response = $this->actingAs($trooper)
            ->post(route('events.toggle-upload-type', ['event_upload' => $upload->id]));

        $response->assertRedirect(route('events.display', ['event' => $upload->event_id]));
        $this->assertSame('success', $this->toastType());
        $this->assertTrue($upload->refresh()->is_administrative);
    }

    public function test_invoke_lets_officer_move_any_photo_to_mission_review(): void
    {
        $officer = Trooper::factory()->asSystemOwner()->withVerifiedEmail()->create();
        $upload = EventUpload::factory()->for(Event::factory())->create([
            EventUpload::IS_ADMINISTRATIVE => true,
        ]);

        $response = $this->actingAs($officer)
            ->post(route('events.toggle-upload-type', ['event_upload' => $upload->id]));

        $response->assertRedirect();
        $this->assertFalse($upload->refresh()->is_administrative);
    }

    public function test_invoke_forbids_member_moving_someone_elses_photo(): void
    {
        $trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();
        $upload = EventUpload::factory()->for(Event::factory())->create([
            EventUpload::IS_ADMINISTRATIVE => false,
        ]);

        $response = $this->actingAs($trooper)
            ->post(route('events.toggle-upload-type', ['event_upload' => $upload->id]));

        $response->assertForbidden();
        $this->assertFalse($upload->refresh()->is_administrative);
    }
}
