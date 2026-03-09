<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Shares;

use App\Models\Event;
use App\Models\EventUpload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShareEventControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_share_event_page(): void
    {
        $event = Event::factory()->create();

        $response = $this->get(route('shares.event', $event));

        $response->assertOk();
        $response->assertViewIs('pages.shares.event');
    }

    public function test_invoke_displays_share_event_with_upload_query_param(): void
    {
        $event = Event::factory()->create();
        $upload = EventUpload::factory()->for($event)->create();

        $response = $this->get(
            route('shares.event', ['event' => $event, 'event_upload' => $upload->id])
        );

        $response->assertOk();
        $response->assertViewIs('pages.shares.event');
    }
}
