<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\Tests;

use App\Exceptions\PermissionApprovalException;
use App\Models\OauthLogin;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use TroopTracker\Plugins\Xenforo\Auth\XenforoLoginProvider;
use TroopTracker\Plugins\Xenforo\ForumGroups\XenforoForumGroups;

class UpdateForumGroupsTest extends TestCase
{
    use RefreshDatabase;
    use UsesXenforoPlugin;

    private Trooper $trooper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->enableXenforo();

        config([
            'plugins.xenforo.base_url' => 'https://xf.test',
            'plugins.xenforo.api_key' => 'key',
        ]);

        $this->trooper = Trooper::factory()->asMember()->create();

        OauthLogin::factory()->forTrooper($this->trooper)->create([
            OauthLogin::PROVIDER => XenforoLoginProvider::KEY,
            OauthLogin::PROVIDER_ID => '77',
        ]);
    }

    public function test_update_groups_skips_forum_when_nothing_changes(): void
    {
        Http::fake([
            'https://xf.test/api/users/77' => Http::response(
                ['user' => ['secondary_group_ids' => [5, 20]]],
                200
            ),
        ]);

        app(XenforoForumGroups::class)->updateGroups($this->trooper, [20], []);

        Http::assertSentCount(1);
    }

    public function test_update_groups_clears_last_group_with_empty_value(): void
    {
        Http::fake([
            'https://xf.test/api/users/77' => Http::sequence()
                ->push(['user' => ['secondary_group_ids' => '9']], 200)
                ->push(['success' => true], 200),
        ]);

        app(XenforoForumGroups::class)->updateGroups($this->trooper, [], [9]);

        Http::assertSent(fn ($request): bool => $request->method() === 'POST'
            && $request['secondary_group_ids'] === '');
    }

    public function test_update_groups_throws_when_forum_user_cannot_be_read(): void
    {
        Http::fake([
            'https://xf.test/api/users/77' => Http::response([], 404),
        ]);

        $this->expectException(PermissionApprovalException::class);

        app(XenforoForumGroups::class)->updateGroups($this->trooper, [20], []);
    }

    public function test_update_groups_does_nothing_without_groups(): void
    {
        Http::fake();

        app(XenforoForumGroups::class)->updateGroups(Trooper::factory()->create(), [], []);

        Http::assertNothingSent();
    }
}
