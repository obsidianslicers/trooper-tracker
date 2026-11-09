<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

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
        $trooper = Trooper::factory()->asAdministrator()->create();

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
        $trooper = Trooper::factory()->asAdministrator()->create();

        $event = $this->actingAs($trooper)
            ->withSession(['_old_input' => ['name' => '', 'allow_blasters' => '0', 'venue' => 'Old Venue']])
            ->get(route('admin.events.create'))
            ->viewData('event');

        $this->assertFalse($event->allow_blasters);
        $this->assertSame('Old Venue', $event->venue);
    }

    public function test_invoke_defaults_can_attend_from_the_organization(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->asOrganization()->create();

        $response = $this->actingAs($trooper)->get(route('admin.events.create'));

        $listed = $response->viewData('organizations')->firstWhere('id', $organization->id);
        $this->assertTrue($listed->pivot->can_attend);
    }

    public function test_invoke_keeps_an_unchecked_can_attend_after_a_failed_submit(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
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

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('admin.events.create'));

        $response->assertRedirect(route('auth.login'));
    }
}
