<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Queries\Membership;

use App\Enums\TrooperRequestStatus;
use App\Models\Organization;
use App\Models\TrooperRequest;
use Hyperdrive\Message;

/**
 * @method static bool call(Organization $primary_organization, string|null $identifier, int|null $ignore_trooper_id = null, int|null $ignore_trooper_request_id = null)
 */
final class DoesTrooperRequestExist extends Message
{
    public function __construct(
        private readonly Organization $primary_organization,
        private readonly ?string $identifier,
        private readonly ?int $ignore_trooper_id = null,
        private readonly ?int $ignore_trooper_request_id = null,
        private readonly TrooperRequestStatus $status = TrooperRequestStatus::PENDING,
    ) {}

    public function handle(): bool
    {
        $query = TrooperRequest::query()
            ->where(TrooperRequest::PRIMARY_ORGANIZATION_ID, $this->primary_organization->id)
            ->where(TrooperRequest::IDENTIFIER, $this->identifier)
            ->where(TrooperRequest::STATUS, $this->status);

        if ($this->ignore_trooper_id !== null)
        {
            $query->where(TrooperRequest::TROOPER_ID, '!=', $this->ignore_trooper_id);
        }

        if ($this->ignore_trooper_request_id !== null)
        {
            $query->where(TrooperRequest::ID, '!=', $this->ignore_trooper_request_id);
        }

        return $query->exists();
    }
}
