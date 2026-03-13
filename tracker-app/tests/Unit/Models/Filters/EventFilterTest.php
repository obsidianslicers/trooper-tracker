<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Filters;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Filters\EventFilter;
use Illuminate\Http\Request;
use Tests\TestCase;

class EventFilterTest extends TestCase
{
    public function test_apply_adds_status_constraint(): void
    {
        $request = Request::create('/', 'GET', ['status' => EventStatus::OPEN->value]);

        $subject = new EventFilter($request);

        $query = $subject->apply(Event::query());

        $this->assertStringContainsString('"status" = ?', $query->toBase()->toSql());
        $this->assertSame([EventStatus::OPEN->value], $query->getBindings());
    }

    public function test_short_search_term_does_not_modify_query(): void
    {
        $request = Request::create('/', 'GET', ['search_term' => 'ab']);

        $subject = new EventFilter($request);

        $base = Event::query()->toBase()->toSql();
        $filtered = $subject->apply(Event::query())->toBase()->toSql();

        $this->assertSame($base, $filtered);
    }
}