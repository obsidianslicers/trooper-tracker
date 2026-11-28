<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events\Commands;

use App\Enums\EventTrooperStatus;
use App\Enums\TroopCorrectionStatus;
use App\Models\EventTrooper;
use App\Models\TroopCorrection;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\BuildsTroopCorrections;
use Tests\TestCase;

class RequestTroopCorrectionControllerTest extends TestCase
{
    use BuildsTroopCorrections;
    use RefreshDatabase;

    public function test_invoke_records_the_request_and_goes_to_the_event(): void
    {
        Notification::fake();
        $coordinator = Trooper::factory()->create();
        $shift = $this->completedShift($coordinator);
        $trooper = Trooper::factory()->withVerifiedEmail()->create();

        $response = $this->actingAs($trooper)->post(route('events.request-troop-correction'), [
            'event_id' => $shift->event_id,
            'details' => 'I was the Vader at the front of the parade.',
            'proof_url' => 'https://example.com/photos/parade',
        ]);

        $response->assertRedirect(route('events.display', $shift->event_id));

        $correction = TroopCorrection::sole();
        $this->assertSame($trooper->id, $correction->trooper_id);
        $this->assertSame(TroopCorrectionStatus::AWAITING_COORDINATOR, $correction->status);
        $this->assertSame('https://example.com/photos/parade', $correction->proof_url);
        $this->assertSame(EventTrooperStatus::AWAITING_VERIFICATION, $correction->event_trooper->status);
    }

    public function test_invoke_requires_details(): void
    {
        $shift = $this->completedShift();
        $trooper = Trooper::factory()->withVerifiedEmail()->create();

        $response = $this->actingAs($trooper)->post(route('events.request-troop-correction'), [
            'event_id' => $shift->event_id,
            'details' => '',
        ]);

        $response->assertSessionHasErrors('details');
        $this->assertSame(0, TroopCorrection::count());
    }

    public function test_invoke_refuses_an_event_the_trooper_has_credit_for(): void
    {
        $shift = $this->completedShift();
        $trooper = Trooper::factory()->withVerifiedEmail()->create();
        EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->asAttended()->create();

        $response = $this->actingAs($trooper)->post(route('events.request-troop-correction'), [
            'event_id' => $shift->event_id,
            'details' => 'I was there, honest.',
        ]);

        $response->assertSessionHasErrors('event_id');
    }

    public function test_invoke_refuses_a_costume_that_is_not_theirs(): void
    {
        $shift = $this->completedShift();
        $trooper = Trooper::factory()->withVerifiedEmail()->create();

        $response = $this->actingAs($trooper)->post(route('events.request-troop-correction'), [
            'event_id' => $shift->event_id,
            'costume_id' => 999999,
            'details' => 'I was there, honest.',
        ]);

        $response->assertSessionHasErrors('costume_id');
    }

    public function test_invoke_refuses_a_shift_of_another_event(): void
    {
        $shift = $this->completedShift();
        $other = $this->completedShift(name: 'Other Troop');
        $trooper = Trooper::factory()->withVerifiedEmail()->create();

        $response = $this->actingAs($trooper)->post(route('events.request-troop-correction'), [
            'event_id' => $shift->event_id,
            'event_shift_id' => $other->id,
            'details' => 'I was there, honest.',
        ]);

        $response->assertSessionHasErrors('event_shift_id');
    }
}
