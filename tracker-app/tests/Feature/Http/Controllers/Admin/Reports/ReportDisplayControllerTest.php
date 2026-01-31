<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Reports;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportDisplayControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_moderator_or_administrator(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        $response = $this->actingAs($trooper)->get(route('admin.reports.display'));

        $response->assertForbidden();
    }

    public function test_invoke_displays_reports_menu_view(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();

        $response = $this->actingAs($moderator)->get(route('admin.reports.display'));

        $response->assertOk();
        $response->assertViewIs('pages.admin.reports.display');
    }

    public function test_invoke_administrator_can_access(): void
    {
        $administrator = Trooper::factory()->asAdministrator()->create();

        $response = $this->actingAs($administrator)->get(route('admin.reports.display'));

        $response->assertOk();
    }
}
