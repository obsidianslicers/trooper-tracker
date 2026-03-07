<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Auth;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * Feature tests for the LoginSubmitController.
 *
 * Validates authentication logic, status checks, and redirect behavior.
 * Note: Form validation is tested separately in LoginRequestTest.
 */
class LoginSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_authenticates_active_trooper_with_valid_credentials(): void
    {
        // Arrange
        $trooper = Trooper::factory()
            ->asActive()
            ->withPassword('secret123')
            ->create([Trooper::EMAIL => 'test@example.com']);

        // Act
        $response = $this->post(route('auth.login'), [
            Trooper::EMAIL => 'test@example.com',
            Trooper::PASSWORD => 'secret123',
        ]);

        // Assert
        $response->assertRedirect(route('events.list'));
        $this->assertTrue(Auth::check());
        $this->assertEquals($trooper->id, Auth::id());
    }

    public function test_invoke_fails_with_invalid_password(): void
    {
        // Arrange
        $trooper = Trooper::factory()
            ->asActive()
            ->withPassword('correctpassword')
            ->create([Trooper::EMAIL => 'test@example.com']);

        // Act
        $response = $this->post(route('auth.login'), [
            Trooper::EMAIL => 'test@example.com',
            Trooper::PASSWORD => 'wrongpassword',
        ]);

        // Assert
        $response->assertRedirect();
        $response->assertSessionHasErrors(['email']);
        $this->assertFalse(Auth::check());
    }

    public function test_invoke_rejects_pending_trooper(): void
    {
        // Arrange
        $trooper = Trooper::factory()
            ->asPending()
            ->withPassword('secret123')
            ->create([Trooper::EMAIL => 'pending@example.com']);

        // Act
        $response = $this->post(route('auth.login'), [
            Trooper::EMAIL => 'pending@example.com',
            Trooper::PASSWORD => 'secret123',
        ]);

        // Assert
        $response->assertRedirect();
        $response->assertSessionHasErrors(['email']);
        $response->assertSessionHas('flash_messages');
        $this->assertFalse(Auth::check());
    }

    public function test_invoke_rejects_retired_trooper(): void
    {
        // Arrange
        $trooper = Trooper::factory()
            ->asRetired()
            ->withPassword('secret123')
            ->create([Trooper::EMAIL => 'retired@example.com']);

        // Act
        $response = $this->post(route('auth.login'), [
            Trooper::EMAIL => 'retired@example.com',
            Trooper::PASSWORD => 'secret123',
        ]);

        // Assert
        $response->assertRedirect();
        $response->assertSessionHasErrors(['email']);
        $response->assertSessionHas('flash_messages');
        $this->assertFalse(Auth::check());
    }

    public function test_invoke_remembers_trooper_when_remember_me_checked(): void
    {
        // Arrange
        $trooper = Trooper::factory()
            ->asActive()
            ->withPassword('secret123')
            ->create([Trooper::EMAIL => 'test@example.com']);

        // Act
        $response = $this->post(route('auth.login'), [
            Trooper::EMAIL => 'test@example.com',
            Trooper::PASSWORD => 'secret123',
            'remember_me' => 'Y',
        ]);

        // Assert
        $response->assertRedirect(route('events.list'));
        $this->assertTrue(Auth::check());
        $response->assertCookie(Auth::guard()->getRecallerName());
    }

    public function test_invoke_redirects_to_intended_url_after_login(): void
    {
        // Arrange
        $trooper = Trooper::factory()
            ->asActive()
            ->withPassword('secret123')
            ->create([Trooper::EMAIL => 'test@example.com']);

        // Simulate trying to access a protected route, which sets the intended URL
        $this->get(route('events.list'))
            ->assertRedirect(); // Should redirect because not authenticated

        // Act - Now login
        $response = $this->post(route('auth.login'), [
            Trooper::EMAIL => 'test@example.com',
            Trooper::PASSWORD => 'secret123',
        ]);

        // Assert - Should redirect to events.list (either as intended or fallback)
        $response->assertRedirect(route('events.list'));
        $this->assertTrue(Auth::check());
    }

    public function test_invoke_blocks_email_password_login_when_xenforo_is_required(): void
    {
        // Arrange
        config()->set('tracker.auth.require_xenforo', true);

        $trooper = Trooper::factory()
            ->asActive()
            ->withPassword('secret123')
            ->create([Trooper::EMAIL => 'test@example.com']);

        // Act
        $response = $this->post(route('auth.login'), [
            Trooper::EMAIL => 'test@example.com',
            Trooper::PASSWORD => 'secret123',
        ]);

        // Assert
        $response->assertRedirect(route('auth.login'));
        $response->assertSessionHasErrors(['oauth']);
        $this->assertFalse(Auth::check());
    }
}
