<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Bus\MagicBus;
use App\Enums\MembershipRole;
use App\Jobs\SendTrooperRegisteredNotificationsJob;
use App\Mail\Admin\Troopers\TrooperAwaitingApproval;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Tests for the SendTrooperRegisteredNotificationsJob class.
 *
 * Validates that the job correctly sends awaiting approval notifications
 * to administrators and moderators when a new trooper registers.
 */
class SendTrooperRegisteredNotificationsJobTest extends TestCase
{
    use RefreshDatabase;

    private Trooper $trooper;
    private MockInterface $bus_mock;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->trooper = Trooper::factory()->create();
        $this->bus_mock = $this->mock(MagicBus::class);
    }

    public function test_handle_sends_notifications_to_administrators(): void
    {
        // Arrange
        $admin1 = Trooper::factory()->asAdministrator()->create();
        $admin2 = Trooper::factory()->asAdministrator()->create();
        $admins = new Collection([$admin1, $admin2]);

        $this->bus_mock->shouldReceive('send')
            ->once()
            ->andReturn($admins);

        $this->bus_mock->shouldReceive('send')
            ->once()
            ->andReturn(new Collection());

        $subject = new SendTrooperRegisteredNotificationsJob($this->trooper);

        // Act
        $subject->handle($this->bus_mock);

        // Assert
        Mail::assertQueued(TrooperAwaitingApproval::class, 2);
    }

    public function test_handle_sends_notifications_to_moderators(): void
    {
        // Arrange
        $moderator1 = Trooper::factory()->asModerator()->create();
        $moderator2 = Trooper::factory()->asModerator()->create();

        // Get or create organizations for the moderators and create moderator assignments
        $org1 = $moderator1->organizations->first() ?? \App\Models\Organization::factory()->create();
        $org2 = $moderator2->organizations->first() ?? \App\Models\Organization::factory()->create();

        // Create moderator assignments for the moderators
        $moderator1->trooper_assignments()->create([
            'organization_id' => $org1->id,
            'is_moderator' => true,
            'is_member' => true,
            'can_notify' => true,
        ]);

        $moderator2->trooper_assignments()->create([
            'organization_id' => $org2->id,
            'is_moderator' => true,
            'is_member' => true,
            'can_notify' => true,
        ]);

        // Attach the trooper to the same organizations so moderators can moderate them
        $this->trooper->organizations()->attach($org1->id, ['identifier' => 'TK-12345']);
        $this->trooper->organizations()->attach($org2->id, ['identifier' => 'TK-67890']);

        // Create trooper assignments for the new trooper as well
        $this->trooper->trooper_assignments()->create([
            'organization_id' => $org1->id,
            'is_moderator' => false,
            'is_member' => true,
            'can_notify' => true,
        ]);

        $this->trooper->trooper_assignments()->create([
            'organization_id' => $org2->id,
            'is_moderator' => false,
            'is_member' => true,
            'can_notify' => true,
        ]);

        $moderators = new Collection([$moderator1, $moderator2]);

        $this->bus_mock->shouldReceive('send')
            ->once()
            ->andReturn(new Collection());

        $this->bus_mock->shouldReceive('send')
            ->once()
            ->andReturn($moderators);

        $subject = new SendTrooperRegisteredNotificationsJob($this->trooper);

        // Act
        $subject->handle($this->bus_mock);

        // Assert
        Mail::assertQueued(TrooperAwaitingApproval::class, 2);
    }

    public function test_handle_sends_notifications_to_both_admins_and_moderators(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $moderator = Trooper::factory()->asModerator()->create();

        // Set up moderator with organization assignment
        $org = $moderator->organizations->first() ?? \App\Models\Organization::factory()->create();

        $moderator->trooper_assignments()->create([
            'organization_id' => $org->id,
            'is_moderator' => true,
            'is_member' => true,
            'can_notify' => true,
        ]);

        // Attach the trooper to the organization and create assignment
        $this->trooper->organizations()->attach($org->id, ['identifier' => 'TK-12345']);
        $this->trooper->trooper_assignments()->create([
            'organization_id' => $org->id,
            'is_moderator' => false,
            'is_member' => true,
            'can_notify' => true,
        ]);

        $admins = new Collection([$admin]);
        $moderators = new Collection([$moderator]);

        $this->bus_mock->shouldReceive('send')
            ->once()
            ->andReturn($admins);

        $this->bus_mock->shouldReceive('send')
            ->once()
            ->andReturn($moderators);

        $subject = new SendTrooperRegisteredNotificationsJob($this->trooper);

        // Act
        $subject->handle($this->bus_mock);

        // Assert
        Mail::assertQueued(TrooperAwaitingApproval::class, 2);
    }

    public function test_handle_skips_troopers_with_invalid_email(): void
    {
        // Arrange
        $admin_valid = Trooper::factory()->asAdministrator()->create([
            Trooper::EMAIL => 'valid@example.com',
        ]);

        $admin_invalid = Trooper::factory()->asAdministrator()->create([
            Trooper::EMAIL => '',
        ]);

        $admins = new Collection([$admin_valid, $admin_invalid]);

        $this->bus_mock->shouldReceive('send')
            ->once()
            ->andReturn($admins);

        $this->bus_mock->shouldReceive('send')
            ->once()
            ->andReturn(new Collection());

        $subject = new SendTrooperRegisteredNotificationsJob($this->trooper);

        // Act
        $subject->handle($this->bus_mock);

        // Assert - only one email should be sent
        Mail::assertQueued(TrooperAwaitingApproval::class, 1);
    }

    public function test_handle_works_with_empty_admin_collection(): void
    {
        // Arrange
        $moderator = Trooper::factory()->asModerator()->create();
        $moderators = new Collection([$moderator]);

        $this->bus_mock->shouldReceive('send')
            ->once()
            ->andReturn(new Collection());

        $this->bus_mock->shouldReceive('send')
            ->once()
            ->andReturn($moderators);

        $subject = new SendTrooperRegisteredNotificationsJob($this->trooper);

        // Act
        $subject->handle($this->bus_mock);

        // Assert
        Mail::assertQueued(TrooperAwaitingApproval::class, 1);
    }

    public function test_handle_works_with_empty_moderator_collection(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $admins = new Collection([$admin]);

        $this->bus_mock->shouldReceive('send')
            ->once()
            ->andReturn($admins);

        $this->bus_mock->shouldReceive('send')
            ->once()
            ->andReturn(new Collection());

        $subject = new SendTrooperRegisteredNotificationsJob($this->trooper);

        // Act
        $subject->handle($this->bus_mock);

        // Assert
        Mail::assertQueued(TrooperAwaitingApproval::class, 1);
    }

    public function test_handle_sends_no_emails_when_no_admins_or_moderators(): void
    {
        // Arrange
        $this->bus_mock->shouldReceive('send')
            ->once()
            ->andReturn(new Collection());

        $this->bus_mock->shouldReceive('send')
            ->once()
            ->andReturn(new Collection());

        $subject = new SendTrooperRegisteredNotificationsJob($this->trooper);

        // Act
        $subject->handle($this->bus_mock);

        // Assert
        Mail::assertNothingQueued();
    }

    public function test_job_implements_should_queue(): void
    {
        // Arrange
        $subject = new SendTrooperRegisteredNotificationsJob($this->trooper);

        // Assert
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $subject);
    }

    public function test_handle_queries_for_administrator_role(): void
    {
        // Arrange
        $this->bus_mock->shouldReceive('send')
            ->once()
            ->with(\Mockery::on(function ($query)
            {
                return $query instanceof \App\Features\Troopers\Queries\GetTroopersByRoleQuery
                    && $query->role === MembershipRole::ADMINISTRATOR;
            }))
            ->andReturn(new Collection());

        $this->bus_mock->shouldReceive('send')
            ->once()
            ->andReturn(new Collection());

        $subject = new SendTrooperRegisteredNotificationsJob($this->trooper);

        // Act
        $subject->handle($this->bus_mock);
    }

    public function test_handle_queries_for_moderator_role(): void
    {
        // Arrange
        $this->bus_mock->shouldReceive('send')
            ->once()
            ->andReturn(new Collection());

        $this->bus_mock->shouldReceive('send')
            ->once()
            ->with(\Mockery::on(function ($query)
            {
                return $query instanceof \App\Features\Troopers\Queries\GetTroopersByRoleQuery
                    && $query->role === MembershipRole::MODERATOR;
            }))
            ->andReturn(new Collection());

        $subject = new SendTrooperRegisteredNotificationsJob($this->trooper);

        // Act
        $subject->handle($this->bus_mock);
    }

    public function test_handle_checks_moderator_policy_before_sending(): void
    {
        // Arrange
        $new_trooper = Trooper::factory()->create();

        // Create a moderator who CAN moderate the new trooper
        $authorized_moderator = Trooper::factory()->asModerator()->create();

        // Create a moderator who CANNOT moderate the new trooper (different scope)
        $unauthorized_moderator = Trooper::factory()->asModerator()->create();

        // Set up the new trooper to be moderated by the authorized moderator only
        $new_trooper->organizations()->attach($authorized_moderator->organizations->first());

        $moderators = new Collection([$authorized_moderator, $unauthorized_moderator]);

        $this->bus_mock->shouldReceive('send')
            ->once()
            ->andReturn(new Collection());

        $this->bus_mock->shouldReceive('send')
            ->once()
            ->andReturn($moderators);

        $subject = new SendTrooperRegisteredNotificationsJob($new_trooper);

        // Act
        $subject->handle($this->bus_mock);

        // Assert - only the authorized moderator should receive email
        Mail::assertQueued(TrooperAwaitingApproval::class, function ($mail) use ($authorized_moderator)
        {
            return $mail->hasTo($authorized_moderator->email);
        });

        Mail::assertNotQueued(TrooperAwaitingApproval::class, function ($mail) use ($unauthorized_moderator)
        {
            return $mail->hasTo($unauthorized_moderator->email);
        });
    }

    public function test_handle_admins_bypass_policy_check(): void
    {
        // Arrange
        $new_trooper = Trooper::factory()->create();
        $admin = Trooper::factory()->asAdministrator()->create();

        $admins = new Collection([$admin]);

        $this->bus_mock->shouldReceive('send')
            ->once()
            ->andReturn($admins);

        $this->bus_mock->shouldReceive('send')
            ->once()
            ->andReturn(new Collection());

        $subject = new SendTrooperRegisteredNotificationsJob($new_trooper);

        // Act
        $subject->handle($this->bus_mock);

        // Assert - admin should receive email regardless of organizational scope
        Mail::assertQueued(TrooperAwaitingApproval::class, 1);
        Mail::assertQueued(TrooperAwaitingApproval::class, function ($mail) use ($admin)
        {
            return $mail->hasTo($admin->email);
        });
    }

    public function test_handle_moderator_with_invalid_email_skipped_despite_policy(): void
    {
        // Arrange
        $new_trooper = Trooper::factory()->create();

        $moderator_invalid = Trooper::factory()->asModerator()->create([
            Trooper::EMAIL => '',
        ]);

        $new_trooper->organizations()->attach($moderator_invalid->organizations->first());

        $moderators = new Collection([$moderator_invalid]);

        $this->bus_mock->shouldReceive('send')
            ->once()
            ->andReturn(new Collection());

        $this->bus_mock->shouldReceive('send')
            ->once()
            ->andReturn($moderators);

        $subject = new SendTrooperRegisteredNotificationsJob($new_trooper);

        // Act
        $subject->handle($this->bus_mock);

        // Assert - no emails sent due to invalid email
        Mail::assertNothingQueued();
    }

    public function test_handle_moderator_authorized_and_valid_email_receives_notification(): void
    {
        // Arrange
        $new_trooper = Trooper::factory()->create();
        $moderator = Trooper::factory()->asModerator()->create();

        $new_trooper->organizations()->attach($moderator->organizations->first());

        $moderators = new Collection([$moderator]);

        $this->bus_mock->shouldReceive('send')
            ->once()
            ->andReturn(new Collection());

        $this->bus_mock->shouldReceive('send')
            ->once()
            ->andReturn($moderators);

        $subject = new SendTrooperRegisteredNotificationsJob($new_trooper);

        // Act
        $subject->handle($this->bus_mock);

        // Assert
        Mail::assertQueued(TrooperAwaitingApproval::class, 1);
        Mail::assertQueued(TrooperAwaitingApproval::class, function ($mail) use ($moderator)
        {
            return $mail->hasTo($moderator->email);
        });
    }
}
