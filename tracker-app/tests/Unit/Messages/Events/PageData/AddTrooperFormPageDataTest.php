<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\PageData;

use App\Messages\Events\PageData\AddTrooperFormPageData;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\Trooper;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddTrooperFormPageDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_names_the_trooper_being_added(): void
    {
        $event_shift = EventShift::factory()->forEvent(Event::factory()->create())->create();
        $trooper = Trooper::factory()->asActive()->create(['display_name' => 'TK-421']);

        $subject = new AddTrooperFormPageData(event_shift: $event_shift, trooper_id: $trooper->id);
        $result = $subject->handle();

        $this->assertSame(['id' => $trooper->id, 'name' => 'TK-421'], $result['trooper']);
        $this->assertSame([], $result['stations']->all());
        $this->assertSame([], $result['organizations']->all());
    }

    public function test_handle_refuses_a_trooper_who_is_not_active(): void
    {
        $event_shift = EventShift::factory()->forEvent(Event::factory()->create())->create();

        $this->expectException(ModelNotFoundException::class);

        (new AddTrooperFormPageData(event_shift: $event_shift))->handle();
    }
}
