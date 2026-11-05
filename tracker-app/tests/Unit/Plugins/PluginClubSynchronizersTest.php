<?php

declare(strict_types=1);

namespace Tests\Unit\Plugins;

use App\Models\Organization;
use App\Plugins\PluginClubSynchronizers;
use App\Services\Synchronizers\TheLegionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use stdClass;
use Tests\Fixtures\Synchronizers\LookupOnlySynchronizer;
use Tests\TestCase;

class PluginClubSynchronizersTest extends TestCase
{
    use RefreshDatabase;

    private PluginClubSynchronizers $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = $this->app->make(PluginClubSynchronizers::class);
        LookupOnlySynchronizer::$lookup = null;
    }

    public function test_the_built_in_clubs_are_registered(): void
    {
        $options = $this->subject->options();

        $this->assertSame('501st Legion', $options['501st']);
        $this->assertSame('Saber Guild', $options['saber-guild']);
        $this->assertCount(6, $options);
    }

    public function test_options_are_sorted_by_label(): void
    {
        $this->subject->add('aaa-last', 'Zebra Club', LookupOnlySynchronizer::class);
        $this->subject->add('zzz-first', 'Alpha Club', LookupOnlySynchronizer::class);

        $keys = array_keys($this->subject->options());

        $this->assertLessThan(
            array_search('aaa-last', $keys, true),
            array_search('zzz-first', $keys, true),
        );
    }

    public function test_synchronizer_makes_the_class_with_the_organization(): void
    {
        $organization = $this->club('501st');

        $result = $this->subject->synchronizer($organization);

        $this->assertInstanceOf(TheLegionService::class, $result);
    }

    public function test_a_lookup_only_synchronizer_does_not_run(): void
    {
        $this->subject->add('lookup', 'Lookup Club', LookupOnlySynchronizer::class);
        $organization = $this->club('lookup');

        $lookup = $this->subject->memberLookup($organization);

        $this->assertNull($this->subject->synchronizer($organization));
        $this->assertNotNull($lookup);
        $this->assertSame(LookupOnlySynchronizer::$lookup, $lookup);
    }

    public function test_an_unknown_key_gives_nothing(): void
    {
        $organization = $this->club('from-a-disabled-plugin');

        $this->assertNull($this->subject->synchronizer($organization));
        $this->assertNull($this->subject->memberLookup($organization));
    }

    public function test_add_refuses_a_key_that_is_taken(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->subject->add('501st', 'Another 501st', LookupOnlySynchronizer::class);
    }

    public function test_add_refuses_a_class_that_does_neither(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->subject->add('nothing', 'Nothing', stdClass::class);
    }

    private function club(string $key): Organization
    {
        return Organization::factory()->asOrganization()->create([
            Organization::SYNCHRONIZER => $key,
        ]);
    }
}
