<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Auth;

use App\Enums\MembershipStatus;
use App\Enums\TrooperRequestStatus;
use App\Features\Troopers\Commands\ApproveTrooperCommand;
use App\Features\Troopers\Commands\ApproveTrooperCommandHandler;
use App\Mail\Auth\TrooperRegistered;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperOrganization;
use App\Models\TrooperRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RegisterSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_creates_pending_join_request_for_selected_unit_without_active_membership(): void
    {
        Mail::fake();
        Queue::fake();

        session(['registration_auth' => ['method' => 'email', 'email' => null, 'expires_at' => now()->addMinutes(20)]]);

        [$organization, $region, $unit] = $this->createOrganizationHierarchy();

        $response = $this->post(route('auth.register'), $this->registrationData([
            'organizations' => [
                $organization->id => [
                    'selected' => '1',
                    'identifier' => 'TK-12345',
                    'region_id' => $region->id,
                    'unit_id' => $unit->id,
                    'should_notify' => '1',
                ],
            ],
        ]));

        $response->assertRedirect(route('auth.thank-you'));

        $trooper = Trooper::where(Trooper::EMAIL, 'johndoe@example.com')->firstOrFail();

        $this->assertEquals(MembershipStatus::PENDING, $trooper->membership_status);
        $this->assertDatabaseHas('tt_trooper_requests', [
            TrooperRequest::TROOPER_ID => $trooper->id,
            TrooperRequest::ORGANIZATION_ID => $unit->id,
            TrooperRequest::PRIMARY_ORGANIZATION_ID => $organization->id,
            TrooperRequest::IDENTIFIER => 'TK-12345',
            TrooperRequest::STATUS => TrooperRequestStatus::PENDING->value,
        ]);
        $this->assertDatabaseMissing('tt_trooper_organizations', [
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $organization->id,
        ]);
        $this->assertDatabaseMissing('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->id,
        ]);
        Mail::assertQueued(TrooperRegistered::class);
    }

    public function test_invoke_rejects_duplicate_pending_identifier_without_creating_trooper(): void
    {
        Mail::fake();
        Queue::fake();

        session(['registration_auth' => ['method' => 'email', 'email' => null, 'expires_at' => now()->addMinutes(20)]]);

        [$organization, $region, $unit] = $this->createOrganizationHierarchy();

        TrooperRequest::factory()
            ->forTrooper(Trooper::factory()->asMember()->create())
            ->forOrganization($unit)
            ->forPrimaryOrganization($organization)
            ->withIdentifier('TK-12345')
            ->create();

        $response = $this->post(route('auth.register'), $this->registrationData([
            'organizations' => [
                $organization->id => [
                    'selected' => '1',
                    'identifier' => 'TK-12345',
                    'region_id' => $region->id,
                    'unit_id' => $unit->id,
                    'should_notify' => '1',
                ],
            ],
        ]));

        $response->assertSessionHasErrors("organizations.{$organization->id}.identifier");
        $this->assertDatabaseMissing('tt_troopers', [
            Trooper::EMAIL => 'johndoe@example.com',
        ]);
        $this->assertDatabaseCount('tt_trooper_requests', 1);
        Mail::assertNothingQueued();
    }

    public function test_invoke_allows_identifier_from_resolved_historical_request(): void
    {
        Mail::fake();
        Queue::fake();

        session(['registration_auth' => ['method' => 'email', 'email' => null, 'expires_at' => now()->addMinutes(20)]]);

        [$organization, $region, $unit] = $this->createOrganizationHierarchy();

        TrooperRequest::factory()
            ->forTrooper(Trooper::factory()->asMember()->create())
            ->forOrganization($unit)
            ->forPrimaryOrganization($organization)
            ->withIdentifier('TK-12345')
            ->asDenied()
            ->create();

        $response = $this->post(route('auth.register'), $this->registrationData([
            'organizations' => [
                $organization->id => [
                    'selected' => '1',
                    'identifier' => 'TK-12345',
                    'region_id' => $region->id,
                    'unit_id' => $unit->id,
                    'should_notify' => '1',
                ],
            ],
        ]));

        $response->assertRedirect(route('auth.thank-you'));
        $this->assertDatabaseHas('tt_troopers', [
            Trooper::EMAIL => 'johndoe@example.com',
        ]);
    }

    public function test_approving_registered_trooper_converts_pending_join_request_to_membership(): void
    {
        Mail::fake();
        Notification::fake();
        Queue::fake();

        session(['registration_auth' => ['method' => 'email', 'email' => null, 'expires_at' => now()->addMinutes(20)]]);

        [$organization, $region, $unit] = $this->createOrganizationHierarchy();

        $this->post(route('auth.register'), $this->registrationData([
            'organizations' => [
                $organization->id => [
                    'selected' => '1',
                    'identifier' => 'TK-12345',
                    'region_id' => $region->id,
                    'unit_id' => $unit->id,
                    'should_notify' => '1',
                ],
            ],
        ]))->assertRedirect(route('auth.thank-you'));

        $trooper = Trooper::where(Trooper::EMAIL, 'johndoe@example.com')->firstOrFail();

        app(ApproveTrooperCommandHandler::class)(new ApproveTrooperCommand(
            trooper: $trooper,
            is_approved: true
        ));

        $trooper->refresh();

        $this->assertEquals(MembershipStatus::ACTIVE, $trooper->membership_status);
        $this->assertDatabaseHas('tt_trooper_requests', [
            TrooperRequest::TROOPER_ID => $trooper->id,
            TrooperRequest::ORGANIZATION_ID => $unit->id,
            TrooperRequest::STATUS => TrooperRequestStatus::APPROVED->value,
        ]);
        $this->assertDatabaseHas('tt_trooper_organizations', [
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $organization->id,
            TrooperOrganization::IDENTIFIER => 'TK-12345',
            TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE->value,
        ]);
        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $unit->id,
            TrooperAssignment::IS_MEMBER => true,
            TrooperAssignment::SHOULD_NOTIFY => true,
        ]);
        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::IS_MEMBER => false,
            TrooperAssignment::SHOULD_NOTIFY => true,
        ]);
        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $region->id,
            TrooperAssignment::IS_MEMBER => false,
            TrooperAssignment::SHOULD_NOTIFY => true,
        ]);
    }

    public function test_invoke_creates_pending_join_request_for_region_without_units(): void
    {
        Mail::fake();
        Queue::fake();

        session(['registration_auth' => ['method' => 'email', 'email' => null, 'expires_at' => now()->addMinutes(20)]]);

        $organization = Organization::factory()
            ->asOrganization()
            ->withNodePath('100:')
            ->state([
                Organization::IDENTIFIER_DISPLAY => 'TKID',
                Organization::IDENTIFIER_VALIDATION => 'string',
            ])
            ->create();
        $region = Organization::factory()->asRegion()->withParent($organization)->withNodePath('100:200:')->create();

        $this->post(route('auth.register'), $this->registrationData([
            'organizations' => [
                $organization->id => [
                    'selected' => '1',
                    'identifier' => 'TK-12345',
                    'region_id' => $region->id,
                ],
            ],
        ]))->assertRedirect(route('auth.thank-you'));

        $trooper = Trooper::where(Trooper::EMAIL, 'johndoe@example.com')->firstOrFail();

        $this->assertDatabaseHas('tt_trooper_requests', [
            TrooperRequest::TROOPER_ID => $trooper->id,
            TrooperRequest::ORGANIZATION_ID => $region->id,
            TrooperRequest::PRIMARY_ORGANIZATION_ID => $organization->id,
            TrooperRequest::IDENTIFIER => 'TK-12345',
            TrooperRequest::STATUS => TrooperRequestStatus::PENDING->value,
        ]);
        $this->assertDatabaseMissing('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->id,
        ]);
    }

    public function test_invoke_creates_pending_join_request_for_visitor_top_level_organization(): void
    {
        Mail::fake();
        Queue::fake();

        session(['registration_auth' => ['method' => 'email', 'email' => null, 'expires_at' => now()->addMinutes(20)]]);

        [$organization] = $this->createOrganizationHierarchy();

        $this->post(route('auth.register'), $this->registrationData([
            'membership_role' => 'visitor',
            'organizations' => [
                $organization->id => [
                    'selected' => '1',
                    'identifier' => 'VIS-123',
                ],
            ],
        ]))->assertRedirect(route('auth.thank-you'));

        $trooper = Trooper::where(Trooper::EMAIL, 'johndoe@example.com')->firstOrFail();

        $this->assertDatabaseHas('tt_trooper_requests', [
            TrooperRequest::TROOPER_ID => $trooper->id,
            TrooperRequest::ORGANIZATION_ID => $organization->id,
            TrooperRequest::PRIMARY_ORGANIZATION_ID => $organization->id,
            TrooperRequest::IDENTIFIER => 'VIS-123',
            TrooperRequest::STATUS => TrooperRequestStatus::PENDING->value,
        ]);
        $this->assertDatabaseMissing('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->id,
        ]);
    }

    public function test_denied_registered_trooper_does_not_receive_active_membership(): void
    {
        Mail::fake();
        Notification::fake();
        Queue::fake();

        session(['registration_auth' => ['method' => 'email', 'email' => null, 'expires_at' => now()->addMinutes(20)]]);

        [$organization, $region, $unit] = $this->createOrganizationHierarchy();

        $this->post(route('auth.register'), $this->registrationData([
            'organizations' => [
                $organization->id => [
                    'selected' => '1',
                    'identifier' => 'TK-12345',
                    'region_id' => $region->id,
                    'unit_id' => $unit->id,
                ],
            ],
        ]))->assertRedirect(route('auth.thank-you'));

        $trooper = Trooper::where(Trooper::EMAIL, 'johndoe@example.com')->firstOrFail();

        app(ApproveTrooperCommandHandler::class)(new ApproveTrooperCommand(
            trooper: $trooper,
            is_approved: false,
            denial_reason: 'Not eligible'
        ));

        $trooper->refresh();

        $this->assertEquals(MembershipStatus::DENIED, $trooper->membership_status);
        $this->assertDatabaseHas('tt_trooper_requests', [
            TrooperRequest::TROOPER_ID => $trooper->id,
            TrooperRequest::ORGANIZATION_ID => $unit->id,
            TrooperRequest::STATUS => TrooperRequestStatus::DENIED->value,
        ]);
        $this->assertDatabaseMissing('tt_trooper_organizations', [
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $organization->id,
        ]);
        $this->assertDatabaseMissing('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->id,
        ]);
    }

    /**
     * @return array{Organization, Organization, Organization}
     */
    private function createOrganizationHierarchy(): array
    {
        $organization = Organization::factory()
            ->asOrganization()
            ->withNodePath('100:')
            ->state([
                Organization::IDENTIFIER_DISPLAY => 'TKID',
                Organization::IDENTIFIER_VALIDATION => 'string',
            ])
            ->create();
        $region = Organization::factory()
            ->asRegion()
            ->withParent($organization)
            ->withNodePath('100:200:')
            ->create();
        $unit = Organization::factory()
            ->asUnit()
            ->withParent($region)
            ->withNodePath('100:200:300:')
            ->create();

        return [$organization, $region, $unit];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function registrationData(array $overrides = []): array
    {
        return array_replace_recursive([
            'legal_name' => 'John Doe',
            'display_name' => 'JohnDoe',
            'email' => 'johndoe@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'membership_role' => 'member',
            'organizations' => [],
        ], $overrides);
    }
}
