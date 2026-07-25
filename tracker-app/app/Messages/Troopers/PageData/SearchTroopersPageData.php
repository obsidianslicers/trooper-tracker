<?php

declare(strict_types=1);

namespace App\Messages\Troopers\PageData;

use App\Messages\Auth\Queries\GetAuthConfig;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;
use App\Enums\TrooperPickerMode;
use App\Models\Trooper;
use App\Messages\Troopers\Queries\SearchTroopers;

/**
 * Retrieves search results for troopers based on the provided search term, organization, and other filters.
 *
 * This page data message responds with a list of troopers matching the search criteria, formatted for frontend consumption.
 *
 * @method static array call(Actor $actor, string $search_term, int|null $organization_id = null, bool $moderated_only = false, TrooperPickerMode $picker_mode = TrooperPickerMode::NONE)
 */
final class SearchTroopersPageData extends Message
{
    /**
     * Summary of __construct
     * @param Actor&Trooper $actor
     * @param string $search_term
     * @param int|null $organization_id
     * @param bool $moderated_only
     * @param TrooperPickerMode $picker_mode
     */
    public function __construct(
        private readonly Actor $actor,
        private readonly string $search_term,
        private readonly int|null $organization_id = null,
        private readonly bool $moderated_only = false,
        private readonly TrooperPickerMode $picker_mode = TrooperPickerMode::NONE)
    {
    }

    /**
     * Retrieves application configuration as a nested associative array.
     *
     * @return array Configuration array with auth provider status, URLs, features, and localization settings
     */
    public function handle(): array
    {
        $troopers = SearchTroopers::call(
            trooper: $this->actor,
            search_term: $this->search_term,
            organization_id: $this->organization_id,
            moderated_only: $this->moderated_only,
            picker_mode: $this->picker_mode
        );

        $data = $troopers->map(fn(Trooper $trooper) => [
            Trooper::ID => $trooper->id,
            Trooper::LEGAL_NAME => $trooper->legal_name,
            Trooper::DISPLAY_NAME => $trooper->display_name,
        ])->toArray();

        return $data;
    }
}
