<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Bus\MagicBus;
use App\Enums\MembershipStatus;
use App\Features\Forums\Commands\SyncXenforoUserCommand;
use App\Features\Forums\Queries\GetXenforoSyncStateQuery;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SynchronizeXenforoUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_outputs_error_for_invalid_id(): void
    {
        $this->artisan('tracker:synchronize-xenforo-user', ['trooper' => 0])
            ->expectsOutputToContain('Please provide a valid trooper ID.')
            ->assertExitCode(0);
    }

    public function test_command_outputs_error_for_missing_trooper(): void
    {
        $this->artisan('tracker:synchronize-xenforo-user', ['trooper' => 99999])
            ->expectsOutputToContain('Trooper with ID 99999 was not found.')
            ->assertExitCode(0);
    }

    public function test_command_dispatches_sync_command_via_magicbus(): void
    {
        $trooper = Trooper::factory()->create([Trooper::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE->value]);

        $bus = Mockery::mock(MagicBus::class);
        $bus->shouldReceive('send')
            ->once()
            ->with(Mockery::type(SyncXenforoUserCommand::class))
            ->andReturn(null);

        $this->app->bind(MagicBus::class, fn () => $bus);

        $this->artisan('tracker:synchronize-xenforo-user', ['trooper' => $trooper->id])
            ->assertExitCode(0);
    }

    public function test_dry_run_dispatches_query_and_not_sync_command(): void
    {
        $trooper = Trooper::factory()->create();

        $debug_state = $this->makeDebugState(xenforo_user_id: null);

        $bus = Mockery::mock(MagicBus::class);
        $bus->shouldReceive('send')
            ->once()
            ->with(Mockery::type(GetXenforoSyncStateQuery::class))
            ->andReturn($debug_state);

        $this->app->bind(MagicBus::class, fn () => $bus);

        $this->artisan('tracker:synchronize-xenforo-user', ['trooper' => $trooper->id, '--dry-run' => true])
            ->assertExitCode(0);
    }

    public function test_dry_run_shows_no_change_message_when_groups_already_match(): void
    {
        $trooper = Trooper::factory()->create();

        $debug_state = $this->makeDebugState(xenforo_user_id: 42, would_send: false);

        $bus = Mockery::mock(MagicBus::class);
        $bus->shouldReceive('send')->once()->andReturn($debug_state);
        $this->app->bind(MagicBus::class, fn () => $bus);

        $this->artisan('tracker:synchronize-xenforo-user', ['trooper' => $trooper->id, '--dry-run' => true])
            ->expectsOutputToContain('No change')
            ->assertExitCode(0);
    }

    public function test_dry_run_warns_when_a_change_would_be_sent(): void
    {
        $trooper = Trooper::factory()->create();

        $debug_state = $this->makeDebugState(
            xenforo_user_id: 42,
            desired_managed: [10],
            computed_result: [10],
            would_send: true,
        );

        $bus = Mockery::mock(MagicBus::class);
        $bus->shouldReceive('send')->once()->andReturn($debug_state);
        $this->app->bind(MagicBus::class, fn () => $bus);

        $this->artisan('tracker:synchronize-xenforo-user', ['trooper' => $trooper->id, '--dry-run' => true])
            ->expectsOutputToContain('Change detected')
            ->assertExitCode(0);
    }

    public function test_dry_run_warns_when_no_xenforo_account_linked(): void
    {
        $trooper = Trooper::factory()->create();

        $debug_state = $this->makeDebugState(xenforo_user_id: null);

        $bus = Mockery::mock(MagicBus::class);
        $bus->shouldReceive('send')->once()->andReturn($debug_state);
        $this->app->bind(MagicBus::class, fn () => $bus);

        $this->artisan('tracker:synchronize-xenforo-user', ['trooper' => $trooper->id, '--dry-run' => true])
            ->expectsOutputToContain('No XenForo account linked')
            ->assertExitCode(0);
    }

    /** @return array<string,mixed> */
    private function makeDebugState(
        ?int $xenforo_user_id = 42,
        array $managed_group_ids = [],
        array $current_secondary = [],
        array $current_tt_managed = [],
        array $current_preserved = [],
        array $desired_managed = [],
        ?array $computed_result = null,
        bool $would_send = false,
    ): array {
        return compact(
            'xenforo_user_id',
            'managed_group_ids',
            'current_secondary',
            'current_tt_managed',
            'current_preserved',
            'desired_managed',
            'computed_result',
            'would_send',
        );
    }
}
