<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins\Xenforo;

use App\Exceptions\ForumGroupUpdateException;
use App\Messages\Troopers\Commands\Permissions\UpdateTrooperForumGroups;
use App\Models\OauthLogin;
use App\Models\Trooper;
use App\Plugins\PluginForumGroups;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use TroopTracker\Plugins\Xenforo\Auth\XenforoLoginProvider;

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
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.api_key' => 'key',
        ]);

        $this->trooper = Trooper::factory()->asMember()->create();

        OauthLogin::factory()->forTrooper($this->trooper)->create([
            OauthLogin::PROVIDER => XenforoLoginProvider::KEY,
            OauthLogin::PROVIDER_ID => '77',
        ]);
    }

    public function test_handle_skips_forum_when_nothing_changes(): void
    {
        Http::fake([
            'https://xf.test/api/users/77' => Http::response(
                ['user' => ['secondary_group_ids' => [5, 20]]],
                200
            ),
        ]);

        $subject = new UpdateTrooperForumGroups($this->trooper, group_ids_to_add: [20]);
        $subject->handle(app(PluginForumGroups::class));

        Http::assertSentCount(1);
    }

    public function test_handle_clears_last_group_with_empty_value(): void
    {
        Http::fake([
            'https://xf.test/api/users/77' => Http::sequence()
                ->push(['user' => ['secondary_group_ids' => '9']], 200)
                ->push(['success' => true], 200),
        ]);

        $subject = new UpdateTrooperForumGroups($this->trooper, group_ids_to_remove: [9]);
        $subject->handle(app(PluginForumGroups::class));

        Http::assertSent(fn ($request): bool => $request->method() === 'POST'
            && $request['secondary_group_ids'] === '');
    }

    public function test_handle_throws_when_forum_user_cannot_be_read(): void
    {
        Http::fake([
            'https://xf.test/api/users/77' => Http::response([], 404),
        ]);

        $subject = new UpdateTrooperForumGroups($this->trooper, group_ids_to_add: [20]);

        $this->expectException(ForumGroupUpdateException::class);

        $subject->handle(app(PluginForumGroups::class));
    }

    public function test_handle_does_nothing_without_groups(): void
    {
        Http::fake();

        $subject = new UpdateTrooperForumGroups(Trooper::factory()->create());
        $subject->handle(app(PluginForumGroups::class));

        Http::assertNothingSent();
    }
}
