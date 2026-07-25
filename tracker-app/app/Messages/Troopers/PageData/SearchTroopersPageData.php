<?php

declare(strict_types=1);

namespace App\Messages\Troopers\PageData;

use App\Messages\Auth\Queries\GetAuthConfig;
use Hyperdrive\Message;
use App\Enums\TrooperPickerMode;
use App\Models\Filters\TrooperFilter;
use App\Models\Trooper;
use App\Models\TrooperFriend;
use Illuminate\Support\Collection;
use App\Messages\Troopers\Queries\SearchTroopers;

/**
 * Retrieves application configuration including authentication provider status and feature toggles.
 *
 * This query message responds with configuration data for authorization providers (XenForo OAuth,
 * Google OAuth, email/password authentication), application metadata, and feature/localization settings.
 * Used by frontend clients to determine available authentication methods and application capabilities.
 *
 * @method static array call(Request $request)
 */
final class SearchTroopersPageData extends Message
{
    public function __construct(
        private readonly Trooper $trooper,
        private readonly TrooperFilter $filter,
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
            trooper: $this->trooper,
            filter: $this->filter,
            organization_id: $this->organization_id,
            moderated_only: $this->moderated_only,
            picker_mode: $this->picker_mode
        );

        $data = $troopers->map(fn(Trooper $trooper) => [
            Trooper::ID => $trooper->id,
            Trooper::DISPLAY_NAME => $trooper->display_name,
        ])->toArray();

        return $data;
    }
}
