<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Forums;

use App\Models\OauthLogin;
use App\Models\Trooper;
use App\Services\Forums\XenforoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
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
            OauthLogin::PROVIDER => 'xenforo',
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
            OauthLogin::PROVIDER => 'xenforo',
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
}
