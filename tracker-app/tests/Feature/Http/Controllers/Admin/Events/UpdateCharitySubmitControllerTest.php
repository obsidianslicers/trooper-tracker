<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

use App\Models\Event;
use App\Models\EventShift;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateCharitySubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_saves_funds_with_cents(): void
    {
        [$trooper, $event, $shift] = $this->eventWithShift();

        $response = $this->actingAs($trooper)->post(route('admin.events.charity', compact('event')), [
            'shifts' => [$shift->id => [
                'charity_direct_funds' => '12.50',
                'charity_indirect_funds' => '$1,234.05',
            ]],
        ]);

        $response->assertRedirect(route('admin.events.charity', compact('event')));
        $response->assertSessionHasNoErrors();
        $shift->refresh();
        $this->assertSame(12.5, $shift->charity_direct_funds);
        $this->assertSame(1234.05, $shift->charity_indirect_funds);
    }

    public function test_invoke_still_saves_whole_dollars_and_blanks(): void
    {
        [$trooper, $event, $shift] = $this->eventWithShift();

        $this->actingAs($trooper)->post(route('admin.events.charity', compact('event')), [
            'shifts' => [$shift->id => [
                'charity_direct_funds' => '500',
                'charity_indirect_funds' => '',
            ]],
        ])->assertSessionHasNoErrors();

        $shift->refresh();
        $this->assertSame(500.0, $shift->charity_direct_funds);
        $this->assertSame(0.0, $shift->charity_indirect_funds);
    }

    public function test_invoke_rejects_fractions_of_a_cent_and_text(): void
    {
        [$trooper, $event, $shift] = $this->eventWithShift();

        $response = $this->actingAs($trooper)->post(route('admin.events.charity', compact('event')), [
            'shifts' => [$shift->id => [
                'charity_direct_funds' => '12.345',
                'charity_indirect_funds' => 'ten',
            ]],
        ]);

        $response->assertSessionHasErrors([
            'shifts.'.$shift->id.'.charity_direct_funds' => 'Enter an amount in dollars and cents, like 12.50.',
            'shifts.'.$shift->id.'.charity_indirect_funds' => 'Enter an amount in dollars and cents, like 12.50.',
        ]);
        $this->assertSame(0.0, $shift->refresh()->charity_direct_funds);
    }

    public function test_charity_page_and_event_page_show_cents_only_when_there_are_some(): void
    {
        [$trooper, $event, $shift] = $this->eventWithShift();
        $shift->update([
            EventShift::CHARITY_DIRECT_FUNDS => 1234.5,
            EventShift::CHARITY_INDIRECT_FUNDS => 300,
        ]);

        $this->actingAs($trooper)->get(route('admin.events.charity', compact('event')))
            ->assertOk()
            ->assertSee('value="1234.50"', false)
            ->assertSee('value="300"', false);

        $this->actingAs($trooper)->get(route('events.display', compact('event')))
            ->assertOk()
            ->assertSee('Direct Funds: $1,234.50')
            ->assertSee('Indirect Funds: $300');
    }

    /**
     * @return array{Trooper, Event, EventShift}
     */
    private function eventWithShift(): array
    {
        $trooper = Trooper::factory()->asSystemOwner()->asActive()->withVerifiedEmail()->create();
        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();

        return [$trooper, $event, $shift];
    }
}
