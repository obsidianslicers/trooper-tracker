<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Notices\Queries;

use App\Features\Notices\Queries\GetTrooperNoticesQuery;
use App\Features\Notices\Queries\GetTrooperNoticesQueryHandler;
use App\Models\Notice;
use App\Models\NoticeTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetTrooperNoticesQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_global_notices_visible_to_any_trooper(): void
    {
        $trooper = Trooper::factory()->asMember()->create();

        $global_notice = Notice::factory()->asGlobal()->asActive()->withTitle('Global Notice')->create();

        $subject = new GetTrooperNoticesQueryHandler();

        $result = $subject(new GetTrooperNoticesQuery($trooper));

        $this->assertCount(1, $result);
        $this->assertSame('Global Notice', $result->first()->title);
    }

    public function test_invoke_returns_organization_notices_for_member_troopers(): void
    {
        $trooper = Trooper::factory()->asMember()->create();

        $org = Organization::factory()->asOrganization()->withNodePath('100.')->create();

        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($org)
            ->asMember()
            ->create();

        $org_notice = Notice::factory()
            ->withOrganization($org)
            ->asActive()
            ->withTitle('Org Notice')
            ->create();

        $subject = new GetTrooperNoticesQueryHandler();

        $result = $subject(new GetTrooperNoticesQuery($trooper));

        $this->assertCount(1, $result);
        $this->assertSame('Org Notice', $result->first()->title);
    }

    public function test_invoke_excludes_notices_from_non_member_organizations(): void
    {
        $trooper = Trooper::factory()->asMember()->create();

        $member_org = Organization::factory()->asOrganization()->withNodePath('100.')->create();
        $outside_org = Organization::factory()->asOrganization()->withNodePath('900.')->create();

        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($member_org)
            ->asMember()
            ->create();

        Notice::factory()->withOrganization($member_org)->asActive()->withTitle('Member Org')->create();
        Notice::factory()->withOrganization($outside_org)->asActive()->withTitle('Outside Org')->create();

        $subject = new GetTrooperNoticesQueryHandler();

        $result = $subject(new GetTrooperNoticesQuery($trooper));

        $this->assertCount(1, $result);
        $this->assertSame('Member Org', $result->first()->title);
    }

    public function test_invoke_filters_to_unread_notices_only(): void
    {
        $trooper = Trooper::factory()->asMember()->create();

        $unread_notice = Notice::factory()->asGlobal()->asActive()->withTitle('Unread Notice')->create();
        $read_notice = Notice::factory()->asGlobal()->asActive()->withTitle('Read Notice')->create();

        NoticeTrooper::factory()
            ->forNotice($read_notice)
            ->forTrooper($trooper)
            ->asRead()
            ->create();

        $subject = new GetTrooperNoticesQueryHandler();

        $result = $subject(new GetTrooperNoticesQuery($trooper));

        $this->assertCount(1, $result);
        $this->assertSame('Unread Notice', $result->first()->title);
    }

    public function test_invoke_returns_notices_sorted_by_starts_at(): void
    {
        $trooper = Trooper::factory()->asMember()->create();

        Notice::factory()->asGlobal()->asActive()->withTitle('Notice B')->create(['starts_at' => now()->subHours(2)]);
        Notice::factory()->asGlobal()->asActive()->withTitle('Notice A')->create(['starts_at' => now()->subHours(3)]);
        Notice::factory()->asGlobal()->asActive()->withTitle('Notice C')->create(['starts_at' => now()->subHours(1)]);

        $subject = new GetTrooperNoticesQueryHandler();

        $result = $subject(new GetTrooperNoticesQuery($trooper));

        $this->assertSame(['Notice A', 'Notice B', 'Notice C'], $result->pluck(Notice::TITLE)->all());
    }
}
