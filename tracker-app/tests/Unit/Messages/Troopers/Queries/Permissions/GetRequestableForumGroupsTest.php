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
            ['id' => 20, 'title' => 'Event Coordinators'],
            ['id' => 4, 'title' => 'Moderating'],
        ], $result->all());
    }
}
