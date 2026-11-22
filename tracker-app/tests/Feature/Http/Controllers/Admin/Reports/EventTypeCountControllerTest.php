<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Reports;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EventTypeCountControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_event_type_count_report(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();

        $response = $this->actingAs($trooper)->get(route('admin.reports.event-type-count'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('admin/reports/EventTypeCount')
            ->where('days', 30)->has('event_types')->has('totals'));
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('admin.reports.event-type-count'));

        $response->assertRedirect(route('auth.login'));
    }
}
