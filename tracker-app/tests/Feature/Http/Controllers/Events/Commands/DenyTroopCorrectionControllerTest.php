<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events\Commands;

use App\Enums\TroopCorrectionStatus;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\BuildsTroopCorrections;
use Tests\TestCase;

class DenyTroopCorrectionControllerTest extends TestCase
{
    use BuildsTroopCorrections;
    use RefreshDatabase;

    public function test_invoke_hands_the_request_to_moderators(): void
    {
        Notification::fake();
        Queue::fake();
        $coordinator = Trooper::factory()->withVerifiedEmail()->create();
        $correction = $this->requestCorrection(Trooper::factory()->create(), $this->completedShift($coordinator)->event);

        $response = $this->actingAs($coordinator)->post(route('events.deny-troop-correction', $correction));

        $response->assertRedirect(route('events.troop-correction', $correction));
        $this->assertSame(TroopCorrectionStatus::AWAITING_MODERATOR, $correction->fresh()->status);
        $this->assertNotNull($correction->fresh()->coordinator_denied_at);
    }

    public function test_invoke_refuses_anyone_but_the_coordinator(): void
    {
        Notification::fake();
        $correction = $this->requestCorrection(
            Trooper::factory()->create(),
            $this->completedShift(Trooper::factory()->create())->event,
        );

        $response = $this->actingAs(Trooper::factory()->withVerifiedEmail()->create())
            ->post(route('events.deny-troop-correction', $correction));

        $response->assertForbidden();
    }
}
