<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\EnsureXenforoLinked;
use App\Models\OauthLogin;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class EnsureXenforoLinkedTest extends TestCase
{
	use RefreshDatabase;

	private EnsureXenforoLinked $middleware;

	protected function setUp(): void
	{
		parent::setUp();

		$this->middleware = new EnsureXenforoLinked();
	}

	public function test_allows_request_when_xenforo_not_required(): void
	{
		$trooper = Trooper::factory()->asActive()->create();
		Auth::login($trooper);

		config(['tracker.auth.require_xenforo' => false]);

		$response = $this->runMiddleware();

		$this->assertSame(200, $response->getStatusCode());
		$this->assertSame('OK', $response->getContent());
	}

	public function test_allows_guest_when_xenforo_required(): void
	{
		config(['tracker.auth.require_xenforo' => true]);

		$response = $this->runMiddleware();

		$this->assertSame(200, $response->getStatusCode());
	}

	public function test_redirects_authenticated_trooper_without_xenforo_link_when_required(): void
	{
		$trooper = Trooper::factory()->asActive()->create();
		Auth::login($trooper);

		config(['tracker.auth.require_xenforo' => true]);

		$response = $this->runMiddleware();

		$this->assertSame(302, $response->getStatusCode());
		$this->assertSame(route('account.xenforo.required'), $response->headers->get('Location'));
	}

	public function test_allows_authenticated_trooper_with_xenforo_link_when_required(): void
	{
		$trooper = Trooper::factory()->asActive()->create();
		Auth::login($trooper);

		OauthLogin::factory()->create([
			OauthLogin::TROOPER_ID => $trooper->id,
			OauthLogin::PROVIDER => 'xenforo',
			OauthLogin::PROVIDER_ID => '123',
		]);

		config(['tracker.auth.require_xenforo' => true]);

		$response = $this->runMiddleware();

		$this->assertSame(200, $response->getStatusCode());
	}

	public function test_allows_xenforo_linking_routes_even_without_link(): void
	{
		$trooper = Trooper::factory()->asActive()->create();
		Auth::login($trooper);

		config(['tracker.auth.require_xenforo' => true]);

		$response = $this->runMiddleware(routeName: 'account.xenforo.link');

		$this->assertSame(200, $response->getStatusCode());
	}

	private function runMiddleware(?string $routeName = null): Response|RedirectResponse
	{
		$request = Request::create('/dummy', 'GET');

		if ($routeName !== null) {
			$route = app('router')->get('/dummy', static fn () => 'OK')->name($routeName);
			$request->setRouteResolver(static fn () => $route);
		}

		$next = static fn () => new Response('OK', 200);

		return $this->middleware->handle($request, $next);
	}
}
