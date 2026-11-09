<?php

declare(strict_types=1);

namespace Tests\Feature\Account\Pages;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeniedControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_denied_controller_displays_denied_page(): void
    {
        $trooper = Trooper::factory()->asDenied()->create();

        $response = $this->actingAs($trooper)->get(route('account.denied'));

        $response->assertOk();
        $response->assertViewIs('pages.account.denied');
    }

    public function test_denied_controller_redirects_active_trooper(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        $response = $this->actingAs($trooper)->get(route('account.denied'));

        $response->assertRedirect(route('account.index'));
    }

    public function test_denied_controller_redirects_pending_trooper(): void
    {
        $trooper = Trooper::factory()->asPending()->create();

        $response = $this->actingAs($trooper)->get(route('account.denied'));

        $response->assertRedirect(route('account.setup'));
    }
}
