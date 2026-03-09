<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Auth;

use App\Features\Organizations\Queries\GetOrganizationHierarchyQuery;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class RegisterControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_registration_page_when_registration_session_exists(): void
    {
        session(['registration_auth' => ['method' => 'email', 'email' => null, 'expires_at' => now()->addMinutes(20)]]);

        $organization = Organization::factory()->create();

        $response = $this->get(route('auth.register'));

        $response->assertOk();
        $response->assertViewIs('pages.auth.register');
    }
}
