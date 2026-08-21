<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Costumes\PageData;

use App\Messages\Costumes\PageData\SearchCostumesPageData;
use App\Messages\Costumes\Queries\SearchCostumes;
use App\Models\Costume;
use App\Models\Organization;
use App\Models\Trooper;
use Mockery;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Tests\TestCase;

class SearchCostumesPageDataTest extends TestCase
{
    #[RunInSeparateProcess]
    public function test_handle_returns_mapped_costume_data(): void
    {
        $requesting_trooper = Trooper::factory()->asMember()->asActive()->make([
            Trooper::ID => 10,
        ]);

        $first_costume = Costume::factory()->make([
            Costume::ID => 101,
            Costume::NAME => 'Alpha Armor',
        ]);
        $first_costume->setRelation('organization_costumes', collect([
            (object) [
                'organization' => (object) [
                    Organization::ID => 1,
                    Organization::NAME => 'Core Worlds',
                ],
            ],
            (object) [
                'organization' => (object) [
                    Organization::ID => 2,
                    Organization::NAME => 'Outer Rim',
                ],
            ],
        ]));

        $second_costume = Costume::factory()->make([
            Costume::ID => 202,
            Costume::NAME => 'Beta Armor',
        ]);
        $second_costume->setRelation('organization_costumes', collect([
            (object) [
                'organization' => (object) [
                    Organization::ID => 3,
                    Organization::NAME => 'Jedi Temple',
                ],
            ],
        ]));

        Mockery::mock('alias:' . SearchCostumes::class)
            ->shouldReceive('call')
            ->once()
            ->withArgs(function (string $search_term, Trooper $trooper) use ($requesting_trooper): bool
            {
                return $search_term === 'Armor'
                    && $trooper === $requesting_trooper;
            })
            ->andReturn(collect([$first_costume, $second_costume]));

        $subject = new SearchCostumesPageData(
            search_term: 'Armor',
            trooper: $requesting_trooper,
        );

        $result = $subject->handle();

        $this->assertSame([
            [
                Costume::ID => 101,
                Costume::NAME => 'Alpha Armor',
                'organizations' => [
                    [
                        Organization::ID => 1,
                        Organization::NAME => 'Core Worlds',
                    ],
                    [
                        Organization::ID => 2,
                        Organization::NAME => 'Outer Rim',
                    ],
                ],
            ],
            [
                Costume::ID => 202,
                Costume::NAME => 'Beta Armor',
                'organizations' => [
                    [
                        Organization::ID => 3,
                        Organization::NAME => 'Jedi Temple',
                    ],
                ],
            ],
        ], $result);
    }

    #[RunInSeparateProcess]
    public function test_handle_returns_empty_array_when_query_returns_no_costumes(): void
    {
        $requesting_trooper = Trooper::factory()->asMember()->asActive()->make([
            Trooper::ID => 10,
        ]);

        Mockery::mock('alias:' . SearchCostumes::class)
            ->shouldReceive('call')
            ->once()
            ->andReturn(collect());

        $subject = new SearchCostumesPageData(
            search_term: '',
            trooper: $requesting_trooper,
        );

        $result = $subject->handle();

        $this->assertSame([], $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}
