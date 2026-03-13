<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\ServiceRecords;

use App\Bus\MagicBus;
use App\Features\Troopers\Queries\GetTrooperCostumesQuery;
use App\Features\Troopers\Queries\GetTrooperServiceRecordQuery;
use App\Models\Costume;
use App\Models\Trooper;
use App\Services\BreadCrumbService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Mockery\MockInterface;
use Tests\TestCase;

class TrooperControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        $trooper = Trooper::factory()->asMember()->create();

        $response = $this->get(route('service-records.trooper', ['trooper' => $trooper]));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_renders_service_record_and_filters_staff_costumes(): void
    {
        $auth_trooper = Trooper::factory()->asMember()->create();
        $target_trooper = Trooper::factory()->asMember()->create();

        $data = $this->makeServiceRecordData($target_trooper);

        $trooper_costumes = collect([
            Costume::factory()->withName('TK Classic')->make(),
            Costume::factory()->withName(Costume::COMMAND_STAFF)->make(),
            Costume::factory()->withName(Costume::HANDLER)->make(),
        ]);

        $this->mock(MagicBus::class, function (MockInterface $mock) use ($data, $trooper_costumes, $target_trooper)
        {
            $mock->shouldReceive('send')
                ->once()
                ->withArgs(function (GetTrooperServiceRecordQuery $query) use ($target_trooper): bool
                {
                    return $query->trooper_id === $target_trooper->id;
                })
                ->andReturn($data);

            $mock->shouldReceive('send')
                ->once()
                ->withArgs(function (GetTrooperCostumesQuery $query) use ($target_trooper): bool
                {
                    return $query->trooper->id === $target_trooper->id;
                })
                ->andReturn($trooper_costumes);
        });

        $response = $this->actingAs($auth_trooper)
            ->get(route('service-records.trooper', ['trooper' => $target_trooper]));

        $response->assertOk();
        $response->assertViewIs('pages.service-records.trooper');
        $response->assertViewHas('trooper_costumes', function (Collection $result): bool
        {
            return $result->pluck(Costume::NAME)->values()->all() === ['TK Classic'];
        });
    }

    public function test_invoke_adds_profile_breadcrumb_for_authenticated_trooper(): void
    {
        $trooper = Trooper::factory()->asMember()->create();

        $this->mock(BreadCrumbService::class, function (MockInterface $mock): void
        {
            $mock->shouldIgnoreMissing();

            $mock->shouldReceive('addRoute')
                ->once()
                ->with('Profile', 'account.profile');
        });

        $this->mock(MagicBus::class, function (MockInterface $mock) use ($trooper): void
        {
            $mock->shouldReceive('send')
                ->once()
                ->withArgs(function (GetTrooperServiceRecordQuery $query) use ($trooper): bool
                {
                    return $query->trooper_id === $trooper->id;
                })
                ->andReturn($this->makeServiceRecordData($trooper));

            $mock->shouldReceive('send')
                ->once()
                ->withArgs(function (GetTrooperCostumesQuery $query) use ($trooper): bool
                {
                    return $query->trooper->id === $trooper->id;
                })
                ->andReturn(collect());
        });

        $response = $this->actingAs($trooper)
            ->get(route('service-records.trooper', ['trooper' => $trooper]));

        $response->assertOk();
    }

    /**
     * @return array<string, mixed>
     */
    private function makeServiceRecordData(Trooper $trooper): array
    {
        return [
            'trooper' => $trooper,
            'trooper_organizations' => collect(),
            'tagged_uploads' => collect(),
            'service_summary' => [
                'total_shifts' => 0,
                'total_hours' => 0,
                'rank' => 0,
                'direct_funds' => 0,
                'indirect_funds' => 0,
                'rank_theme' => 'secondary',
                'rank_title' => 'Recruit',
                'milestones' => [],
            ],
            'upcoming_shifts' => collect(),
            'recent_shifts' => collect(),
            'recent_donations' => collect(),
            'awards' => collect(),
        ];
    }
}
