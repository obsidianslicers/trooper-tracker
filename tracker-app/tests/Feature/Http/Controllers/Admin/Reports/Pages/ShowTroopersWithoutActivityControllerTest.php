<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Reports\Pages;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ShowTroopersWithoutActivityControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_troopers_without_activity_report(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();

        $response = $this->actingAs($trooper)->get(route('admin.reports.troopers-without-activity'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('admin/reports/TroopersWithoutActivity')
            ->has('caption')->has('troopers'));
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('admin.reports.troopers-without-activity'));

        $response->assertRedirect(route('auth.login'));
    }
}
