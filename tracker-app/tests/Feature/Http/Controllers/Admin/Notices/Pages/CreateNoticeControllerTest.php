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

class CreateNoticeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_create_notice_page_for_admin(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();

        $response = $this->actingAs($trooper)->get(route('admin.notices.create'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('admin/notices/Create')
            ->where('notice.id', null)
            ->where('notice.type', 'info')
            ->where('organization_options.0.value', null)
            ->has('type_options', 4));
    }

    public function test_invoke_copies_a_notice(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();
        $copy = Notice::factory()->create([Notice::TITLE => 'Muster']);

        $response = $this->actingAs($trooper)
            ->get(route('admin.notices.create', ['copy_id' => $copy->id]));

        $response->assertInertia(fn (Assert $page) => $page
            ->where('notice.id', null)
            ->where('notice.title', 'Muster (Copy)')
            ->where('notice.message', $copy->message));
    }

    public function test_invoke_offers_a_moderator_only_their_organizations(): void
    {
        $garrison = Organization::factory()->asRegion()->create();
        Organization::factory()->asRegion()->create();
        $moderator = Trooper::factory()->asModerator()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($garrison)->asModerator()->create();

        $response = $this->actingAs($moderator)
            ->get(route('admin.notices.create', ['organization_id' => $garrison->id]));

        $response->assertInertia(fn (Assert $page) => $page
            ->where('notice.organization_id', $garrison->id)
            ->has('organization_options', 1)
            ->where('organization_options.0.value', $garrison->id));
    }

    public function test_invoke_refuses_an_organization_the_moderator_does_not_moderate(): void
    {
        $other = Organization::factory()->asRegion()->create();
        $moderator = Trooper::factory()->asModerator()->create();

        $response = $this->actingAs($moderator)
            ->get(route('admin.notices.create', ['organization_id' => $other->id]));

        $response->assertNotFound();
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('admin.notices.create'));

        $response->assertRedirect(route('auth.login'));
    }
}
