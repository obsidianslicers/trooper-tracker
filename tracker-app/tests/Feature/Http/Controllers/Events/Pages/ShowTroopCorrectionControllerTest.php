<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events\Pages;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsTroopCorrections;
use Tests\TestCase;

class ShowTroopCorrectionControllerTest extends TestCase
{
    use BuildsTroopCorrections;
    use RefreshDatabase;

    public function test_invoke_lets_the_coordinator_answer(): void
    {
        Notification::fake();
        $coordinator = Trooper::factory()->withVerifiedEmail()->create();
        $trooper = Trooper::factory()->withDisplayName('TK-1138')->create();
        $correction = $this->requestCorrection($trooper, $this->completedShift($coordinator)->event);

        $response = $this->actingAs($coordinator)->get(route('events.troop-correction', $correction));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('events/TroopCorrection')
            ->where('correction.trooper_name', 'TK-1138')
            ->where('correction.details', 'I held the door for the parade.')
            ->where('correction.status', 'awaiting_coordinator')
            ->where('correction.can_answer', true));
    }

    public function test_invoke_shows_the_trooper_their_request_without_the_answer_buttons(): void
    {
        Notification::fake();
        $trooper = Trooper::factory()->withVerifiedEmail()->create();
        $correction = $this->requestCorrection($trooper, $this->completedShift(Trooper::factory()->create())->event);

        $response = $this->actingAs($trooper)->get(route('events.troop-correction', $correction));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page->where('correction.can_answer', false));
    }

    public function test_invoke_refuses_anyone_else(): void
    {
        Notification::fake();
        $correction = $this->requestCorrection(
            Trooper::factory()->create(),
            $this->completedShift(Trooper::factory()->create())->event,
        );

        $response = $this->actingAs(Trooper::factory()->withVerifiedEmail()->create())
            ->get(route('events.troop-correction', $correction));

        $response->assertForbidden();
    }
}
