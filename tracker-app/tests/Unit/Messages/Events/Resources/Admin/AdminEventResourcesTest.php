<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\Resources\Admin;

use App\Messages\Events\Resources\Admin\AdminEventListOrganization;
use App\Messages\Events\Resources\Admin\ChoiceOptions;
use App\Messages\Events\Resources\Admin\EventOrganizationLimitCollection;
use App\Messages\Events\Resources\Admin\HostOptionCollection;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class AdminEventResourcesTest extends TestCase
{
    use RefreshDatabase;

    public function test_organization_limits_start_a_new_event_at_each_clubs_default(): void
    {
        $attends = Organization::factory()->asOrganization()->create([Organization::CAN_ATTEND_DEFAULT => true]);
        $stays = Organization::factory()->asOrganization()->create([Organization::CAN_ATTEND_DEFAULT => false]);

        $subject = new EventOrganizationLimitCollection(collect([$attends, $stays]));

        $rows = collect($subject->toArray(new Request))->keyBy('id');
        $this->assertTrue($rows[$attends->id]['can_attend']);
        $this->assertFalse($rows[$stays->id]['can_attend']);
        $this->assertNull($rows[$attends->id]['troopers_allowed']);
    }

    public function test_organization_limits_use_the_events_own_limits(): void
    {
        $organization = Organization::factory()->asOrganization()->create([Organization::CAN_ATTEND_DEFAULT => false]);
        $other = Organization::factory()->asOrganization()->create([Organization::CAN_ATTEND_DEFAULT => true]);
        $event = Event::factory()->create();
        $event->organizations()->attach($organization->id, [
            EventOrganization::CAN_ATTEND => true,
            EventOrganization::TROOPERS_ALLOWED => 4,
            EventOrganization::HANDLERS_ALLOWED => 1,
        ]);

        $subject = new EventOrganizationLimitCollection(collect([$organization, $other]), $event->organizations()->get());

        $rows = collect($subject->toArray(new Request))->keyBy('id');
        $this->assertSame(
            ['can_attend' => true, 'troopers_allowed' => 4, 'handlers_allowed' => 1],
            collect($rows[$organization->id])->only(['can_attend', 'troopers_allowed', 'handlers_allowed'])->all()
        );
        $this->assertFalse($rows[$other->id]['can_attend']);
    }

    public function test_host_options_name_the_club_only_when_asked(): void
    {
        $organization = Organization::factory()->asOrganization()->create();

        $without = (new HostOptionCollection(collect([$organization])))->toArray(new Request);
        $with = (new HostOptionCollection(collect([$organization]), with_club: true))->toArray(new Request);

        $this->assertSame(['value', 'label'], array_keys($without[0]));
        $this->assertSame($organization->name, $with[0]['club']);
    }

    public function test_choice_options_keep_the_order_given(): void
    {
        $subject = new ChoiceOptions(['b' => 'Bravo', 'a' => 'Alpha']);

        $this->assertSame(
            [['value' => 'b', 'label' => 'Bravo'], ['value' => 'a', 'label' => 'Alpha']],
            $subject->toArray(new Request)
        );
    }

    public function test_list_organization_sends_only_its_name(): void
    {
        $organization = Organization::factory()->asOrganization()->create();

        $subject = new AdminEventListOrganization($organization);

        $this->assertSame(['name' => $organization->name], $subject->toArray(new Request));
    }
}
