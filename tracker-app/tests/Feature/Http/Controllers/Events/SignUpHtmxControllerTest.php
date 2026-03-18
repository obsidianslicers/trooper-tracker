<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events;

use App\Models\Event;
use App\Models\EventShift;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SignUpHtmxControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_signs_up_trooper_and_returns_shift_partial(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $organization = Organization::factory()->create();

        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->asMember()->create();

        $event = Event::factory()->withOrganization($organization)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();

        $response = $this->actingAs($trooper)->post(route('events.signup-htmx', ['event_shift' => $event_shift->id]));

        $response->assertOk();
    }

    public function test_invoke_requires_authentication(): void
    {
        $organization = Organization::factory()->create();
        $event = Event::factory()->withOrganization($organization)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();

        $response = $this->post(route('events.signup-htmx', ['event_shift' => $event_shift->id]));

        $response->assertRedirect(route('auth.login'));
    }
}
