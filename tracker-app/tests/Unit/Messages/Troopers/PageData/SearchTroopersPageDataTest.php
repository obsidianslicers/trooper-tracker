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
            Trooper::LEGAL_NAME => 'Alpha Trooper',
        ]);

        $second_trooper = Trooper::factory()->asMember()->make([
            Trooper::ID => 202,
            Trooper::LEGAL_NAME => 'Bravo Trooper',
        ]);

        Mockery::mock('alias:' . SearchTroopers::class)
            ->shouldReceive('call')
            ->once()
            ->withArgs(function (Trooper $trooper, string $search_term, ?int $organization_id, bool $moderated_only, TrooperPickerMode $picker_mode, ) use ($requesting_trooper): bool
            {
                return $trooper === $requesting_trooper
                    && $search_term === 'Trooper'
                    && $organization_id === 42
                    && $moderated_only === true
                    && $picker_mode === TrooperPickerMode::FRIENDS;
            })
            ->andReturn(collect([$first_trooper, $second_trooper]));

        $subject = new SearchTroopersPageData(
            actor: $requesting_trooper,
            search_term: 'Trooper',
            organization_id: 42,
            moderated_only: true,
            picker_mode: TrooperPickerMode::FRIENDS,
        );

        $result = $subject->handle();

        $this->assertSame([
            [
                Trooper::ID => 101,
                Trooper::LEGAL_NAME => 'Alpha Trooper',
            ],
            [
                Trooper::ID => 202,
                Trooper::LEGAL_NAME => 'Bravo Trooper',
            ],
        ], $result);
    }

    #[RunInSeparateProcess]
    public function test_handle_returns_empty_array_when_query_returns_no_troopers(): void
    {
        $requesting_trooper = Trooper::factory()->asMember()->make([
            Trooper::ID => 10,
        ]);

        Mockery::mock('alias:' . SearchTroopers::class)
            ->shouldReceive('call')
            ->once()
            ->andReturn(collect());

        $subject = new SearchTroopersPageData(
            actor: $requesting_trooper,
            search_term: '',
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
