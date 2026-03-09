<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Awards;

use App\Models\Award;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_update_award_page_for_admin(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $award = Award::factory()->create();

        $response = $this->actingAs($trooper)->get(route('admin.awards.update', ['award' => $award->id]));

        $response->assertOk();
        $response->assertViewIs('pages.admin.awards.update');
    }

    public function test_invoke_requires_authentication(): void
    {
        $award = Award::factory()->create();

        $response = $this->get(route('admin.awards.update', ['award' => $award->id]));

        $response->assertRedirect(route('auth.login'));
    }
}
