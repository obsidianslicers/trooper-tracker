<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Socialite;

use App\Services\Socialite\XenforoProvider;
use Illuminate\Http\Request;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class XenforoProviderTest extends TestCase
{
	public function test_get_avatar_url_prefers_original_size(): void
	{
		$provider = $this->makeProvider();

		$userData = [
			'avatar_urls' => [
				'o' => 'original-url',
				'l' => 'large-url',
				'm' => 'medium-url',
			],
		];

		$result = $this->callProtectedMethod($provider, 'getAvatarUrl', [$userData]);

		$this->assertSame('original-url', $result);
	}

	public function test_get_avatar_url_falls_back_to_smaller_sizes_and_null(): void
	{
		$provider = $this->makeProvider();

		$userData = ['avatar_urls' => ['l' => 'large-url']];
		$this->assertSame('large-url', $this->callProtectedMethod($provider, 'getAvatarUrl', [$userData]));

		$userData = ['avatar_urls' => ['m' => 'medium-url']];
		$this->assertSame('medium-url', $this->callProtectedMethod($provider, 'getAvatarUrl', [$userData]));

		$userData = [];
		$this->assertNull($this->callProtectedMethod($provider, 'getAvatarUrl', [$userData]));
	}

	public function test_map_user_to_object_uses_me_payload_and_avatar(): void
	{
		$provider = $this->makeProvider();

		$payload = [
			'me' => [
				'user_id' => 123,
				'username' => 'trooper123',
				'email' => 'trooper@example.com',
				'avatar_urls' => [
					'm' => 'https://example.com/avatars/trooper123.png',
				],
			],
		];

		/** @var SocialiteUser $user */
		$user = $this->callProtectedMethod($provider, 'mapUserToObject', [$payload]);

		$this->assertSame(123, $user->getId());
		$this->assertSame('trooper123', $user->getNickname());
		$this->assertSame('trooper123', $user->getName());
		$this->assertSame('trooper@example.com', $user->getEmail());
		$this->assertSame('https://example.com/avatars/trooper123.png', $user->getAvatar());
	}

	private function makeProvider(): XenforoProvider
	{
		$request = Request::create('/', 'GET');

		config(['services.xenforo.base_url' => 'https://forum.example.com']);

		return new XenforoProvider(
			$request,
			'client-id',
			'client-secret',
			'https://app.example.com/oauth/xenforo/callback'
		);
	}

	/**
	 * Call a protected method on the provider using reflection.
	 *
	 * @param  array<int, mixed>  $args
	 */
	private function callProtectedMethod(object $object, string $method, array $args = []): mixed
	{
		$ref = new \ReflectionClass($object);
		$m = $ref->getMethod($method);
		$m->setAccessible(true);

		return $m->invokeArgs($object, $args);
	}
}
