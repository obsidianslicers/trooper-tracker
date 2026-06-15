<?php

declare(strict_types=1);

namespace App\Features\Troopers\Support;

use App\Enums\TrooperRequestStatus;
use App\Features\Troopers\Exceptions\DuplicateOrganizationIdentifierException;
use App\Models\Organization;
use App\Models\TrooperOrganization;
use App\Models\TrooperRequest;

class OrganizationIdentifierAvailability
{
    public function isAvailable(
        Organization $organization,
        ?string $identifier,
        ?int $ignore_trooper_id = null,
        ?int $ignore_trooper_request_id = null,
        ?int $ignore_trooper_organization_id = null,
    ): bool {
        if (empty($identifier))
        {
            return true;
        }

        $primary_club = $organization->getPrimaryClub();

        return !$this->membershipExists($primary_club, $identifier, $ignore_trooper_id, $ignore_trooper_organization_id)
            && !$this->pendingRequestExists($primary_club, $identifier, $ignore_trooper_id, $ignore_trooper_request_id);
    }

    public function ensureAvailable(
        Organization $organization,
        ?string $identifier,
        ?int $ignore_trooper_id = null,
        ?int $ignore_trooper_request_id = null,
        ?int $ignore_trooper_organization_id = null,
    ): void {
        $primary_club = $organization->getPrimaryClub();

        if (!$this->isAvailable(
            $primary_club,
            $identifier,
            $ignore_trooper_id,
            $ignore_trooper_request_id,
            $ignore_trooper_organization_id
        ))
        {
            throw new DuplicateOrganizationIdentifierException($primary_club, (string) $identifier);
        }
    }

    private function membershipExists(
        Organization $primary_club,
        string $identifier,
        ?int $ignore_trooper_id,
        ?int $ignore_trooper_organization_id,
    ): bool {
        $query = TrooperOrganization::withTrashed()
            ->where(TrooperOrganization::ORGANIZATION_ID, $primary_club->id)
            ->where(TrooperOrganization::IDENTIFIER, $identifier);

        if ($ignore_trooper_id !== null)
        {
            $query->where(TrooperOrganization::TROOPER_ID, '!=', $ignore_trooper_id);
        }

        if ($ignore_trooper_organization_id !== null)
        {
            $query->where(TrooperOrganization::ID, '!=', $ignore_trooper_organization_id);
        }

        return $query->exists();
    }

    private function pendingRequestExists(
        Organization $primary_club,
        string $identifier,
        ?int $ignore_trooper_id,
        ?int $ignore_trooper_request_id,
    ): bool {
        $query = TrooperRequest::query()
            ->where(TrooperRequest::PRIMARY_ORGANIZATION_ID, $primary_club->id)
            ->where(TrooperRequest::IDENTIFIER, $identifier)
            ->where(TrooperRequest::STATUS, TrooperRequestStatus::PENDING->value);

        if ($ignore_trooper_id !== null)
        {
            $query->where(TrooperRequest::TROOPER_ID, '!=', $ignore_trooper_id);
        }

        if ($ignore_trooper_request_id !== null)
        {
            $query->where(TrooperRequest::ID, '!=', $ignore_trooper_request_id);
        }

        return $query->exists();
    }
}
