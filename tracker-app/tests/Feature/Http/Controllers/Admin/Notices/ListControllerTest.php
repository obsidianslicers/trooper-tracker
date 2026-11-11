<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Notices;

use App\Models\Notice;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_notices_list_for_admin(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();
        Notice::factory()->count(2)->create();

        $response = $this->actingAs($trooper)->get(route('admin.notices.list'));

        $response->assertOk();
        $response->assertViewIs('pages.admin.notices.list');
    }

    public function test_invoke_offers_update_for_a_notice_in_a_moderated_child_organization(): void
    {
        $club = Organization::factory()->asOrganization()->create();
        $garrison = Organization::factory()->asRegion()->withParent($club)->create();
        $moderator = Trooper::factory()->asModerator()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($club)->asModerator()->create();
        $notice = Notice::factory()->forOrganization($garrison)->asActive()->create();

        $response = $this->actingAs($moderator)->get(route('admin.notices.list'));

        $response->assertOk();
        $response->assertSee(route('admin.notices.update', ['notice' => $notice]), false);
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('admin.notices.list'));

        $response->assertRedirect(route('auth.login'));
    }
}
