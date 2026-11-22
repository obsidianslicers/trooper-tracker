<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\PageData\Reviews;

use App\Messages\Troopers\PageData\Reviews\CostumeApprovalsPageData;
use App\Messages\Troopers\PageData\Reviews\TroopVerificationsPageData;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\Concerns\BuildsCostumeReviews;
use Tests\TestCase;

class ReviewQueuePageDataTest extends TestCase
{
    use BuildsCostumeReviews;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpClubs();
    }

    public function test_costume_approvals_lists_what_the_moderator_may_approve(): void
    {
        $moderator = $this->moderatorOf($this->garrison);
        $trooper = $this->memberOf($this->garrison);
        $this->costumeFor($trooper, $this->club);

        $rows = $this->rows((new CostumeApprovalsPageData($moderator))->handle()['trooper_costumes']);

        $this->assertCount(1, $rows);
        $this->assertSame(['id' => $trooper->id, 'name' => $trooper->display_name], $rows[0]['trooper']);
        $this->assertSame('Emperor Palpatine', $rows[0]['costume']);
        $this->assertSame($this->club->name, $rows[0]['club']);
    }

    public function test_troop_verifications_say_when_no_costume_was_picked(): void
    {
        $moderator = $this->moderatorOf($this->garrison);
        $without = $this->awaiting($this->memberOf($this->garrison), attending_without_costume: true);
        $unpicked = $this->awaiting($this->memberOf($this->garrison));

        $rows = collect($this->rows((new TroopVerificationsPageData($moderator))->handle()['event_troopers']))
            ->keyBy('id');

        $this->assertSame('No costume', $rows[$without->id]['costume']);
        $this->assertSame('None picked', $rows[$unpicked->id]['costume']);
        $this->assertFalse($rows[$unpicked->id]['has_costume']);
        $this->assertSame($this->garrison->id, $rows[$unpicked->id]['clubs'][0]['id']);
        $this->assertNotNull($rows[$unpicked->id]['event']);
    }

    /** @return array<int, array<string, mixed>> */
    private function rows(mixed $collection): array
    {
        return json_decode(json_encode($collection->toArray(Request::create('/'))), true);
    }

    private function awaiting(Trooper $trooper, bool $attending_without_costume = false): EventTrooper
    {
        $event = Event::factory()->withOrganization($this->club)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $event_trooper = EventTrooper::factory()->forEventShift($event_shift)->forTrooper($trooper)->asGoing()->create([
            EventTrooper::COSTUME_ID => null,
            EventTrooper::IS_ATTENDING_WITHOUT_COSTUME => $attending_without_costume,
        ]);

        $event_trooper->requestVerification([$this->garrison->id]);
        $event_trooper->save();

        return $event_trooper;
    }
}
