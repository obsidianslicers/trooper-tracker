<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

use App\Models\EventType;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_create_event_page_for_admin(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();

        $response = $this->actingAs($trooper)->get(route('admin.events.create'));

        $response->assertOk();
        $response->assertViewIs('pages.admin.events.create');
    }

    public function test_invoke_opens_the_yes_no_fields_as_before(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();

        $event = $this->actingAs($trooper)->get(route('admin.events.create'))->viewData('event');

        $this->assertTrue($event->tentative_signups_allowed);
        $this->assertTrue($event->secure_staging_area);
        $this->assertTrue($event->allow_blasters);
        $this->assertTrue($event->allow_props);
        $this->assertTrue($event->parking_available);
        $this->assertTrue($event->accessible);
        $this->assertFalse($event->require_mission_brief_ack);
    }

    public function test_invoke_keeps_old_input_over_the_defaults(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();

        $event = $this->actingAs($trooper)
            ->withSession(['_old_input' => ['name' => '', 'allow_blasters' => '0', 'venue' => 'Old Venue']])
            ->get(route('admin.events.create'))
            ->viewData('event');

        $this->assertFalse($event->allow_blasters);
        $this->assertSame('Old Venue', $event->venue);
    }

    public function test_invoke_defaults_can_attend_from_the_organization(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();
        $organization = Organization::factory()->asOrganization()->create();

        $response = $this->actingAs($trooper)->get(route('admin.events.create'));

        $listed = $response->viewData('organizations')->firstWhere('id', $organization->id);
        $this->assertTrue($listed->pivot->can_attend);
    }

    public function test_invoke_keeps_an_unchecked_can_attend_after_a_failed_submit(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();
        $checked = Organization::factory()->asOrganization()->create();
        $unchecked = Organization::factory()->asOrganization()->create();

        //  An unchecked box isn't posted, so the old input only has the checked one.
        $response = $this->actingAs($trooper)
            ->withSession(['_old_input' => [
                'name' => '',
                'organizations' => [$checked->id => ['can_attend' => '1']],
            ]])
            ->get(route('admin.events.create'));

        $organizations = $response->viewData('organizations');
        $this->assertTrue($organizations->firstWhere('id', $checked->id)->pivot->can_attend);
        $this->assertFalse($organizations->firstWhere('id', $unchecked->id)->pivot->can_attend);
    }

    public function test_invoke_lists_offered_types_in_order_with_their_help(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();
        EventType::query()->update([EventType::SORT_ORDER => 50]);
        EventType::where(EventType::SLUG, 'wedding')->update([EventType::SORT_ORDER => 1]);
        EventType::where(EventType::SLUG, 'disney')->update([EventType::IS_ACTIVE => false]);

        $response = $this->actingAs($trooper)->get(route('admin.events.create'));

        $response->assertOk();
        $response->assertSeeInOrder(['value="wedding"', 'value="armorparty"'], false);
        $response->assertDontSee('value="disney"', false);
        $response->assertSee("x-data=\"{ event_type: 'wedding'", false);
        $response->assertDontSee('@js(', false);
    }

    public function test_invoke_preselects_regular_with_the_seeded_types(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();

        $response = $this->actingAs($trooper)->get(route('admin.events.create'));

        $response->assertOk();
        $response->assertSee("x-data=\"{ event_type: 'regular'", false);
        $response->assertSeeInOrder(['value="regular"', 'value="armorparty"'], false);
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('admin.events.create'));

        $response->assertRedirect(route('auth.login'));
    }
}
