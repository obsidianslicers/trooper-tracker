<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Bus\MagicBus;
use App\Enums\AchievementType;
use App\Enums\MembershipRole;
use App\Features\Troopers\Queries\GetTroopersByRoleQuery;
use App\Jobs\SendTrooperMilestoneNotificationsJob;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAchievement;
use App\Models\TrooperAssignment;
use App\Notifications\Admin\TrooperMilestoneNotification;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Tests\TestCase;

class SendTrooperMilestoneNotificationsJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_notifies_admins_with_should_notify_for_troopers_org(): void
    {
        Notification::fake();

        $org = Organization::factory()->create();
        $trooper = Trooper::factory()->asMember()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org)->asMember()->create();

        $achievement = TrooperAchievement::factory()->create([
            TrooperAchievement::TROOPER_ID => $trooper->id,
            TrooperAchievement::TYPE => AchievementType::FIRST_TROOP,
        ]);
        $achievement->setRelation('trooper', $trooper);

        $admin_opted_in = Trooper::factory()->asAdministrator()->withEmail('in@example.com')->create();
        TrooperAssignment::factory()
            ->forTrooper($admin_opted_in)
            ->forOrganization($org)
            ->withShouldNotify(true)
            ->create();

        $admin_opted_out = Trooper::factory()->asAdministrator()->withEmail('out@example.com')->create();
        TrooperAssignment::factory()
            ->forTrooper($admin_opted_out)
            ->forOrganization($org)
            ->withShouldNotify(false)
            ->create();

        $bus = Mockery::mock(MagicBus::class);
        $bus->shouldReceive('send')
            ->once()
            ->withArgs(fn (object $query): bool => $query instanceof GetTroopersByRoleQuery
                && $query->membership_role === MembershipRole::ADMINISTRATOR)
            ->andReturn(collect([$admin_opted_in, $admin_opted_out]));

        $bus->shouldReceive('send')
            ->once()
            ->withArgs(fn (object $query): bool => $query instanceof GetTroopersByRoleQuery
                && $query->membership_role === MembershipRole::MODERATOR)
            ->andReturn(collect([]));

        $subject = new SendTrooperMilestoneNotificationsJob;
        $subject->handle($bus);

        Notification::assertSentTo($admin_opted_in, TrooperMilestoneNotification::class);
        Notification::assertNotSentTo($admin_opted_out, TrooperMilestoneNotification::class);
        $this->assertNotNull($achievement->fresh()->notification_sent_at);
    }

    public function test_club_scoped_notification_body_includes_club_context(): void
    {
        $org = Organization::factory()->withName('501st Legion')->create();
        $trooper = Trooper::factory()->asMember()->create();

        $achievement = TrooperAchievement::factory()
            ->forTrooper($trooper)
            ->forOrganization($org)
            ->create([
                TrooperAchievement::TYPE => AchievementType::FIRST_TROOP,
            ]);
        $achievement->setRelation('trooper', $trooper);
        $achievement->setRelation('organization', $org);

        $notification = new TrooperMilestoneNotification(collect([$achievement]));

        $this->assertStringContainsString(
            'Daily Trooper Milestones',
            $notification->toArray($trooper)['title'],
        );
        $this->assertSame($notification->toArray($trooper), $notification->toFcm($trooper));
    }

    public function test_notification_summarizes_multiple_troopers_and_abbreviates_names(): void
    {
        $achievements = collect();

        foreach (range(1, 4) as $number)
        {
            $trooper = Trooper::factory()->create(['display_name' => 'Trooper '.$number]);
            $achievement = TrooperAchievement::factory()->forTrooper($trooper)->create();
            $achievement->setRelation('trooper', $trooper);
            $achievements->push($achievement);
        }

        $notification = new TrooperMilestoneNotification($achievements);
        $data = $notification->toArray($achievements->first()->trooper);

        $this->assertSame(4, $data['trooper_count']);
        $this->assertSame(4, $data['milestone_count']);
        $this->assertSame(route('service-records.achievements'), $data['url']);
        $this->assertSame(
            'Trooper 1, Trooper 2, Trooper 3 and 1 more achieved 4 milestones.',
            $data['body'],
        );
    }

    public function test_handle_notifies_moderators_in_scope_with_should_notify(): void
    {
        Notification::fake();

        $root = Organization::factory()->withNodePath('org.root')->create();
        $unit = Organization::factory()->withParent($root)->withNodePath('org.root.unit')->create();

        $trooper = Trooper::factory()->asMember()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($unit)->asMember()->create();

        $achievement = TrooperAchievement::factory()->create([
            TrooperAchievement::TROOPER_ID => $trooper->id,
            TrooperAchievement::TYPE => AchievementType::FIRST_TROOP,
        ]);
        $achievement->setRelation('trooper', $trooper);

        $mod_in_scope = Trooper::factory()->asModerator()->withEmail('mod@example.com')->create();
        // Authority assignment at parent org level
        TrooperAssignment::factory()
            ->forTrooper($mod_in_scope)
            ->forOrganization($root)
            ->asModerator()
            ->create();
        // Notification subscription at trooper's org level
        TrooperAssignment::factory()
            ->forTrooper($mod_in_scope)
            ->forOrganization($unit)
            ->withShouldNotify(true)
            ->create();

        $mod_out_of_scope = Trooper::factory()->asModerator()->withEmail('other@example.com')->create();

        $bus = Mockery::mock(MagicBus::class);
        $bus->shouldReceive('send')
            ->once()
            ->withArgs(fn (object $query): bool => $query instanceof GetTroopersByRoleQuery
                && $query->membership_role === MembershipRole::ADMINISTRATOR)
            ->andReturn(collect([]));

        $bus->shouldReceive('send')
            ->once()
            ->withArgs(fn (object $query): bool => $query instanceof GetTroopersByRoleQuery
                && $query->membership_role === MembershipRole::MODERATOR)
            ->andReturn(collect([$mod_in_scope, $mod_out_of_scope]));

        $subject = new SendTrooperMilestoneNotificationsJob;
        $subject->handle($bus);

        Notification::assertSentTo($mod_in_scope, TrooperMilestoneNotification::class);
        Notification::assertNotSentTo($mod_out_of_scope, TrooperMilestoneNotification::class);
    }

    public function test_handle_does_not_notify_moderator_in_scope_who_opted_out(): void
    {
        Notification::fake();

        $root = Organization::factory()->withNodePath('org.root')->create();
        $unit = Organization::factory()->withParent($root)->withNodePath('org.root.unit')->create();

        $trooper = Trooper::factory()->asMember()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($unit)->asMember()->create();

        $achievement = TrooperAchievement::factory()->create([
            TrooperAchievement::TROOPER_ID => $trooper->id,
            TrooperAchievement::TYPE => AchievementType::FIRST_TROOP,
        ]);
        $achievement->setRelation('trooper', $trooper);

        $mod_opted_out = Trooper::factory()->asModerator()->withEmail('mod@example.com')->create();
        // Authority assignment at parent org level
        TrooperAssignment::factory()
            ->forTrooper($mod_opted_out)
            ->forOrganization($root)
            ->asModerator()
            ->create();
        // Notification subscription opted out at trooper's org level
        TrooperAssignment::factory()
            ->forTrooper($mod_opted_out)
            ->forOrganization($unit)
            ->withShouldNotify(false)
            ->create();

        $bus = Mockery::mock(MagicBus::class);
        $bus->shouldReceive('send')
            ->once()
            ->withArgs(fn (object $query): bool => $query instanceof GetTroopersByRoleQuery
                && $query->membership_role === MembershipRole::ADMINISTRATOR)
            ->andReturn(collect([]));

        $bus->shouldReceive('send')
            ->once()
            ->withArgs(fn (object $query): bool => $query instanceof GetTroopersByRoleQuery
                && $query->membership_role === MembershipRole::MODERATOR)
            ->andReturn(collect([$mod_opted_out]));

        $subject = new SendTrooperMilestoneNotificationsJob;
        $subject->handle($bus);

        Notification::assertNotSentTo($mod_opted_out, TrooperMilestoneNotification::class);
    }

    public function test_handle_does_nothing_when_trooper_has_no_org_memberships(): void
    {
        Notification::fake();

        $trooper = Trooper::factory()->asMember()->create();

        $achievement = TrooperAchievement::factory()->create([
            TrooperAchievement::TROOPER_ID => $trooper->id,
            TrooperAchievement::TYPE => AchievementType::FIRST_TROOP,
        ]);
        $achievement->setRelation('trooper', $trooper);

        $bus = Mockery::mock(MagicBus::class);
        $bus->shouldReceive('send')->twice()->andReturn(collect());

        $subject = new SendTrooperMilestoneNotificationsJob;
        $subject->handle($bus);

        Notification::assertNothingSent();
        $this->assertNotNull($achievement->fresh()->notification_sent_at);
    }

    public function test_handle_does_not_mark_notification_sent_when_delivery_fails(): void
    {
        $org = Organization::factory()->create();
        $trooper = Trooper::factory()->asMember()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org)->asMember()->create();

        $achievement = TrooperAchievement::factory()->create([
            TrooperAchievement::TROOPER_ID => $trooper->id,
            TrooperAchievement::TYPE => AchievementType::FIRST_TROOP,
        ]);
        $achievement->setRelation('trooper', $trooper);

        $bus = Mockery::mock(MagicBus::class);
        $bus->shouldReceive('send')
            ->once()
            ->withArgs(fn (object $query): bool => $query instanceof GetTroopersByRoleQuery
                && $query->membership_role === MembershipRole::ADMINISTRATOR)
            ->andThrow(new Exception('delivery failed'));

        $subject = new SendTrooperMilestoneNotificationsJob;

        $this->expectException(Exception::class);

        try
        {
            $subject->handle($bus);
        }
        finally
        {
            $this->assertNull($achievement->fresh()->notification_sent_at);
        }
    }
}
