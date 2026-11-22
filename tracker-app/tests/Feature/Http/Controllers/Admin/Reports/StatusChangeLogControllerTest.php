<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Reports;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StatusChangeLogControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_status_change_log_report(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();

        $response = $this->actingAs($trooper)->get(route('admin.reports.status-change-log'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('admin/reports/StatusChangeLog')
            ->has('caption')->has('changes'));
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('admin.reports.status-change-log'));

        $response->assertRedirect(route('auth.login'));
    }
}
