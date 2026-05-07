<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use App\Enums\EventTrooperStatus;
use App\Features\Events\Commands\PromoteNextInLineEventTrooperCommand;
use App\Features\Events\Commands\PromoteNextInLineEventTrooperCommandHandler;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use App\Notifications\Events\TrooperPromotedToGoingNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PromoteNextInLineEventTrooperCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;
    private Event $event;
    private EventShift $shift;
    private PromoteNextInLineEventTrooperCommandHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $this->org = Organization::factory()->create();

        $this->event = Event::factory()->create([
            Event::TROOPERS_ALLOWED => null,
            Event::HANDLERS_ALLOWED => null,
            Event::ORGANIZATION_ID  => $this->org->id,
        ]);

        EventOrganization::factory()
            ->forEvent($this->event)
            ->forOrganization($this->org)
            ->canAttend()
            ->create(['troopers_allowed' => 2]);

        $this->shift = EventShift::factory()->forEvent($this->event)->create();

        $this->handler = new PromoteNextInLineEventTrooperCommandHandler();
    }

    private function makeTrooper(EventTrooperStatus $status, Carbon $signed_up_at, ?int $org_id = null): EventTrooper
    {
        $trooper = Trooper::factory()->create();

        return EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($trooper)
            ->withSignedUpAt($signed_up_at)
            ->create([
                EventTrooper::STATUS            => $status,
                EventTrooper::ORGANIZATION_ID   => $org_id,
                EventTrooper::IS_HANDLER        => false,
                EventTrooper::COSTUME_ID        => null,
                EventTrooper::BACKUP_COSTUME_ID => null,
            ]);
    }

    public function test_promotes_next_standby_from_same_org(): void
    {
        $going   = $this->makeTrooper(EventTrooperStatus::GOING,    now()->subMinutes(2), $this->org->id);
        $standby = $this->makeTrooper(EventTrooperStatus::STAND_BY, now()->subMinute(),   $this->org->id);

        ($this->handler)(new PromoteNextInLineEventTrooperCommand($going, false, $this->org->id));

        // Handler only promotes the next-in-line; the caller is responsible for
        // changing the departing trooper's status before invoking this command.
        $this->assertSame(EventTrooperStatus::GOING, $going->fresh()->status);
        $this->assertSame(EventTrooperStatus::GOING, $standby->fresh()->status);

        Notification::assertSentTo($standby->trooper, TrooperPromotedToGoingNotification::class);
    }

    public function test_picks_earliest_standby_when_multiple_exist(): void
    {
        $going    = $this->makeTrooper(EventTrooperStatus::GOING,    now()->subMinutes(5), $this->org->id);
        $standby1 = $this->makeTrooper(EventTrooperStatus::STAND_BY, now()->subMinutes(3), $this->org->id);
        $standby2 = $this->makeTrooper(EventTrooperStatus::STAND_BY, now()->subMinute(),   $this->org->id);

        ($this->handler)(new PromoteNextInLineEventTrooperCommand($going, false, $this->org->id));

        $this->assertSame(EventTrooperStatus::GOING,    $standby1->fresh()->status);
        $this->assertSame(EventTrooperStatus::STAND_BY, $standby2->fresh()->status);

        Notification::assertSentTo($standby1->trooper, TrooperPromotedToGoingNotification::class);
    }

    public function test_does_not_promote_different_org_when_only_org_was_full(): void
    {
        $other_org = Organization::factory()->create();

        $going          = $this->makeTrooper(EventTrooperStatus::GOING,    now()->subMinutes(2), $this->org->id);
        $other_standby  = $this->makeTrooper(EventTrooperStatus::STAND_BY, now()->subMinute(),   $other_org->id);

        // global_was_full = false → should not fall back to cross-org promotion
        ($this->handler)(new PromoteNextInLineEventTrooperCommand($going, false, $this->org->id));

        $this->assertSame(EventTrooperStatus::STAND_BY, $other_standby->fresh()->status);

        Notification::assertNothingSent();
    }

    public function test_falls_back_to_any_standby_when_global_was_full(): void
    {
        $other_org = Organization::factory()->create();

        $going         = $this->makeTrooper(EventTrooperStatus::GOING,    now()->subMinutes(2), $this->org->id);
        $other_standby = $this->makeTrooper(EventTrooperStatus::STAND_BY, now()->subMinute(),   $other_org->id);

        // global_was_full = true → should promote across orgs
        ($this->handler)(new PromoteNextInLineEventTrooperCommand($going, true, $this->org->id));

        $this->assertSame(EventTrooperStatus::GOING, $other_standby->fresh()->status);

        Notification::assertSentTo($other_standby->trooper, TrooperPromotedToGoingNotification::class);
    }

    public function test_does_nothing_when_no_standby_exists(): void
    {
        $going = $this->makeTrooper(EventTrooperStatus::GOING, now()->subMinute(), $this->org->id);

        ($this->handler)(new PromoteNextInLineEventTrooperCommand($going, false, $this->org->id));

        Notification::assertNothingSent();
    }

    public function test_promotes_via_costume_org_ids_when_organization_id_is_null(): void
    {
        $this->skipIfSqlite();

        $going   = $this->makeTrooper(EventTrooperStatus::GOING,    now()->subMinutes(2), $this->org->id);
        $standby = $this->makeTrooper(EventTrooperStatus::STAND_BY, now()->subMinute(),   null);

        // Standby trooper has no explicit org but costume belongs to $this->org
        \Illuminate\Support\Facades\DB::table($standby->getTable())
            ->where('id', $standby->id)
            ->update(['costume_organization_ids' => json_encode([$this->org->id])]);

        ($this->handler)(new PromoteNextInLineEventTrooperCommand($going, false, $this->org->id));

        $this->assertSame(EventTrooperStatus::GOING, $standby->fresh()->status);

        Notification::assertSentTo($standby->trooper, TrooperPromotedToGoingNotification::class);
    }
}
