<?php

declare(strict_types=1);

namespace Tests\Feature\Account\Pages;

use App\Models\Notice;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoticesControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_notices_controller_displays_page(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        $response = $this->actingAs($trooper)->get(route('account.notices'));

        $response->assertOk();
        $response->assertViewIs('pages.account.notices');
    }

    public function test_notices_controller_passes_unread_notices(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->{Trooper::ID},
            TrooperAssignment::ORGANIZATION_ID => $organization->{Organization::ID},
            TrooperAssignment::IS_MEMBER => true,
        ]);

        Notice::factory()
            ->forOrganization($organization)
            ->asActive()
            ->create();

        $response = $this->actingAs($trooper)->get(route('account.notices'));

        $response->assertViewHas('notices');
        $this->assertCount(1, $response->viewData('notices'));
    }

    public function test_notices_controller_only_shows_visible_notices(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $organization_member = Organization::factory()->create();
        $organization_other = Organization::factory()->create();

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->{Trooper::ID},
            TrooperAssignment::ORGANIZATION_ID => $organization_member->{Organization::ID},
            TrooperAssignment::IS_MEMBER => true,
        ]);

        Notice::factory()
            ->forOrganization($organization_member)
            ->asActive()
            ->create();

        Notice::factory()
            ->forOrganization($organization_other)
            ->asActive()
            ->create();

        $response = $this->actingAs($trooper)->get(route('account.notices'));

        $this->assertCount(1, $response->viewData('notices'));
    }

    public function test_notices_controller_includes_global_notices(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        Notice::factory()->asGlobal()->asActive()->create();

        $response = $this->actingAs($trooper)->get(route('account.notices'));

        $this->assertCount(1, $response->viewData('notices'));
    }

    public function test_notices_controller_excludes_past_notices(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        $past = Notice::factory()->asGlobal()->asPast()->create();
        $active = Notice::factory()->asGlobal()->asActive()->create();

        $response = $this->actingAs($trooper)->get(route('account.notices'));

        $notices = $response->viewData('notices');
        $this->assertTrue($notices->contains($past));
        $this->assertTrue($notices->contains($active));
    }

    public function test_notices_controller_excludes_future_notices(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        $future = Notice::factory()->asGlobal()->asFuture()->create();
        $active = Notice::factory()->asGlobal()->asActive()->create();

        $response = $this->actingAs($trooper)->get(route('account.notices'));

        $notices = $response->viewData('notices');
        $this->assertTrue($notices->contains($future));
        $this->assertTrue($notices->contains($active));
    }
}
