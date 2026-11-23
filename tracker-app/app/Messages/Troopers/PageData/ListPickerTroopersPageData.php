<?php

declare(strict_types=1);

namespace App\Messages\Troopers\PageData;

use App\Messages\Troopers\Queries\GetTroopersForPicker;
use App\Models\Filters\TrooperFilter;
use App\Models\Trooper;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;

/**
 * The troopers a trooper picker offers, for the form field named by `property`.
 *
 * @method static array<string, mixed> call(Actor $actor, string $property, array $query = [])
 */
final class ListPickerTroopersPageData extends Message
{
    /**
     * @param  Actor&Trooper  $actor
     * @param  array<string, mixed>  $query  The picker's search and options: search_term, organization_id, event, ...
     */
    public function __construct(
        private readonly Actor $actor,
        private readonly string $property,
        private readonly array $query = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(TrooperFilter $filter): array
    {
        $organization_id = $this->query['organization_id'] ?? null;

        return [
            'troopers' => GetTroopersForPicker::call(trooper: $this->actor, filter: $filter, data: $this->query),
            'property' => $this->property,
            'search_term' => $this->query['search_term'] ?? null,
            'organization_id' => $organization_id !== null ? (int) $organization_id : null,
            'event_name' => $this->query['event'] ?? '',
        ];
    }
}
