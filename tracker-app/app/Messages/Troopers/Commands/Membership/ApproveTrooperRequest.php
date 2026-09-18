<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands\Membership;

use App\Enums\TrooperRequestStatus;
use App\Messages\Troopers\Commands\Notifications\CreateOrUpdateOrganizationNotification;
use App\Messages\Troopers\Queries\Membership\IsOrganizationIdentifierAvailable;
use App\Models\Organization;
use App\Models\TrooperRequest;
use App\Notifications\Troopers\TrooperRequestApprovedNotification;
use Exception;
use Hyperdrive\Message;

/**
 * Handler for approving a trooper's membership.
 *
 * @method static void call(Trooper $trooper)
 */
final class ApproveTrooperRequest extends Message
{
    public function __construct(
        private readonly TrooperRequest $trooper_request,
        private readonly bool $suppress_notification = false,
    ) {
    }

    public function handle(): void
    {
        $trooper_request = $this->trooper_request;
        $trooper = $trooper_request->trooper;
        $primary_organization = $trooper_request->primary_organization;
        $requested_org = $trooper_request->organization;

        $this->ensureIdentifierIsAvailable($primary_organization, $trooper_request);

        CreateOrUpdateOrganizationMembership::call(
            trooper_id: $trooper_request->trooper_id,
            primary_organization_id: $primary_organization->id,
            organization_id: $trooper_request->organization_id,
            identifier: $trooper_request->identifier,
        );

        $this->createOrUpdateNotifications($primary_organization, $requested_org, $trooper->id);

        $trooper_request->status = TrooperRequestStatus::APPROVED;
        $trooper_request->save();

        if (!$this->suppress_notification)
        {
            $trooper->notify(new TrooperRequestApprovedNotification($trooper_request));
        }
    }

    private function ensureIdentifierIsAvailable(Organization $primary_organization, TrooperRequest $trooper_request): void
    {
        $identifier_available = IsOrganizationIdentifierAvailable::call(
            primary_organization: $primary_organization,
            identifier: $trooper_request->identifier,
            ignore_trooper_id: $trooper_request->trooper_id
        );

        if (!$identifier_available)
        {
            $label = $primary_organization->identifier_display ?? 'identifier';

            $msg = "{$primary_organization->name} {$label} {$trooper_request->identifier} is already assigned to another trooper.";

            throw new Exception($msg);
        }
    }

    private function createOrUpdateNotifications(Organization $primary_organization, Organization $requested_org, int $trooper_id): void
    {
        foreach ($this->notificationOrganizations($primary_organization, $requested_org) as $organization)
        {
            CreateOrUpdateOrganizationNotification::call(
                trooper_id: $trooper_id,
                organization_id: $organization->id,
                enabled: true,
            );
        }
    }

    /**
     * @return array<int, Organization>
     */
    private function notificationOrganizations(Organization $primary_organization, Organization $requested_org): array
    {
        $organizations = [];
        $organization = $requested_org;

        while ($organization !== null)
        {
            $organizations[$organization->id] = $organization;

            if ($organization->id === $primary_organization->id)
            {
                break;
            }

            $organization = $organization->parent;
        }

        $organizations[$primary_organization->id] = $primary_organization;

        return $organizations;
    }
}
