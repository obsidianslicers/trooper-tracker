<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Search\PageData;

use App\Enums\SearchType;
use App\Enums\TrooperTheme;
use App\Messages\Search\PageData\SearchAllPageData;
use App\Messages\Search\Queries\GlobalSearch;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class SearchAllPageDataTest extends TestCase
{
    use FakesMessages;
    use RefreshDatabase;

    public function test_handle_searches_the_chosen_type(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $fake = $this->fakeMessages([GlobalSearch::class => ['troopers' => []]]);

        $subject = new SearchAllPageData(actor: $trooper, q: 'vader', type: SearchType::TROOPERS->value);
        $result = $subject->handle();

        $this->assertSame('vader', $result['term']);
        $this->assertSame(SearchType::TROOPERS->value, $result['type']);
        $this->assertSame(['troopers' => []], $result['results']);
        $this->assertTrue($result['show_troopers']);
        $this->assertFalse($result['show_events']);
        $fake->assertCalled(GlobalSearch::class, fn (array $params): bool => $params['term'] === 'vader');
    }

    public function test_handle_does_not_search_a_term_too_short_to_match(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $fake = $this->fakeMessages([GlobalSearch::class => []]);

        $result = (new SearchAllPageData(actor: $trooper, q: 'v', type: 'nonsense'))->handle();

        $this->assertNull($result['results']);
        $this->assertSame(SearchType::ALL->value, $result['type']);
        $fake->assertNotCalled(GlobalSearch::class);
    }

    public function test_handle_titles_the_results_for_the_trooper_theme(): void
    {
        $trooper = Trooper::factory()->asActive()->create(['theme' => TrooperTheme::SITH]);
        $this->fakeMessages([GlobalSearch::class => []]);

        $result = (new SearchAllPageData(actor: $trooper))->handle();

        $this->assertSame(__('search.Force Sight'), $result['search_title']);
    }
}
