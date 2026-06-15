<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Observers;

use App\Jobs\UpdateEventForumThreadJob;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Observers\EventTrooperObserver;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EventTrooperObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving_assigns_costume_organization_ids_when_costume_changes(): void
    {
        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();
        $event = Event::factory()->withOrganization($organization)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $costume = Costume::factory()->create();

        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $organization->id,
            EventOrganization::CAN_ATTEND => true,
            EventOrganization::TROOPERS_ALLOWED => null,
        ]);

        $organization_costume = OrganizationCostume::factory()
            ->forOrganization($organization)
            ->forCostume($costume)
            ->create();

        TrooperCostume::factory()
            ->forTrooper($trooper)
            ->forOrganizationCostume($organization_costume)
            ->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->create([
                EventTrooper::COSTUME_ID => null,
                EventTrooper::BACKUP_COSTUME_ID => null,
            ]);

        $event_trooper->{EventTrooper::COSTUME_ID} = $costume->id;

        $subject = new EventTrooperObserver;

        $subject->saving($event_trooper);

        $this->assertSame([
            $organization->id,
        ], $event_trooper->{EventTrooper::COSTUME_ORGANIZATION_IDS});
        $this->assertSame([], $event_trooper->{EventTrooper::BACKUP_COSTUME_ORGANIZATION_IDS});
    }

    public function test_saving_assigns_costume_organization_ids_for_handler_costume(): void
    {
        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();
        $event = Event::factory()->withOrganization($organization)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();

        $handler_costume = Costume::factory()->withName(Costume::HANDLER)->create();

        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $organization->id,
            EventOrganization::CAN_ATTEND => true,
            EventOrganization::TROOPERS_ALLOWED => null,
        ]);

        // No OrganizationCostume or TrooperCostume needed — handler credit flows via membership
        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->asMember()
            ->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->create([
                EventTrooper::COSTUME_ID => null,
                EventTrooper::BACKUP_COSTUME_ID => null,
            ]);

        $event_trooper->{EventTrooper::COSTUME_ID} = $handler_costume->id;

        $subject = new EventTrooperObserver;
        $subject->saving($event_trooper);

        $this->assertSame([$organization->id], $event_trooper->{EventTrooper::COSTUME_ORGANIZATION_IDS});
    }

    public function test_saving_assigns_costume_organization_ids_for_no_costume(): void
    {
        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();
        $event = Event::factory()->withOrganization($organization)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();

        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->asMember()
            ->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->create([
                EventTrooper::COSTUME_ID => Costume::factory()->create()->id,
                EventTrooper::BACKUP_COSTUME_ID => null,
            ]);

        $event_trooper->{EventTrooper::COSTUME_ID} = null;
        $event_trooper->{EventTrooper::COSTUME_ORGANIZATION_IDS} = [$organization->id];

        $subject = new EventTrooperObserver;
        $subject->saving($event_trooper);

        $this->assertSame([$organization->id], $event_trooper->{EventTrooper::COSTUME_ORGANIZATION_IDS});
    }

    public function test_saving_strips_non_member_costume_organization_ids_for_no_costume(): void
    {
        $trooper = Trooper::factory()->create();
        $member_org = Organization::factory()->create();
        $non_member_org = Organization::factory()->create();
        $event = Event::factory()->withOrganization($member_org)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();

        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($member_org)
            ->asMember()
            ->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->create([
                EventTrooper::COSTUME_ID => Costume::factory()->create()->id,
                EventTrooper::BACKUP_COSTUME_ID => null,
            ]);

        $event_trooper->{EventTrooper::COSTUME_ID} = null;
        $event_trooper->{EventTrooper::COSTUME_ORGANIZATION_IDS} = [$non_member_org->id];

        $subject = new EventTrooperObserver;
        $subject->saving($event_trooper);

        $this->assertSame([$member_org->id], $event_trooper->{EventTrooper::COSTUME_ORGANIZATION_IDS});
    }

    public function test_saving_preserves_explicit_empty_costume_organization_ids(): void
    {
        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();
        $event = Event::factory()->withOrganization($organization)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();

        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->asMember()
            ->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->create([
                EventTrooper::COSTUME_ID => null,
                EventTrooper::COSTUME_ORGANIZATION_IDS => [$organization->id],
                EventTrooper::BACKUP_COSTUME_ID => null,
            ]);

        $event_trooper->{EventTrooper::COSTUME_ORGANIZATION_IDS} = [];
        $event_trooper->preserve_empty_credit_organization_ids = true;

        $subject = new EventTrooperObserver;
        $subject->saving($event_trooper);

        $this->assertSame([], $event_trooper->{EventTrooper::COSTUME_ORGANIZATION_IDS});
    }

    public function test_saving_preserves_submitted_costume_organization_ids(): void
    {
        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();
        $event = Event::factory()->withOrganization($organization)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $costume = Costume::factory()->create();

        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->asMember()
            ->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->create([
                EventTrooper::COSTUME_ID => null,
                EventTrooper::BACKUP_COSTUME_ID => null,
            ]);

        $event_trooper->{EventTrooper::COSTUME_ID} = $costume->id;
        $event_trooper->{EventTrooper::COSTUME_ORGANIZATION_IDS} = [$organization->id];
        $event_trooper->preserve_empty_credit_organization_ids = true;

        $subject = new EventTrooperObserver;
        $subject->saving($event_trooper);

        $this->assertSame([$organization->id], $event_trooper->{EventTrooper::COSTUME_ORGANIZATION_IDS});
    }

    public function test_created_queues_forum_thread_sync_for_event_roster_changes(): void
    {
        Queue::fake();

        config([
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.api_key' => 'test-key',
        ]);

        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();
        $event = Event::factory()
            ->withOrganization($organization)
            ->withForumThreadEnabled()
            ->withForumThreadId(321)
            ->create([
                Event::POST_ID => 654,
            ]);
        $event_shift = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->create();

        Queue::assertPushed(UpdateEventForumThreadJob::class, function (UpdateEventForumThreadJob $job) use ($event): bool {
            return $job->uniqueId() === 'forum-thread-sync:event:'.$event->id;
        });
    }

    public function test_updated_queues_forum_thread_sync_when_status_changes(): void
    {
        Queue::fake();

        config([
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.api_key' => 'test-key',
        ]);

        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();
        $event = Event::factory()
            ->withOrganization($organization)
            ->withForumThreadEnabled()
            ->withForumThreadId(321)
            ->create([
                Event::POST_ID => 654,
            ]);
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->create();

        Queue::fake();

        $event_trooper->status = 'cancelled';
        $event_trooper->save();

        Queue::assertPushed(UpdateEventForumThreadJob::class, function (UpdateEventForumThreadJob $job) use ($event): bool {
            return $job->uniqueId() === 'forum-thread-sync:event:'.$event->id;
        });
    }

    public function test_created_does_not_queue_forum_thread_sync_when_xenforo_is_not_configured(): void
    {
        Queue::fake();

        config([
            'services.xenforo.base_url' => null,
            'services.xenforo.api_key' => null,
        ]);

        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();
        $event = Event::factory()
            ->withOrganization($organization)
            ->withForumThreadEnabled()
            ->withForumThreadId(321)
            ->create([
                Event::POST_ID => 654,
            ]);
        $event_shift = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->create();

        Queue::assertNothingPushed();
    }

    public function test_saving_preserves_valid_submitted_org_subset(): void
    {
        $trooper = Trooper::factory()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();
        $event = Event::factory()->withOrganization($org1)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $costume = Costume::factory()->create();

        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $org1->id,
            EventOrganization::CAN_ATTEND => true,
            EventOrganization::TROOPERS_ALLOWED => null,
        ]);
        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $org2->id,
            EventOrganization::CAN_ATTEND => true,
            EventOrganization::TROOPERS_ALLOWED => null,
        ]);

        $org_costume1 = OrganizationCostume::factory()->forOrganization($org1)->forCostume($costume)->create();
        $org_costume2 = OrganizationCostume::factory()->forOrganization($org2)->forCostume($costume)->create();

        TrooperCostume::factory()->forTrooper($trooper)->forOrganizationCostume($org_costume1)->create();
        TrooperCostume::factory()->forTrooper($trooper)->forOrganizationCostume($org_costume2)->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->create([EventTrooper::COSTUME_ID => null]);

        $event_trooper->{EventTrooper::COSTUME_ID} = $costume->id;
        $event_trooper->{EventTrooper::COSTUME_ORGANIZATION_IDS} = [$org1->id];

        $subject = new EventTrooperObserver;
        $subject->saving($event_trooper);

        $this->assertSame([$org1->id], $event_trooper->{EventTrooper::COSTUME_ORGANIZATION_IDS});
    }

    public function test_saving_strips_ineligible_orgs_and_defaults_to_all_eligible(): void
    {
        $trooper = Trooper::factory()->create();
        $eligible_org = Organization::factory()->create();
        $ineligible_org = Organization::factory()->create();
        $event = Event::factory()->withOrganization($eligible_org)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $costume = Costume::factory()->create();

        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $eligible_org->id,
            EventOrganization::CAN_ATTEND => true,
            EventOrganization::TROOPERS_ALLOWED => null,
        ]);
        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $ineligible_org->id,
            EventOrganization::CAN_ATTEND => true,
            EventOrganization::TROOPERS_ALLOWED => null,
        ]);

        $org_costume = OrganizationCostume::factory()->forOrganization($eligible_org)->forCostume($costume)->create();
        TrooperCostume::factory()->forTrooper($trooper)->forOrganizationCostume($org_costume)->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->create([EventTrooper::COSTUME_ID => null]);

        $event_trooper->{EventTrooper::COSTUME_ID} = $costume->id;
        $event_trooper->{EventTrooper::COSTUME_ORGANIZATION_IDS} = [$ineligible_org->id];

        $subject = new EventTrooperObserver;
        $subject->saving($event_trooper);

        $this->assertSame([$eligible_org->id], $event_trooper->{EventTrooper::COSTUME_ORGANIZATION_IDS});
    }

    public function test_saving_defaults_to_all_eligible_when_no_orgs_submitted(): void
    {
        $trooper = Trooper::factory()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();
        $event = Event::factory()->withOrganization($org1)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $costume = Costume::factory()->create();

        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $org1->id,
            EventOrganization::CAN_ATTEND => true,
            EventOrganization::TROOPERS_ALLOWED => null,
        ]);
        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $org2->id,
            EventOrganization::CAN_ATTEND => true,
            EventOrganization::TROOPERS_ALLOWED => null,
        ]);

        $org_costume1 = OrganizationCostume::factory()->forOrganization($org1)->forCostume($costume)->create();
        $org_costume2 = OrganizationCostume::factory()->forOrganization($org2)->forCostume($costume)->create();

        TrooperCostume::factory()->forTrooper($trooper)->forOrganizationCostume($org_costume1)->create();
        TrooperCostume::factory()->forTrooper($trooper)->forOrganizationCostume($org_costume2)->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->create([EventTrooper::COSTUME_ID => null]);

        $event_trooper->{EventTrooper::COSTUME_ID} = $costume->id;
        $event_trooper->{EventTrooper::COSTUME_ORGANIZATION_IDS} = [];

        $subject = new EventTrooperObserver;
        $subject->saving($event_trooper);

        $this->assertEqualsCanonicalizing(
            [$org1->id, $org2->id],
            $event_trooper->{EventTrooper::COSTUME_ORGANIZATION_IDS}
        );
    }

    public function test_saving_allows_reselecting_previously_removed_org(): void
    {
        $trooper = Trooper::factory()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();
        $event = Event::factory()->withOrganization($org1)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $costume = Costume::factory()->create();

        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $org1->id,
            EventOrganization::CAN_ATTEND => true,
            EventOrganization::TROOPERS_ALLOWED => null,
        ]);
        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $org2->id,
            EventOrganization::CAN_ATTEND => true,
            EventOrganization::TROOPERS_ALLOWED => null,
        ]);

        $org_costume1 = OrganizationCostume::factory()->forOrganization($org1)->forCostume($costume)->create();
        $org_costume2 = OrganizationCostume::factory()->forOrganization($org2)->forCostume($costume)->create();

        TrooperCostume::factory()->forTrooper($trooper)->forOrganizationCostume($org_costume1)->create();
        TrooperCostume::factory()->forTrooper($trooper)->forOrganizationCostume($org_costume2)->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->create([
                EventTrooper::COSTUME_ID => null,
                EventTrooper::IS_HANDLER => false,
            ]);

        // Simulate DB state after a prior save that set costume + only org1 credited.
        // Using syncOriginal() avoids triggering the observer again so the costume_id
        // is treated as already saved (not dirty), matching the "same costume, update
        // org selection" scenario we're testing.
        $event_trooper->costume_id = $costume->id;
        $event_trooper->costume_organization_ids = [$org1->id];
        $event_trooper->syncOriginal();

        // Same costume (not dirty), re-select both orgs
        $event_trooper->costume_organization_ids = [$org1->id, $org2->id];

        $subject = new EventTrooperObserver;
        $subject->saving($event_trooper);

        $this->assertEqualsCanonicalizing(
            [$org1->id, $org2->id],
            $event_trooper->{EventTrooper::COSTUME_ORGANIZATION_IDS}
        );
    }

    public function test_saving_clears_costume_organization_ids_when_costume_cleared(): void
    {
        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();
        $event = Event::factory()->withOrganization($organization)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $costume = Costume::factory()->create();

        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $organization->id,
            EventOrganization::CAN_ATTEND => true,
            EventOrganization::TROOPERS_ALLOWED => null,
        ]);

        $org_costume = OrganizationCostume::factory()->forOrganization($organization)->forCostume($costume)->create();
        TrooperCostume::factory()->forTrooper($trooper)->forOrganizationCostume($org_costume)->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->create([
                EventTrooper::COSTUME_ID => $costume->id,
                EventTrooper::COSTUME_ORGANIZATION_IDS => [$organization->id],
            ]);

        $event_trooper->{EventTrooper::COSTUME_ID} = null;

        $subject = new EventTrooperObserver;
        $subject->saving($event_trooper);

        $this->assertSame([], $event_trooper->{EventTrooper::COSTUME_ORGANIZATION_IDS});
    }
}
