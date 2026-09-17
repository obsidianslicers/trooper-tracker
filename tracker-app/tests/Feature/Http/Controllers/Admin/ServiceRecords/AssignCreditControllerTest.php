<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\ServiceRecords;

use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignCreditControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_assigns_credit_for_administrator(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $org = $this->makeRootOrganization();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org)->asMember()->create();

        $event_trooper = $this->makeAttendedEventTrooper($trooper);

        $response = $this->actingAs($admin)->post(
            route('admin.service-records.missing-credits.assign', ['event_trooper' => $event_trooper]),
            ['organization_ids' => [$org->id]]
        );

        $response->assertRedirect();
        $this->assertSame([$org->id], $event_trooper->fresh()->costume_organization_ids);
    }

    public function test_invoke_allows_moderator_within_scope(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $org = $this->makeRootOrganization();

        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org)->asMember()->create();

        $event_trooper = $this->makeAttendedEventTrooper($trooper);

        $response = $this->actingAs($moderator)->post(
            route('admin.service-records.missing-credits.assign', ['event_trooper' => $event_trooper]),
            ['organization_ids' => [$org->id]]
        );

        $response->assertRedirect();
        $this->assertSame([$org->id], $event_trooper->fresh()->costume_organization_ids);
    }

    public function test_invoke_forbids_moderator_outside_scope(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $moderator_org = $this->makeRootOrganization();
        $trooper_org = $this->makeRootOrganization();

        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($moderator_org)->asModerator()->create();

        $trooper = Trooper::factory()->asActive()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($trooper_org)->asMember()->create();

        $event_trooper = $this->makeAttendedEventTrooper($trooper);

        $response = $this->actingAs($moderator)->post(
            route('admin.service-records.missing-credits.assign', ['event_trooper' => $event_trooper]),
            ['organization_ids' => [$trooper_org->id]]
        );

        $response->assertForbidden();
    }

    public function test_invoke_requires_organization_ids(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $event_trooper = $this->makeAttendedEventTrooper($trooper);

        $response = $this->actingAs($admin)->post(
            route('admin.service-records.missing-credits.assign', ['event_trooper' => $event_trooper]),
            ['organization_ids' => []]
        );

        $response->assertSessionHasErrors('organization_ids');
    }

    public function test_invoke_requires_authentication(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $event_trooper = $this->makeAttendedEventTrooper($trooper);

        $response = $this->post(
            route('admin.service-records.missing-credits.assign', ['event_trooper' => $event_trooper]),
            ['organization_ids' => [1]]
        );

        $response->assertRedirect(route('auth.login'));
    }

    private function makeRootOrganization(): Organization
    {
        $organization = Organization::factory()->create();
        $organization->update([Organization::NODE_PATH => (string) $organization->id]);

        return $organization->fresh();
    }

    private function makeAttendedEventTrooper(Trooper $trooper): EventTrooper
    {
        $event = Event::factory()->asClosed()->create();
        $shift = EventShift::factory()->forEvent($event)->asClosed()->create();

        return EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($trooper)
            ->asAttended()
            ->create([
                EventTrooper::COSTUME_ID => null,
                EventTrooper::ORGANIZATION_ID => null,
            ]);
    }
}
