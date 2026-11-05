<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\Tests;

use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use TroopTracker\Plugins\Xenforo\ForumGroups\XenforoForumGroups;
use TroopTracker\Plugins\Xenforo\Models\OrganizationForum;

class RequestableForumGroupsTest extends TestCase
{
    use RefreshDatabase;
    use UsesXenforoPlugin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->enableXenforo();
    }

    public function test_requestable_groups_returns_empty_when_xenforo_not_configured(): void
    {
        config(['plugins.xenforo.base_url' => '', 'plugins.xenforo.api_key' => null]);
        Http::fake();

        $subject = app(XenforoForumGroups::class);

        $this->assertTrue($subject->requestableGroups()->isEmpty());

        Http::assertNothingSent();
    }

    public function test_requestable_groups_excludes_groups_managed_by_organizations(): void
    {
        config([
            'plugins.xenforo.base_url' => 'https://xf.test',
            'plugins.xenforo.api_key' => 'key',
        ]);

        OrganizationForum::store(
            Organization::factory()->asOrganization()->withNodePath('100:')->create()->id,
            [OrganizationForum::ACTIVE_GROUP_ID => 30],
        );

        Http::fake([
            'https://xf.test/index.php?api/user-groups' => Http::response([
                'userGroups' => [
                    ['groupID' => 30, 'title' => 'Garrison Active'],
                    ['groupID' => 20, 'title' => 'Event Coordinators'],
                    ['groupID' => 4, 'title' => 'Moderating'],
                ],
            ], 200),
        ]);

        $subject = app(XenforoForumGroups::class);
        $result = $subject->requestableGroups();

        $this->assertSame([
            ['id' => 20, 'title' => 'Event Coordinators', 'banner_image_url' => null],
            ['id' => 4, 'title' => 'Moderating', 'banner_image_url' => null],
        ], $result->all());
    }

    public function test_requestable_groups_does_not_cache_empty_result(): void
    {
        config([
            'plugins.xenforo.base_url' => 'https://xf.test',
            'plugins.xenforo.api_key' => 'key',
        ]);

        Http::fakeSequence('https://xf.test/index.php?api/user-groups')
            ->push(['userGroups' => []], 200)
            ->push(['userGroups' => [['groupID' => 20, 'title' => 'Event Coordinators']]], 200);

        $subject = app(XenforoForumGroups::class);

        $this->assertTrue($subject->requestableGroups()->isEmpty());
        $this->assertSame([20], $subject->requestableGroups()->pluck('id')->all());
    }

    public function test_requestable_groups_excludes_groups_granted_by_user_upgrades(): void
    {
        config([
            'plugins.xenforo.base_url' => 'https://xf.test',
            'plugins.xenforo.api_key' => 'key',
        ]);

        Http::fake([
            'https://xf.test/index.php?api/upgrade-stats' => Http::response([
                'userUpgrades' => [
                    ['user_upgrade_id' => 1, 'extra_group_ids' => '1507'],
                ],
            ], 200),
            'https://xf.test/index.php?api/user-groups' => Http::response([
                'userGroups' => [
                    ['groupID' => 1507, 'title' => 'FLG Supporter'],
                    ['groupID' => 20, 'title' => 'Event Coordinators'],
                ],
            ], 200),
        ]);

        $subject = app(XenforoForumGroups::class);

        $this->assertSame([20], $subject->requestableGroups()->pluck('id')->all());
    }

    public function test_requestable_groups_returns_groups_when_upgrade_lookup_fails(): void
    {
        config([
            'plugins.xenforo.base_url' => 'https://xf.test',
            'plugins.xenforo.api_key' => 'key',
        ]);

        Http::fake([
            'https://xf.test/index.php?api/upgrade-stats' => Http::response([], 500),
            'https://xf.test/index.php?api/user-groups' => Http::response([
                'userGroups' => [['groupID' => 20, 'title' => 'Event Coordinators']],
            ], 200),
        ]);

        $subject = app(XenforoForumGroups::class);

        $this->assertSame([20], $subject->requestableGroups()->pluck('id')->all());
    }
}
