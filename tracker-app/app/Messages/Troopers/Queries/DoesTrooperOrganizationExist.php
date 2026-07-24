<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Queries;

use App\Models\Organization;
use App\Models\TrooperOrganization;
use Hyperdrive\Message;

/**
 * @method static bool call(Organization $primary_organization, string|null $identifier, int|null $ignore_trooper_id = null, int|null $ignore_trooper_organization_id = null)
 */
final class DoesTrooperOrganizationExist extends Message
{
    public function __construct(
        private readonly Organization $primary_organization,
        private readonly ?string $identifier,
        private readonly ?int $ignore_trooper_id = null,
        private readonly ?int $ignore_trooper_organization_id = null,
    ) {}

    public function handle(): bool
    {
        $query = TrooperOrganization::query()
            ->withTrashed()
            ->where(TrooperOrganization::ORGANIZATION_ID, $this->primary_organization->id)
            ->where(TrooperOrganization::IDENTIFIER, $this->identifier);

        if ($this->ignore_trooper_id !== null)
        {
            $query->where(TrooperOrganization::TROOPER_ID, '!=', $this->ignore_trooper_id);
        }

        if ($this->ignore_trooper_organization_id !== null)
        {
            $query->where(TrooperOrganization::ID, '!=', $this->ignore_trooper_organization_id);
        }

        return $query->exists();
    }
}
