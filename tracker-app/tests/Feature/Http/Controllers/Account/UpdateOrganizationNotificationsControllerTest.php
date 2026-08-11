<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Account;

use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UpdateOrganizationNotificationsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_notifications_for_selected_organizations(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $organization_one = Organization::factory()->create();
        $organization_two = Organization::factory()->create();

        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization_one)
            ->withShouldNotify(false)
            ->create();

        $response = $this->actingAs($trooper)->post(
            route('account.update-organization-notifications'),
            [
                'organization_ids' => [
                    $organization_one->{Organization::ID},
                    $organization_two->{Organization::ID},
                ],
                'enabled' => true,
            ]
        );

        $response->assertOk();
        $response->assertViewIs('layouts.inertia');
        $response->assertInertia(fn(Assert $page) => $page
            ->component('account/Index')
        );

        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->{Trooper::ID},
            TrooperAssignment::ORGANIZATION_ID => $organization_one->{Organization::ID},
            TrooperAssignment::SHOULD_NOTIFY => true,
        ]);

        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->{Trooper::ID},
            TrooperAssignment::ORGANIZATION_ID => $organization_two->{Organization::ID},
            TrooperAssignment::SHOULD_NOTIFY => true,
        ]);
    }
}
