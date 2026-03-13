<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Scopes;

use App\Enums\EventTrooperStatus;
use App\Models\EventTrooper;
use Tests\TestCase;

class HasEventTrooperScopesTest extends TestCase
{
    public function test_troopers_scope_filters_to_going_non_handlers(): void
    {
        $query = EventTrooper::query()->troopers();

        $this->assertStringContainsString('"status" = ?', $query->toBase()->toSql());
        $this->assertStringContainsString('"is_handler" = ?', $query->toBase()->toSql());
        $this->assertContains(EventTrooperStatus::GOING->value, $query->getBindings());
    }

    public function test_handlers_scope_filters_to_going_handlers(): void
    {
        $query = EventTrooper::query()->handlers();

        $this->assertStringContainsString('"status" = ?', $query->toBase()->toSql());
        $this->assertStringContainsString('"is_handler" = ?', $query->toBase()->toSql());
        $this->assertContains(EventTrooperStatus::GOING->value, $query->getBindings());
    }
}