<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PublicApiControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_roster_returns_empty_roster_when_org_not_found(): void
    {
        $response = $this->get(route('api.public', ['roster' => '', 'org' => 9999]));

        $response->assertOk();
        $response->assertSee('No members to display.');
    }

    public function test_roster_returns_html_with_active_member_names(): void
    {
        $org = Organization::factory()->create();
        $trooper = Trooper::factory()->asActive()->withDisplayName('Test Member')->create();

        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($org)
            ->asMember()
            ->create();

        $response = $this->get(route('api.public', ['roster' => '', 'org' => $org->id]));

        $response->assertOk();
        $response->assertSee('Test Member');
    }

    public function test_roster_does_not_show_non_members(): void
    {
        $org = Organization::factory()->create();
        $trooper = Trooper::factory()->asActive()->withDisplayName('Non Member')->create();

        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($org)
            ->create();

        $response = $this->get(route('api.public', ['roster' => '', 'org' => $org->id]));

        $response->assertOk();
        $response->assertDontSee('Non Member');
    }

    public function test_roster_caches_response_after_first_request(): void
    {
        $org = Organization::factory()->create();

        $this->get(route('api.public', ['roster' => '', 'org' => $org->id]));

        $this->assertTrue(Cache::has("tracker:roster:{$org->id}"));
    }

    public function test_roster_returns_cached_html_on_subsequent_requests(): void
    {
        $org = Organization::factory()->create();

        Cache::put("tracker:roster:{$org->id}", '<p>cached content</p>', 86400);

        $response = $this->get(route('api.public', ['roster' => '', 'org' => $org->id]));

        $response->assertOk();
        $response->assertSee('cached content');
    }

    public function test_roster_does_not_cache_missing_org_response(): void
    {
        $this->get(route('api.public', ['roster' => '', 'org' => 9999]));

        $this->assertFalse(Cache::has('tracker:roster:9999'));
    }

    public function test_roster_places_moderators_in_staff_section(): void
    {
        $org = Organization::factory()->create();
        $trooper = Trooper::factory()->asActive()->withDisplayName('Staff Member')->create();

        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($org)
            ->asMember()
            ->asModerator()
            ->create();

        $response = $this->get(route('api.public', ['roster' => '', 'org' => $org->id]));

        $response->assertOk();
        $response->assertSeeInOrder(['Leadership, Liaisons, and Unit Staff', 'Staff Member']);
    }

    public function test_roster_places_administrators_in_staff_section(): void
    {
        $org = Organization::factory()->create();
        $trooper = Trooper::factory()->asActive()->asAdministrator()->withDisplayName('Admin Member')->create();

        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($org)
            ->asMember()
            ->create();

        $response = $this->get(route('api.public', ['roster' => '', 'org' => $org->id]));

        $response->assertOk();
        $response->assertSeeInOrder(['Leadership, Liaisons, and Unit Staff', 'Admin Member']);
    }
}
