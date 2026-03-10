<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Filters;

use App\Enums\MembershipRole;
use App\Models\Filters\TrooperFilter;
use App\Models\Trooper;
use Illuminate\Http\Request;
use Tests\TestCase;

class TrooperFilterTest extends TestCase
{
    public function test_apply_adds_membership_role_constraint(): void
    {
        $request = Request::create('/', 'GET', ['membership_role' => MembershipRole::MODERATOR->value]);

        $subject = new TrooperFilter($request);

        $query = $subject->apply(Trooper::query());

        $this->assertStringContainsString('"membership_role" = ?', $query->toBase()->toSql());
        $this->assertSame([MembershipRole::MODERATOR->value], $query->getBindings());
    }

    public function test_short_search_term_does_not_modify_query(): void
    {
        $request = Request::create('/', 'GET', ['search_term' => 'ab']);

        $subject = new TrooperFilter($request);

        $base = Trooper::query()->toBase()->toSql();
        $filtered = $subject->apply(Trooper::query())->toBase()->toSql();

        $this->assertSame($base, $filtered);
    }
}