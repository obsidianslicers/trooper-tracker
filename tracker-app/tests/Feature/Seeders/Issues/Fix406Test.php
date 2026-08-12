<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders\Issues;

use App\Bus\MagicBus;
use App\Enums\EventTrooperStatus;
use App\Mail\Fix406OutstandingCredit;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Database\Seeders\Issues\Fix406;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class Fix406Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    public function test_resolves_single_eligible_club(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org)->asMember()->create();

        $event_shift = EventShift::factory()->forEvent(Event::factory()->create())->create();
        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->create([
                EventTrooper::STATUS => EventTrooperStatus::ATTENDED,
                EventTrooper::COSTUME_ID => null,
                EventTrooper::COSTUME_ORGANIZATION_IDS => null,
                EventTrooper::ORGANIZATION_ID => null,
            ]);

        $subject = new Fix406;
        $subject->run(app(MagicBus::class));

        $event_trooper->refresh();
        $this->assertSame([$org->id], $event_trooper->costume_organization_ids);
        Mail::assertNothingQueued();
    }

    public function test_resolves_multi_club_by_crediting_all_eligible_clubs(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org1)->asMember()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org2)->asMember()->create();

        $event_shift = EventShift::factory()->forEvent(Event::factory()->create())->create();
        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->create([
                EventTrooper::STATUS => EventTrooperStatus::ATTENDED,
                EventTrooper::COSTUME_ID => null,
                EventTrooper::COSTUME_ORGANIZATION_IDS => null,
                EventTrooper::ORGANIZATION_ID => null,
            ]);

        $subject = new Fix406;
        $subject->run(app(MagicBus::class));

        $event_trooper->refresh();
        $this->assertEqualsCanonicalizing([$org1->id, $org2->id], $event_trooper->costume_organization_ids);
        Mail::assertNothingQueued();
    }

    public function test_skips_when_no_eligible_org(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        $event_shift = EventShift::factory()->forEvent(Event::factory()->create())->create();
        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->create([
                EventTrooper::STATUS => EventTrooperStatus::ATTENDED,
                EventTrooper::COSTUME_ID => null,
                EventTrooper::COSTUME_ORGANIZATION_IDS => null,
                EventTrooper::ORGANIZATION_ID => null,
            ]);

        $subject = new Fix406;
        $subject->run(app(MagicBus::class));

        $event_trooper->refresh();
        $this->assertNull($event_trooper->costume_organization_ids);
    }

    public function test_ignores_rows_that_already_have_a_credit_source(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $org = Organization::factory()->create();
        $other_org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($other_org)->asMember()->create();

        $event_shift = EventShift::factory()->forEvent(Event::factory()->create())->create();
        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->create([
                EventTrooper::STATUS => EventTrooperStatus::ATTENDED,
                EventTrooper::COSTUME_ID => null,
                EventTrooper::COSTUME_ORGANIZATION_IDS => [$org->id],
                EventTrooper::ORGANIZATION_ID => null,
            ]);

        $subject = new Fix406;
        $subject->run(app(MagicBus::class));

        $event_trooper->refresh();
        $this->assertSame([$org->id], $event_trooper->costume_organization_ids);
        Mail::assertNothingQueued();
    }

    public function test_emails_administrators_with_outstanding_rows_when_no_eligible_org(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();

        $event = Event::factory()->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->create([
                EventTrooper::STATUS => EventTrooperStatus::ATTENDED,
                EventTrooper::COSTUME_ID => null,
                EventTrooper::COSTUME_ORGANIZATION_IDS => null,
                EventTrooper::ORGANIZATION_ID => null,
            ]);

        $subject = new Fix406;
        $subject->run(app(MagicBus::class));

        Mail::assertQueued(Fix406OutstandingCredit::class, function (Fix406OutstandingCredit $mail) use ($admin): bool {
            return $mail->hasTo($admin->email);
        });

        Mail::assertQueued(Fix406OutstandingCredit::class, function (Fix406OutstandingCredit $mail) use ($event_trooper): bool {
            $rendered = $mail->render();

            return str_contains($rendered, (string) $event_trooper->id);
        });
    }

    public function test_does_not_email_administrators_when_nothing_outstanding(): void
    {
        Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org)->asMember()->create();

        $event_shift = EventShift::factory()->forEvent(Event::factory()->create())->create();
        EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->create([
                EventTrooper::STATUS => EventTrooperStatus::ATTENDED,
                EventTrooper::COSTUME_ID => null,
                EventTrooper::COSTUME_ORGANIZATION_IDS => null,
                EventTrooper::ORGANIZATION_ID => null,
            ]);

        $subject = new Fix406;
        $subject->run(app(MagicBus::class));

        Mail::assertNothingQueued();
    }
}
