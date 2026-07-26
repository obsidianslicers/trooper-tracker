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

    public function test_has_multi_word_search_term_requires_two_or_more_words(): void
    {
        $single = new TrooperFilter(Request::create('/', 'GET', ['search_term' => 'Matthew']));
        $multi = new TrooperFilter(Request::create('/', 'GET', ['search_term' => 'Matthew Drennan']));
        $none = new TrooperFilter(Request::create('/', 'GET'));

        $this->assertFalse($single->hasMultiWordSearchTerm());
        $this->assertTrue($multi->hasMultiWordSearchTerm());
        $this->assertFalse($none->hasMultiWordSearchTerm());
    }

    public function test_use_loose_search_applies_any_token_matching(): void
    {
        $request = Request::create('/', 'GET', ['search_term' => 'matthew drennan']);
        $subject = (new TrooperFilter($request))->useLooseSearch();

        $query = $subject->apply(Trooper::query());

        $this->assertSame(
            [
                '%matthew%', '%matthew%', '%matthew%', '%matthew%',
                '%drennan%', '%drennan%', '%drennan%', '%drennan%',
            ],
            $query->getBindings()
        );
    }
}
