<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Filters;

use App\Models\Award;
use App\Models\Filters\AwardFilter;
use Illuminate\Http\Request;
use Tests\TestCase;

class AwardFilterTest extends TestCase
{
    public function test_apply_adds_organization_constraint(): void
    {
        $request = Request::create('/', 'GET', ['organization_id' => 123]);

        $subject = new AwardFilter($request);

        $query = $subject->apply(Award::query());

        $this->assertStringContainsString('"organization_id" = ?', $query->toBase()->toSql());
        $this->assertSame([123], $query->getBindings());
    }
}