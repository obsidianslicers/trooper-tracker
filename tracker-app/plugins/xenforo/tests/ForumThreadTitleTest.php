<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\Tests;

use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;
use TroopTracker\Plugins\Xenforo\Support\ForumThreadTitle;

class ForumThreadTitleTest extends TestCase
{
    use RefreshDatabase;
    use UsesXenforoPlugin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->enableXenforo();
    }

    public function test_title_is_the_event_name_by_default(): void
    {
        $event = $this->event('Hospital Visit', '2026-03-14 10:00:00');

        $this->assertSame('Hospital Visit', ForumThreadTitle::for($event));
    }

    public function test_title_starts_with_the_date_when_the_setting_is_on(): void
    {
        config(['plugins.xenforo.date_in_thread_titles' => true]);

        $event = $this->event('Hospital Visit', '2026-03-14 10:00:00');

        $this->assertSame('2026/03/14 Sat - Hospital Visit', ForumThreadTitle::for($event));
    }

    public function test_dated_titles_sort_in_date_order(): void
    {
        config(['plugins.xenforo.date_in_thread_titles' => true]);

        $titles = [
            ForumThreadTitle::for($this->event('Zoo Day', '2027-01-09 10:00:00')),
            ForumThreadTitle::for($this->event('Parade', '2026-12-05 10:00:00')),
            ForumThreadTitle::for($this->event('Bake Sale', '2026-11-21 10:00:00')),
        ];

        sort($titles);

        $this->assertSame([
            '2026/11/21 Sat - Bake Sale',
            '2026/12/05 Sat - Parade',
            '2027/01/09 Sat - Zoo Day',
        ], $titles);
    }

    private function event(string $name, string $starts_at): Event
    {
        $start = Carbon::parse($starts_at);

        return Event::factory()->create([
            Event::NAME => $name,
            Event::EVENT_START => $start,
            Event::EVENT_END => $start->copy()->addHours(2),
        ]);
    }
}
