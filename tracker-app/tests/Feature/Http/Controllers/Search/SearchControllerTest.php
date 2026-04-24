<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Search;

use App\Bus\MagicBus;
use App\Features\Search\Queries\GlobalSearchQuery;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Mockery\MockInterface;
use Tests\TestCase;

class SearchControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('search'));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_does_not_dispatch_search_for_short_term(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();

        $this->mock(MagicBus::class, function (MockInterface $mock): void {
            $mock->shouldReceive('send')->never();
        });

        $response = $this->actingAs($trooper)->get(route('search', [
            'q' => 'a',
            'type' => 'invalid',
        ]));

        $response->assertOk();
        $response->assertViewIs('pages.search.results');
        $response->assertViewHas('term', 'a');
        $response->assertViewHas('type', 'all');
        $response->assertViewHas('results', null);
    }

    public function test_invoke_dispatches_global_search_for_valid_trooper_type(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();

        $payload = [
            'troopers' => collect([Trooper::factory()->withDisplayName('Matched Trooper')->create()]),
            'events' => collect(),
        ];

        $this->mock(MagicBus::class, function (MockInterface $mock) use ($payload): void {
            $mock->shouldReceive('send')
                ->once()
                ->withArgs(function (GlobalSearchQuery $query): bool {
                    return $query->term === 'Vader' && $query->type === 'troopers';
                })
                ->andReturn($payload);
        });

        $response = $this->actingAs($trooper)->get(route('search', [
            'q' => 'Vader',
            'type' => 'troopers',
        ]));

        $response->assertOk();
        $response->assertViewIs('pages.search.results');
        $response->assertViewHas('term', 'Vader');
        $response->assertViewHas('type', 'troopers');
        $response->assertViewHas('results', function (array $results): bool {
            return $results['troopers'] instanceof Collection
                && $results['events'] instanceof Collection
                && $results['troopers']->pluck(Trooper::DISPLAY_NAME)->all() === ['Matched Trooper'];
        });
    }

    public function test_invoke_dispatches_global_search_with_all_type_when_type_is_invalid(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();

        $payload = [
            'troopers' => collect(),
            'events' => collect(),
        ];

        $this->mock(MagicBus::class, function (MockInterface $mock) use ($payload): void {
            $mock->shouldReceive('send')
                ->once()
                ->withArgs(function (GlobalSearchQuery $query): bool {
                    return $query->term === 'Legion' && $query->type === 'all';
                })
                ->andReturn($payload);
        });

        $response = $this->actingAs($trooper)->get(route('search', [
            'q' => 'Legion',
            'type' => 'unknown',
        ]));

        $response->assertOk();
        $response->assertViewHas('type', 'all');
    }
}
