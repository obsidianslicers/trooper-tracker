<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Organizations\PageData;

use App\Messages\Organizations\PageData\UpdateOrganizationPageData;
use App\Models\Organization;
use App\Models\Trooper;
use App\Plugins\PluginClubSynchronizers;
use App\Plugins\PluginSlots;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateOrganizationPageDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_names_the_parent_by_its_type(): void
    {
        $club = Organization::factory()->asOrganization()->withName('501st Legion')->create();
        $region = Organization::factory()->asRegion()->create([Organization::PARENT_ID => $club->id]);

        $result = $this->pageDataFor(Trooper::factory()->asSystemOwner()->create(), $region);

        $this->assertSame('501st Legion', $result['organization']['parent']['name']);
        $this->assertSame('Parent Organization', $result['organization']['parent']['label']);
        $this->assertNull($result['synchronizer_options']);
    }

    public function test_handle_keeps_a_roster_sync_that_is_no_longer_offered(): void
    {
        $club = Organization::factory()->asOrganization()->create([
            Organization::SYNCHRONIZER => 'gone',
        ]);

        $result = $this->pageDataFor(Trooper::factory()->asSystemOwner()->create(), $club);
        $options = collect($result['synchronizer_options']);

        $this->assertSame('', $options->first()['value']);
        $this->assertSame('gone (not available)', $options->firstWhere('value', 'gone')['label']);
    }

    public function test_handle_hides_the_roster_sync_from_a_moderator(): void
    {
        $club = Organization::factory()->asOrganization()->create();
        $moderator = Trooper::factory()->asModerator()->create();

        $this->assertNull($this->pageDataFor($moderator, $club)['synchronizer_options']);
    }

    /** @return array<string, mixed> */
    private function pageDataFor(Trooper $actor, Organization $organization): array
    {
        $subject = new UpdateOrganizationPageData($actor, $organization);

        return $subject->handle(app(PluginClubSynchronizers::class), app(PluginSlots::class));
    }
}
