<?php

declare(strict_types=1);

namespace Tests\Unit\Plugins;

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
}
