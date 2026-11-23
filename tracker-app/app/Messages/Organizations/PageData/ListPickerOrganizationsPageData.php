<?php

declare(strict_types=1);

namespace App\Messages\Organizations\PageData;

use App\Messages\Organizations\Queries\GetOrganizationsForPicker;
use App\Models\Trooper;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;

/**
 * The organizations an organization picker offers, for the form field named by `property`.
 *
 * @method static array<string, mixed> call(Actor $actor, string $property, array $query = [])
 */
final class ListPickerOrganizationsPageData extends Message
{
    /**
     * @param  Actor&Trooper  $actor
     * @param  array<string, mixed>  $query  The picker's options: moderated_only, event_host_only, ...
     */
    public function __construct(
        private readonly Actor $actor,
        private readonly string $property,
        private readonly array $query = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        return [
            'organizations' => GetOrganizationsForPicker::call(trooper: $this->actor, data: $this->query),
            'property' => $this->property,
        ];
    }
}
