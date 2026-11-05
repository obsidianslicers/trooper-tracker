<?php

declare(strict_types=1);

namespace Tests\Unit\Plugins;

use App\Contracts\ForumGroupProvider;
use App\Exceptions\ForumGroupUpdateException;
use App\Messages\Troopers\Commands\Permissions\UpdateTrooperForumGroups;
use App\Messages\Troopers\Queries\Permissions\GetRequestableForumGroups;
use App\Models\Trooper;
use App\Plugins\PluginForumGroups;
use InvalidArgumentException;
use Mockery;
use Tests\TestCase;

class PluginForumGroupsTest extends TestCase
{
    public function test_without_a_provider_there_are_no_forum_groups(): void
    {
        $this->assertNull(app(PluginForumGroups::class)->provider());
        $this->assertTrue(GetRequestableForumGroups::call()->isEmpty());
    }

    public function test_updating_groups_without_a_provider_fails(): void
    {
        $this->expectException(ForumGroupUpdateException::class);

        UpdateTrooperForumGroups::call(trooper: Trooper::factory()->make(), group_ids_to_add: [5]);
    }

    public function test_an_unavailable_provider_is_left_out(): void
    {
        $provider = Mockery::mock(ForumGroupProvider::class);
        $provider->shouldReceive('isAvailable')->andReturn(false);
        $this->app->instance($provider::class, $provider);

        $subject = app(PluginForumGroups::class);
        $subject->set($provider::class);

        $this->assertNull($subject->provider());
    }

    public function test_set_rejects_classes_that_are_not_providers(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(PluginForumGroups::class)->set(self::class);
    }
}
