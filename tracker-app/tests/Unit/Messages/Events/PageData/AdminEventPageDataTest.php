<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\PageData;

use App\Messages\Events\PageData\AdminEventPageData;
use App\Models\Event;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminEventPageDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_every_section_by_default(): void
    {
        $actor = Trooper::factory()->asSystemOwner()->create();
        $event = Event::factory()->create();

        $subject = new AdminEventPageData($actor, $event);
        $result = $subject->handle();

        $this->assertSame([
            'event_id', 'name', 'status_label', 'is_active', 'is_draft', 'overview',
            'shifts', 'charity', 'roster', 'uploads', 'mission_review',
        ], array_keys($result));
    }

    public function test_handle_returns_only_the_requested_sections(): void
    {
        $actor = Trooper::factory()->asSystemOwner()->create();
        $event = Event::factory()->create();

        $subject = new AdminEventPageData($actor, $event, [
            AdminEventPageData::SHIFTS,
            AdminEventPageData::ROSTER,
        ]);
        $result = $subject->handle();

        $this->assertSame(['shifts', 'roster'], array_keys($result));
    }
}
