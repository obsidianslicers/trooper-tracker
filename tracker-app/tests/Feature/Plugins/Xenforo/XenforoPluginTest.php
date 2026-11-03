<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins\Xenforo;

use App\Models\Trooper;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;
use TroopTracker\Plugins\Xenforo\Services\XenforoUserSyncService;

class XenforoPluginTest extends TestCase
{
    use RefreshDatabase;
    use UsesXenforoPlugin;

    public function test_saved_settings_win_over_env_config(): void
    {
        config([
            'services.xenforo.base_url' => 'https://env.example.com',
            'services.xenforo.api_key' => 'env-key',
            'tracker.auth.require_xenforo' => true,
        ]);
        $plugins = $this->app->make(\App\Plugins\PluginManager::class);
        $plugins->enable('xenforo');
        $plugins->updateSettings('xenforo', [
            'base_url' => 'https://forum.example.com/',
            'require_xenforo' => '0',
        ]);

        $plugins->load($plugins->findOrFail('xenforo'));

        $this->assertSame('https://forum.example.com', config('services.xenforo.base_url'));
        $this->assertSame('env-key', config('services.xenforo.api_key'));
        $this->assertFalse(config('tracker.auth.require_xenforo'));
    }

    public function test_env_config_is_kept_until_settings_are_saved(): void
    {
        config(['services.xenforo.base_url' => 'https://env.example.com', 'tracker.auth.require_xenforo' => true]);

        $this->enableXenforo();

        $this->assertSame('https://env.example.com', config('services.xenforo.base_url'));
        $this->assertTrue(config('tracker.auth.require_xenforo'));
    }

    public function test_user_sync_is_scheduled_hourly_while_enabled(): void
    {
        $this->enableXenforo();

        $events = collect($this->app->make(Schedule::class)->events())
            ->filter(fn ($event): bool => str_contains((string) $event->command, 'xenforo:sync-users'));

        $this->assertCount(1, $events);
        $this->assertSame('0 * * * *', $events->first()->expression);
    }

    public function test_sync_users_syncs_every_trooper(): void
    {
        $troopers = Trooper::factory()->count(3)->create();
        $sync = Mockery::mock(XenforoUserSyncService::class);
        $this->enableXenforo();
        $this->app->instance(XenforoUserSyncService::class, $sync);
        $sync->shouldReceive('syncTrooper')->times($troopers->count());

        $this->artisan('xenforo:sync-users', ['--chunk' => 2])->assertSuccessful();
    }

    public function test_sync_user_syncs_one_trooper(): void
    {
        $trooper = Trooper::factory()->create();
        $sync = Mockery::mock(XenforoUserSyncService::class);
        $this->enableXenforo();
        $this->app->instance(XenforoUserSyncService::class, $sync);
        $sync->shouldReceive('syncTrooper')->once()->with(Mockery::on(
            fn (Trooper $synced): bool => $synced->id === $trooper->id
        ));

        $this->artisan('xenforo:sync-user', ['trooper' => $trooper->id])
            ->expectsOutput('Synchronization complete.')
            ->assertSuccessful();
    }

    public function test_sync_user_rejects_a_missing_trooper(): void
    {
        $this->enableXenforo();

        $this->artisan('xenforo:sync-user', ['trooper' => 99999])
            ->expectsOutput('Trooper with ID 99999 was not found.')
            ->assertFailed();

        $this->artisan('xenforo:sync-user', ['trooper' => 0])
            ->expectsOutput('Please provide a valid trooper ID.')
            ->assertFailed();
    }

    public function test_dry_run_reports_without_syncing(): void
    {
        $trooper = Trooper::factory()->create();
        $sync = Mockery::mock(XenforoUserSyncService::class);
        $this->enableXenforo();
        $this->app->instance(XenforoUserSyncService::class, $sync);
        $sync->shouldNotReceive('syncTrooper');
        $sync->shouldReceive('debugSync')->once()->andReturn([
            'xenforo_user_id' => 7,
            'managed_group_ids' => [10, 11],
            'current_secondary' => [10, 99],
            'current_tt_managed' => [10],
            'current_preserved' => [99],
            'desired_managed' => [11],
            'computed_result' => [11, 99],
            'would_send' => true,
        ]);

        $this->artisan('xenforo:sync-user', ['trooper' => $trooper->id, '--dry-run' => true])
            ->expectsOutputToContain('XenForo user ID: 7')
            ->expectsOutput('  Would send: 11, 99')
            ->assertSuccessful();
    }
}
