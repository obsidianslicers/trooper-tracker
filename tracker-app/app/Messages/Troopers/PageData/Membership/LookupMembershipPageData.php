<?php

declare(strict_types=1);

namespace App\Messages\Troopers\PageData\Membership;

use App\Messages\Troopers\Queries\FindExistingTrooperMembership;
use App\Messages\Troopers\Resources\MembershipLookupResource;
use App\Models\Trooper;
use App\Models\TrooperRequest;
use App\Services\MemberLookup\MemberLookupResolver;
use Hyperdrive\Message;

/**
 * Checks a membership request's identifier (admin.troopers.approvals.lookup-membership): what the
 * club's member lookup service says about it, and whether another trooper already holds it.
 *
 * @method static MembershipLookupResource call(TrooperRequest $trooper_request)
 */
final class LookupMembershipPageData extends Message
{
    public function __construct(
        private readonly TrooperRequest $trooper_request) {}

    public function handle(MemberLookupResolver $resolver): MembershipLookupResource
    {
        $service = $resolver->resolve($this->trooper_request->primary_organization);
        $identifier = $this->trooper_request->identifier;

        return new MembershipLookupResource(
            trooper_request: $this->trooper_request,
            service_name: $service === null ? null : class_basename($service),
            member: ($service !== null && !empty($identifier)) ? $service->lookup($identifier) : null,
            existing_trooper: $this->findExistingTrooper(),
        );
    }

    private function findExistingTrooper(): ?Trooper
    {
        if (empty($this->trooper_request->identifier))
        {
            return null;
        }

        return FindExistingTrooperMembership::call(
            identifier: $this->trooper_request->identifier,
            primary_organization: $this->trooper_request->primary_organization,
            ignore_trooper: $this->trooper_request->trooper
        );
    }
}
