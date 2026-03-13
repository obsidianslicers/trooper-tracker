<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Troopers;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalListControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_troopers_pending_approval(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        Trooper::factory()->asPending()->create();

        $response = $this->actingAs($trooper)->get(route('admin.troopers.approvals'));

        $response->assertOk();
        $response->assertViewIs('pages.admin.troopers.approvals');
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('admin.troopers.approvals'));

        $response->assertRedirect(route('auth.login'));
    }
}
