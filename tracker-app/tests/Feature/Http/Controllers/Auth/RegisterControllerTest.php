<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Auth;

use App\Enums\MembershipRole;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RegisterControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_registration_page_with_page_data(): void
    {
        session(['registration_auth' => [
            'method' => 'email',
            'email' => 'alpha@example.com',
            'expires_at' => now()->addMinutes(20),
        ]]);

        $organization = Organization::factory()
            ->asOrganization()
            ->withName('Alpha Organization')
            ->withIdentifierDisplay('AO')
            ->create();

        $region = Organization::factory()
            ->asRegion()
            ->withParent($organization)
            ->withName('Alpha Region')
            ->create();

        Organization::factory()
            ->asUnit()
            ->withParent($region)
            ->withName('Alpha Unit')
            ->create();

        $response = $this->get(route('auth.register'));

        $response->assertOk();
        $response->assertViewIs('layouts.inertia');
        $response->assertInertia(fn(Assert $page) => $page
            ->component('auth/Register')
            ->where('oauth.session.method', 'email')
            ->where('oauth.session.email', 'alpha@example.com')
            ->has('oauth.xenforo')
            ->has('oauth.google')
            ->has('oauth.email_password')
            ->has('organizations', 1)
            ->where('organizations.0.name', 'Alpha Organization')
            ->where('organizations.0.identifier_display', 'AO')
            ->where('organizations.0.requires_guardian', false)
            ->has('organizations.0.regions', 1)
            ->where('organizations.0.regions.0.name', 'Alpha Region')
            ->has('organizations.0.regions.0.units', 1)
            ->where('organizations.0.regions.0.units.0.name', 'Alpha Unit')
            ->where('membership_roles', MembershipRole::toValueLabels([
                MembershipRole::MODERATOR,
                MembershipRole::ADMINISTRATOR,
            ]))
        );
    }
}
