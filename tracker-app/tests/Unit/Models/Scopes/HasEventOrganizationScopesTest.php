<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Scopes;

use App\Models\EventOrganization;
use App\Models\EventShift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HasEventOrganizationScopesTest extends TestCase
{
    use RefreshDatabase;

    public function test_pluck_can_attend_returns_allowed_organization_ids(): void
    {
        $shift = EventShift::factory()->create();

        $allowed = EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $shift->event_id,
            EventOrganization::CAN_ATTEND => true,
            EventOrganization::TROOPERS_ALLOWED => null,
        ]);

        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $shift->event_id,
            EventOrganization::CAN_ATTEND => false,
            EventOrganization::TROOPERS_ALLOWED => null,
        ]);

        $result = EventOrganization::query()
            ->where(EventOrganization::EVENT_ID, $shift->event_id)
            ->pluckCanAttend($shift)
            ->all();

        $this->assertContains($allowed->organization_id, $result);
        $this->assertCount(1, $result);
    }
}