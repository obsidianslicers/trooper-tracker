<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Auth\Commands;

use App\Messages\Auth\Commands\LogoutTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class LogoutTrooperTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_logs_the_trooper_out(): void
    {
        Auth::login(Trooper::factory()->asActive()->create());

        $subject = new LogoutTrooper;
        $subject->handle();

        $this->assertGuest();
    }
}
