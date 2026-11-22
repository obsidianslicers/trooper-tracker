<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Organizations\PageData;

use App\Messages\Organizations\PageData\ListOrganizationsPageData;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ListOrganizationsPageDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_lists_the_tree_with_what_an_administrator_can_do(): void
    {
        $admin = Trooper::factory()->asSystemOwner()->create();
        $club = Organization::factory()->asOrganization()->create();
        $region = Organization::factory()->asRegion()->create([Organization::PARENT_ID => $club->id]);
        $unit = Organization::factory()->asUnit()->create([Organization::PARENT_ID => $region->id]);

        $rows = $this->rowsFor($admin);

        $this->assertSame('Add Region', $rows[$club->id]['add_child_label']);
        $this->assertTrue($rows[$club->id]['has_costumes']);
        $this->assertSame('Add Unit', $rows[$region->id]['add_child_label']);
        $this->assertFalse($rows[$region->id]['has_costumes']);
        $this->assertNull($rows[$unit->id]['add_child_label']);
        $this->assertTrue($rows[$unit->id]['can_manage']);
    }

    public function test_handle_lets_a_member_manage_nothing(): void
    {
        $member = Trooper::factory()->create();
        $club = Organization::factory()->asOrganization()->create();

        $rows = $this->rowsFor($member);

        $this->assertFalse($rows[$club->id]['can_manage']);
        $this->assertNull($rows[$club->id]['add_child_label']);
    }

    /** @return array<int, array<string, mixed>> */
    private function rowsFor(Trooper $trooper): array
    {
        $request = Request::create('/');
        $request->setUserResolver(fn () => $trooper);
        $this->app->instance('request', $request);
        $this->actingAs($trooper);

        $subject = new ListOrganizationsPageData($trooper);

        return collect($subject->handle()['organizations']->toArray($request))->keyBy('id')->all();
    }
}
