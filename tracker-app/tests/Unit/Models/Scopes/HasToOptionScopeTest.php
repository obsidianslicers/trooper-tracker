<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Scopes;

use App\Models\OrganizationCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HasToOptionScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_to_options_returns_key_value_array(): void
    {
        $one = OrganizationCostume::factory()->withPrefix('TK')->create();
        $two = OrganizationCostume::factory()->withPrefix('TS')->create();

        $result = OrganizationCostume::query()
            ->whereIn(OrganizationCostume::ID, [$one->id, $two->id])
            ->toOptions(OrganizationCostume::PREFIX, OrganizationCostume::ID);

        $this->assertSame('TK', $result[$one->id]);
        $this->assertSame('TS', $result[$two->id]);
    }
}