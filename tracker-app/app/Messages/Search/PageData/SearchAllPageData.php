<?php

declare(strict_types=1);

namespace App\Messages\Search\PageData;

use App\Enums\SearchType;
use App\Enums\TrooperTheme;
use App\Messages\Search\Queries\GlobalSearch;
use App\Models\Trooper;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;

/**
 * Results for the search box: troopers and events matching the term, titled to suit the
 * trooper's theme.
 *
 * @method static array<string, mixed> call(Actor $actor, ?string $q = null, ?string $type = null)
 */
final class SearchAllPageData extends Message
{
    public const MIN_TERM_LENGTH = 2;

    /** @param  Actor&Trooper  $actor */
    public function __construct(
        private readonly Actor $actor,
        private readonly ?string $q = null,
        private readonly ?string $type = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        $term = (string) $this->q;
        $type = SearchType::tryFrom($this->type ?? SearchType::ALL->value) ?? SearchType::ALL;

        return [
            'term' => $term,
            'type' => $type->value,
            'results' => strlen(trim($term)) >= self::MIN_TERM_LENGTH
                ? GlobalSearch::call(term: $term, type: $type->value)
                : null,
            'search_title' => $this->searchTitle(),
            'search_types' => SearchType::toArray(),
            'show_troopers' => in_array($type, [SearchType::ALL, SearchType::TROOPERS], true),
            'show_events' => in_array($type, [SearchType::ALL, SearchType::EVENTS], true),
        ];
    }

    private function searchTitle(): string
    {
        return match ($this->actor->theme)
        {
            TrooperTheme::STORMTROOPER => __('search.Holonet Scanner'),
            TrooperTheme::BOUNTY_HUNTER => __('search.Bounty Puck'),
            TrooperTheme::SITH => __('search.Force Sight'),
            TrooperTheme::CLONE => __('search.Battalion Ledger'),
            TrooperTheme::REBEL => __('search.Echo Base Intel'),
            default => __('search.Search Results'),
        };
    }
}
