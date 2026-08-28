<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Queries;

use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperOrganization;
use App\Models\TrooperRequest;
use Hyperdrive\Message;

/**
 * @method static Trooper|null call(string $identifier, Organization $primary_organization, Trooper $ignore_trooper)
 */
final class FindExistingTrooperMembership extends Message
{
    public function __construct(
        private readonly string $identifier,
        private readonly Organization $primary_organization,
        private readonly Trooper $ignore_trooper
    ) {
    }

    public function handle(): Trooper|null
    {
        $existing_request = TrooperRequest::query()
            ->where(TrooperRequest::PRIMARY_ORGANIZATION_ID, $this->primary_organization->id)
            ->where(TrooperRequest::IDENTIFIER, $this->identifier)
            ->where(TrooperRequest::TROOPER_ID, '!=', $this->ignore_trooper->id)
            ->with('trooper')
            ->first();

        if ($existing_request !== null)
        {
            return $existing_request->trooper;
        }

        $existing_trooper = TrooperOrganization::query()
            ->where(TrooperOrganization::IDENTIFIER, $this->identifier)
            ->where(TrooperOrganization::TROOPER_ID, '!=', $this->ignore_trooper->id)
            ->with('trooper')
            ->first();


        if ($existing_trooper !== null)
        {
            return $existing_trooper->trooper;
        }

        return null;
    }
}
