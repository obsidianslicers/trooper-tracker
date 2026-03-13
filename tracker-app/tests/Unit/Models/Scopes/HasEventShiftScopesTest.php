<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Scopes;

use App\Enums\EventStatus;
use App\Models\EventShift;
use Tests\TestCase;

class HasEventShiftScopesTest extends TestCase
{
    public function test_active_scope_filters_to_open_draft_and_locked_statuses(): void
    {
        $query = EventShift::query()->active();

        $this->assertStringContainsString('"status" in (?, ?, ?)', $query->toBase()->toSql());
        $this->assertSame([
            EventStatus::OPEN->value,
            EventStatus::DRAFT->value,
            EventStatus::SIGN_UP_LOCKED->value,
        ], $query->getBindings());
    }

    public function test_by_trooper_adds_status_and_participation_filters(): void
    {
        $query = EventShift::query()->byTrooper(7, false);

        $this->assertStringContainsString('"status" = ?', $query->toBase()->toSql());
        $this->assertStringContainsString('exists', $query->toBase()->toSql());
        $this->assertContains(EventStatus::OPEN->value, $query->getBindings());
        $this->assertContains(7, $query->getBindings());
    }
}