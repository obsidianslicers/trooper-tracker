<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Reports;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TroopersWithoutActivityControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_troopers_without_activity_report(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();

        $response = $this->actingAs($trooper)->get(route('admin.reports.troopers-without-activity'));

        $response->assertOk();
        $response->assertViewIs('pages.admin.reports.troopers-without-activity');
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('admin.reports.troopers-without-activity'));

        $response->assertRedirect(route('auth.login'));
    }
}
