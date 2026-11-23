<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\PageData\Admin;

use App\Enums\MembershipStatus;
use App\Messages\Troopers\PageData\Admin\ListTroopersPageData;
use App\Models\Filters\TrooperFilter;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ListTroopersPageDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_describes_each_trooper_for_the_list(): void
    {
        $admin = Trooper::factory()->asSystemOwner()->create();
        $departed = Trooper::factory()->create([
            Trooper::EMAIL => '^missing',
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::DEPARTED,
            Trooper::LAST_ACTIVE_AT => now()->subYears(2),
        ]);

        $rows = collect($this->pageData($admin)['troopers']->toArray(Request::create('/')))->keyBy('id');

        $this->assertNull($rows[$departed->id]['email']);
        $this->assertTrue($rows[$departed->id]['is_departed']);
        $this->assertTrue($rows[$departed->id]['is_long_gone']);
    }

    public function test_handle_offers_all_groups_then_the_moderated_organizations(): void
    {
        $admin = Trooper::factory()->asSystemOwner()->create();
        $club = Organization::factory()->asOrganization()->withName('Rebel Legion')->create();

        $result = $this->pageData($admin, organization_id: $club->id);

        $this->assertSame(['value' => null, 'label' => 'All Groups'], $result['organization_options'][0]);
        //  the badge only shows the name
        $this->assertSame(['name' => 'Rebel Legion'], $result['organization']->resolve());
        $this->assertSame('Name / Email', $result['sort_columns']->resolve()[0]['label']);
    }

    /** @return array<string, mixed> */
    private function pageData(Trooper $actor, ?int $organization_id = null): array
    {
        $filter = new TrooperFilter(Request::create('/'));

        return (new ListTroopersPageData($actor, $filter, organization_id: $organization_id))->handle();
    }
}
