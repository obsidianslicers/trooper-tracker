<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Forums;

use App\Enums\OauthProvider;
use App\Models\OauthLogin;
use App\Models\Trooper;
use App\Services\Forums\XenforoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class XenforoServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolve_user_id_for_trooper_returns_null_when_trooper_id_is_null(): void
    {
        $subject = new XenforoService;

        $this->assertNull($subject->resolve_user_id_for_trooper(null));
    }

    public function test_resolve_user_id_for_trooper_returns_provider_id_when_linked(): void
    {
        $trooper = Trooper::factory()->create();

        OauthLogin::factory()->create([
            OauthLogin::TROOPER_ID => $trooper->id,
            OauthLogin::PROVIDER => OauthProvider::XENFORO,
            OauthLogin::PROVIDER_ID => '9876',
        ]);

        $subject = new XenforoService;

        $this->assertSame(9876, $subject->resolve_user_id_for_trooper($trooper->id));
    }

    public function test_create_thread_uses_resolved_user_id_when_not_explicitly_provided(): void
    {
        config([
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.api_key' => 'key-1',
            'services.xenforo.api_user' => '42',
        ]);

        $trooper = Trooper::factory()->create();

        OauthLogin::factory()->create([
            OauthLogin::TROOPER_ID => $trooper->id,
            OauthLogin::PROVIDER => OauthProvider::XENFORO,
            OauthLogin::PROVIDER_ID => '999',
        ]);

        Auth::login($trooper);

        Http::fake([
            'https://xf.test/api/threads' => Http::response(['ok' => true], 201),
        ]);

        $subject = new XenforoService;

        $result = $subject->create_thread(10, 'Thread', 'Body');

        $this->assertSame(201, $result['status']);
        $this->assertSame(['ok' => true], $result['body']);

        Http::assertSent(function ($request)
        {
            return $request->url() === 'https://xf.test/api/threads'
                && $request->header('XF-Api-Key')[0] === 'key-1'
                && $request->header('XF-Api-User')[0] === '999'
                && ($request['node_id'] ?? null) === 10;
        });
    }

    public function test_create_thread_uses_explicit_user_and_prefix_and_extra_fields(): void
    {
        config([
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.api_key' => 'key-2',
            'services.xenforo.api_user' => '42',
        ]);

        Http::fake([
            'https://xf.test/api/threads' => Http::response(['thread_id' => 123], 200),
        ]);

        $subject = new XenforoService;

        $result = $subject->create_thread(11, 'Post', 'Msg', 555, 3, ['custom' => 'value']);

        $this->assertSame(200, $result['status']);
        $this->assertSame(['thread_id' => 123], $result['body']);

        Http::assertSent(function ($request)
        {
            return $request->header('XF-Api-User')[0] === '555'
                && ($request['prefix_id'] ?? null) === 3
                && ($request['custom'] ?? null) === 'value';
        });
    }

    public function test_get_user_groups_returns_payload_for_configured_user(): void
    {
        config([
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.api_key' => 'key-3',
            'services.xenforo.api_user' => '42',
        ]);

        Http::fake([
            '*' => Http::response([
                'userId' => 15802,
                'userGroups' => [
                    [
                        'groupID' => 1,
                        'title' => 'Primary',
                        'bannerText' => '<span>Primary</span>',
                        'order' => 10,
                        'isPrimary' => true,
                    ],
                ],
            ], 200),
        ]);

        $subject = new XenforoService;

        $result = $subject->get_user_groups(15802);

        $this->assertIsArray($result);
        $this->assertSame(15802, $result['userId']);
        $this->assertCount(1, $result['userGroups']);
    }

    public function test_get_all_user_groups_normalizes_and_sorts_groups(): void
    {
        config([
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.api_key' => 'key-3',
        ]);

        Http::fake([
            'https://xf.test/index.php?api/user-groups' => Http::response([
                'userGroups' => [
                    ['groupID' => 4, 'title' => 'Moderating'],
                    ['groupID' => 20, 'title' => 'Event Coordinators'],
                    ['groupID' => 0, 'title' => 'Broken'],
                    'not-a-group',
                ],
            ], 200),
        ]);

        $subject = new XenforoService;

        $this->assertSame([
            ['id' => 20, 'title' => 'Event Coordinators', 'banner_image_url' => null],
            ['id' => 4, 'title' => 'Moderating', 'banner_image_url' => null],
        ], $subject->getAllUserGroups());
    }

    public function test_get_all_user_groups_extracts_banner_image_urls(): void
    {
        config([
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.api_key' => 'key-3',
        ]);

        Http::fake([
            'https://xf.test/index.php?api/user-groups' => Http::response([
                'userGroups' => [
                    $this->bannerGroup(1, 'A Absolute', '<img src="https://cdn.test/a.png">'),
                    $this->bannerGroup(2, 'B Relative', '<img src="data/assets/b.png" />'),
                    $this->bannerGroup(3, 'C Text', '<span class="userBanner">C</span>'),
                    $this->bannerGroup(4, 'D Script', '<img src="javascript:alert(1)">'),
                ],
            ], 200),
        ]);

        $subject = new XenforoService;

        $urls = array_column($subject->getAllUserGroups() ?? [], 'banner_image_url', 'id');

        $this->assertSame([
            1 => 'https://cdn.test/a.png',
            2 => 'https://xf.test/data/assets/b.png',
            3 => null,
            4 => null,
        ], $urls);
    }

    public function test_get_upgrade_group_ids_parses_extra_group_ids(): void
    {
        config([
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.api_key' => 'key-3',
        ]);

        Http::fake([
            'https://xf.test/index.php?api/upgrade-stats' => Http::response([
                'userUpgrades' => [
                    ['user_upgrade_id' => 1, 'extra_group_ids' => '1507'],
                    ['user_upgrade_id' => 2, 'extra_group_ids' => '1507, 1600'],
                    ['user_upgrade_id' => 3, 'extra_group_ids' => [1601]],
                    ['user_upgrade_id' => 4, 'extra_group_ids' => ''],
                ],
            ], 200),
        ]);

        $subject = new XenforoService;

        $this->assertSame([1507, 1600, 1601], $subject->getUpgradeGroupIds());
    }

    public function test_get_user_secondary_group_ids_parses_comma_separated_string(): void
    {
        config([
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.api_key' => 'key-3',
        ]);

        Http::fake([
            '*' => Http::response(['user' => ['secondary_group_ids' => ' 5, 20,,x,5']], 200),
        ]);

        $subject = new XenforoService;

        $this->assertSame([5, 20], $subject->getUserSecondaryGroupIds(77));
    }

    public function test_get_user_secondary_group_ids_returns_null_on_failure(): void
    {
        config([
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.api_key' => 'key-3',
        ]);

        Http::fake(['*' => Http::response([], 500)]);

        $subject = new XenforoService;

        $this->assertNull($subject->getUserSecondaryGroupIds(77));
    }

    public function test_get_user_returns_zero_status_when_connection_fails(): void
    {
        config([
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.api_key' => 'key-3',
        ]);

        Http::fake(['*' => fn () => throw new ConnectionException('cURL error 28: Operation timed out')]);

        $subject = new XenforoService;

        $this->assertSame(['status' => 0, 'body' => null], $subject->get_user(77));
    }

    public function test_update_user_returns_zero_status_when_connection_fails(): void
    {
        config([
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.api_key' => 'key-3',
        ]);

        Http::fake(['*' => fn () => throw new ConnectionException('cURL error 28: Operation timed out')]);

        $subject = new XenforoService;

        $this->assertSame(['status' => 0, 'body' => null], $subject->update_user(77, ['custom_fields[trackerid]' => '1']));
    }

    public function test_get_upgrade_group_ids_returns_null_on_failure(): void
    {
        config([
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.api_key' => 'key-3',
        ]);

        Http::fake(['*' => Http::response([], 500)]);

        $subject = new XenforoService;

        $this->assertNull($subject->getUpgradeGroupIds());
    }

    public function test_get_all_user_groups_returns_null_when_response_has_no_group_list(): void
    {
        config([
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.api_key' => 'key-3',
        ]);

        Http::fake(['*' => Http::response(['userId' => 0], 200)]);

        $subject = new XenforoService;

        $this->assertNull($subject->getAllUserGroups());
    }

    public function test_get_all_user_groups_returns_null_on_failure(): void
    {
        config([
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.api_key' => 'key-3',
        ]);

        Http::fake(['*' => Http::response([], 500)]);

        $subject = new XenforoService;

        $this->assertNull($subject->getAllUserGroups());
    }

    public function test_update_thread_updates_title_for_configured_thread(): void
    {
        config([
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.api_key' => 'key-4',
            'services.xenforo.api_user' => '42',
        ]);

        Http::fake([
            'https://xf.test/api/threads/123' => Http::response(['ok' => true], 200),
        ]);

        $subject = new XenforoService;

        $result = $subject->update_thread(123, 'Updated Event Name');

        $this->assertSame(200, $result['status']);
        $this->assertSame(['ok' => true], $result['body']);

        Http::assertSent(function ($request)
        {
            return $request->url() === 'https://xf.test/api/threads/123'
                && $request->header('XF-Api-Key')[0] === 'key-4'
                && $request->header('XF-Api-User')[0] === '42'
                && ($request['title'] ?? null) === 'Updated Event Name';
        });
    }

    public function test_watch_thread_posts_to_trooper_api_endpoint_with_thread_id_and_email_subscribe(): void
    {
        config([
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.api_key' => 'key-5',
        ]);

        Http::fake(['https://xf.test/api/trooper-api/watch-thread' => Http::response(['success' => true, 'watching' => true, 'email_subscribe' => true], 200)]);

        $subject = new XenforoService;
        $result = $subject->watch_thread(77, 888);

        $this->assertSame(200, $result['status']);

        Http::assertSent(function ($request)
        {
            return $request->url() === 'https://xf.test/api/trooper-api/watch-thread'
                && $request->method() === 'POST'
                && $request->header('XF-Api-Key')[0] === 'key-5'
                && $request->header('XF-Api-User')[0] === '888'
                && ($request['thread_id'] ?? null) == 77
                && ($request['email_subscribe'] ?? null) == true;
        });
    }

    public function test_watch_thread_returns_zero_status_when_not_configured(): void
    {
        config(['services.xenforo.base_url' => '', 'services.xenforo.api_key' => '']);

        Http::fake();

        $subject = new XenforoService;
        $result = $subject->watch_thread(77, 888);

        $this->assertSame(0, $result['status']);
        Http::assertNothingSent();
    }

    public function test_watch_thread_returns_zero_status_for_invalid_ids(): void
    {
        config(['services.xenforo.base_url' => 'https://xf.test', 'services.xenforo.api_key' => 'key']);

        Http::fake();

        $subject = new XenforoService;

        $this->assertSame(0, $subject->watch_thread(0, 888)['status']);
        $this->assertSame(0, $subject->watch_thread(77, 0)['status']);
        Http::assertNothingSent();
    }

    public function test_unwatch_thread_sends_delete_with_thread_id_to_trooper_api_endpoint(): void
    {
        config([
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.api_key' => 'key-6',
        ]);

        Http::fake(['https://xf.test/api/trooper-api/watch-thread' => Http::response(['success' => true, 'watching' => false], 200)]);

        $subject = new XenforoService;
        $result = $subject->unwatch_thread(77, 888);

        $this->assertSame(200, $result['status']);

        Http::assertSent(function ($request)
        {
            return $request->url() === 'https://xf.test/api/trooper-api/watch-thread'
                && $request->method() === 'DELETE'
                && $request->header('XF-Api-Key')[0] === 'key-6'
                && $request->header('XF-Api-User')[0] === '888'
                && ($request['thread_id'] ?? null) == 77;
        });
    }

    public function test_unwatch_thread_returns_zero_status_when_not_configured(): void
    {
        config(['services.xenforo.base_url' => '', 'services.xenforo.api_key' => '']);

        Http::fake();

        $subject = new XenforoService;
        $result = $subject->unwatch_thread(77, 888);

        $this->assertSame(0, $result['status']);
        Http::assertNothingSent();
    }

    public function test_get_thread_posts_fetches_every_page_when_forum_page_size_is_smaller(): void
    {
        config([
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.api_key' => 'key-5',
            'services.xenforo.api_user' => '42',
        ]);

        Cache::put('xenforo_smilies', [], 60);

        Http::fake([
            'https://xf.test/api/threads/46922/posts?page=1*' => Http::response([
                'posts' => $this->threadPosts(1, 20),
                'pagination' => ['current_page' => 1, 'last_page' => 2, 'per_page' => 20],
            ], 200),
            'https://xf.test/api/threads/46922/posts?page=2*' => Http::response([
                'posts' => $this->threadPosts(21, 6),
                'pagination' => ['current_page' => 2, 'last_page' => 2, 'per_page' => 20],
            ], 200),
        ]);

        $subject = new XenforoService;
        $result = $subject->get_thread_posts(46922, exclude_post_id: 1, per_page: 50, max_pages: 20);

        $this->assertCount(25, $result);
        $this->assertSame(26, $result[0]['post_id']);
        Http::assertSentCount(2);
    }

    public function test_get_thread_posts_stops_on_empty_page_without_pagination(): void
    {
        config([
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.api_key' => 'key-6',
        ]);

        Cache::put('xenforo_smilies', [], 60);

        Http::fake([
            'https://xf.test/api/threads/9/posts?page=1*' => Http::response([
                'posts' => $this->threadPosts(1, 3),
            ], 200),
            'https://xf.test/api/threads/9/posts?page=2*' => Http::response(['posts' => []], 200),
        ]);

        $subject = new XenforoService;
        $result = $subject->get_thread_posts(9, max_pages: 5);

        $this->assertCount(3, $result);
        Http::assertSentCount(2);
    }

    public function test_get_thread_posts_renders_attachments_through_signed_tracker_urls(): void
    {
        config([
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.api_key' => 'key-7',
        ]);

        Cache::put('xenforo_smilies', [], 60);

        Http::fake([
            'https://xf.test/api/threads/9/posts?page=1*' => Http::response([
                'posts' => [[
                    'post_id' => 5,
                    'message' => 'Cannot make it.[ATTACH type="full" size="619x721"]7398[/ATTACH]',
                    'post_date' => 1_700_000_000,
                    'User' => ['username' => 'trooper'],
                    'Attachments' => [[
                        'attachment_id' => 7398,
                        'filename' => 'photo.jpg',
                        'height' => 721,
                        'width' => 619,
                    ]],
                ]],
                'pagination' => ['current_page' => 1, 'last_page' => 1],
            ], 200),
        ]);

        $subject = new XenforoService;
        $result = $subject->get_thread_posts(9);

        $this->assertSame('Cannot make it.', $result[0]['message']);
        $this->assertStringContainsString('alt="photo.jpg"', $result[0]['message_html']);
        $this->assertStringContainsString('/events/forum-attachments/7398?signature=', $result[0]['message_html']);
    }

    public function test_get_attachment_data_returns_file_contents(): void
    {
        config([
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.api_key' => 'key-8',
        ]);

        Http::fake([
            'https://xf.test/api/attachments/7398/data' => Http::response('png-bytes', 200, [
                'Content-Type' => 'image/png',
                'Content-Disposition' => 'inline; filename="photo.png"',
            ]),
        ]);

        $subject = new XenforoService;

        $this->assertSame(
            ['content' => 'png-bytes', 'content_type' => 'image/png', 'filename' => 'photo.png'],
            $subject->get_attachment_data(7398)
        );
    }

    public function test_get_attachment_data_returns_null_on_failure(): void
    {
        config([
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.api_key' => 'key-9',
        ]);

        Http::fake([
            'https://xf.test/api/attachments/1/data' => Http::response(null, 404),
        ]);

        $subject = new XenforoService;

        $this->assertNull($subject->get_attachment_data(1));
    }

    /**
     * @return array<int, array{post_id:int,message:string,post_date:int,User:array{username:string}}>
     */
    private function threadPosts(int $first_id, int $count): array
    {
        $posts = [];

        for ($post_id = $first_id; $post_id < $first_id + $count; $post_id++)
        {
            $posts[] = [
                'post_id' => $post_id,
                'message' => 'Post '.$post_id,
                'post_date' => 1_700_000_000 + $post_id,
                'User' => ['username' => 'trooper'.$post_id],
            ];
        }

        return $posts;
    }

    /**
     * @return array{groupID:int,title:string,bannerText:string}
     */
    private function bannerGroup(int $id, string $title, string $banner_html): array
    {
        return ['groupID' => $id, 'title' => $title, 'bannerText' => $banner_html];
    }
}
