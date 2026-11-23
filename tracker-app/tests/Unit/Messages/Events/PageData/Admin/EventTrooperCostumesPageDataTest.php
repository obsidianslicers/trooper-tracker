<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\PageData\Admin;

use App\Messages\Events\PageData\Admin\EventTrooperCostumesPageData;
use App\Messages\Events\Resources\AddEventTrooperChoices;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\Trooper;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class EventTrooperCostumesPageDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_the_choices_for_an_active_trooper(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();

        $subject = new EventTrooperCostumesPageData($event, $event_shift, $trooper->id);
        $result = $subject->handle();

        $this->assertInstanceOf(AddEventTrooperChoices::class, $result);
        $this->assertSame($trooper->id, $result->resolve(new Request)['trooper_id']);
    }

    public function test_handle_refuses_a_trooper_who_is_not_active(): void
    {
        $trooper = Trooper::factory()->asPending()->create();
        $event = Event::factory()->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();

        $this->expectException(ModelNotFoundException::class);

        $subject = new EventTrooperCostumesPageData($event, $event_shift, $trooper->id);
        $subject->handle();
    }
}
