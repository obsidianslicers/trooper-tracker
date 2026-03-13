<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Filters;

use App\Models\Filters\QueryFilter;
use App\Models\Trooper;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Illuminate\Database\Eloquent\Builder;
use Tests\TestCase;

class QueryFilterTest extends TestCase
{
    public function test_get_returns_request_value_for_known_filter(): void
    {
        $subject = $this->makeFilter(Request::create('/', 'GET', ['needle' => 'Vader']));

        $this->assertSame('Vader', $subject->needle);
    }

    public function test_get_throws_for_unknown_filter_key(): void
    {
        $subject = $this->makeFilter(Request::create('/', 'GET'));

        $this->expectException(InvalidArgumentException::class);

        /** @phpstan-ignore-next-line */
        $subject->unknown;
    }

    public function test_has_filter_returns_true_when_request_has_known_filter(): void
    {
        $subject = $this->makeFilter(Request::create('/', 'GET', ['needle' => 'solo']));

        $this->assertTrue($subject->hasFilter());
    }

    public function test_apply_uses_default_values_when_filter_not_present(): void
    {
        $subject = $this->makeFilter(Request::create('/', 'GET'));

        $query = $subject->apply(Trooper::query());

        $this->assertStringContainsString('where "display_name" like ?', $query->toBase()->toSql());
        $this->assertSame(['%trooper%'], $query->getBindings());
    }

    private function makeFilter(Request $request): QueryFilter
    {
        return new class ($request) extends QueryFilter
        {
            protected function filters(): array
            {
                return [
                'needle' => 'needle',
                ];
            }

            protected function defaults(): array
            {
                return [
                'needle' => 'trooper',
                ];
            }

            protected function needle(Builder $query, string $value): Builder
            {
                return $query->where(Trooper::DISPLAY_NAME, 'like', '%' . $value . '%');
            }
        };
    }
}