<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Auth\Commands;

use App\Messages\Auth\Commands\Login;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_call_returns_trooper_and_logs_in_with_valid_credentials(): void
    {
        $trooper = Trooper::factory()
            ->asActive()
            ->withPassword('password123')
            ->create();

        Auth::shouldReceive('login')
            ->once()
            ->with(Mockery::on(static function (Trooper $login_trooper) use ($trooper): bool
            {
                return $login_trooper->is($trooper);
            }), true);

        $result = (new Login($trooper->email, 'password123', true))->handle();

        $this->assertInstanceOf(Trooper::class, $result);
        $this->assertSame($trooper->id, $result->id);
    }

    public function test_call_returns_null_for_invalid_password(): void
    {
        $trooper = Trooper::factory()
            ->asActive()
            ->withPassword('correct-password')
            ->create();

        Auth::shouldReceive('login')->never();

        $result = (new Login($trooper->email, 'wrong-password', false))->handle();

        $this->assertNull($result);
    }

    public function test_call_returns_null_when_trooper_does_not_exist(): void
    {
        Auth::shouldReceive('login')->never();

        $result = (new Login('missing@example.com', 'password123', false))->handle();

        $this->assertNull($result);
    }
}