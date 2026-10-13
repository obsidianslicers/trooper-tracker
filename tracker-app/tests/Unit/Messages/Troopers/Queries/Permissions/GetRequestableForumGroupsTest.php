<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Queries\Permissions;

use App\Messages\Troopers\Queries\Permissions\GetRequestableForumGroups;
use App\Models\Organization;
use App\Services\Forums\XenforoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GetRequestableForumGroupsTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_empty_when_xenforo_not_configured(): void
    {
        config(['services.xenforo.base_url' => '', 'services.xenforo.api_key' => null]);
        Http::fake();

        $subject = new GetRequestableForumGroups;

        $this->assertTrue($subject->handle(new XenforoService)->isEmpty());

        Http::assertNothingSent();
    }

    public function test_handle_excludes_groups_managed_by_organizations(): void
    {
        config([
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.api_key' => 'key',
        ]);

        Organization::factory()->asOrganization()->withNodePath('100:')->create([
            Organization::XENFORO_GROUP_ACTIVE_ID => 30,
        ]);

        Http::fake([
            'https://xf.test/index.php?api/user-groups' => Http::response([
                'userGroups' => [
                    ['groupID' => 30, 'title' => 'Garrison Active'],
                    ['groupID' => 20, 'title' => 'Event Coordinators'],
                    ['groupID' => 4, 'title' => 'Moderating'],
                ],
            ], 200),
        ]);

        $subject = new GetRequestableForumGroups;
        $result = $subject->handle(new XenforoService);

        $this->assertSame([
            ['id' => 20, 'title' => 'Event Coordinators', 'banner_image_url' => null],
            ['id' => 4, 'title' => 'Moderating', 'banner_image_url' => null],
        ], $result->all());
    }

    public function test_handle_does_not_cache_empty_result(): void
    {
        config([
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.api_key' => 'key',
        ]);

        Http::fakeSequence('https://xf.test/index.php?api/user-groups')
            ->push(['userGroups' => []], 200)
            ->push(['userGroups' => [['groupID' => 20, 'title' => 'Event Coordinators']]], 200);

        $subject = new GetRequestableForumGroups;

        $this->assertTrue($subject->handle(new XenforoService)->isEmpty());
        $this->assertSame([20], $subject->handle(new XenforoService)->pluck('id')->all());
    }

    public function test_handle_excludes_groups_granted_by_user_upgrades(): void
    {
        config([
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.api_key' => 'key',
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

        $subject = new GetRequestableForumGroups;

        $this->assertSame([20], $subject->handle(new XenforoService)->pluck('id')->all());
    }

    public function test_handle_returns_groups_when_upgrade_lookup_fails(): void
    {
        config([
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.api_key' => 'key',
        ]);

        Http::fake([
            'https://xf.test/index.php?api/upgrade-stats' => Http::response([], 500),
            'https://xf.test/index.php?api/user-groups' => Http::response([
                'userGroups' => [['groupID' => 20, 'title' => 'Event Coordinators']],
            ], 200),
        ]);

        $subject = new GetRequestableForumGroups;

        $this->assertSame([20], $subject->handle(new XenforoService)->pluck('id')->all());
    }

    public function test_handle_excludes_the_rip_group_managed_by_the_tracker(): void
    {
        config([
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.api_key' => 'key',
            'services.xenforo.rip_group_id' => '77',
        ]);

        Http::fake([
            'https://xf.test/index.php?api/user-groups' => Http::response([
                'userGroups' => [
                    ['groupID' => 77, 'title' => 'Fallen Troopers'],
                    ['groupID' => 20, 'title' => 'Event Coordinators'],
                ],
            ], 200),
        ]);

        $subject = new GetRequestableForumGroups;

        $this->assertSame([20], $subject->handle(new XenforoService)->pluck('id')->all());
    }
}
