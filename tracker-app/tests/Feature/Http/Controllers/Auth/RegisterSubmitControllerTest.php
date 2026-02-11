<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Auth;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Jobs\SendTrooperRegisteredNotificationsJob;
use App\Mail\Auth\TrooperRegistered;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Tests for RegisterSubmitController.
 *
 * Verifies the complete registration flow including:
 * - Trooper account creation with PENDING status
 * - Member identifier assignment to organizations
 * - Membership assignments (region or unit level)
 * - Notification preferences for selected organizations
 * - Confirmation email queued
 * - Admin notification job dispatched
 * - Success flash message
 * - Redirect to thank you page
 */
class RegisterSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_creates_trooper_with_pending_status(): void
    {
        // Arrange
        Mail::fake();
        Queue::fake();

        session([
            'registration_auth' => [
                'method' => 'email',
                'email' => 'new.trooper@example.com',
                'expires_at' => now()->addHour(),
            ],
        ]);

        $organization = Organization::factory()->create();

        $registration_data = [
            'legal_name' => 'John Michael Doe',
            'display_name' => 'John Doe',
            'email' => 'new.trooper@example.com',
            'phone' => '5551234567',
            'password' => 'SecurePassword123',
            'account_type' => 'member',
            'organizations' => [
                $organization->id => [
                    'selected' => '1',
                ],
            ],
        ];

        // Act
        $response = $this->post(route('auth.register'), $registration_data);

        // Assert
        $response->assertRedirect(route('auth.thank-you'));

        $trooper = Trooper::where(Trooper::EMAIL, 'new.trooper@example.com')->first();
        $this->assertNotNull($trooper);
        $this->assertEquals('John Doe', $trooper->{Trooper::DISPLAY_NAME});
        $this->assertEquals('John Michael Doe', $trooper->{Trooper::LEGAL_NAME});
        $this->assertEquals(MembershipStatus::PENDING, $trooper->{Trooper::MEMBERSHIP_STATUS});
        $this->assertEquals(MembershipRole::MEMBER, $trooper->{Trooper::MEMBERSHIP_ROLE});
    }

    public function test_invoke_creates_trooper_with_handler_role(): void
    {
        // Arrange
        Mail::fake();
        Queue::fake();

        session([
            'registration_auth' => [
                'method' => 'email',
                'email' => 'handler@example.com',
                'expires_at' => now()->addHour(),
            ],
        ]);

        $organization = Organization::factory()->create();

        $registration_data = [
            'legal_name' => 'Jane Smith',
            'display_name' => 'Jane',
            'email' => 'handler@example.com',
            'password' => 'password',
            'account_type' => 'handler',
            'organizations' => [
                $organization->id => [
                    'selected' => '1',
                ],
            ],
        ];

        // Act
        $response = $this->post(route('auth.register'), $registration_data);

        // Assert
        $response->assertRedirect(route('auth.thank-you'));

        $trooper = Trooper::where(Trooper::EMAIL, 'handler@example.com')->first();
        $this->assertNotNull($trooper);
        $this->assertEquals(MembershipRole::HANDLER, $trooper->{Trooper::MEMBERSHIP_ROLE});
    }

    public function test_invoke_assigns_identifier_to_organization(): void
    {
        // Arrange
        Mail::fake();
        Queue::fake();

        session([
            'registration_auth' => [
                'method' => 'email',
                'email' => 'member@501st.com',
                'expires_at' => now()->addHour(),
            ],
        ]);

        $organization = Organization::factory()->withIdentifierValidation()->create();

        $registration_data = [
            'legal_name' => 'Test Member',
            'display_name' => 'TK-12345',
            'email' => 'member@501st.com',
            'password' => 'password',
            'account_type' => 'member',
            'organizations' => [
                $organization->id => [
                    'selected' => '1',
                    'identifier' => '12345',
                ],
            ],
        ];

        // Act
        $response = $this->post(route('auth.register'), $registration_data);

        // Assert
        $response->assertRedirect(route('auth.thank-you'));

        $trooper = Trooper::where(Trooper::EMAIL, 'member@501st.com')->first();

        $trooper_org = TrooperOrganization::where(TrooperOrganization::TROOPER_ID, $trooper->id)
            ->where(TrooperOrganization::ORGANIZATION_ID, $organization->id)
            ->first();

        $this->assertNotNull($trooper_org);
        $this->assertEquals('12345', $trooper_org->{TrooperOrganization::IDENTIFIER});
    }

    public function test_invoke_assigns_membership_to_region_without_units(): void
    {
        // Arrange
        Mail::fake();
        Queue::fake();

        session([
            'registration_auth' => [
                'method' => 'email',
                'email' => 'region.member@example.com',
                'expires_at' => now()->addHour(),
            ],
        ]);

        $club = Organization::factory()->create();
        $region = Organization::factory()->asRegion()->create([
            Organization::PARENT_ID => $club->id,
        ]);

        $registration_data = [
            'legal_name' => 'Region Member',
            'display_name' => 'Region Member',
            'email' => 'region.member@example.com',
            'password' => 'password',
            'account_type' => 'member',
            'organizations' => [
                $club->id => [
                    'selected' => '1',
                    'region_id' => $region->id,
                ],
            ],
        ];

        // Act
        $response = $this->post(route('auth.register'), $registration_data);

        // Assert
        $response->assertRedirect(route('auth.thank-you'));

        $trooper = Trooper::where(Trooper::EMAIL, 'region.member@example.com')->first();

        $assignment = TrooperAssignment::where(TrooperAssignment::TROOPER_ID, $trooper->id)
            ->where(TrooperAssignment::ORGANIZATION_ID, $region->id)
            ->first();

        $this->assertNotNull($assignment);
        $this->assertTrue($assignment->{TrooperAssignment::IS_MEMBER});
    }

    public function test_invoke_assigns_membership_to_unit_when_region_has_units(): void
    {
        // Arrange
        Mail::fake();
        Queue::fake();

        session([
            'registration_auth' => [
                'method' => 'email',
                'email' => 'unit.member@example.com',
                'expires_at' => now()->addHour(),
            ],
        ]);

        $club = Organization::factory()->create();
        $region = Organization::factory()->asRegion()->create([
            Organization::PARENT_ID => $club->id,
        ]);
        $unit = Organization::factory()->create([
            Organization::PARENT_ID => $region->id,
        ]);

        $registration_data = [
            'legal_name' => 'Unit Member',
            'display_name' => 'Unit Member',
            'email' => 'unit.member@example.com',
            'password' => 'password',
            'account_type' => 'member',
            'organizations' => [
                $club->id => [
                    'selected' => '1',
                    'region_id' => $region->id,
                    'unit_id' => $unit->id,
                ],
            ],
        ];

        // Act
        $response = $this->post(route('auth.register'), $registration_data);

        // Assert
        $response->assertRedirect(route('auth.thank-you'));

        $trooper = Trooper::where(Trooper::EMAIL, 'unit.member@example.com')->first();

        $assignment = TrooperAssignment::where(TrooperAssignment::TROOPER_ID, $trooper->id)
            ->where(TrooperAssignment::ORGANIZATION_ID, $unit->id)
            ->first();

        $this->assertNotNull($assignment);
        $this->assertTrue($assignment->{TrooperAssignment::IS_MEMBER});
    }

    public function test_invoke_sets_notification_preferences_for_organization_hierarchy(): void
    {
        // Arrange
        Mail::fake();
        Queue::fake();

        session([
            'registration_auth' => [
                'method' => 'email',
                'email' => 'notify@example.com',
                'expires_at' => now()->addHour(),
            ],
        ]);

        $club = Organization::factory()->create();
        $region = Organization::factory()->asRegion()->create([
            Organization::PARENT_ID => $club->id,
        ]);
        $unit = Organization::factory()->create([
            Organization::PARENT_ID => $region->id,
        ]);

        $registration_data = [
            'legal_name' => 'Notify Test',
            'display_name' => 'Notify Test',
            'email' => 'notify@example.com',
            'password' => 'password',
            'account_type' => 'member',
            'organizations' => [
                $club->id => [
                    'selected' => '1',
                    'region_id' => $region->id,
                    'unit_id' => $unit->id,
                ],
            ],
        ];

        // Act
        $response = $this->post(route('auth.register'), $registration_data);

        // Assert
        $response->assertRedirect(route('auth.thank-you'));

        $trooper = Trooper::where(Trooper::EMAIL, 'notify@example.com')->first();

        // Should notify for club
        $club_assignment = TrooperAssignment::where(TrooperAssignment::TROOPER_ID, $trooper->id)
            ->where(TrooperAssignment::ORGANIZATION_ID, $club->id)
            ->first();
        $this->assertNotNull($club_assignment);
        $this->assertTrue($club_assignment->{TrooperAssignment::SHOULD_NOTIFY});

        // Should notify for region
        $region_assignment = TrooperAssignment::where(TrooperAssignment::TROOPER_ID, $trooper->id)
            ->where(TrooperAssignment::ORGANIZATION_ID, $region->id)
            ->first();
        $this->assertNotNull($region_assignment);
        $this->assertTrue($region_assignment->{TrooperAssignment::SHOULD_NOTIFY});

        // Should notify for unit
        $unit_assignment = TrooperAssignment::where(TrooperAssignment::TROOPER_ID, $trooper->id)
            ->where(TrooperAssignment::ORGANIZATION_ID, $unit->id)
            ->first();
        $this->assertNotNull($unit_assignment);
        $this->assertTrue($unit_assignment->{TrooperAssignment::SHOULD_NOTIFY});
    }

    public function test_invoke_queues_confirmation_email(): void
    {
        // Arrange
        Mail::fake();
        Queue::fake();

        session([
            'registration_auth' => [
                'method' => 'email',
                'email' => 'emailtest@example.com',
                'expires_at' => now()->addHour(),
            ],
        ]);

        $organization = Organization::factory()->create();

        $registration_data = [
            'legal_name' => 'Email Test',
            'display_name' => 'Email Test',
            'email' => 'emailtest@example.com',
            'password' => 'password',
            'account_type' => 'member',
            'organizations' => [
                $organization->id => [
                    'selected' => '1',
                ],
            ],
        ];

        // Act
        $response = $this->post(route('auth.register'), $registration_data);

        // Assert
        $response->assertRedirect(route('auth.thank-you'));

        Mail::assertQueued(TrooperRegistered::class, function ($mail)
        {
            return $mail->hasTo('emailtest@example.com');
        });
    }

    public function test_invoke_dispatches_admin_notification_job(): void
    {
        // Arrange
        Mail::fake();
        Queue::fake();

        session([
            'registration_auth' => [
                'method' => 'email',
                'email' => 'jobtest@example.com',
                'expires_at' => now()->addHour(),
            ],
        ]);

        $organization = Organization::factory()->create();

        $registration_data = [
            'legal_name' => 'Job Test',
            'display_name' => 'Job Test',
            'email' => 'jobtest@example.com',
            'password' => 'password',
            'account_type' => 'member',
            'organizations' => [
                $organization->id => [
                    'selected' => '1',
                ],
            ],
        ];

        // Act
        $response = $this->post(route('auth.register'), $registration_data);

        // Assert
        $response->assertRedirect(route('auth.thank-you'));

        Queue::assertPushed(SendTrooperRegisteredNotificationsJob::class);
    }

    public function test_invoke_displays_success_flash_message(): void
    {
        // Arrange
        Mail::fake();
        Queue::fake();

        session([
            'registration_auth' => [
                'method' => 'email',
                'email' => 'flash@example.com',
                'expires_at' => now()->addHour(),
            ],
        ]);

        $organization = Organization::factory()->create();

        $registration_data = [
            'legal_name' => 'Flash Test',
            'display_name' => 'Flash Test',
            'email' => 'flash@example.com',
            'password' => 'password',
            'account_type' => 'member',
            'organizations' => [
                $organization->id => [
                    'selected' => '1',
                ],
            ],
        ];

        // Act
        $response = $this->post(route('auth.register'), $registration_data);

        // Assert
        $response->assertRedirect(route('auth.thank-you'));
    }

    public function test_invoke_redirects_to_thank_you_page(): void
    {
        // Arrange
        Mail::fake();
        Queue::fake();

        session([
            'registration_auth' => [
                'method' => 'email',
                'email' => 'redirect@example.com',
                'expires_at' => now()->addHour(),
            ],
        ]);

        $organization = Organization::factory()->create();

        $registration_data = [
            'legal_name' => 'Redirect Test',
            'display_name' => 'Redirect Test',
            'email' => 'redirect@example.com',
            'password' => 'password',
            'account_type' => 'member',
            'organizations' => [
                $organization->id => [
                    'selected' => '1',
                ],
            ],
        ];

        // Act
        $response = $this->post(route('auth.register'), $registration_data);
        $response->assertSessionHas('flash_messages');
        // Assert
        $response->assertRedirect(route('auth.thank-you'));
    }

    public function test_invoke_handles_multiple_organizations_with_identifiers(): void
    {
        // Arrange
        Mail::fake();
        Queue::fake();

        session([
            'registration_auth' => [
                'method' => 'email',
                'email' => 'multi@example.com',
                'expires_at' => now()->addHour(),
            ],
        ]);

        $org1 = Organization::factory()->withIdentifierValidation()->create();
        $org2 = Organization::factory()->withIdentifierValidation()->create();

        $registration_data = [
            'legal_name' => 'Multi Org Member',
            'display_name' => 'Multi',
            'email' => 'multi@example.com',
            'password' => 'password',
            'account_type' => 'member',
            'organizations' => [
                $org1->id => [
                    'selected' => '1',
                    'identifier' => '11111',
                ],
                $org2->id => [
                    'selected' => '1',
                    'identifier' => '22222',
                ],
            ],
        ];

        // Act
        $response = $this->post(route('auth.register'), $registration_data);

        // Assert
        $response->assertRedirect(route('auth.thank-you'));

        $trooper = Trooper::where(Trooper::EMAIL, 'multi@example.com')->first();

        $this->assertEquals(2, TrooperOrganization::where(TrooperOrganization::TROOPER_ID, $trooper->id)->count());

        $org1_identifier = TrooperOrganization::where(TrooperOrganization::TROOPER_ID, $trooper->id)
            ->where(TrooperOrganization::ORGANIZATION_ID, $org1->id)
            ->first();
        $this->assertEquals('11111', $org1_identifier->{TrooperOrganization::IDENTIFIER});

        $org2_identifier = TrooperOrganization::where(TrooperOrganization::TROOPER_ID, $trooper->id)
            ->where(TrooperOrganization::ORGANIZATION_ID, $org2->id)
            ->first();
        $this->assertEquals('22222', $org2_identifier->{TrooperOrganization::IDENTIFIER});
    }

    public function test_invoke_handles_registration_without_phone_number(): void
    {
        // Arrange
        Mail::fake();
        Queue::fake();

        session([
            'registration_auth' => [
                'method' => 'email',
                'email' => 'nophone@example.com',
                'expires_at' => now()->addHour(),
            ],
        ]);

        $organization = Organization::factory()->create();

        $registration_data = [
            'legal_name' => 'No Phone',
            'display_name' => 'No Phone',
            'email' => 'nophone@example.com',
            'password' => 'password',
            'account_type' => 'member',
            'organizations' => [
                $organization->id => [
                    'selected' => '1',
                ],
            ],
        ];

        // Act
        $response = $this->post(route('auth.register'), $registration_data);

        // Assert
        $response->assertRedirect(route('auth.thank-you'));

        $trooper = Trooper::where(Trooper::EMAIL, 'nophone@example.com')->first();
        $this->assertNotNull($trooper);
        $this->assertNull($trooper->{Trooper::PHONE});
    }
}
