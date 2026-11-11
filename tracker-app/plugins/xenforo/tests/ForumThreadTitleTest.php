<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\Tests;

use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public function test_title_starts_with_the_date(): void
    {
        $event = $this->event('Hospital Visit', '2026-03-14 10:00:00');

        $this->assertSame('2026/03/14 Sat - Hospital Visit', ForumThreadTitle::for($event));
    }

    public function test_titles_sort_in_date_order(): void
    {
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

    #[DataProvider('names_starting_with_the_events_date')]
    public function test_a_name_that_starts_with_the_events_date_is_used_as_it_is(string $name): void
    {
        $event = $this->event($name, '2026-11-14 10:00:00');

        $this->assertSame($name, ForumThreadTitle::for($event));
    }

    public static function names_starting_with_the_events_date(): array
    {
        return [
            ['11/14 Parade'],
            ['11/14/26 Parade'],
            ['11/14/2026 - Parade'],
            ['11-14 Parade'],
            ['2026-11-14 Parade'],
            ['2026/11/14 Sat - Parade'],
            ['Nov 14 - Parade'],
            ['Nov. 14th Parade'],
            ['November 14, 2026 Parade'],
            ['Sat, Nov 14 - Parade'],
            ['Saturday 11/14 Parade'],
        ];
    }

    #[DataProvider('names_not_starting_with_the_events_date')]
    public function test_a_name_that_does_not_start_with_the_events_date_gets_the_date(string $name): void
    {
        $event = $this->event($name, '2026-11-14 10:00:00');

        $this->assertSame('2026/11/14 Sat - '.$name, ForumThreadTitle::for($event));
    }

    public static function names_not_starting_with_the_events_date(): array
    {
        return [
            'another day' => ['11/7 Parade'],
            'another year' => ['11/14/25 Parade'],
            'a holiday named for a date' => ['May 4th Celebration'],
            'a number' => ['501st Day'],
            'a count' => ['12 Days of Christmas'],
            'a word starting like a weekday' => ['Wedding Reception'],
            'a weekday without a date' => ['Sat C2E2'],
        ];
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
