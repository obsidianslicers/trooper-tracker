<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Reports;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportDisplayControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_reports_dashboard_for_admin(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();

        $response = $this->actingAs($trooper)->get(route('admin.reports.display'));

        $response->assertOk();
        $response->assertViewIs('pages.admin.reports.display');
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('admin.reports.display'));

        $response->assertRedirect(route('auth.login'));
    }
}
