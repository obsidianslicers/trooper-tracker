<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Queries\Membership;

use App\Enums\TrooperRequestStatus;
use App\Models\Organization;
use Hyperdrive\Message;

use Exception;

/**
 * @method static bool call(Organization $primary_organization, string|null $identifier = null, int|null $ignore_trooper_id = null, int|null $ignore_trooper_request_id = null, int|null $ignore_trooper_organization_id = null)
 */
final class AssertOrganizationIdentifierAvailable extends Message
{
    public function __construct(
        private readonly Organization $primary_organization,
        private readonly ?string $identifier,
        private readonly ?int $ignore_trooper_id = null,
        private readonly ?int $ignore_trooper_request_id = null,
        private readonly ?int $ignore_trooper_organization_id = null,
    ) {
    }

    public function handle(): void
    {
        $identifier_available = IsOrganizationIdentifierAvailable::call(
            primary_organization: $this->primary_organization,
            identifier: $this->identifier,
            ignore_trooper_id: $this->ignore_trooper_id,
            ignore_trooper_request_id: $this->ignore_trooper_request_id,
            ignore_trooper_organization_id: $this->ignore_trooper_organization_id
        );

        if (!$identifier_available)
        {
            $label = $this->primary_organization->identifier_display ?? 'identifier';

            $msg = "{$this->primary_organization->name} {$label} {$this->identifier} is already assigned to another trooper.";

            throw new Exception($msg);
        }
    }
}
