<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Search;

use App\Messages\Search\Queries\GlobalSearch;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class SearchControllerTest extends TestCase
{
    use FakesMessages;
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('search.all'));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_does_not_dispatch_search_for_short_term(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();

        $messages = $this->fakeMessages([GlobalSearch::class => null]);

        $response = $this->actingAs($trooper)->get(route('search.all', [
            'q' => 'a',
            'type' => 'invalid',
        ]));

        $messages->assertNotCalled(GlobalSearch::class);

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

        $messages = $this->fakeMessages([GlobalSearch::class => $payload]);

        $response = $this->actingAs($trooper)->get(route('search.all', [
            'q' => 'Vader',
            'type' => 'troopers',
        ]));

        $messages->assertCalled(GlobalSearch::class, fn (array $params): bool => $params['term'] === 'Vader' && $params['type'] === 'troopers');

        $response->assertOk();
        $response->assertViewIs('pages.search.results');
        $response->assertViewHas('term', 'Vader');
        $response->assertViewHas('type', 'troopers');
        $response->assertViewHas('results', function (array $results): bool
        {
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

        $messages = $this->fakeMessages([GlobalSearch::class => $payload]);

        $response = $this->actingAs($trooper)->get(route('search.all', [
            'q' => 'Legion',
            'type' => 'unknown',
        ]));

        $messages->assertCalled(GlobalSearch::class, fn (array $params): bool => $params['term'] === 'Legion' && $params['type'] === 'all');

        $response->assertOk();
        $response->assertViewHas('type', 'all');
    }
}
