<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events\Commands;

use App\Enums\TroopCorrectionStatus;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\BuildsTroopCorrections;
use Tests\TestCase;

class ConfirmTroopCorrectionControllerTest extends TestCase
{
    use BuildsTroopCorrections;
    use RefreshDatabase;

    public function test_invoke_lets_the_coordinator_confirm(): void
    {
        Notification::fake();
        $coordinator = Trooper::factory()->withVerifiedEmail()->create();
        $correction = $this->requestCorrection(Trooper::factory()->create(), $this->completedShift($coordinator)->event);

        $response = $this->actingAs($coordinator)->post(route('events.confirm-troop-correction', $correction));

        $response->assertRedirect(route('events.troop-correction', $correction));
        $this->assertSame(TroopCorrectionStatus::APPROVED, $correction->fresh()->status);
    }

    public function test_invoke_refuses_anyone_but_the_coordinator(): void
    {
        Notification::fake();
        $trooper = Trooper::factory()->withVerifiedEmail()->create();
        $correction = $this->requestCorrection($trooper, $this->completedShift(Trooper::factory()->create())->event);

        $response = $this->actingAs($trooper)->post(route('events.confirm-troop-correction', $correction));

        $response->assertForbidden();
        $this->assertSame(TroopCorrectionStatus::AWAITING_COORDINATOR, $correction->fresh()->status);
    }
}
