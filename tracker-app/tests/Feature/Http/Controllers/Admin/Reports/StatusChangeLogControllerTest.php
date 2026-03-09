<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Reports;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatusChangeLogControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_status_change_log_report(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();

        $response = $this->actingAs($trooper)->get(route('admin.reports.status-change-log'));

        $response->assertOk();
        $response->assertViewIs('pages.admin.reports.status-change-log');
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('admin.reports.status-change-log'));

        $response->assertRedirect(route('auth.login'));
    }
}
