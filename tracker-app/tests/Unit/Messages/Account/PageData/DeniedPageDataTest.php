<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Account\PageData;

use App\Enums\OrganizationType;
use App\Messages\Account\PageData\DeniedPageData;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeniedPageDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_the_reason_requests_and_clubs_to_pick(): void
    {
        $trooper = Trooper::factory()->asDenied()->create();

        $result = (new DeniedPageData($trooper))->handle();

        $this->assertSame(
            ['denial_reason', 'denied_requests', 'organizations', 'account_type'],
            array_keys($result)
        );
        $this->assertSame([], $result['denied_requests']);
        $this->assertSame('member', $result['account_type']);
    }

    public function test_handle_fills_a_denied_unit_request_back_into_its_club(): void
    {
        $trooper = Trooper::factory()->asDenied()->create();
        $club = Organization::factory()->create([Organization::NAME => 'E2E Legion']);
        $region = Organization::factory()->create([
            Organization::NAME => 'E2E Garrison',
            Organization::PARENT_ID => $club->id,
            Organization::TYPE => OrganizationType::REGION,
        ]);
        $unit = Organization::factory()->create([
            Organization::NAME => 'E2E Squad',
            Organization::PARENT_ID => $region->id,
            Organization::TYPE => OrganizationType::UNIT,
        ]);
        TrooperRequest::factory()
            ->forTrooper($trooper)
            ->asDenied(reason: 'No photos.')
            ->create([
                TrooperRequest::ORGANIZATION_ID => $unit->id,
                TrooperRequest::PRIMARY_ORGANIZATION_ID => $club->id,
                TrooperRequest::IDENTIFIER => '1234',
            ]);

        $result = (new DeniedPageData($trooper))->handle();
        $request = $result['denied_requests'][0];

        $this->assertSame('No photos.', $result['denial_reason']);
        $this->assertSame($club->id, $request['organization_id']);
        $this->assertSame('1234', $request['identifier']);
        $this->assertSame($region->id, $request['region_id']);
        $this->assertSame($unit->id, $request['unit_id']);
        $this->assertStringContainsString('E2E Squad', (string) $request['club']);
    }
}
