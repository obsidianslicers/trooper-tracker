<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Troopers\Pages;

use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Admin\Troopers\RecruitController
 */
class RecruitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('admin.troopers.recruit'));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_renders_recruit_view_for_moderator(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($organization)->asModerator()->create();

        $response = $this->actingAs($moderator)->get(route('admin.troopers.recruit'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('admin/troopers/Recruit')
            ->where('organization_options.0.value', $organization->id)
            ->has("identifiers.{$organization->id}"));
    }

    public function test_invoke_renders_recruit_view_for_administrator(): void
    {
        $admin = Trooper::factory()->asSystemOwner()->create();

        $response = $this->actingAs($admin)->get(route('admin.troopers.recruit'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page->component('admin/troopers/Recruit'));
    }
}
