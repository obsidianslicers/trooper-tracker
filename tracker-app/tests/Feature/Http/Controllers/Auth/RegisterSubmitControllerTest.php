<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Auth;

use App\Enums\MembershipRole;
use App\Mail\Auth\TrooperRegistered;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Tests for RegisterSubmitController.
 *
 * Verifies:
 * - Trooper registration with valid data.
 * - Member identifier assignment to organizations.
 * - Membership and notification preferences are saved correctly.
 * - Confirmation email is sent.
 * - Redirects to thank you page.
 */
class RegisterSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_invoke_creates_trooper_with_member_account_type(): void
    {
        // Arrange
        Mail::fake();

        session([
            'registration_auth' => [
                'method' => 'email',
                'email' => 'john.doe@gmail.com',
                'expires_at' => now()->addHour(),
            ],
        ]);

        $organization = Organization::factory()->create();

        $registration_data = [
            'name' => 'John Doe',
            'email' => 'john.doe@gmail.com',
            'phone' => '555-1234',
            'password' => 'password123',
            'password_confirmation' => 'password123',
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

        $trooper = Trooper::where(Trooper::EMAIL, 'john.doe@gmail.com')->first();
        $this->assertNotNull($trooper);
        $this->assertEquals('John Doe', $trooper->name);
        $this->assertEquals(MembershipRole::MEMBER, $trooper->membership_role);
    }

    public function test_invoke_creates_trooper_with_handler_account_type(): void
    {
        // Arrange
        Mail::fake();

        session([
            'registration_auth' => [
                'method' => 'email',
                'email' => 'jane.handler@gmail.com',
                'expires_at' => now()->addHour(),
            ],
        ]);

        $organization = Organization::factory()->create();

        $registration_data = [
            'name' => 'Jane Handler',
            'email' => 'jane.handler@gmail.com',
            'password' => 'password456',
            'password_confirmation' => 'password456',
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
        $trooper = Trooper::where(Trooper::EMAIL, 'jane.handler@gmail.com')->first();
        $this->assertNotNull($trooper);
        $this->assertEquals(MembershipRole::HANDLER, $trooper->membership_role);
    }

    public function test_invoke_assigns_trooper_identifiers_to_organizations(): void
    {
        // Arrange
        Mail::fake();

        session([
            'registration_auth' => [
                'method' => 'email',
                'email' => 'test.trooper@gmail.com',
                'expires_at' => now()->addHour(),
            ],
        ]);

        $organization = Organization::factory()->withIdentifierValidation()->create();

        $registration_data = [
            'name' => 'Test Trooper',
            'email' => 'test.trooper@gmail.com',
            'password' => 'password',
            'password_confirmation' => 'password',
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

        $trooper = Trooper::where(Trooper::EMAIL, 'test.trooper@gmail.com')->first();

        $trooper_org = TrooperOrganization::where(TrooperOrganization::TROOPER_ID, $trooper->id)
            ->where(TrooperOrganization::ORGANIZATION_ID, $organization->id)
            ->first();

        $this->assertNotNull($trooper_org);
        $this->assertEquals('12345', $trooper_org->identifier);
    }

    public function test_invoke_assigns_memberships_to_region_without_units(): void
    {
        // Arrange
        Mail::fake();

        session([
            'registration_auth' => [
                'method' => 'email',
                'email' => 'region.member@gmail.com',
                'expires_at' => now()->addHour(),
            ],
        ]);

        $club = Organization::factory()->create();
        $region = Organization::factory()->asRegion()->create([
            Organization::PARENT_ID => $club->id,
        ]);

        $registration_data = [
            'name' => 'Region Member',
            'email' => 'region.member@gmail.com',
            'password' => 'password',
            'password_confirmation' => 'password',
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
        $trooper = Trooper::where(Trooper::EMAIL, 'region.member@gmail.com')->first();

        $assignment = TrooperAssignment::where(TrooperAssignment::TROOPER_ID, $trooper->id)
            ->where(TrooperAssignment::ORGANIZATION_ID, $region->id)
            ->first();

        $this->assertNotNull($assignment);
        $this->assertTrue($assignment->is_member);
    }

    public function test_invoke_assigns_memberships_to_unit_when_selected(): void
    {
        // Arrange
        Mail::fake();

        session([
            'registration_auth' => [
                'method' => 'email',
                'email' => 'unit.member@gmail.com',
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
            'name' => 'Unit Member',
            'email' => 'unit.member@gmail.com',
            'password' => 'password',
            'password_confirmation' => 'password',
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
        $trooper = Trooper::where(Trooper::EMAIL, 'unit.member@gmail.com')->first();

        $assignment = TrooperAssignment::where(TrooperAssignment::TROOPER_ID, $trooper->id)
            ->where(TrooperAssignment::ORGANIZATION_ID, $unit->id)
            ->first();

        $this->assertNotNull($assignment);
        $this->assertTrue($assignment->is_member);
    }

    public function test_invoke_assigns_notification_preferences_for_selected_organizations(): void
    {
        // Arrange
        Mail::fake();

        session([
            'registration_auth' => [
                'method' => 'email',
                'email' => 'notify.test@gmail.com',
                'expires_at' => now()->addHour(),
            ],
        ]);

        $club = Organization::factory()->create();
        $region = Organization::factory()->asRegion()->create([
            Organization::PARENT_ID => $club->id,
        ]);

        $registration_data = [
            'name' => 'Notification Test',
            'email' => 'notify.test@gmail.com',
            'password' => 'password',
            'password_confirmation' => 'password',
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
        $trooper = Trooper::where(Trooper::EMAIL, 'notify.test@gmail.com')->first();

        $club_assignment = TrooperAssignment::where(TrooperAssignment::TROOPER_ID, $trooper->id)
            ->where(TrooperAssignment::ORGANIZATION_ID, $club->id)
            ->first();
        $this->assertNotNull($club_assignment);
        $this->assertTrue($club_assignment->should_notify);

        $region_assignment = TrooperAssignment::where(TrooperAssignment::TROOPER_ID, $trooper->id)
            ->where(TrooperAssignment::ORGANIZATION_ID, $region->id)
            ->first();
        $this->assertNotNull($region_assignment);
    }

    public function test_invoke_sends_registration_confirmation_email(): void
    {
        // Arrange
        Mail::fake();

        session([
            'registration_auth' => [
                'method' => 'email',
                'email' => 'email.test@gmail.com',
                'expires_at' => now()->addHour(),
            ],
        ]);

        $organization = Organization::factory()->create();

        $registration_data = [
            'name' => 'Email Test',
            'email' => 'email.test@gmail.com',
            'password' => 'password',
            'password_confirmation' => 'password',
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
        Mail::assertQueued(TrooperRegistered::class, function ($mail)
        {
            return $mail->hasTo('email.test@gmail.com');
        });
    }

    public function test_invoke_redirects_to_thank_you_page(): void
    {
        // Arrange
        Mail::fake();

        session([
            'registration_auth' => [
                'method' => 'email',
                'email' => 'redirect.test@gmail.com',
                'expires_at' => now()->addHour(),
            ],
        ]);

        $organization = Organization::factory()->create();

        $registration_data = [
            'name' => 'Redirect Test',
            'email' => 'redirect.test@gmail.com',
            'password' => 'password',
            'password_confirmation' => 'password',
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

    public function test_invoke_handles_multiple_organizations(): void
    {
        // Arrange
        Mail::fake();

        session([
            'registration_auth' => [
                'method' => 'email',
                'email' => 'multi.org@gmail.com',
                'expires_at' => now()->addHour(),
            ],
        ]);

        $org1 = Organization::factory()->withIdentifierValidation()->create();
        $org2 = Organization::factory()->withIdentifierValidation()->create();

        $registration_data = [
            'name' => 'Multi Org',
            'email' => 'multi.org@gmail.com',
            'password' => 'password',
            'password_confirmation' => 'password',
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

        $trooper = Trooper::where(Trooper::EMAIL, 'multi.org@gmail.com')->first();

        $this->assertEquals(2, TrooperOrganization::where(TrooperOrganization::TROOPER_ID, $trooper->id)->count());

        $org1_identifier = TrooperOrganization::where(TrooperOrganization::TROOPER_ID, $trooper->id)
            ->where(TrooperOrganization::ORGANIZATION_ID, $org1->id)
            ->first();
        $this->assertEquals('11111', $org1_identifier->identifier);

        $org2_identifier = TrooperOrganization::where(TrooperOrganization::TROOPER_ID, $trooper->id)
            ->where(TrooperOrganization::ORGANIZATION_ID, $org2->id)
            ->first();
        $this->assertEquals('22222', $org2_identifier->identifier);
    }
}
