<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Queries;

use App\Features\Troopers\Queries\GetTrooperServiceRecordQuery;
use App\Features\Troopers\Queries\GetTrooperServiceRecordQueryHandler;
use App\Models\Award;
use App\Models\AwardTrooper;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\EventUpload;
use App\Models\EventUploadTrooper;
use App\Models\Organization;
use App\Models\TrooperAssignment;
use App\Models\Trooper;
use App\Models\TrooperDonation;
use App\Models\TrooperOrganization;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetTrooperServiceRecordQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_full_service_record_payload(): void
    {
        $trooper = Trooper::factory()->asMember()->create();

        $organization = Organization::factory()->asOrganization()->withNodePath('100.')->withName('Alpha')->create();
        TrooperOrganization::factory()->forTrooper($trooper)->forOrganization($organization)->withIdentifier('TK-1')->create();
        $assignment = TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->asMember()
            ->create();

        $event_upload = EventUpload::factory()->create();
        EventUploadTrooper::factory()->forEventUpload($event_upload)->forTrooper($trooper)->create();

        $event = Event::factory()->withOrganization($organization)->create();

        $upcoming_shift = EventShift::factory()
            ->forEvent($event)
            ->withShiftStartsAt(now()->addDays(2))
            ->create();
        EventTrooper::factory()->forEventShift($upcoming_shift)->forTrooper($trooper)->create();

        $recent_shift = EventShift::factory()
            ->forEvent($event)
            ->asClosed()
            ->withShiftStartsAt(now()->subDays(30))
            ->create();
        EventTrooper::factory()->forEventShift($recent_shift)->forTrooper($trooper)->create();

        TrooperDonation::factory()->forTrooper($trooper)->createdAt(Carbon::now()->subDays(10))->create();

        $award = Award::factory()->create();
        AwardTrooper::factory()->forTrooper($trooper)->forAward($award)->create();

        $subject = new GetTrooperServiceRecordQueryHandler();

        $result = $subject(new GetTrooperServiceRecordQuery($trooper->id));

        $this->assertIsArray($result);
        $this->assertArrayHasKey('trooper', $result);
        $this->assertArrayHasKey('trooper_organizations', $result);
        $this->assertArrayHasKey('tagged_uploads', $result);
        $this->assertArrayHasKey('service_summary', $result);
        $this->assertArrayHasKey('upcoming_shifts', $result);
        $this->assertArrayHasKey('recent_shifts', $result);
        $this->assertArrayHasKey('recent_donations', $result);
        $this->assertArrayHasKey('awards', $result);
        $this->assertSame($trooper->id, $result['trooper']->id);
        $this->assertCount(1, $result['trooper_organizations']);
        $this->assertSame(
            $assignment->id,
            $result['trooper_organizations']->first()->assignment->id,
        );
        $this->assertCount(1, $result['tagged_uploads']);
        $this->assertCount(1, $result['recent_donations']);
        $this->assertCount(1, $result['awards']);
    }
}
