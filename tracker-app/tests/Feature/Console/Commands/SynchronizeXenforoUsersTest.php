<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Bus\MagicBus;
use App\Features\Forums\Commands\SyncXenforoUserCommand;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SynchronizeXenforoUsersTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_dispatches_sync_command_for_each_trooper(): void
    {
        $trooper_one = Trooper::factory()->create();
        $trooper_two = Trooper::factory()->create();

        $bus = Mockery::mock(MagicBus::class);
        $bus->shouldReceive('send')
            ->twice()
            ->with(Mockery::type(SyncXenforoUserCommand::class));

        $this->app->bind(MagicBus::class, fn () => $bus);

        $this->artisan('tracker:synchronize-xenforo-users')
            ->assertExitCode(0);
    }

    public function test_handle_outputs_completion_time_in_seconds(): void
    {
        $bus = Mockery::mock(MagicBus::class);
        $bus->shouldReceive('send')->andReturn(null);
        $this->app->bind(MagicBus::class, fn () => $bus);

        $this->artisan('tracker:synchronize-xenforo-users')
            ->expectsOutputToContain('seconds')
            ->assertExitCode(0);
    }

    public function test_handle_respects_chunk_option(): void
    {
        $trooper = Trooper::factory()->create();

        $bus = Mockery::mock(MagicBus::class);
        $bus->shouldReceive('send')
            ->once()
            ->with(Mockery::type(SyncXenforoUserCommand::class));

        $this->app->bind(MagicBus::class, fn () => $bus);

        $this->artisan('tracker:synchronize-xenforo-users', ['--chunk' => 10])
            ->assertExitCode(0);
    }
}
