<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Account;

use App\Models\TrooperRequest;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Account\ClubMembershipsController
 */
class ClubMembershipsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('account.club-memberships'));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_renders_club_memberships_view(): void
    {
        $trooper = Trooper::factory()->asMember()->create();

        $response = $this->actingAs($trooper)->get(route('account.club-memberships'));

        $response->assertOk();
        $response->assertViewIs('pages.account.club-memberships');
        $response->assertViewHas(['available_clubs', 'current_clubs', 'pending_requests', 'denied_requests']);
    }

    public function test_invoke_passes_denied_requests_within_30_days(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        TrooperRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->asDenied()
            ->create(['updated_at' => now()->subDays(10)]);

        $response = $this->actingAs($trooper)->get(route('account.club-memberships'));

        $response->assertViewHas('denied_requests', fn($denied) => $denied->count() === 1);
    }

    public function test_invoke_excludes_denied_requests_older_than_30_days(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        TrooperRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->asDenied()
            ->create(['updated_at' => now()->subDays(31)]);

        $response = $this->actingAs($trooper)->get(route('account.club-memberships'));

        $response->assertViewHas('denied_requests', fn($denied) => $denied->isEmpty());
    }
}
