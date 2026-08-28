<?php

declare(strict_types=1);

namespace App\Messages\Troopers\PageData\Membership;

use App\Messages\Troopers\Queries\FindExistingTrooperMembership;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperRequest;
use App\Services\MemberLookup\MemberLookupResolver;
use Hyperdrive\Message;

/**
 * Retrieves data for the trooper lookup page.

 * This query message responds with the necessary data for displaying troopers pending approval,
 * including their details, notifications, costumes, memberships, friends, and minors.
 *
 * @method static array<string, mixed> call()
 */
final class LookupMembershipPageData extends Message
{
    /**
     * Constructs the LookupMembershipPageData message.
     * 
     * @param  TrooperRequest  $trooper_request  The trooper request being looked up
     */
    public function __construct(
        private readonly MemberLookupResolver $resolver,
        private readonly TrooperRequest $trooper_request)
    {
    }

    /**
     * Retrieves trooper approvals as a nested associative array.
     *
     * @return array Configuration array with trooper approvals
     */
    public function handle(): array
    {
        $service = $this->resolver->resolve($this->trooper_request->primary_organization);

        $data = [
            'identifier' => $this->trooper_request->identifier,
            'primary_organization' => $this->getPrimaryOrganization(),
            'existing_trooper_membership' => $this->findExistingTrooperMembership(),
            'service_name' => $this->getServiceName($service),
            'member' => null,
        ];

        return $data;
    }

    private function getServiceName(object|null $service): string|null
    {
        if ($service === null)
        {
            return null;
        }

        $class_name = get_class($service);

        $parts = explode('\\', $class_name);

        return end($parts);
    }

    private function getPrimaryOrganization(): ?array
    {
        $columns = [Organization::ID, Organization::NAME];

        return $this->trooper_request
            ->primary_organization
            ->only($columns);
    }

    private function findExistingTrooperMembership(): array|null
    {
        $existing_trooper = FindExistingTrooperMembership::call(
            identifier: $this->trooper_request->identifier,
            primary_organization: $this->trooper_request->primary_organization,
            ignore_trooper: $this->trooper_request->trooper
        );

        if ($existing_trooper)
        {
            $columns = [
                Trooper::ID,
                Trooper::LEGAL_NAME,
                Trooper::DISPLAY_NAME,
                Trooper::MEMBERSHIP_STATUS
            ];

            return $existing_trooper->only($columns);
        }

        return null;
    }
}
