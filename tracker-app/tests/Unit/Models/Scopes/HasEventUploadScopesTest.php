<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Scopes;

use App\Models\EventUpload;
use Tests\TestCase;

class HasEventUploadScopesTest extends TestCase
{
    public function test_by_event_adds_event_filter(): void
    {
        $query = EventUpload::query()->byEvent(9);

        $this->assertStringContainsString('"event_id" = ?', $query->toBase()->toSql());
        $this->assertSame([9], $query->getBindings());
    }

    public function test_by_trooper_adds_where_has_clause(): void
    {
        $query = EventUpload::query()->byTrooper(12);

        $this->assertStringContainsString('exists', $query->toBase()->toSql());
        $this->assertContains(12, $query->getBindings());
    }
}