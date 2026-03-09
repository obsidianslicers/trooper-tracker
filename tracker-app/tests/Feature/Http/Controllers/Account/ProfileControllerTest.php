<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Account;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_profile_page_for_authenticated_trooper(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        $response = $this->actingAs($trooper)->get(route('account.profile'));

        $response->assertOk();
        $response->assertViewIs('pages.account.profile');
        $response->assertViewHas('trooper', $trooper);
    }

    public function test_invoke_redirects_guest_to_login(): void
    {
        $response = $this->get(route('account.profile'));

        $response->assertRedirect(route('auth.login'));
    }
}
