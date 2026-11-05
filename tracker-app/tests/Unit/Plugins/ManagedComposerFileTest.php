<?php

declare(strict_types=1);

namespace Tests\Unit\Plugins;

use App\Plugins\Deploy\ManagedComposerFile;
use PHPUnit\Framework\TestCase;

class ManagedComposerFileTest extends TestCase
{
    private const APP_JSON = [
        'name' => 'trooptracker/app',
        'require' => [
            'php' => '^8.2',
            'laravel/framework' => '^12.0',
            'acme/dev-tool' => 'dev-main',
        ],
        'require-dev' => ['phpunit/phpunit' => '^11.0'],
        'repositories' => [['type' => 'vcs', 'url' => 'https://github.com/acme/core-fork']],
        'config' => ['allow-plugins' => ['pestphp/pest-plugin' => true]],
    ];

    private const APP_LOCK = [
        'packages' => [
            ['name' => 'laravel/framework', 'version' => 'v12.3.0'],
            ['name' => 'guzzlehttp/guzzle', 'version' => '7.9.2'],
            [
                'name' => 'acme/dev-tool',
                'version' => 'dev-main',
                'source' => ['reference' => 'abc123'],
            ],
            ['name' => 'acme/local', 'version' => 'dev-main', 'dist' => ['type' => 'path']],
        ],
        'packages-dev' => [['name' => 'phpunit/phpunit', 'version' => '11.5.1']],
    ];

    private const REQUIREMENTS = [
        'packages' => ['trooptracker/google-sheets' => '*', 'acme/badges' => '1.2.0'],
        'repositories' => [['type' => 'path', 'url' => '/srv/plugins/sheets']],
    ];

    public function test_core_packages_are_held_at_the_app_lock_versions(): void
    {
        $subject = $this->subject();

        $json = $subject->json();

        $this->assertSame('v12.3.0', $json['require']['laravel/framework']);
        $this->assertSame('7.9.2', $json['require']['guzzlehttp/guzzle']);
        $this->assertSame('dev-main#abc123', $json['require']['acme/dev-tool']);
        $this->assertSame('11.5.1', $json['require-dev']['phpunit/phpunit']);
        $this->assertArrayNotHasKey('acme/local', $json['require']);
        $this->assertSame('^8.2', $json['require']['php']);
    }

    public function test_plugin_packages_and_repositories_are_added(): void
    {
        $subject = $this->subject();

        $json = $subject->json();

        $this->assertTrue($subject->hasPluginPackages());
        $this->assertSame('*', $json['require']['trooptracker/google-sheets']);
        $this->assertSame('1.2.0', $json['require']['acme/badges']);
        $this->assertSame([
            ['type' => 'vcs', 'url' => 'https://github.com/acme/core-fork'],
            ['type' => 'path', 'url' => '/srv/plugins/sheets'],
        ], $json['repositories']);
    }

    public function test_a_plugin_cannot_change_a_core_package(): void
    {
        $requirements = ['packages' => ['laravel/framework' => '^13.0']];
        $subject = new ManagedComposerFile(self::APP_JSON, self::APP_LOCK, null, $requirements);

        $this->assertFalse($subject->hasPluginPackages());
        $this->assertSame('v12.3.0', $subject->json()['require']['laravel/framework']);
    }

    public function test_only_the_composer_plugins_the_app_trusts_may_run(): void
    {
        $subject = $this->subject();

        $this->assertSame(
            ['pestphp/pest-plugin' => true],
            $subject->json()['config']['allow-plugins'],
        );

        $app_json = self::APP_JSON;
        unset($app_json['config']);
        $subject = new ManagedComposerFile($app_json, self::APP_LOCK, null, self::REQUIREMENTS);

        $this->assertFalse($subject->json()['config']['allow-plugins']);
    }

    public function test_only_plugin_packages_update_when_core_is_unchanged(): void
    {
        $subject = $this->subject(self::APP_LOCK);

        $this->assertSame(
            ['trooptracker/google-sheets', 'acme/badges'],
            $subject->packagesToUpdate(),
        );
    }

    public function test_core_packages_the_release_changed_update_too(): void
    {
        $managed = self::APP_LOCK;
        $managed['packages'][0]['version'] = 'v12.2.0';
        $subject = $this->subject($managed);

        $this->assertContains('laravel/framework', $subject->packagesToUpdate());
        $this->assertNotContains('guzzlehttp/guzzle', $subject->packagesToUpdate());
    }

    public function test_no_plugin_packages_means_a_plain_install(): void
    {
        $subject = new ManagedComposerFile(self::APP_JSON, self::APP_LOCK, null, []);

        $this->assertFalse($subject->hasPluginPackages());
        $this->assertSame([], $subject->packagesToUpdate());
    }

    /**
     * @param  array<string, mixed>|null  $managed_lock
     */
    private function subject(?array $managed_lock = null): ManagedComposerFile
    {
        return new ManagedComposerFile(
            self::APP_JSON,
            self::APP_LOCK,
            $managed_lock,
            self::REQUIREMENTS,
        );
    }
}
