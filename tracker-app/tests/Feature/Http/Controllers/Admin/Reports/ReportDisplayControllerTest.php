<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Reports;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReportDisplayControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_reports_dashboard_for_admin(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();

        $response = $this->actingAs($trooper)->get(route('admin.reports.display'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('admin/reports/Display')
            ->where('days', 30)->has('personnel')->has('leaderboard'));
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('admin.reports.display'));

        $response->assertRedirect(route('auth.login'));
    }
}
