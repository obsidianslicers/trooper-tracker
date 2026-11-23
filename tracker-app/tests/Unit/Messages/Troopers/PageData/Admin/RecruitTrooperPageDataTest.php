<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\PageData\Admin;

use App\Messages\Troopers\PageData\Admin\RecruitTrooperPageData;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecruitTrooperPageDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_names_each_units_identifier_from_its_club(): void
    {
        $club = Organization::factory()->asOrganization()->create([
            Organization::IDENTIFIER_DISPLAY => 'TKID',
            Organization::IDENTIFIER_VALIDATION => 'required|integer',
        ]);
        $club->update([Organization::NODE_PATH => "{$club->id}:"]);
        $unit = Organization::factory()->asUnit()->withParent($club)->create([
            Organization::NODE_PATH => "{$club->id}:999:",
        ]);
        $other = Organization::factory()->asOrganization()->create();
        $other->update([Organization::NODE_PATH => "{$other->id}:"]);

        $result = json_decode(json_encode(
            (new RecruitTrooperPageData(Trooper::factory()->asSystemOwner()->create()))->handle()
        ), true);

        $this->assertSame(['label' => 'TKID', 'is_required' => true], $result['identifiers'][$unit->id]);
        $this->assertSame(['label' => 'Member ID', 'is_required' => false], $result['identifiers'][$other->id]);
        $this->assertContains($unit->id, array_column($result['organization_options'], 'value'));
    }
}
