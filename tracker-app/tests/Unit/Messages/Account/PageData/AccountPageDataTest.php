<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Account\PageData;

use App\Enums\MembershipRole;
use App\Enums\NotificationFrequency;
use App\Messages\Account\PageData\AccountPageData;
use App\Messages\Account\Queries\GetOrganizationNotifications;
use App\Messages\Account\Resources\OrganizationNotificationCollection;
use App\Messages\Account\Resources\TrooperCostumeCollection;
use App\Messages\Account\Resources\TrooperDetails;
use App\Messages\Account\Resources\TrooperFriendCollection;
use App\Messages\Account\Resources\TrooperMembershipCollection;
use App\Messages\Account\Resources\TrooperMinorCollection;
use App\Messages\Account\Resources\TrooperRequestCollection;
use App\Messages\Organizations\Queries\GetOrganizationHierarchy;
use App\Messages\Organizations\Resources\OrganizationHierarchy;
use App\Messages\Organizations\Resources\OrganizationOptions;
use App\Messages\Troopers\Queries\Costumes\GetTrooperCostumes;
use App\Messages\Troopers\Queries\GetTrooperFriends;
use App\Messages\Troopers\Queries\GetTrooperMinors;
use App\Messages\Troopers\Queries\Membership\GetTrooperMemberships;
use App\Messages\Troopers\Queries\Membership\GetTrooperRequests;
use App\Models\Trooper;
use Mockery;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Tests\TestCase;

class AccountPageDataTest extends TestCase
{
    #[RunInSeparateProcess]
    public function test_handle_returns_account_snapshot_with_default_notification_preferences(): void
    {
        $actor = Trooper::factory()->make([
            Trooper::ID => 42,
            Trooper::EMAIL => 'ada@example.com',
            Trooper::MEMBERSHIP_ROLE => MembershipRole::HANDLER,
            Trooper::MEMBERSHIP_STATUS => 'active',
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::INSTANT,
            Trooper::PUSH_NOTIFICATIONS_ENABLED => true,
            Trooper::NOTIFICATION_PREFERENCES => null,
            Trooper::DELETION_REQUESTED_AT => null,
        ]);

        $actor->setRelation('organizations', collect());

        Mockery::mock('alias:' . GetOrganizationNotifications::class)
            ->shouldReceive('call')
            ->once()
            ->withArgs(fn(Trooper $trooper) => $trooper === $actor)
            ->andReturn(collect());

        Mockery::mock('alias:' . GetTrooperFriends::class)
            ->shouldReceive('call')
            ->once()
            ->withArgs(fn(Trooper $trooper) => $trooper === $actor)
            ->andReturn(collect());

        Mockery::mock('alias:' . GetTrooperMinors::class)
            ->shouldReceive('call')
            ->once()
            ->withArgs(fn(Trooper $trooper) => $trooper === $actor)
            ->andReturn(collect());

        Mockery::mock('alias:' . GetTrooperCostumes::class)
            ->shouldReceive('call')
            ->once()
            ->withArgs(fn(Trooper $trooper) => $trooper === $actor)
            ->andReturn(collect());

        Mockery::mock('alias:' . GetTrooperMemberships::class)
            ->shouldReceive('call')
            ->once()
            ->withArgs(fn(Trooper $trooper) => $trooper === $actor)
            ->andReturn(collect());

        Mockery::mock('alias:' . GetTrooperRequests::class)
            ->shouldReceive('call')
            ->once()
            ->withArgs(fn(Trooper $trooper) => $trooper === $actor)
            ->andReturn(collect());

        Mockery::mock('alias:' . GetOrganizationHierarchy::class)
            ->shouldReceive('call')
            ->twice()
            ->andReturn(collect());

        $subject = new AccountPageData($actor);

        $result = $subject->handle();

        $this->assertSame(42, $result['trooper_id']);
        $this->assertFalse($result['is_visitor']);
        $this->assertTrue($result['is_handler']);
        $this->assertNull($result['deletion_requested_at']);
        $this->assertSame('ada@example.com', $result['email']);
        $this->assertInstanceOf(TrooperDetails::class, $result['details']);

        $this->assertFalse($result['notifications']['is_administrator']);
        $this->assertSame(NotificationFrequency::INSTANT, $result['notifications']['notification_frequency']);
        $this->assertTrue($result['notifications']['push_notifications_enabled']);
        $this->assertIsArray($result['notifications']['notification_preferences']);
        $this->assertNotEmpty($result['notifications']['notification_preferences']);
        $this->assertInstanceOf(OrganizationNotificationCollection::class, $result['notifications']['organization_notifications']);

        $this->assertInstanceOf(TrooperCostumeCollection::class, $result['costumes']);
        $this->assertInstanceOf(TrooperFriendCollection::class, $result['friends']);
        $this->assertInstanceOf(TrooperMinorCollection::class, $result['minors']);

        $this->assertInstanceOf(OrganizationHierarchy::class, $result['memberships']['organizations']);
        $this->assertInstanceOf(OrganizationOptions::class, $result['memberships']['organization_options']);
        $this->assertInstanceOf(TrooperMembershipCollection::class, $result['memberships']['organization_memberships']);
        $this->assertInstanceOf(TrooperRequestCollection::class, $result['memberships']['organization_requests']);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}
