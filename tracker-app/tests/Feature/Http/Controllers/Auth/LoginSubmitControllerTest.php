<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Auth;

use App\Facades\TroopTracker;
use App\Http\Requests\Auth\LoginRequest;
use App\Messages\Auth\Commands\Login;
use App\Models\Trooper;
use Hyperdrive\MessageDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class LoginSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_redirects_to_events_list_after_successful_login(): void
    {
        $trooper = Trooper::factory()
            ->asActive()
            ->withPassword('password123')
            ->create();

        $this->instance(TroopTracker::class, Mockery::mock(TroopTracker::class, function ($mock): void
        {
            $mock->shouldReceive('isXenforoOAuthRequired')->once()->andReturnFalse();
        }));

        $dispatcher = new class ($trooper)
        {
            public array $calls = [];

            public function __construct(
            private readonly mixed $return_value
            ) {
            }

            public function handle(string $message_class, ?Request $request = null, array $params = []): mixed
            {
                $this->calls[] = [$message_class, $request, $params];

                return $this->return_value;
            }
        };

        $this->instance(MessageDispatcher::class, $dispatcher);

        $response = $this->from(route('auth.login'))->post(route('auth.login'), [
            'email' => $trooper->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('events.list'));

        $login_calls = array_values(array_filter(
            $dispatcher->calls,
            static fn(array $call): bool => $call[0] === Login::class
        ));

        $this->assertCount(1, $login_calls);
        $this->assertInstanceOf(LoginRequest::class, $login_calls[0][1]);
        $this->assertSame([], $login_calls[0][2]);
    }

    public function test_invoke_returns_back_with_danger_when_xenforo_login_is_required(): void
    {
        $trooper = Trooper::factory()
            ->asActive()
            ->withPassword('password123')
            ->create();

        $this->instance(TroopTracker::class, Mockery::mock(TroopTracker::class, function ($mock): void
        {
            $mock->shouldReceive('isXenforoOAuthRequired')->once()->andReturnTrue();
        }));

        $dispatcher = new class
        {
            public array $calls = [];

            public function handle(string $message_class, ?Request $request = null, array $params = []): mixed
            {
                $this->calls[] = [$message_class, $request, $params];

                return null;
            }
        };

        $this->instance(MessageDispatcher::class, $dispatcher);

        $response = $this->from(route('auth.login'))->post(route('auth.login'), [
            'email' => $trooper->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('auth.login'));

        $login_calls = array_values(array_filter(
            $dispatcher->calls,
            static fn(array $call): bool => $call[0] === Login::class
        ));

        $this->assertSame([], $login_calls);
    }

    public function test_invoke_returns_back_with_danger_when_login_fails(): void
    {
        $trooper = Trooper::factory()
            ->asActive()
            ->withPassword('password123')
            ->create();

        $this->instance(TroopTracker::class, Mockery::mock(TroopTracker::class, function ($mock): void
        {
            $mock->shouldReceive('isXenforoOAuthRequired')->once()->andReturnFalse();
        }));

        $dispatcher = new class
        {
            public array $calls = [];

            public function handle(string $message_class, ?Request $request = null, array $params = []): mixed
            {
                $this->calls[] = [$message_class, $request, $params];

                return null;
            }
        };

        $this->instance(MessageDispatcher::class, $dispatcher);

        $response = $this->from(route('auth.login'))->post(route('auth.login'), [
            'email' => $trooper->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('auth.login'));

        $login_calls = array_values(array_filter(
            $dispatcher->calls,
            static fn(array $call): bool => $call[0] === Login::class
        ));

        $this->assertCount(1, $login_calls);
        $this->assertInstanceOf(LoginRequest::class, $login_calls[0][1]);
        $this->assertSame([], $login_calls[0][2]);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}
