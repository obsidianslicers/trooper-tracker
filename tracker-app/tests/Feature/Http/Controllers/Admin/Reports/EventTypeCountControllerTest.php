<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Reports;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventTypeCountControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_event_type_count_report(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();

        $response = $this->actingAs($trooper)->get(route('admin.reports.event-type-count'));

        $response->assertOk();
        $response->assertViewIs('pages.admin.reports.event-type-count');
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('admin.reports.event-type-count'));

        $response->assertRedirect(route('auth.login'));
    }
}
