<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Filters;

use App\Models\Filters\NoticeFilter;
use App\Models\Notice;
use Illuminate\Http\Request;
use Tests\TestCase;

class NoticeFilterTest extends TestCase
{
    public function test_defaults_apply_active_scope_when_scope_missing(): void
    {
        $request = Request::create('/', 'GET');

        $subject = new NoticeFilter($request);

        $query = $subject->apply(Notice::query());

        $this->assertStringContainsString('"starts_at" <= ?', $query->toBase()->toSql());
    }

    public function test_apply_adds_organization_constraint_when_provided(): void
    {
        $request = Request::create('/', 'GET', ['organization_id' => 44]);

        $subject = new NoticeFilter($request);

        $query = $subject->apply(Notice::query());

        $this->assertStringContainsString('"organization_id" = ?', $query->toBase()->toSql());
        $this->assertContains(44, $query->getBindings());
    }
}