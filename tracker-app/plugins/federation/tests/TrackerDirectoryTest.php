<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Federation\Tests;

use App\Models\Trooper;
use App\Plugins\PluginManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use TroopTracker\Plugins\Federation\Directory\TrackerDirectory;

class TrackerDirectoryTest extends TestCase
{
    use RefreshDatabase;
    use UsesFederationPlugin;

    public function test_list_merges_the_bundled_and_shared_trackers(): void
    {
        $this->fakeTrackers();
        $this->enableFederation();

        $directory = $this->app->make(TrackerDirectory::class);

        $this->assertEqualsCanonicalizing(
            ['https://florida.test', self::GEORGIA, 'https://tracker.fl501st.com'],
            $directory->all()->keys()->all(),
        );
        $this->assertFalse($directory->others()->has('https://florida.test'));
        $this->assertTrue($directory->listsThisTracker());
        $this->assertSame('501st Georgia Garrison', $directory->find(self::GEORGIA.'/')?->name);
    }

    public function test_plain_http_trackers_are_ignored_outside_local_and_testing(): void
    {
        Http::fake([self::DIRECTORY => Http::response(['trackers' => [
            ['name' => 'Plain', 'url' => 'http://plain.test'],
        ]])]);
        $this->enableFederation();
        $this->app['env'] = 'production';

        $this->assertFalse($this->app->make(TrackerDirectory::class)->all()->has('http://plain.test'));
    }

    public function test_an_unreachable_shared_list_falls_back_to_the_bundled_one(): void
    {
        Http::fake([self::DIRECTORY => fn () => throw new ConnectionException('down')]);
        $this->enableFederation();

        $this->assertSame(
            ['https://tracker.fl501st.com'],
            $this->app->make(TrackerDirectory::class)->all()->keys()->all(),
        );
    }

    public function test_the_tracker_list_url_setting_wins(): void
    {
        Http::fake(['https://other.test/list.json' => Http::response(['trackers' => [
            ['name' => 'Texas', 'url' => 'https://texas.test'],
        ]])]);
        $this->enableFederation();
        $this->app->make(PluginManager::class)
            ->updateSettings('federation', ['directory_url' => 'https://other.test/list.json']);

        $this->assertTrue($this->app->make(TrackerDirectory::class)->all()->has('https://texas.test'));
    }

    public function test_pages_are_gone_while_the_plugin_is_disabled(): void
    {
        $trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();

        $this->actingAs($trooper)->get('/plugins/federation')->assertNotFound();
        $this->postJson('/webhooks/federation/history')->assertNotFound();
        $this->actingAs($trooper)
            ->get(route('service-records.trooper', ['trooper' => $trooper]))
            ->assertOk()
            ->assertDontSee('Linked Trackers');
    }
}
