<?php

declare(strict_types=1);

namespace Tests\Unit\Plugins;

use App\Plugins\PluginApi;
use App\Plugins\PluginRequirements;
use PHPUnit\Framework\TestCase;

class PluginRequirementsTest extends TestCase
{
    public function test_missing_is_empty_when_the_app_meets_every_requirement(): void
    {
        $subject = new PluginRequirements;

        $this->assertSame([], $subject->missing([
            'php' => '>=8.2',
            'laravel/framework' => '*',
            'ext-json' => '*',
        ]));
    }

    public function test_missing_lists_unmet_requirements(): void
    {
        $subject = new PluginRequirements;

        $result = $subject->missing([
            'php' => '<5.0',
            'acme/not-installed' => '^1.0',
            'laravel/framework' => '^1.0',
            'ext-not-a-real-extension' => '*',
            'composer/semver' => 'not a constraint',
        ]);

        $this->assertSame([
            'php <5.0',
            'acme/not-installed ^1.0',
            'laravel/framework ^1.0',
            'ext-not-a-real-extension *',
            'composer/semver not a constraint',
        ], $result);
    }

    public function test_unmet_describes_each_requirement(): void
    {
        $subject = new PluginRequirements;

        $unmet = $subject->unmet([
            'php' => '>=99.0',
            'ext-not_a_real_extension' => '*',
            'acme/not-installed' => '^1.0',
            'laravel/framework' => '^1.0',
            'trooptracker' => '^99.0',
            'ext-json' => '*',
        ]);

        $this->assertCount(5, $unmet);
        $php = 'PHP >=99.0 (this server has '.PHP_MAJOR_VERSION.'.';
        $this->assertStringStartsWith($php, $unmet[0]);
        $this->assertSame('the not_a_real_extension PHP extension', $unmet[1]);
        $this->assertSame('the Composer package acme/not-installed ^1.0', $unmet[2]);
        $this->assertStringStartsWith('the Composer package laravel/framework ^1.0 (', $unmet[3]);
        $this->assertSame(
            'TroopTracker plugin API ^99.0 (this app has '.PluginApi::VERSION.')',
            $unmet[4],
        );
    }

    public function test_the_plugin_api_version_is_checked(): void
    {
        $subject = new PluginRequirements;

        $this->assertSame([], $subject->unmet(['trooptracker' => '^1.0']));
    }

    public function test_satisfies_is_false_for_a_constraint_it_cannot_read(): void
    {
        $this->assertFalse((new PluginRequirements)->satisfies('1.0.0', 'not a version'));
        $this->assertTrue((new PluginRequirements)->satisfies('1.2.0', '^1.0'));
    }
}
