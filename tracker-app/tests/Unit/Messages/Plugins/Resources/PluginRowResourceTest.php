<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Plugins\Resources;

use App\Enums\PluginCapability;
use App\Messages\Plugins\Resources\PluginRowResource;
use App\Plugins\PluginManifest;
use Illuminate\Http\Request;
use Tests\TestCase;

class PluginRowResourceTest extends TestCase
{
    public function test_to_array_describes_a_disabled_plugin_that_asks_before_enabling(): void
    {
        $row = $this->rowFor([], [PluginCapability::cases()[0]]);

        $this->assertSame('Disabled', $row['status']['label']);
        $this->assertSame('secondary', $row['status']['tone']);
        $this->assertSame('enable', $row['toggle']);
        $this->assertTrue($row['can_enable']);
        $this->assertStringStartsWith('Turn on Greeter? It ', $row['enable_confirm']);
        $this->assertSame('By Obsidian Slicers', $row['author']);
        $this->assertSame('Bundled', $row['source']);
    }

    public function test_to_array_enables_without_asking_when_it_does_nothing_special(): void
    {
        $this->assertNull($this->rowFor()['enable_confirm']);
    }

    public function test_to_array_shows_an_enabled_plugin_that_failed_to_load(): void
    {
        $row = $this->rowFor(['is_enabled' => true, 'is_loaded' => false, 'is_installed' => true]);

        $this->assertSame('Failed to load', $row['status']['label']);
        $this->assertSame('danger', $row['status']['tone']);
        $this->assertSame('disable', $row['toggle']);
        $this->assertTrue($row['is_installed']);
    }

    public function test_to_array_flags_an_enabled_plugin_whose_deploy_failed(): void
    {
        $row = $this->rowFor([
            'is_enabled' => true,
            'is_loaded' => true,
            'status' => 'failed',
            'last_error' => 'boom',
        ]);

        $this->assertSame('Needs attention', $row['status']['label']);
        $this->assertSame('Last deploy: boom', $row['last_error']);
    }

    public function test_to_array_lets_a_waiting_plugin_be_cancelled(): void
    {
        $row = $this->rowFor([
            'is_enable_pending' => true,
            'unmet_requirements' => ['acme/sheets ^1.0'],
            'can_install_on_deploy' => true,
        ]);

        $this->assertSame('Turns on at next deploy', $row['status']['label']);
        $this->assertSame('cancel', $row['toggle']);
        $this->assertNull($row['requirements']);
    }

    public function test_to_array_explains_packages_the_deploy_installs(): void
    {
        $row = $this->rowFor(['unmet_requirements' => ['acme/sheets ^1.0'], 'can_install_on_deploy' => true]);

        $this->assertFalse($row['requirements']['is_problem']);
        $this->assertStringContainsString('acme/sheets ^1.0', $row['requirements']['text']);
        $this->assertTrue($row['can_enable']);
    }

    public function test_to_array_refuses_to_enable_a_plugin_this_server_cannot_run(): void
    {
        $row = $this->rowFor(['unmet_requirements' => ['PHP >=99.0']]);

        $this->assertSame(['text' => 'Needs PHP >=99.0.', 'is_problem' => true], $row['requirements']);
        $this->assertFalse($row['can_enable']);
    }

    public function test_to_array_shows_a_pending_upgrade(): void
    {
        $row = $this->rowFor(['has_pending_upgrade' => true, 'installed_version' => '1.0.0']);

        $this->assertSame('Update pending from 1.0.0', $row['pending_upgrade']);
    }

    /**
     * @param  array<string, mixed>  $state
     * @param  array<int, PluginCapability>  $capabilities
     * @return array<string, mixed>
     */
    private function rowFor(array $state = [], array $capabilities = []): array
    {
        $manifest = new PluginManifest(
            slug: 'greeter',
            name: 'Greeter',
            version: '2.0.0',
            description: 'Says hello.',
            author: 'Obsidian Slicers',
            provider: 'Greeter\\Provider',
            path: '/tmp/greeter',
            namespace: 'Greeter',
            source_path: null,
            capabilities: $capabilities,
        );

        $plugin = array_merge([
            'manifest' => $manifest,
            'source' => 'bundled',
            'is_enabled' => false,
            'is_installed' => false,
            'is_loaded' => false,
            'installed_version' => null,
            'has_pending_upgrade' => false,
            'unmet_requirements' => [],
            'capability_summary' => 'reacts when things happen',
            'is_enable_pending' => false,
            'can_install_on_deploy' => false,
            'status' => null,
            'last_error' => null,
        ], $state);

        return (new PluginRowResource($plugin))->toArray(Request::create('/'));
    }
}
