<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Notices\Pages;

use App\Models\Notice;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class IndexNoticesControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_notices_list_for_admin(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();
        Notice::factory()->count(2)->create();

        $response = $this->actingAs($trooper)->get(route('admin.notices.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('admin/notices/List')
            ->where('scope', 'active')
            ->where('organization', null)
            ->has('notices.data')
            ->has('notices.meta.links'));
    }

    public function test_invoke_offers_update_for_a_notice_in_a_moderated_child_organization(): void
    {
        $club = Organization::factory()->asOrganization()->create();
        $garrison = Organization::factory()->asRegion()->withParent($club)->create();
        $moderator = Trooper::factory()->asModerator()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($club)->asModerator()->create();
        $notice = Notice::factory()->forOrganization($garrison)->asActive()->create();

        $response = $this->actingAs($moderator)->get(route('admin.notices.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->where('notices.data.0.id', $notice->id)
            ->where('notices.data.0.can_update', true)
            ->where('notices.data.0.organization.id', $garrison->id));
    }

    public function test_invoke_filters_by_organization_and_scope(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();
        $garrison = Organization::factory()->asRegion()->create();
        Notice::factory()->forOrganization($garrison)->asActive()->create();
        $future = Notice::factory()->forOrganization($garrison)->create([
            Notice::STARTS_AT => now()->addWeek(),
            Notice::ENDS_AT => now()->addWeeks(2),
        ]);
        Notice::factory()->create([
            Notice::STARTS_AT => now()->addWeek(),
            Notice::ENDS_AT => now()->addWeeks(2),
        ]);

        $response = $this->actingAs($trooper)->get(route('admin.notices.index', [
            'scope' => 'future',
            'organization_id' => $garrison->id,
        ]));

        $response->assertInertia(fn (Assert $page) => $page
            ->where('scope', 'future')
            ->where('organization.name', $garrison->name)
            ->has('notices.data', 1)
            ->where('notices.data.0.id', $future->id));
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('admin.notices.index'));

        $response->assertRedirect(route('auth.login'));
    }
}
