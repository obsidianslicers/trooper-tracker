<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Scopes;

use App\Models\AwardTrooper;
use Tests\TestCase;

class HasAwardTrooperScopesTest extends TestCase
{
    public function test_by_trooper_adds_filter_and_sort(): void
    {
        $query = AwardTrooper::query()->byTrooper(55);

        $this->assertStringContainsString('"trooper_id" = ?', $this->sql($query));
        $this->assertStringContainsString('order by "award_date" desc', $this->sql($query));
        $this->assertSame([55], $query->getBindings());
    }
}