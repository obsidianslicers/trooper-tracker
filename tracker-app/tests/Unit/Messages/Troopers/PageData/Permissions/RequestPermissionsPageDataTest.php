<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\PageData\Permissions;

use App\Messages\Troopers\PageData\Permissions\RequestPermissionsPageData;
use App\Messages\Troopers\Queries\Permissions\GetRequestableForumGroups;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class RequestPermissionsPageDataTest extends TestCase
{
    use FakesMessages;
    use RefreshDatabase;

    public function test_handle_lists_forum_groups_when_xenforo_is_configured(): void
    {
        config(['services.xenforo.base_url' => 'https://forums.example.com', 'services.xenforo.api_key' => 'key']);
        $groups = [['id' => 3, 'title' => 'Command Staff']];
        $this->fakeMessages([GetRequestableForumGroups::class => collect($groups)]);

        $result = (new RequestPermissionsPageData(Trooper::factory()->asAdministrator()->create()))->handle();

        $this->assertTrue($result['is_xenforo_configured']);
        $this->assertSame($groups, $result['forum_groups']);
    }

    public function test_handle_skips_forum_groups_without_xenforo(): void
    {
        config(['services.xenforo.base_url' => '', 'services.xenforo.api_key' => null]);
        $messages = $this->fakeMessages([GetRequestableForumGroups::class => collect()]);

        $result = (new RequestPermissionsPageData(Trooper::factory()->asAdministrator()->create()))->handle();

        $this->assertFalse($result['is_xenforo_configured']);
        $this->assertSame([], $result['forum_groups']);
        $messages->assertNotCalled(GetRequestableForumGroups::class);
    }
}
