<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Search\Pages;

use App\Messages\Search\Queries\GlobalSearch;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class SearchAllControllerTest extends TestCase
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
        $response->assertInertia(fn (Assert $page): Assert => $page
            ->component('search/Results')
            ->where('term', 'a')
            ->where('type', 'all')
            ->where('searched', false)
            ->where('troopers', null)
            ->where('events', null));
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
        $response->assertInertia(fn (Assert $page): Assert => $page
            ->component('search/Results')
            ->where('term', 'Vader')
            ->where('type', 'troopers')
            ->has('troopers', 1)
            ->where('troopers.0.display_name', 'Matched Trooper')
            ->where('events', null));
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
        $response->assertInertia(fn (Assert $page): Assert => $page
            ->where('type', 'all')
            ->has('troopers', 0)
            ->has('events', 0));
    }
}
