<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Forums;

use App\Models\OauthLogin;
use App\Models\Trooper;
use App\Services\Forums\XenforoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class XenforoServiceTest extends TestCase
{
	use RefreshDatabase;

	public function test_resolve_user_id_for_trooper_returns_null_for_null_id(): void
	{
		$service = new XenforoService();

		$result = $service->resolve_user_id_for_trooper(null);

		$this->assertNull($result);
	}

	public function test_resolve_user_id_for_trooper_returns_null_when_no_xenforo_login(): void
	{
		$trooper = Trooper::factory()->asActive()->create();

		$service = new XenforoService();

		$result = $service->resolve_user_id_for_trooper($trooper->id);

		$this->assertNull($result);
	}

	public function test_resolve_user_id_for_trooper_returns_casted_provider_id(): void
	{
		$trooper = Trooper::factory()->asActive()->create();

		OauthLogin::factory()->create([
			OauthLogin::TROOPER_ID => $trooper->id,
			OauthLogin::PROVIDER => 'xenforo',
			OauthLogin::PROVIDER_ID => '123',
		]);

		$service = new XenforoService();

		$result = $service->resolve_user_id_for_trooper($trooper->id);

		$this->assertSame(123, $result);
	}

	public function test_create_thread_uses_explicit_user_id_and_sends_expected_payload(): void
	{
		config([
			'services.xenforo.base_url' => 'https://forum.example.com',
			'services.xenforo.api_key' => 'test-api-key',
			'services.xenforo.api_user' => 'api-user',
		]);

		Http::fake(fn (HttpRequest $request) => Http::response(['thread_id' => 999], 201));

		$service = new XenforoService();

		$result = $service->create_thread(
			node_id: 5,
			title: 'Test Thread',
			message: 'Test message body',
			user_id: 42,
			prefix_id: 7,
			extra_fields: ['custom' => 'value']
		);

		Http::assertSent(function (HttpRequest $request): bool {
			$this->assertSame('https://forum.example.com/api/threads', $request->url());
			$this->assertSame('POST', $request->method());

			$this->assertSame([
				'node_id' => 5,
				'title' => 'Test Thread',
				'message' => 'Test message body',
				'api_bypass_permissions' => 1,
				'prefix_id' => 7,
				'custom' => 'value',
			], $request->data());

			$this->assertSame('test-api-key', $request->header('XF-Api-Key')[0] ?? null);
			$this->assertSame('42', $request->header('XF-Api-User')[0] ?? null);

			return true;
		});

		$this->assertSame(201, $result['status']);
		$this->assertSame(['thread_id' => 999], $result['body']);
	}

	public function test_create_thread_resolves_xenforo_user_from_authenticated_trooper_when_user_id_not_provided(): void
	{
		config([
			'services.xenforo.base_url' => 'https://forum.example.com',
			'services.xenforo.api_key' => 'test-api-key',
			'services.xenforo.api_user' => 'api-user',
		]);

		Http::fake(fn (HttpRequest $request) => Http::response(['thread_id' => 1000], 200));

		$trooper = Trooper::factory()->asActive()->create();
		Auth::login($trooper);

		OauthLogin::factory()->create([
			OauthLogin::TROOPER_ID => $trooper->id,
			OauthLogin::PROVIDER => 'xenforo',
			OauthLogin::PROVIDER_ID => '777',
		]);

		$service = new XenforoService();

		$service->create_thread(
			node_id: 10,
			title: 'Another Thread',
			message: 'Another message'
		);

		Http::assertSent(function (HttpRequest $request): bool {
			$this->assertSame('https://forum.example.com/api/threads', $request->url());
			$this->assertSame('test-api-key', $request->header('XF-Api-Key')[0] ?? null);
			$this->assertSame('777', $request->header('XF-Api-User')[0] ?? null);

			return true;
		});
	}
}
