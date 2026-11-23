<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\PageData\Admin;

use App\Messages\Events\PageData\Admin\CreateEventPageData;
use App\Models\EventType;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateEventPageDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_opens_a_draft_with_the_logistics_questions_on_yes(): void
    {
        $actor = Trooper::factory()->asSystemOwner()->create();

        $subject = new CreateEventPageData($actor);
        $form = $this->payload($subject)['form'];

        $this->assertNull($form['organization_id']);
        $this->assertSame('draft', $form['status']);
        $this->assertSame(EventType::idNamed('Regular'), $form['event_type_id']);
        $this->assertNull($form['name']);
        foreach ([
            'tentative_signups_allowed', 'secure_staging_area', 'parking_available',
            'allow_blasters', 'allow_props', 'accessible',
        ] as $flag)
        {
            $this->assertTrue($form[$flag], $flag);
        }
        $this->assertFalse($form['require_mission_brief_ack']);
    }

    public function test_handle_starts_with_the_host_it_was_opened_for(): void
    {
        $actor = Trooper::factory()->asSystemOwner()->create();
        $organization = Organization::factory()->asOrganization()->create();

        $subject = new CreateEventPageData($actor, $organization->id);

        $this->assertSame($organization->id, $this->payload($subject)['form']['organization_id']);
    }

    public function test_handle_refuses_a_host_the_trooper_cannot_host_for(): void
    {
        $actor = Trooper::factory()->create();
        $organization = Organization::factory()->asOrganization()->create();

        $subject = new CreateEventPageData($actor, $organization->id);

        $this->expectException(ModelNotFoundException::class);
        $subject->handle();
    }

    public function test_handle_lists_hosts_indented_with_their_club(): void
    {
        $actor = Trooper::factory()->asSystemOwner()->create();
        $club = Organization::factory()->asOrganization()->withName('E2E Club')->create();
        $garrison = Organization::factory()->asRegion()->withParent($club)
            ->withName('E2E Garrison')->create();

        $subject = new CreateEventPageData($actor);
        $hosts = collect($this->payload($subject)['host_options'])->keyBy('value');

        $this->assertSame(['value' => $club->id, 'label' => 'E2E Club', 'club' => 'E2E Club'], $hosts[$club->id]);
        $this->assertSame('— E2E Garrison', $hosts[$garrison->id]['label']);
        $this->assertSame('E2E Club', $hosts[$garrison->id]['club']);
    }

    public function test_handle_opens_the_clubs_set_to_attend_by_default(): void
    {
        $actor = Trooper::factory()->asSystemOwner()->create();
        $attends = Organization::factory()->asOrganization()->create(['can_attend_default' => true]);
        $stays = Organization::factory()->asOrganization()->create(['can_attend_default' => false]);

        $subject = new CreateEventPageData($actor);
        $organizations = collect($this->payload($subject)['organizations'])->keyBy('id');

        $this->assertTrue($organizations[$attends->id]['can_attend']);
        $this->assertFalse($organizations[$stays->id]['can_attend']);
        $this->assertNull($organizations[$attends->id]['troopers_allowed']);
    }

    public function test_handle_offers_only_the_types_on_the_form_in_order(): void
    {
        $actor = Trooper::factory()->asSystemOwner()->create();
        EventType::query()->update([EventType::SORT_ORDER => 50]);
        EventType::where(EventType::NAME, 'Wedding')->update([EventType::SORT_ORDER => 1]);
        EventType::where(EventType::NAME, 'Disney')->update([EventType::IS_ACTIVE => false]);

        $subject = new CreateEventPageData($actor);
        $types = collect($this->payload($subject)['event_types']);

        $this->assertSame('Wedding', $types->first()['label']);
        $this->assertNotContains('Disney', $types->pluck('label'));
    }

    /**
     * The props as the page receives them, with every Resource resolved.
     *
     * @return array<string, mixed>
     */
    private function payload(CreateEventPageData $subject): array
    {
        return json_decode(json_encode($subject->handle()), true);
    }
}
