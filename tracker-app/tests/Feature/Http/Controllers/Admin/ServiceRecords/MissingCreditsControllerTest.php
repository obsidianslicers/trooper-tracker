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
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MissingCreditsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_page_for_administrator(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();

        $response = $this->actingAs($admin)->get(route('admin.service-records.missing-credits'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page->component('admin/service-records/MissingCredits'));
    }

    public function test_invoke_scopes_rows_to_moderator_authority(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $allowed_org = $this->makeRootOrganization();
        $blocked_org = $this->makeRootOrganization();

        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($allowed_org)->asModerator()->create();

        $in_scope_trooper = Trooper::factory()->asActive()->create();
        TrooperAssignment::factory()->forTrooper($in_scope_trooper)->forOrganization($allowed_org)->asMember()->create();
        $in_scope_event_trooper = $this->makeAttendedEventTrooper($in_scope_trooper);

        $out_of_scope_trooper = Trooper::factory()->asActive()->create();
        TrooperAssignment::factory()->forTrooper($out_of_scope_trooper)->forOrganization($blocked_org)->asMember()->create();
        $this->makeAttendedEventTrooper($out_of_scope_trooper);

        $response = $this->actingAs($moderator)->get(route('admin.service-records.missing-credits'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('admin/service-records/MissingCredits')
            ->where('rows.0.event_trooper_id', $in_scope_event_trooper->id)
            ->has('rows', 1)
        );
    }

    public function test_invoke_forbids_plain_member(): void
    {
        $member = Trooper::factory()->asActive()->create();

        $response = $this->actingAs($member)->get(route('admin.service-records.missing-credits'));

        $response->assertForbidden();
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('admin.service-records.missing-credits'));

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
