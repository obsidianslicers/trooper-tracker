<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Auth;

use App\Enums\MembershipStatus;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_authenticates_active_trooper_with_valid_credentials(): void
    {
        $trooper = Trooper::factory()
            ->asActive()
            ->withPassword('password123')
            ->create();

        $response = $this->post(route('auth.login'), [
            'email' => $trooper->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('events.list'));
        $this->assertAuthenticatedAs($trooper);
    }

    public function test_invoke_rejects_pending_trooper(): void
    {
        $trooper = Trooper::factory()
            ->asPending()
            ->withPassword('password123')
            ->create();

        $response = $this->post(route('auth.login'), [
            'email' => $trooper->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    public function test_invoke_rejects_retired_trooper(): void
    {
        $trooper = Trooper::factory()
            ->asRetired()
            ->withPassword('password123')
            ->create();

        $response = $this->post(route('auth.login'), [
            'email' => $trooper->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    public function test_invoke_rejects_invalid_password(): void
    {
        $trooper = Trooper::factory()
            ->asActive()
            ->withPassword('correctpassword')
            ->create();

        $response = $this->post(route('auth.login'), [
            'email' => $trooper->email,
            'password' => 'wrongpassword',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    public function test_invoke_supports_remember_me_functionality(): void
    {
        $trooper = Trooper::factory()
            ->asActive()
            ->withPassword('password123')
            ->create();

        $response = $this->post(route('auth.login'), [
            'email' => $trooper->email,
            'password' => 'password123',
            'remember_me' => true,
        ]);

        $response->assertRedirect(route('events.list'));
        $this->assertAuthenticatedAs($trooper);
    }

    public function test_invoke_redirects_when_xenforo_oauth_required(): void
    {
        config(['tracker.xenforo_oauth_required' => true]);

        $trooper = Trooper::factory()
            ->asActive()
            ->withPassword('password123')
            ->create();

        $response = $this->post(route('auth.login'), [
            'email' => $trooper->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect();
    }

    public function test_invoke_logs_in_denied_trooper_with_valid_credentials_and_redirects_to_denied(): void
    {
        $trooper = Trooper::factory()
            ->asDenied()
            ->withPassword('password123')
            ->create();

        $response = $this->post(route('auth.login'), [
            'email' => $trooper->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('account.denied'));
        $this->assertAuthenticatedAs($trooper);
    }

    public function test_invoke_rejects_denied_trooper_with_invalid_password(): void
    {
        $trooper = Trooper::factory()
            ->asDenied()
            ->withPassword('correctpassword')
            ->create();

        $response = $this->post(route('auth.login'), [
            'email' => $trooper->email,
            'password' => 'wrongpassword',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }
}
