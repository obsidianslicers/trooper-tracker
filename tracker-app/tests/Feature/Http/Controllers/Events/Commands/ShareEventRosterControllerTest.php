<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events\Commands;

use App\Models\Event;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShareEventRosterControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_forbidden_for_non_moderator_trooper(): void
    {
        $trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($trooper)->post(route('events.share-roster', ['event' => $event->id]), [
            'recipient_email' => 'recipient@example.com',
        ]);

        $response->assertForbidden();
    }

    public function test_invoke_requires_authentication(): void
    {
        $event = Event::factory()->create();

        $response = $this->post(route('events.share-roster', ['event' => $event->id]), [
            'recipient_email' => 'recipient@example.com',
        ]);

        $response->assertRedirect(route('auth.login'));
    }
}
