<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Queries;

use App\Enums\TrooperRequestStatus;
use App\Models\Organization;
use Hyperdrive\Message;

/**
 * @method static bool call(Organization $primary_organization, string|null $identifier = null, int|null $ignore_trooper_id = null, int|null $ignore_trooper_request_id = null, int|null $ignore_trooper_organization_id = null)
 */
final class IsOrganizationIdentifierAvailable extends Message
{
    public function __construct(
        private readonly Organization $primary_organization,
        private readonly ?string $identifier,
        private readonly ?int $ignore_trooper_id = null,
        private readonly ?int $ignore_trooper_request_id = null,
        private readonly ?int $ignore_trooper_organization_id = null,
    ) {}

    public function handle(): bool
    {
        if (empty($this->identifier))
        {
            return true;
        }

        $trooper_organization_exists = DoesTrooperOrganizationExist::call(
            primary_organization: $this->primary_organization,
            identifier: $this->identifier,
            ignore_trooper_id: $this->ignore_trooper_id,
            ignore_trooper_organization_id: $this->ignore_trooper_organization_id
        );

        $trooper_request_exists = DoesTrooperRequestExist::call(
            primary_organization: $this->primary_organization,
            identifier: $this->identifier,
            ignore_trooper_id: $this->ignore_trooper_id,
            ignore_trooper_request_id: $this->ignore_trooper_request_id,
            status: TrooperRequestStatus::PENDING
        );

        return !$trooper_organization_exists && !$trooper_request_exists;
    }
}
