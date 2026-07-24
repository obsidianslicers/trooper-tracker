<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Enums\AchievementType;
use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Enums\NotificationFrequency;
use App\Enums\TrooperTheme;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\EventWatch;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAchievement;
use App\Models\TrooperAssignment;
use App\Models\TrooperOrganization;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrooperTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_can_create_trooper(): void
    {
        $subject = Trooper::factory()->create();

        $this->assertInstanceOf(Trooper::class, $subject);
        $this->assertDatabaseHas($subject->getTable(), [
            'id' => $subject->getKey(),
        ]);
    }

    public function test_get_is_administrator_attribute_returns_true_when_active_and_admin(): void
    {
        $subject = Trooper::factory()->asAdministrator()->create();

        $this->assertTrue($subject->is_administrator);
    }

    public function test_get_is_administrator_attribute_returns_false_when_not_active(): void
    {
        $subject = Trooper::factory()
            ->state([Trooper::MEMBERSHIP_STATUS => MembershipStatus::PENDING])
            ->state([Trooper::MEMBERSHIP_ROLE => MembershipRole::ADMINISTRATOR])
            ->create();

        $this->assertFalse($subject->is_administrator);
    }

    public function test_get_is_administrator_attribute_returns_false_when_not_admin(): void
    {
        $subject = Trooper::factory()->asMember()->create();

        $this->assertFalse($subject->is_administrator);
    }

    public function test_get_is_moderator_attribute_returns_true_when_active_and_moderator(): void
    {
        $subject = Trooper::factory()->asModerator()->create();

        $this->assertTrue($subject->is_moderator);
    }

    public function test_get_is_moderator_attribute_returns_false_when_not_active(): void
    {
        $subject = Trooper::factory()
            ->state([Trooper::MEMBERSHIP_STATUS => MembershipStatus::RETIRED])
            ->state([Trooper::MEMBERSHIP_ROLE => MembershipRole::MODERATOR])
            ->create();

        $this->assertFalse($subject->is_moderator);
    }

    public function test_get_is_handler_attribute_returns_true_when_active_and_handler(): void
    {
        $subject = Trooper::factory()
            ->asActive()
            ->state([Trooper::MEMBERSHIP_ROLE => MembershipRole::HANDLER])
            ->create();

        $this->assertTrue($subject->is_handler);
    }

    public function test_get_is_handler_attribute_returns_false_when_not_handler(): void
    {
        $subject = Trooper::factory()->asMember()->create();

        $this->assertFalse($subject->is_handler);
    }

    public function test_get_is_member_attribute_returns_true_when_active_and_member(): void
    {
        $subject = Trooper::factory()->asMember()->create();

        $this->assertTrue($subject->is_member);
    }

    public function test_get_is_member_attribute_returns_false_when_not_active(): void
    {
        $subject = Trooper::factory()
            ->asRetired()
            ->state([Trooper::MEMBERSHIP_ROLE => MembershipRole::MEMBER])
            ->create();

        $this->assertFalse($subject->is_member);
    }

    public function test_get_is_active_attribute_returns_true_when_status_active(): void
    {
        $subject = Trooper::factory()->asActive()->create();

        $this->assertTrue($subject->is_active);
    }

    public function test_get_is_active_attribute_returns_false_when_status_not_active(): void
    {
        $subject = Trooper::factory()->asPending()->create();

        $this->assertFalse($subject->is_active);
    }

    public function test_get_is_denied_attribute_returns_true_when_status_denied(): void
    {
        $subject = Trooper::factory()
            ->state([Trooper::MEMBERSHIP_STATUS => MembershipStatus::DENIED])
            ->create();

        $this->assertTrue($subject->is_denied);
    }

    public function test_get_is_denied_attribute_returns_false_when_status_not_denied(): void
    {
        $subject = Trooper::factory()->asActive()->create();

        $this->assertFalse($subject->is_denied);
    }

    public function test_get_is_pending_attribute_returns_true_when_status_pending(): void
    {
        $subject = Trooper::factory()->asPending()->create();

        $this->assertTrue($subject->is_pending);
    }

    public function test_get_is_visitor_attribute_returns_true_when_role_visitor(): void
    {
        $subject = Trooper::factory()->asVisitor()->create();

        $this->assertTrue($subject->is_visitor);
    }

    public function test_get_is_visitor_attribute_returns_false_when_role_not_visitor(): void
    {
        $subject = Trooper::factory()->asMember()->create();

        $this->assertFalse($subject->is_visitor);
    }

    public function test_get_visitor_access_expired_attribute_returns_true_for_expired_visitor(): void
    {
        $subject = Trooper::factory()
            ->asVisitor()
            ->state([Trooper::VISITOR_EXPIRES_AT => now()->subDay()])
            ->create();

        $this->assertTrue($subject->visitor_access_expired);
    }

    public function test_get_visitor_access_expired_attribute_returns_false_when_not_expired(): void
    {
        $subject = Trooper::factory()
            ->asVisitor()
            ->state([Trooper::VISITOR_EXPIRES_AT => now()->addDay()])
            ->create();

        $this->assertFalse($subject->visitor_access_expired);
    }

    public function test_get_is_minor_attribute_returns_true_when_trooper_is_under_eighteen(): void
    {
        $subject = Trooper::factory()
            ->state([Trooper::DATE_OF_BIRTH => now()->subYears(17)])
            ->create();

        $this->assertTrue($subject->is_minor);
    }

    public function test_get_is_minor_attribute_returns_false_when_date_of_birth_is_missing(): void
    {
        $subject = Trooper::factory()
            ->state([Trooper::DATE_OF_BIRTH => null])
            ->create();

        $this->assertFalse($subject->is_minor);
    }

    public function test_get_is_adult_attribute_returns_true_when_not_minor(): void
    {
        $subject = Trooper::factory()
            ->state([Trooper::DATE_OF_BIRTH => now()->subYears(20)])
            ->create();

        $this->assertTrue($subject->is_adult);
    }

    public function test_get_is_adult_attribute_returns_false_when_minor(): void
    {
        $subject = Trooper::factory()
            ->state([Trooper::DATE_OF_BIRTH => now()->subYears(16)])
            ->create();

        $this->assertFalse($subject->is_adult);
    }

    public function test_get_audit_label_returns_formatted_string(): void
    {
        $subject = Trooper::factory()
            ->withDisplayName('John Doe')
            ->withEmail('john@example.com')
            ->create();

        $result = $subject->getAuditLabel();

        $this->assertSame('John Doe (john@example.com)', $result);
    }

    public function test_has_active_organization_status_returns_true_when_member_assignment_exists(): void
    {
        $subject = Trooper::factory()->create();
        $organization = Organization::factory()->create();
        TrooperAssignment::factory()
            ->forTrooper($subject)
            ->forOrganization($organization)
            ->asMember()
            ->create();

        $result = $subject->hasActiveOrganizationStatus();

        $this->assertTrue($result);
    }

    public function test_has_active_organization_status_returns_false_when_no_member_assignment(): void
    {
        $subject = Trooper::factory()->create();
        $organization = Organization::factory()->create();
        TrooperAssignment::factory()
            ->forTrooper($subject)
            ->forOrganization($organization)
            ->state([TrooperAssignment::IS_MEMBER => false])
            ->create();

        $result = $subject->hasActiveOrganizationStatus();

        $this->assertFalse($result);
    }

    public function test_is_moderator_for_organization_returns_true_when_administrator(): void
    {
        $subject = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();

        $result = $subject->isModeratorForOrganization($organization);

        $this->assertTrue($result);
    }

    public function test_is_moderator_for_organization_returns_true_when_moderator_with_assignment(): void
    {
        $subject = Trooper::factory()->asModerator()->create();
        $organization = Organization::factory()->create();
        TrooperAssignment::factory()
            ->forTrooper($subject)
            ->forOrganization($organization)
            ->asModerator()
            ->create();

        $result = $subject->isModeratorForOrganization($organization);

        $this->assertTrue($result);
    }

    public function test_is_moderator_for_organization_returns_true_when_moderator_for_parent_organization(): void
    {
        $subject = Trooper::factory()->asModerator()->create();
        $parent = Organization::factory()->create();
        $child = Organization::factory()->withParent($parent)->create()->fresh();
        TrooperAssignment::factory()
            ->forTrooper($subject)
            ->forOrganization($parent)
            ->asModerator()
            ->create();

        $result = $subject->isModeratorForOrganization($child);

        $this->assertTrue($result);
    }

    public function test_is_moderator_for_organization_returns_false_when_moderator_without_assignment(): void
    {
        $subject = Trooper::factory()->asModerator()->create();
        $organization = Organization::factory()->create();

        $result = $subject->isModeratorForOrganization($organization);

        $this->assertFalse($result);
    }

    public function test_is_moderator_for_organization_returns_false_when_member(): void
    {
        $subject = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->create();

        $result = $subject->isModeratorForOrganization($organization);

        $this->assertFalse($result);
    }

    public function test_resolve_moderator_org_ids_returns_null_for_administrator(): void
    {
        $subject = Trooper::factory()->asAdministrator()->create();

        $result = $subject->resolveModeratorOrgIds();

        $this->assertNull($result);
    }

    public function test_resolve_moderator_org_ids_returns_assignment_org_ids_for_moderator(): void
    {
        $subject = Trooper::factory()->asModerator()->create();
        $organization_a = Organization::factory()->create();
        $organization_b = Organization::factory()->create();

        TrooperAssignment::factory()
            ->forTrooper($subject)
            ->forOrganization($organization_a)
            ->asModerator()
            ->create();

        TrooperAssignment::factory()
            ->forTrooper($subject)
            ->forOrganization($organization_b)
            ->asModerator()
            ->create();

        $result = $subject->resolveModeratorOrgIds();

        $this->assertNotNull($result);
        $this->assertEqualsCanonicalizing([
            $organization_a->{Organization::ID},
            $organization_b->{Organization::ID},
        ], $result);
    }

    public function test_email_appears_valid_returns_true_for_valid_email(): void
    {
        $subject = Trooper::factory()->withEmail('valid@example.com')->create();

        $result = $subject->emailAppearsValid();

        $this->assertTrue($result);
    }

    public function test_email_appears_valid_returns_false_for_invalid_email(): void
    {
        $subject = Trooper::factory()->withInvalidEmail()->create();

        $result = $subject->emailAppearsValid();

        $this->assertFalse($result);
    }

    public function test_wants_notification_returns_true_when_preference_is_missing(): void
    {
        $subject = Trooper::factory()->create();

        $result = $subject->wantsNotification('events', 'mail');

        $this->assertTrue($result);
    }

    public function test_wants_notification_returns_false_when_channel_is_disabled(): void
    {
        $subject = Trooper::factory()
            ->state([
                Trooper::NOTIFICATION_PREFERENCES => [
                    'events' => [
                        'mail' => false,
                    ],
                ],
            ])
            ->create();

        $result = $subject->wantsNotification('events', 'mail');

        $this->assertFalse($result);
    }

    public function test_get_trooper_achievement_returns_achievement_when_exists(): void
    {
        $subject = Trooper::factory()->create();
        $achievement = TrooperAchievement::factory()
            ->state([
                TrooperAchievement::TROOPER_ID => $subject->{Trooper::ID},
                TrooperAchievement::TYPE => AchievementType::FIRST_TROOP,
            ])
            ->create();

        $result = $subject->getTrooperAchievement(AchievementType::FIRST_TROOP);

        $this->assertNotNull($result);
        $this->assertSame($achievement->{TrooperAchievement::ID}, $result->{TrooperAchievement::ID});
    }

    public function test_get_trooper_achievement_returns_null_when_not_exists(): void
    {
        $subject = Trooper::factory()->create();

        $result = $subject->getTrooperAchievement(AchievementType::FIRST_TROOP);

        $this->assertNull($result);
    }

    public function test_get_achievement_value_returns_value_when_exists(): void
    {
        $subject = Trooper::factory()->create();
        TrooperAchievement::factory()
            ->state([
                TrooperAchievement::TROOPER_ID => $subject->{Trooper::ID},
                TrooperAchievement::TYPE => AchievementType::TROOPER_SHIFTS,
                TrooperAchievement::VALUE => 42,
            ])
            ->create();

        $result = $subject->getAchievementValue(AchievementType::TROOPER_SHIFTS);

        $this->assertSame(42, $result);
    }

    public function test_get_achievement_value_returns_null_when_not_exists(): void
    {
        $subject = Trooper::factory()->create();

        $result = $subject->getAchievementValue(AchievementType::TROOPER_SHIFTS);

        $this->assertNull($result);
    }

    public function test_get_title_by_trooper_rank_returns_recruit_when_no_shifts(): void
    {
        $subject = Trooper::factory()->create();

        $result = $subject->getTitleByTrooperRank();

        $this->assertSame('Recruit', $result);
    }

    public function test_get_title_by_trooper_rank_returns_recruit_when_less_than_two_shifts(): void
    {
        $subject = Trooper::factory()->create();
        TrooperAchievement::factory()
            ->state([
                TrooperAchievement::TROOPER_ID => $subject->{Trooper::ID},
                TrooperAchievement::TYPE => AchievementType::TROOPER_SHIFTS,
                TrooperAchievement::VALUE => 1,
            ])
            ->create();

        $result = $subject->getTitleByTrooperRank();

        $this->assertSame('Recruit', $result);
    }

    public function test_get_title_by_trooper_rank_returns_grand_moff_when_rank_one(): void
    {
        $subject = Trooper::factory()->create();
        TrooperAchievement::factory()
            ->state([
                TrooperAchievement::TROOPER_ID => $subject->{Trooper::ID},
                TrooperAchievement::TYPE => AchievementType::TROOPER_SHIFTS,
                TrooperAchievement::VALUE => 10,
            ])
            ->create();
        TrooperAchievement::factory()
            ->state([
                TrooperAchievement::TROOPER_ID => $subject->{Trooper::ID},
                TrooperAchievement::TYPE => AchievementType::TROOPER_RANK,
                TrooperAchievement::VALUE => 1,
            ])
            ->create();

        $result = $subject->getTitleByTrooperRank();

        $this->assertSame('Grand Moff', $result);
    }

    public function test_get_title_by_trooper_rank_returns_moff_when_rank_two(): void
    {
        $subject = Trooper::factory()->create();
        TrooperAchievement::factory()
            ->state([
                TrooperAchievement::TROOPER_ID => $subject->{Trooper::ID},
                TrooperAchievement::TYPE => AchievementType::TROOPER_SHIFTS,
                TrooperAchievement::VALUE => 10,
            ])
            ->create();
        TrooperAchievement::factory()
            ->state([
                TrooperAchievement::TROOPER_ID => $subject->{Trooper::ID},
                TrooperAchievement::TYPE => AchievementType::TROOPER_RANK,
                TrooperAchievement::VALUE => 2,
            ])
            ->create();

        $result = $subject->getTitleByTrooperRank();

        $this->assertSame('Moff', $result);
    }

    public function test_get_title_by_trooper_rank_returns_captain_when_rank_thirty_two(): void
    {
        $subject = Trooper::factory()->create();
        TrooperAchievement::factory()
            ->state([
                TrooperAchievement::TROOPER_ID => $subject->{Trooper::ID},
                TrooperAchievement::TYPE => AchievementType::TROOPER_SHIFTS,
                TrooperAchievement::VALUE => 50,
            ])
            ->create();
        TrooperAchievement::factory()
            ->state([
                TrooperAchievement::TROOPER_ID => $subject->{Trooper::ID},
                TrooperAchievement::TYPE => AchievementType::TROOPER_RANK,
                TrooperAchievement::VALUE => 32,
            ])
            ->create();

        $result = $subject->getTitleByTrooperRank();

        $this->assertSame('Captain', $result);
    }

    public function test_get_title_by_trooper_rank_returns_cadet_for_clone_theme_without_shifts(): void
    {
        $subject = Trooper::factory()
            ->state([Trooper::THEME => TrooperTheme::CLONE])
            ->create();

        $result = $subject->getTitleByTrooperRank();

        $this->assertSame('Cadet', $result);
    }

    public function test_get_title_by_trooper_rank_returns_supreme_commander_for_clone_rank_one(): void
    {
        $subject = Trooper::factory()
            ->state([Trooper::THEME => TrooperTheme::CLONE])
            ->create();

        TrooperAchievement::factory()
            ->state([
                TrooperAchievement::TROOPER_ID => $subject->{Trooper::ID},
                TrooperAchievement::TYPE => AchievementType::TROOPER_SHIFTS,
                TrooperAchievement::VALUE => 10,
            ])
            ->create();

        TrooperAchievement::factory()
            ->state([
                TrooperAchievement::TROOPER_ID => $subject->{Trooper::ID},
                TrooperAchievement::TYPE => AchievementType::TROOPER_RANK,
                TrooperAchievement::VALUE => 1,
            ])
            ->create();

        $result = $subject->getTitleByTrooperRank();

        $this->assertSame('Supreme Commander', $result);
    }

    public function test_get_rank_theme_returns_danger_for_command_ranks(): void
    {
        $subject = Trooper::factory()->create();
        TrooperAchievement::factory()
            ->state([
                TrooperAchievement::TROOPER_ID => $subject->{Trooper::ID},
                TrooperAchievement::TYPE => AchievementType::TROOPER_RANK,
                TrooperAchievement::VALUE => 3,
            ])
            ->create();

        $result = $subject->getRankTheme();

        $this->assertSame('danger', $result);
    }

    public function test_get_rank_theme_returns_primary_for_operations_ranks(): void
    {
        $subject = Trooper::factory()->create();
        TrooperAchievement::factory()
            ->state([
                TrooperAchievement::TROOPER_ID => $subject->{Trooper::ID},
                TrooperAchievement::TYPE => AchievementType::TROOPER_RANK,
                TrooperAchievement::VALUE => 32,
            ])
            ->create();

        $result = $subject->getRankTheme();

        $this->assertSame('primary', $result);
    }

    public function test_get_rank_theme_returns_info_for_tactical_ranks(): void
    {
        $subject = Trooper::factory()->create();
        TrooperAchievement::factory()
            ->state([
                TrooperAchievement::TROOPER_ID => $subject->{Trooper::ID},
                TrooperAchievement::TYPE => AchievementType::TROOPER_RANK,
                TrooperAchievement::VALUE => 100,
            ])
            ->create();

        $result = $subject->getRankTheme();

        $this->assertSame('info', $result);
    }

    public function test_get_rank_theme_returns_secondary_for_low_ranks(): void
    {
        $subject = Trooper::factory()->create();
        TrooperAchievement::factory()
            ->state([
                TrooperAchievement::TROOPER_ID => $subject->{Trooper::ID},
                TrooperAchievement::TYPE => AchievementType::TROOPER_RANK,
                TrooperAchievement::VALUE => 500,
            ])
            ->create();

        $result = $subject->getRankTheme();

        $this->assertSame('secondary', $result);
    }

    public function test_email_cast_converts_to_lowercase(): void
    {
        $subject = Trooper::factory()
            ->state([Trooper::EMAIL => 'TEST@EXAMPLE.COM'])
            ->create();

        $this->assertSame('test@example.com', $subject->{Trooper::EMAIL});
    }

    public function test_notification_frequency_cast_works(): void
    {
        $subject = Trooper::factory()
            ->withNotificationFrequency(NotificationFrequency::DAILY)
            ->create();

        $this->assertInstanceOf(NotificationFrequency::class, $subject->{Trooper::NOTIFICATION_FREQUENCY});
        $this->assertSame(NotificationFrequency::DAILY, $subject->{Trooper::NOTIFICATION_FREQUENCY});
    }

    public function test_membership_status_cast_works(): void
    {
        $subject = Trooper::factory()->asActive()->create();

        $this->assertInstanceOf(MembershipStatus::class, $subject->{Trooper::MEMBERSHIP_STATUS});
        $this->assertSame(MembershipStatus::ACTIVE, $subject->{Trooper::MEMBERSHIP_STATUS});
    }

    public function test_membership_role_cast_works(): void
    {
        $subject = Trooper::factory()->asAdministrator()->create();

        $this->assertInstanceOf(MembershipRole::class, $subject->{Trooper::MEMBERSHIP_ROLE});
        $this->assertSame(MembershipRole::ADMINISTRATOR, $subject->{Trooper::MEMBERSHIP_ROLE});
    }

    public function test_eligible_orgs_for_event_returns_empty_when_event_has_no_can_attend_orgs(): void
    {
        $subject = Trooper::factory()->create();
        $organization = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($subject)->forOrganization($organization)->asMember()->create();
        $event = Event::factory()->withOrganization($organization)->create();

        $result = $subject->eligibleOrgsForEvent($event);

        $this->assertCount(0, $result);
    }

    public function test_eligible_orgs_for_event_returns_primary_org_when_member_matches_can_attend(): void
    {
        $subject = Trooper::factory()->create();
        $organization = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($subject)->forOrganization($organization)->asMember()->create();
        $event = Event::factory()->withOrganization($organization)->create();
        EventOrganization::factory()
            ->state([
                EventOrganization::EVENT_ID => $event->id,
                EventOrganization::ORGANIZATION_ID => $organization->id,
                EventOrganization::CAN_ATTEND => true,
            ])
            ->create();

        $result = $subject->eligibleOrgsForEvent($event);

        $this->assertCount(1, $result);
        $this->assertSame($organization->id, $result->first()->id);
    }

    public function test_eligible_orgs_for_event_uses_primary_club_from_child_org(): void
    {
        $subject = Trooper::factory()->create();
        $parent_org = Organization::factory()->create();
        $child_org = Organization::factory()->withParent($parent_org)->create();
        TrooperAssignment::factory()->forTrooper($subject)->forOrganization($child_org)->asMember()->create();
        $event = Event::factory()->withOrganization($parent_org)->create();
        EventOrganization::factory()
            ->state([
                EventOrganization::EVENT_ID => $event->id,
                EventOrganization::ORGANIZATION_ID => $parent_org->id,
                EventOrganization::CAN_ATTEND => true,
            ])
            ->create();

        $result = $subject->eligibleOrgsForEvent($event);

        $this->assertCount(1, $result);
        $this->assertSame($parent_org->id, $result->first()->id);
    }

    public function test_eligible_orgs_for_event_deduplicates_multiple_assignments_under_same_primary(): void
    {
        $subject = Trooper::factory()->create();
        $parent_org = Organization::factory()->create();
        $unit_a = Organization::factory()->withParent($parent_org)->create();
        $unit_b = Organization::factory()->withParent($parent_org)->create();
        TrooperAssignment::factory()->forTrooper($subject)->forOrganization($unit_a)->asMember()->create();
        TrooperAssignment::factory()->forTrooper($subject)->forOrganization($unit_b)->asMember()->create();
        $event = Event::factory()->withOrganization($parent_org)->create();
        EventOrganization::factory()
            ->state([
                EventOrganization::EVENT_ID => $event->id,
                EventOrganization::ORGANIZATION_ID => $parent_org->id,
                EventOrganization::CAN_ATTEND => true,
            ])
            ->create();

        $result = $subject->eligibleOrgsForEvent($event);

        $this->assertCount(1, $result);
    }

    public function test_eligible_orgs_for_event_excludes_orgs_trooper_is_not_member_of(): void
    {
        $subject = Trooper::factory()->create();
        $org_a = Organization::factory()->create();
        $org_b = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($subject)->forOrganization($org_a)->asMember()->create();
        $event = Event::factory()->withOrganization($org_b)->create();
        EventOrganization::factory()
            ->state([
                EventOrganization::EVENT_ID => $event->id,
                EventOrganization::ORGANIZATION_ID => $org_b->id,
                EventOrganization::CAN_ATTEND => true,
            ])
            ->create();

        $result = $subject->eligibleOrgsForEvent($event);

        $this->assertCount(0, $result);
    }

    public function test_eligible_orgs_for_event_returns_empty_when_trooper_has_no_assignments(): void
    {
        $subject = Trooper::factory()->create();
        $organization = Organization::factory()->create();
        $event = Event::factory()->withOrganization($organization)->create();

        EventOrganization::factory()
            ->state([
                EventOrganization::EVENT_ID => $event->id,
                EventOrganization::ORGANIZATION_ID => $organization->id,
                EventOrganization::CAN_ATTEND => true,
            ])
            ->create();

        $result = $subject->eligibleOrgsForEvent($event);

        $this->assertCount(0, $result);
    }

    public function test_event_watches_relation_returns_trooper_event_watches(): void
    {
        $subject = Trooper::factory()->create();
        $event = Event::factory()->create();

        EventWatch::factory()->forTrooper($subject)->forEvent($event)->create();

        $result = $subject->event_watches()->get();

        $this->assertCount(1, $result);
    }

    public function test_guardian_alias_returns_guardian_relationship(): void
    {
        $guardian = Trooper::factory()->create();
        $subject = Trooper::factory()->withGuardian($guardian)->create();

        $result = $subject->guardian()->first();

        $this->assertNotNull($result);
        $this->assertSame($guardian->{Trooper::ID}, $result->{Trooper::ID});
    }

    public function test_minors_alias_returns_children_relationship(): void
    {
        $guardian = Trooper::factory()->create();
        $minor = Trooper::factory()->withGuardian($guardian)->create();

        $result = $guardian->minors()->pluck(Trooper::ID)->all();

        $this->assertContains($minor->{Trooper::ID}, $result);
    }

    public function test_notifications_relation_is_morph_many_with_latest_order(): void
    {
        $subject = Trooper::factory()->create();

        $relation = $subject->notifications();
        $query = $relation->getQuery()->toBase();
        $orders = $query->orders ?? [];

        $this->assertInstanceOf(MorphMany::class, $relation);
        $this->assertSame('desc', $orders[0]['direction'] ?? null);
    }

    public function test_unread_notifications_relation_filters_by_null_read_at(): void
    {
        $subject = Trooper::factory()->create();

        $relation = $subject->unreadNotifications();
        $query = $relation->getQuery()->toBase();
        $wheres = $query->wheres ?? [];

        $has_null_read_at = collect($wheres)->contains(
            fn(array $where): bool => ($where['type'] ?? null) === 'Null'
            && ($where['column'] ?? null) === 'read_at'
        );

        $this->assertInstanceOf(MorphMany::class, $relation);
        $this->assertTrue($has_null_read_at);
    }

    public function test_get_has_guardian_required_membership_attribute_returns_true_when_org_requires_guardian(): void
    {
        $subject = Trooper::factory()->create();
        $organization = Organization::factory()
            ->state([Organization::REQUIRES_GUARDIAN => true])
            ->create();

        TrooperOrganization::factory()
            ->forTrooper($subject)
            ->forOrganization($organization)
            ->create();

        $this->assertTrue($subject->has_guardian_required_membership);
    }

    public function test_get_has_guardian_required_membership_attribute_returns_false_when_no_required_org(): void
    {
        $subject = Trooper::factory()->create();
        $organization = Organization::factory()
            ->state([Organization::REQUIRES_GUARDIAN => false])
            ->create();

        TrooperOrganization::factory()
            ->forTrooper($subject)
            ->forOrganization($organization)
            ->create();

        $this->assertFalse($subject->has_guardian_required_membership);
    }

    public function test_theme_cast_works(): void
    {
        $subject = Trooper::factory()
            ->state([Trooper::THEME => TrooperTheme::STORMTROOPER])
            ->create();

        $this->assertInstanceOf(TrooperTheme::class, $subject->{Trooper::THEME});
        $this->assertSame(TrooperTheme::STORMTROOPER, $subject->{Trooper::THEME});
    }
}