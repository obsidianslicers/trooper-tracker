<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events\Pages;

use App\Enums\EventTrooperStatus;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsTroopsToConfirm;
use Tests\TestCase;

class ListTroopsToConfirmControllerTest extends TestCase
{
    use BuildsTroopsToConfirm;
    use RefreshDatabase;

    public function test_lists_every_troop_left_to_confirm(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $troop = $this->troopToConfirm($trooper, EventTrooperStatus::TENTATIVE);
        $this->troopToConfirm($trooper, EventTrooperStatus::ATTENDED, event_name: 'Already answered');

        $response = $this->actingAs($trooper)->get(route('events.troops-to-confirm'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('events/TroopsToConfirm')
            ->has('troops', 1)
            ->where('troops.0.id', $troop->id)
            ->where('troops.0.event_name', 'Holiday Parade')
            ->where('troops.0.is_tentative', true)
            ->where('troops.0.shift_label', $troop->event_shift->time_display)
            ->where('troops.0.confirm_url', route('events.confirm-attendance', $troop))
        );
    }

    public function test_is_empty_when_everything_is_answered(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();

        $this->actingAs($trooper)->get(route('events.troops-to-confirm'))
            ->assertInertia(fn (Assert $page) => $page->component('events/TroopsToConfirm')->has('troops', 0));
    }
}
