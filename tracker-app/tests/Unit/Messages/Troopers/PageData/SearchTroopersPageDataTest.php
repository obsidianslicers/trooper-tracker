<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\PageData;

use App\Enums\TrooperPickerMode;
use App\Messages\Troopers\PageData\SearchTroopersPageData;
use App\Messages\Troopers\Queries\SearchTroopers;
use App\Models\Filters\TrooperFilter;
use App\Models\Trooper;
use Illuminate\Http\Request;
use Mockery;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Tests\TestCase;

class SearchTroopersPageDataTest extends TestCase
{
    #[RunInSeparateProcess]
    public function test_handle_returns_mapped_trooper_data(): void
    {
        $requesting_trooper = Trooper::factory()->asMember()->make([
            Trooper::ID => 10,
        ]);

        $first_trooper = Trooper::factory()->asMember()->make([
            Trooper::ID => 101,
            Trooper::DISPLAY_NAME => 'Alpha Trooper',
        ]);

        $second_trooper = Trooper::factory()->asMember()->make([
            Trooper::ID => 202,
            Trooper::DISPLAY_NAME => 'Bravo Trooper',
        ]);

        $filter = new TrooperFilter(new Request(['search_term' => 'Trooper']));

        Mockery::mock('alias:' . SearchTroopers::class)
            ->shouldReceive('call')
            ->once()
            ->withArgs(function (Trooper $trooper, TrooperFilter $given_filter, ?int $organization_id, bool $moderated_only, TrooperPickerMode $picker_mode, ) use ($requesting_trooper, $filter): bool
            {
                return $trooper === $requesting_trooper
                    && $given_filter === $filter
                    && $organization_id === 42
                    && $moderated_only === true
                    && $picker_mode === TrooperPickerMode::FRIENDS;
            })
            ->andReturn(collect([$first_trooper, $second_trooper]));

        $subject = new SearchTroopersPageData(
            trooper: $requesting_trooper,
            filter: $filter,
            organization_id: 42,
            moderated_only: true,
            picker_mode: TrooperPickerMode::FRIENDS,
        );

        $result = $subject->handle();

        $this->assertSame([
            [
                Trooper::ID => 101,
                Trooper::DISPLAY_NAME => 'Alpha Trooper',
            ],
            [
                Trooper::ID => 202,
                Trooper::DISPLAY_NAME => 'Bravo Trooper',
            ],
        ], $result);
    }

    #[RunInSeparateProcess]
    public function test_handle_returns_empty_array_when_query_returns_no_troopers(): void
    {
        $requesting_trooper = Trooper::factory()->asMember()->make([
            Trooper::ID => 10,
        ]);

        $filter = new TrooperFilter(new Request());

        Mockery::mock('alias:' . SearchTroopers::class)
            ->shouldReceive('call')
            ->once()
            ->andReturn(collect());

        $subject = new SearchTroopersPageData(
            trooper: $requesting_trooper,
            filter: $filter,
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
