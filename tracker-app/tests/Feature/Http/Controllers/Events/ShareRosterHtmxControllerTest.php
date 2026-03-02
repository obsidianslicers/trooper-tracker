<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events;

use App\Models\Event;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShareRosterHtmxControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        // Arrange
        $event = Event::withoutEvents(fn() => Event::factory()->create());

        // Act
        $response = $this->post(route('events.share-roster-htmx', $event), [
            'recipient_email' => 'coordinator@example.com',
        ]);

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_forbids_non_moderator(): void
    {
        // Arrange
        $event = Event::withoutEvents(fn() => Event::factory()->create());
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)->post(route('events.share-roster-htmx', $event), [
            'recipient_email' => 'coordinator@example.com',
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_returns_view_with_can_moderate_true_on_success(): void
    {
        // Arrange
        $event = Event::withoutEvents(fn() => Event::factory()->create());
        $trooper = Trooper::factory()->asAdministrator()->create();

        // Act
        $response = $this->actingAs($trooper)->post(route('events.share-roster-htmx', $event), [
            'recipient_email' => 'coordinator@example.com',
        ]);

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.events.inc.share-roster');
        $response->assertViewHas('can_moderate', true);
        $response->assertViewHas('message', 'Roster successfully sent.');
        $response->assertViewHas('errors', function ($errors): bool
        {
            return $errors->isEmpty();
        });
    }

    public function test_invoke_returns_validation_error_message_with_can_moderate_true(): void
    {
        // Arrange
        $event = Event::withoutEvents(fn() => Event::factory()->create());
        $trooper = Trooper::factory()->asAdministrator()->create();

        // Act
        $response = $this->actingAs($trooper)->post(
            route('events.share-roster-htmx', $event),
            ['recipient_email' => 'not-an-email'],
            ['HX-Request' => 'true']
        );

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.events.inc.share-roster');
        $response->assertViewHas('can_moderate', true);
        $response->assertViewHas('message', function (string $message): bool
        {
            return str_contains($message, 'Failed to share roster:')
                && str_contains($message, 'valid email address');
        });
        $response->assertViewHas('errors', function ($errors): bool
        {
            return $errors->has('recipient_email');
        });
    }
}
